<?php
/**
 * Member's data base connector for CAFEVDB orchestra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Database\ORM;

use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use OCP\IConfig;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\ISession;
use OCP\Security\ICrypto;

use OCA\CAFeVDBMembers\AppInfo\Application;
use OCA\CAFeVDBMembers\Database\DBAL\Logging\CloudLogger;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Listeners;
use OCA\CAFeVDBMembers\L10N\IBidirectionalTranslator;
use OCA\CAFeVDBMembers\Service\AuthenticationService;
use OCA\CAFeVDBMembers\Service\ConnectorConfiguration;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;
use OCA\CAFeVDBMembers\Wrapped\Carbon;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Platforms\MariaDBPlatform;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Connection as DatabaseConnection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Types\Type;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\UuidType;
use OCA\RotDrop\DevScripts\PhpToTypeScript\GenerateEntityMetadata;

/** Exercise metadata generation without opening the application database. */
#[Attributes\CoversNothing]
class OfflineEntityManagerTest extends TestCase
{
  /** @return void */
  public function testMetadataWithExplicitServerVersion(): void
  {
    require_once dirname(__DIR__, 5) . '/vendor-bin/php-to-typescript/vendor/autoload.php';

    $entityManager = $this->createEntityManager('11.7.0-MariaDB');
    $connection = $entityManager->getConnection();
    self::assertFalse($connection->isConnected());
    self::assertInstanceOf(MariaDBPlatform::class, $connection->getDatabasePlatform());
    self::assertInstanceOf(UuidType::class, Type::getType(UuidType::NAME));
    self::assertSame(UuidType::NAME, $connection->getDatabasePlatform()->getDoctrineTypeMapping(UuidType::NAME));
    self::assertInstanceOf(Carbon\Doctrine\DateTimeType::class, Type::getType('datetime'));

    $generator = new GenerateEntityMetadata(
      devScriptsFolder: dirname(__DIR__, 5) . '/dev-scripts',
      phpNamespacePrefix: 'OCA\\CAFeVDBMembers',
      outputPrefix: '',
      output: new ConsoleOutput(OutputInterface::VERBOSITY_QUIET),
      entityManagerFactory: fn() => $entityManager,
    );
    $metadata = $generator->generateSparseMetadata();

    self::assertArrayHasKey('Musician', $metadata);
    self::assertArrayHasKey('ProjectParticipantField', $metadata);
    self::assertSame('owned', $metadata['Musician']['id']['mapping']);
    self::assertTrue($metadata['Musician']['id']['id']);
    self::assertFalse($connection->isConnected());
  }

  /** @return void */
  public function testDefaultConstructionStillChecksConnectivity(): void
  {
    $entityManager = $this->createEntityManager();
    self::assertNull($entityManager->getConnection()->getParams()['serverVersion'] ?? null);
    self::assertFalse($entityManager->getConnection()->isConnected());
  }

  /** @return void */
  public function testLegacyConnectionParameters(): void
  {
    $params = $this->createEntityManager('11.7.0-MariaDB')->getConnection()->getParams();
    self::assertSame('offline_metadata', $params['dbname']);
    self::assertSame('offline_metadata', $params['user']);
    self::assertSame('offline_metadata', $params['password']);
    self::assertSame('127.0.0.1:1', $params['host']);
    self::assertSame('pdo_mysql', $params['driver']);
  }

  /** @return void */
  public function testIndependentConnectionParameters(): void
  {
    $params = $this->createEntityManager('11.7.0-MariaDB', [
      'host' => 'mariadb.example',
      'port' => '3307',
      'unix_socket' => '/tmp/views.sock',
      'user' => 'restricted',
      'password' => 'connector-secret',
      'accountHost' => '%',
    ])->getConnection()->getParams();
    self::assertSame('offline_metadata', $params['dbname']);
    self::assertSame('mariadb.example', $params['host']);
    self::assertSame(3307, $params['port']);
    self::assertSame('/tmp/views.sock', $params['unix_socket']);
    self::assertSame('restricted', $params['user']);
    self::assertSame('connector-secret', $params['password']);
    self::assertArrayNotHasKey('accountHost', $params);
    self::assertSame('pdo_mysql', $params['driver']);
  }

  /** @return void */
  public function testProducerCredentialsTakePrecedenceOverLocalPassword(): void
  {
    $params = $this->createEntityManager(
      '11.7.0-MariaDB',
      credentials: [
        'host' => 'mariadb.example',
        'port' => '3307',
        'user' => 'legacy_user',
        'password' => 'legacy-secret',
      ],
      viewsPassword: 'local-secret',
    )->getConnection()->getParams();
    self::assertSame('offline_metadata', $params['dbname']);
    self::assertSame('mariadb.example', $params['host']);
    self::assertSame(3307, $params['port']);
    self::assertSame('legacy_user', $params['user']);
    self::assertSame('legacy-secret', $params['password']);
  }

  /** @return void */
  public function testResetDoesNotRestoreAnOldLocalPassword(): void
  {
    $params = $this->createEntityManager(
      '11.7.0-MariaDB',
      viewsPassword: 'local-secret',
      managed: true,
    )->getConnection()->getParams();
    self::assertSame('offline_metadata', $params['user']);
    self::assertSame('offline_metadata', $params['password']);
  }

  /** @return void */
  public function testSocketOnlyProducerCredentials(): void
  {
    $params = $this->createEntityManager('11.7.0-MariaDB', [
      'unix_socket' => '/tmp/views.sock', 'user' => 'restricted', 'password' => 'secret',
    ])->getConnection()->getParams();
    self::assertSame('/tmp/views.sock', $params['unix_socket']);
    self::assertSame('restricted', $params['user']);
  }

  /** @return void */
  public function testSocketOnlyConnectionCanBeMarkedConnected(): void
  {
    $connection = $this->createStub(DatabaseConnection::class);
    $connection->method('getParams')->willReturn([
      'unix_socket' => '/tmp/views.sock', 'user' => 'restricted', 'dbname' => 'views',
    ]);
    $connection->method('isConnected')->willReturn(true);
    $manager = $this->getMockBuilder(EntityManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getConnection'])
      ->getMock();
    $manager->method('getConnection')->willReturn($connection);
    self::assertTrue($manager->connected());
  }

  /** @return void */
  public function testPublishedAccountUsesNextcloudHostWithoutProducerCredentials(): void
  {
    $params = $this->createEntityManager('11.7.0-MariaDB', viewsPassword: 'local-secret')->getConnection()->getParams();
    self::assertSame('127.0.0.1:1', $params['host']);
    self::assertSame('published_user', $params['user']);
    self::assertSame('local-secret', $params['password']);
  }

  /** @return void */
  public function testInheritedConnectionDatabase(): void
  {
    $descriptor = json_encode([
      'version' => 1,
      'database' => 'producer_views',
      'account' => ['user' => 'connector', 'accountHost' => '%'],
      'personalizedViewsEnabled' => true,
      'revision' => 'revision',
    ], JSON_THROW_ON_ERROR);
    $config = $this->createMock(IConfig::class);
    $config->method('getAppValue')->willReturnCallback(
      static fn(string $app, string $key, string $default = '') => match ($key) {
        'enabled' => 'yes',
        ConfigConstants::USER_VIEWS_DATABASE_KEY => 'stored_override',
        ConfigConstants::INHERIT_VIEWS_DATABASE_KEY => 'on',
        ConfigConstants::CONNECTOR_DESCRIPTOR_KEY => $descriptor,
        default => $default,
      },
    );
    $config->method('getSystemValue')->willReturnArgument(1);
    $config->expects($this->never())->method('setAppValue');
    $manager = $this->createStub(IAppManager::class);
    $manager->method('isInstalled')->willReturn(true);
    $resolver = new ConnectorConfiguration(
      $config,
      $this->createStub(IL10N::class),
      $manager,
      'cafevdbmembers',
      $this->createStub(ICrypto::class),
    );
    $container = $this->createStub(ContainerInterface::class);
    $container->method('get')->willReturnMap([[ConnectorConfiguration::class, $resolver]]);
    $reflection = new ReflectionClass(EntityManager::class);
    $entityManager = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('appName')->setValue($entityManager, 'cafevdbmembers');
    $reflection->getProperty('cloudConfig')->setValue($entityManager, $config);
    $reflection->getProperty('appContainer')->setValue($entityManager, $container);
    $params = $reflection->getMethod('connectionParameters')->invoke($entityManager);
    self::assertSame('producer_views', $params['dbname']);
  }

  /** @return void */
  public function testInvalidCredentialsDoNotFallBackOrExposeSecrets(): void
  {
    try {
      $this->createEntityManager('11.7.0-MariaDB', ['password' => 'connector-secret'], viewsPassword: 'local-secret');
      self::fail('Invalid credentials must be rejected');
    } catch (\RuntimeException $exception) {
      self::assertSame('Unable to configure the personalized views database. Check the connector settings.', $exception->getMessage());
      self::assertNull($exception->getPrevious());
    }
  }

  /** @return void */
  public function testConstructorDoesNotExposeOriginalCause(): void
  {
    $cause = new \TypeError('connector-secret');
    try {
      $this->createEntityManager('11.7.0-MariaDB', ['host' => 'localhost'], $cause);
      self::fail('The original failure must be propagated as a cause');
    } catch (\RuntimeException $exception) {
      self::assertSame('Unable to configure the personalized views database. Check the connector settings.', $exception->getMessage());
      self::assertNull($exception->getPrevious());
    }
  }

  /**
   * @param mixed $credentials
   * @param bool $valid
   *
   * @return void
   */
  #[Attributes\DataProvider('credentialShapes')]
  public function testCredentialValidation(mixed $credentials, bool $valid): void
  {
    $reflection = new \ReflectionClass(EntityManager::class);
    $manager = $reflection->newInstanceWithoutConstructor();
    if (!$valid) {
      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage('Invalid connector credentials.');
    }
    $reflection->getMethod('validateConnectorCredentials')->invoke($manager, $credentials);
    if ($valid) {
      self::assertTrue(true);
    }
  }

  /** @return iterable */
  public static function credentialShapes(): iterable
  {
    $base = ['host' => 'localhost', 'user' => 'restricted', 'password' => ''];
    yield 'empty password' => [$base, true];
    yield 'integer port' => [$base + ['port' => 1], true];
    yield 'string port' => [$base + ['port' => '65535'], true];
    yield 'socket' => [$base + ['unix_socket' => '/tmp/views.sock'], true];
    yield 'socket only' => [['unix_socket' => '/tmp/views.sock', 'user' => 'restricted', 'password' => ''], true];
    yield 'empty socket' => [$base + ['unix_socket' => ''], false];
    yield 'non-array' => ['connector-secret', false];
    foreach (['host', 'user', 'password'] as $key) {
      $missing = $base;
      unset($missing[$key]);
      yield 'missing ' . $key => [$missing, false];
      yield 'wrong type ' . $key => [array_replace($base, [$key => 7]), false];
    }
    foreach (['host', 'user'] as $key) {
      yield 'empty ' . $key => [array_replace($base, [$key => '']), false];
    }
    foreach ([0, 65536, -1, 3306.5, true, '3306x', ''] as $index => $port) {
      yield 'invalid port ' . $index => [$base + ['port' => $port], false];
    }
    yield 'invalid socket' => [$base + ['unix_socket' => 7], false];
  }

  /** @return void */
  public function testInaccessibleViewReturnsSafeInstructions(): void
  {
    $connection = $this->createMock(DatabaseConnection::class);
    $connection->expects($this->once())
      ->method('executeQuery')
      ->willThrowException(new \RuntimeException('connector-secret'));
    $manager = $this->getMockBuilder(EntityManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['connected', 'getConnection'])
      ->getMock();
    $manager->expects($this->once())->method('connected')->willReturn(true);
    $manager->expects($this->once())->method('getConnection')->willReturn($connection);
    $l = $this->createStub(IL10N::class);
    $l->method('t')->willReturnCallback(fn(string $text) => $text);
    (new \ReflectionProperty(EntityManager::class, 'l'))->setValue($manager, $l);

    self::assertSame(
      'Cannot query PersonalizedMusiciansView. Regenerate the personalized views in CafeVDB and check the connector account privileges.',
      $manager->validatePersonalizedViews(),
    );
  }

  /**
   * @param null|string $serverVersion
   * @param null|array $credentials
   * @param null|\Throwable $cause
   * @param null|string $viewsPassword
   *
   * @return EntityManager
   */
  private function createEntityManager(
    ?string $serverVersion = null,
    ?array $credentials = null,
    ?\Throwable $cause = null,
    ?string $viewsPassword = null,
    bool $managed = false,
  ): EntityManager
  {
    $entityManager = $this->getMockBuilder(EntityManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['connected'])
      ->getMock();
    $entityManager->expects($serverVersion === null ? $this->once() : $this->never())
      ->method('connected')
      ->willReturn(false);

    $cloudConfig = $this->createStub(IConfig::class);
    $cloudConfig->method('getAppValue')->willReturnCallback(
      fn(string $app, string $key, string $default = '') => match ([$app, $key]) {
        ['cafevdbmembers', ConfigConstants::USER_VIEWS_DATABASE_KEY] => 'offline_metadata',
        [Application::getOrchestraAppName(), ConfigConstants::CLOUD_CONNECTOR_CREDENTIALS_KEY] => $credentials === null ? '' : 'encrypted',
        [Application::getOrchestraAppName(), ConfigConstants::CLOUD_CONNECTOR_MANAGED_KEY] => $managed ? '1' : '',
        default => $default,
      },
    );
    $cloudConfig->method('getSystemValue')->willReturnMap([
      ['dbhost', '', '127.0.0.1:1'],
      ['dbuser', '', 'offline_metadata'],
      ['dbpassword', '', 'offline_metadata'],
    ]);
    $l = $this->createStub(IL10N::class);
    $l->method('getLocaleCode')->willReturn('en_US');

    $crypto = $this->createStub(ICrypto::class);
    if ($cause !== null) {
      $crypto->method('decrypt')->willThrowException($cause);
    } else {
      $crypto->method('decrypt')->willReturn(json_encode($credentials, JSON_THROW_ON_ERROR));
    }
    $container = $this->createStub(ContainerInterface::class);
    $connectorConfiguration = $this->createStub(ConnectorConfiguration::class);
    $connectorConfiguration->method('resolve')->willReturn([
      'database' => 'offline_metadata',
      'descriptor' => ['account' => ['user' => 'published_user', 'accountHost' => 'localhost']],
    ]);
    $connectorConfiguration->method('viewsDatabasePassword')->willReturn($viewsPassword);
    $container->method('get')->willReturnMap([
      [ConnectorConfiguration::class, $connectorConfiguration],
      [ICrypto::class, $crypto],
      [Listeners\Encryption::class, $this->createStub(Listeners\Encryption::class)],
      [Listeners\GedmoTranslatableListener::class, new Listeners\GedmoTranslatableListener(
        $this->createStub(IBidirectionalTranslator::class),
      )],
      ['DefaultLocale', 'en_US'],
    ]);

    $authenticationService = $this->createMock(AuthenticationService::class);
    $authenticationService->expects($this->never())->method('getRowAccessToken');
    $session = $this->createMock(ISession::class);
    $session->expects($this->never())->method('get');
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->never())->method('error');

    $entityManager->__construct(
      userId: null,
      authenticationService: $authenticationService,
      sqlLogger: new CloudLogger($logger, $l),
      cloudConfig: $cloudConfig,
      session: $session,
      appName: 'cafevdbmembers',
      appContainer: $container,
      l: $l,
      logger: $logger,
      databaseServerVersion: $serverVersion,
    );

    return $entityManager;
  }
}
