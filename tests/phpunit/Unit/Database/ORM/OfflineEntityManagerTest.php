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
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use OCP\IConfig;
use OCP\IL10N;
use OCP\ISession;

use OCA\CAFeVDBMembers\Database\DBAL\Logging\CloudLogger;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Listeners;
use OCA\CAFeVDBMembers\L10N\IBidirectionalTranslator;
use OCA\CAFeVDBMembers\Service\AuthenticationService;
use OCA\CAFeVDBMembers\Wrapped\Carbon;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Platforms\MariaDBPlatform;
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

  /**
   * @param null|string $serverVersion
   *
   * @return EntityManager
   */
  private function createEntityManager(?string $serverVersion = null): EntityManager
  {
    $entityManager = $this->getMockBuilder(EntityManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['connected'])
      ->getMock();
    $entityManager->expects($serverVersion === null ? $this->once() : $this->never())
      ->method('connected')
      ->willReturn(false);

    $cloudConfig = $this->createStub(IConfig::class);
    $cloudConfig->method('getAppValue')->willReturn('offline_metadata');
    $cloudConfig->method('getSystemValue')->willReturnMap([
      ['dbhost', '', '127.0.0.1:1'],
      ['dbuser', '', 'offline_metadata'],
      ['dbpassword', '', 'offline_metadata'],
    ]);
    $l = $this->createStub(IL10N::class);
    $l->method('getLocaleCode')->willReturn('en_US');

    $container = $this->createStub(ContainerInterface::class);
    $container->method('get')->willReturnMap([
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
