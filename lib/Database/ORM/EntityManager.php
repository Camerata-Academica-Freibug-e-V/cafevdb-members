<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\CAFeVDBMembers\Database\ORM;

use Psr\Log\LoggerInterface;
use Throwable;

use Psr\Container\ContainerInterface;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ISession;

use OCA\CAFeVDBMembers\Database\DBAL\Types;
use OCA\CAFeVDBMembers\Constants;
use OCA\CAFeVDBMembers\Database\DBAL\Logging\CloudLogger;
use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Database\ORM\Mapping\ReservedWordQuoteStrategy;
use OCA\CAFeVDBMembers\Database\ORM\Repositories;
use OCA\CAFeVDBMembers\Exceptions;
use OCA\CAFeVDBMembers\Service\AuthenticationService;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager;
use OCA\CAFeVDBMembers\Wrapped\Carbon;
use OCA\CAFeVDBMembers\Wrapped\DoctrineExtensions;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Annotations\AnnotationReader;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Annotations\PsrCachedReader;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Cache\ArrayCache;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Cache\Psr6\CacheAdapter;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Cache\Psr6\DoctrineProvider;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\EventManager as DoctrineEventManager;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Connection as DatabaseConnection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Types\Type;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Configuration as OrmConfiguration;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Decorator\EntityManagerDecorator;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\EntityManager as ORMEntityManager;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\EntityManagerInterface;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\ORMSetup;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Query\Filter\SQLFilter;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Persistence\Event\LifecycleEventArgs;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use OCA\CAFeVDBMembers\Wrapped\Firehed\DbalLogger;
use OCA\CAFeVDBMembers\Wrapped\Gedmo;
use OCA\CAFeVDBMembers\Wrapped\Gedmo\SoftDeleteable\SoftDeleteableListener;
use OCA\CAFeVDBMembers\Wrapped\Gedmo\Timestampable\TimestampableListener;
use OCA\CAFeVDBMembers\Wrapped\MediaMonks;
use OCA\CAFeVDBMembers\Wrapped\MediaMonks\Doctrine\Transformable;

/**
 * Use this as the actual EntityManager in order to be able to
 * construct it without a Factory and to define an extension point for
 * later.
 */
class EntityManager extends AbstractEntityManager
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  const ENTITY_PATHS = [
    __DIR__ . "/Entities",
  ];
  const DEV_MODE = false;

  const TRANSFORM_ENCRYPT = 'encrypt';

  const ROW_ACCESS_TOKEN_KEY = 'rowAccessToken';

  /**
   * @var string
   * The name of the soft-deleteable filter
   */
  const SOFT_DELETEABLE_FILTER = 'soft-deleteable';

  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var bool */
  private $typesBound = false;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private ?string $userId,
    private AuthenticationService $authenticationService,
    private CloudLogger $sqlLogger,
    private IConfig $cloudConfig,
    private ISession $session,
    private string $appName,
    protected ContainerInterface $appContainer,
    protected IL10N $l,
    protected LoggerInterface $logger,
  ) {
    try {
      parent::__construct($this->getEntityManager());
    } catch (Throwable $t) {
      $this->logException($t);
      throw $t;
    }
    $this->entityManager = $this->wrapped;
    if ($this->connected()) {
      $this->registerTypes();
    }
  }
  // phpcs:enable

  /** {@inheritdoc} */
  public function getEntityNamespace(): string
  {
    return Entities::class;
  }

  /**
   * @return array
   */
  private function createConfiguration():array
  {
    $config = ORMSetup::createAttributeMetadataConfiguration(self::ENTITY_PATHS, isDevMode: self::DEV_MODE);
    $config->enableNativeLazyObjects(true);
    $config->setEntityListenerResolver(new class($this->appContainer) extends ORM\Mapping\DefaultEntityListenerResolver {

      // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
      public function __construct(
        private ContainerInterface $appContainer,
      ) {
      }
      // phpcs:enable

      /** {@inheritdoc} */
      public function resolve(string $className): object
      {
        try {
          return parent::resolve($className);
        } catch (Throwable $t) {
          $this->register($object = $this->appContainer->get($className));
          return $object;
        }
      }
    });
    $config->setDefaultRepositoryClassName(Repositories\EntityRepository::class);

    $eventManager = new DoctrineEventManager();

    $eventManager->addEventListener([
      // \OCA\CAFEVDB\Wrapped\Doctrine\ORM\Tools\ToolEvents::postGenerateSchema,
      // ORM\Events::loadClassMetadata,
      // ORM\Events::preUpdate,
      // ORM\Events::postUpdate,
      // DBAL\Events::postConnect,
      ORM\Events::postLoad,
    ], $this);

    return [ $config, $eventManager, ];
  }

  /**
   * @param OrmConfiguration $config
   *
   * @param DoctrineEventManager $eventManager
   *
   * @return array
   */
  private function createGedmoConfiguration(OrmConfiguration $config, DoctrineEventManager $eventManager):array
  {
    // create a driver chain for metadata reading
    $driverChain = new MappingDriverChain();

    // load superclass metadata mapping only, into driver chain
    // also registers Gedmo annotations.NOTE: you can personalize it
    Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
      $driverChain, // our metadata driver chain, to hook into
    );
    //<<< Further annotations can go here
    MediaMonks\Doctrine\DoctrineExtensions::registerAnnotations();
    // CJH\Setup::registerAnnotations();
    //>>>

    // now we want to register our application entities,
    // for that we need another metadata driver used for Entity namespace
    $attributeDriver = new ORM\Mapping\Driver\AttributeDriver(
      self::ENTITY_PATHS, // paths to look in
    );

    // NOTE: driver for application Entity can be different, Yaml, Xml or whatever
    // register annotation driver for our application Entity namespace
    $driverChain->addDriver($attributeDriver, 'OCA\CAFeVDBMembers\Database\ORM\Entities');

    // general ORM configuration
    //$config = new \OCA\CAFEVDB\Wrapped\Doctrine\ORM\Configuration;

    // register metadata driver
    $config->setMetadataDriverImpl($driverChain);

    // use our already initialized cache driver
    // $config->setMetadataCache($cache);
    // $config->setQueryCacheImpl(DoctrineProvider::wrap($cache));

    // gedmo extension listeners

    // gedmo extension listeners
    $attributeReader = new Gedmo\Mapping\Driver\AttributeReader();

    // soft deletable
    $softDeletableListener = new SoftDeleteableListener();
    $softDeletableListener->setAnnotationReader($attributeReader);
    $eventManager->addEventSubscriber($softDeletableListener);
    $config->addFilter(self::SOFT_DELETEABLE_FILTER, Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter::class);

    // timestampable
    $timestampableListener = new TimestampableListener();
    $timestampableListener->setAnnotationReader($attributeReader);
    $eventManager->addEventSubscriber($timestampableListener);

    // encryption
    $transformerPool = new Transformable\Transformer\TransformerPool();
    $transformerPool[self::TRANSFORM_ENCRYPT] = $this->appContainer->get(
      Listeners\Encryption::class
    );
    $transformableListener = new Transformable\TransformableSubscriber($transformerPool);
    $transformableListener->setAnnotationReader($attributeReader);
    $eventManager->addEventSubscriber($transformableListener);

    // translatable
    $translatableListener = $this->appContainer->get(Listeners\GedmoTranslatableListener::class);
    // current translation locale should be set from session or hook later into the listener
    // most important, before entity manager is flushed
    $localeCode = $this->l->getLocaleCode();
    if (strpos($localeCode, '_') === false) {
      $localeCode = $localeCode . '_' . strtoupper($localeCode);
    }
    $translatableListener->setTranslatableLocale($localeCode);
    $translatableListener->setDefaultLocale($this->appContainer->get('DefaultLocale'));
    $translatableListener->setTranslationFallback(true);
    $translatableListener->setPersistDefaultLocaleTranslation(true);
    $translatableListener->setAnnotationReader($attributeReader);
    $eventManager->addEventSubscriber($translatableListener);

    $config->setDefaultQueryHint(
      ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
      Gedmo\Translatable\Query\TreeWalker\TranslationWalker::class
    );
    $config->setDefaultQueryHint(
      Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
      $localeCode
    );
    $config->setDefaultQueryHint(
      Gedmo\Translatable\TranslatableListener::HINT_FALLBACK,
      1 // fallback to default values in case if record is not translated
    );

    return [ $config, $eventManager, $attributeReader ];
  }

  /**
   * @param array $params
   *
   * @return array
   */
  private function connectionParameters(array $params = []):array
  {
    $connectionParams = [
      'dbname' => $this->cloudConfig->getAppValue($this->appName, ConfigConstants::USER_VIEWS_DATABASE_KEY),
      'user' => $this->cloudConfig->getSystemValue('dbuser'),
      'password' => $this->cloudConfig->getSystemvalue('dbpassword'),
      'host' => $this->cloudConfig->getSystemValue('dbhost'),
    ];
    if (str_contains(($connectionParams['host'] ?? ''), '://')) {
      $parser = new DBAL\Tools\DsnParser;
      $parsedParams = $parser->parse($connectionParams['host']);
      $connectionParams = array_merge($connectionParams, $parsedParams);
    }
    $driverParams = [
      'driver' => 'pdo_mysql',
      'wrapperClass' => Connection::class,
      Connection::POST_CONNECT_KEY => $this->doEmitRowAccessTokens(...),
    ];
    $charSetParams = [
      'collate' => 'utf8mb4_unicode_520_ci',
      'charset' => 'utf8mb4',
    ];
    $connectionParams = array_merge($connectionParams, $params, $driverParams, $charSetParams);
    return $connectionParams;
  }

  /**
   * Check for a valid database connection.
   *
   * @return bool
   */
  public function connected(): bool
  {
    $connection = $this->getConnection();
    if (empty($connection)) {
      return false;
    }
    $params = $connection->getParams();
    $impossible = false;
    foreach ([ 'host', 'user', 'password', 'dbname' ] as $key) {
      if (empty($params[$key])) {
        $impossible = true;
      }
    }
    if ($impossible) {
      $this->logError('Unable to access database, connection parameters are unset');
      return false;
    }
    try {
      if (!$connection->isConnected()) {
        // $connection->connect() is deprecated
        $connection->getNativeConnection();
        return $connection->isConnected();
      }
    } catch (Throwable $t) {
      $this->logException($t);
      return false;
    }
    return true;
  }

  /** @return void */
  protected function registerTypes(): void
  {
    if ($this->typesBound) {
      return;
    }

    parent::registerTypes();

    try {
      // Override datetime stuff
      Type::overrideType('date', Carbon\Doctrine\CarbonType::class);
      Type::overrideType('date_immutable', Carbon\Doctrine\CarbonImmutableType::class);
      Type::overrideType('datetime', Carbon\Doctrine\DateTimeType::class);
      Type::overrideType('datetime_immutable', Carbon\Doctrine\DateTimeImmutableType::class);
      Type::overrideType('datetimetz', Carbon\Doctrine\DateTimeType::class);
      Type::overrideType('datetimetz_immutable', Carbon\Doctrine\DateTimeImmutableType::class);
      $this->typesBound = true;
    } catch (Throwable $t) {
      throw $t;
      $this->logException($t);
    }
  }

  /**
   * @param OrmConfiguration $config
   *
   * @return void
   */
  private function registerCustomFunctions(OrmConfiguration $config):void
  {
    $config->addCustomStringFunction('sha2', DoctrineExtensions\Query\Mysql\Sha2::class);
  }

  /**
   * @param array $params
   *
   * @return EntityManagerInterface
   */
  private function getEntityManager(array $params = []): EntityManagerInterface
  {
    list($config, $eventManager) = $this->createConfiguration();
    list($config, $eventManager, ) = $this->createGedmoConfiguration($config, $eventManager);

    $this->registerCustomFunctions($config);

    $namingStrategy = new UnderscoreNamingStrategy(CASE_LOWER);
    $config->setNamingStrategy($namingStrategy);

    $config->setMiddlewares([new DBalLogger\Middleware($this->sqlLogger)]);

    // obtaining the entity manager
    $conParams = $this->connectionParameters($params);
    $connection = DBAL\DriverManager::getConnection($conParams, $config, $eventManager);
    $entityManager = new ORMEntityManager($connection, $config, $eventManager);

    return $entityManager;
  }

  /**
   * Install the given $key as DB string variable. Set value to NULL if value is \null.
   *
   * @param DatabaseConnection $connection
   *
   * @param string $key
   *
   * @param null|string $value
   *
   * @return void
   */
  private static function setVariable(
    DatabaseConnection $connection,
    string $key,
    ?string $value,
  ): void {
    $connection->executeStatement(
      $value === null
      ? sprintf('SET @%s = NULL', $key)
      : sprintf('SET @%1$s = "%2$s"', $key, $value),
    );
  }

  /**
   * Emit the row access tokens as appropriate. This sets user variables which
   * enable access to just the database rows containing the data of the
   * authorized identity.
   *
   * @param DatabaseConnection $connection
   *
   * @return void
   */
  private function doEmitRowAccessTokens(DatabaseConnection $connection):void
  {
    try {
      if (!empty($this->userId)) {
        // allow access to the person's private data
        $rowAccessTokenHash = $this->authenticationService->getRowAccessToken();
        self::setVariable($connection, 'CLOUD_USER_ID', $this->userId);
        self::setVariable($connection, 'ROW_ACCESS_TOKEN', $rowAccessTokenHash);
      } else {
        // allow access to the registration data
        $applicationTokens = $this->session->get(Constants::APPLICATION_SESSION_KEY) ?? [];
        $this->logDebug('EMITTING ACCESS TOKENS ' . print_r($applicationTokens, true));
        foreach ($applicationTokens as $name => $value) {
          self::setVariable($connection, $name, $value);
        }
      }
    } catch (Exceptions\AuthenticationException $e) {
      $this->logException($e, 'Unable to set row access token');
    }
  }

  /**
   * Emit the row access tokens which grant access to single rows of the
   * database. This can be used to update the authorization tokens after
   * changing hashes and the like.
   *
   * @return void
   */
  public function emitRowAccessTokens(): void
  {
    if (!$this->connected()) {
      // auth tokens will be emitted automatically on next connect.
      return;
    }
    $this->doEmitRowAccessTokens($this->getConnection());
  }

  /** {@inheritdoc} */
  public function postLoad(LifecycleEventArgs $args)
  {
    $entity = $args->getObject();
    if (\method_exists($entity, '__wakeup')) {
      $entity->__wakeup();
    }
  }
}
