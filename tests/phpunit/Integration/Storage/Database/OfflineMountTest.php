<?php
/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 */

// phpcs:disable Squiz.Commenting.FunctionComment.Missing

namespace OCA\CAFeVDBMembers\Tests\Integration\Storage\Database;

use OC\Files\Cache\Storage as StorageCache;
use OC\Files\Config\UserMountCache;
use OC\Files\Filesystem;
use OC\Files\Mount\Manager;
use OC\Files\Mount\MountPoint;
use OC\Files\SetupManagerFactory;
use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\StorageFactory;
use OC\Files\Storage\Wrapper\Availability;
use OC\Files\Utils\Scanner;
use OC\Files\SetupManager;
use OCP\Diagnostics\IEventLogger;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Mount\IMovableMount;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Exceptions\RowAccessTokenInvalidException;
use OCA\CAFeVDBMembers\Exceptions\RowAccessTokenMissingException;
use OCA\CAFeVDBMembers\Storage\Database\EntityManagerFactory;
use OCA\CAFeVDBMembers\Storage\Database\MountProvider;
use OCA\CAFeVDBMembers\Storage\Database\Storage;
use OCA\CAFeVDBMembers\Storage\Database\UnavailableMount;
use OCA\Files\BackgroundJob\ScanFiles;

/** Exercise upstream reconciliation and scanning against real cache tables. */
#[CoversClass(MountProvider::class)]
#[CoversClass(UnavailableMount::class)]
#[CoversClass(Storage::class)]
class OfflineMountTest extends TestCase
{
  private IDBConnection $connection;
  private IUser $user;
  private IUserManager $userManager;
  private IConfig $config;
  private IUserSession $session;
  private EntityManagerFactory&MockObject $factory;
  private LoggerInterface $logger;
  private StorageFactory $loader;
  private int $storageId;
  private int $rootId;
  private int $fileId;
  private string $mountPath;
  private const FILE_PATH = 'Projects/Example/notes.md';

  protected function setUp(): void
  {
    parent::setUp();
    $this->connection = Server::get(IDBConnection::class);
    $this->connection->beginTransaction();
    // Exercise Nextcloud's hashed storage IDs as well as a moved mount path.
    $this->user = $this->createStub(IUser::class);
    $this->user->method('getUID')->willReturn('offline-mount-test-' . bin2hex(random_bytes(24)));
    $this->userManager = $this->createStub(IUserManager::class);
    $this->userManager->method('userExists')->willReturn(true);
    $this->userManager->method('get')->willReturn($this->user);
    $this->config = $this->createStub(IConfig::class);
    $this->config->method('getUserValue')->willReturn('Archive/Orchestra');
    $this->config->method('getAppValue')->willReturnArgument(2);
    $this->session = $this->createStub(IUserSession::class);
    $this->factory = $this->createMock(EntityManagerFactory::class);
    $this->logger = $this->createStub(LoggerInterface::class);
    $this->loader = new StorageFactory();
    $this->mountPath = '/' . $this->user->getUID() . '/files/Archive/Orchestra/';

    $this->storageId = $this->insert('storages', [
      'id' => StorageCache::adjustStorageId(Storage::idForUser($this->user->getUID())),
      'available' => 1,
      'last_checked' => 0,
    ]);
    $this->rootId = $this->cacheEntry('', -1, 123, 'httpd/unix-directory');
    $projects = $this->cacheEntry('Projects', $this->rootId, 123, 'httpd/unix-directory');
    $project = $this->cacheEntry('Projects/Example', $projects, 123, 'httpd/unix-directory');
    $this->fileId = $this->cacheEntry(self::FILE_PATH, $project, 123, 'text/markdown');
    $this->cacheEntry('Projects/Pending', $projects, -1, 'httpd/unix-directory');
    $storage = new Storage([
      'entityManager' => $this->createStub(EntityManager::class),
      'userId' => $this->user->getUID(),
      'l10n' => $this->createStub(IL10N::class),
    ]);
    $this->mountCache()->registerMounts($this->user, [
      new MountPoint(
        $storage,
        $this->mountPath,
        null,
        $this->loader,
        ['readonly' => true, 'enable_sharing' => false, 'authenticated' => true],
        null,
        MountProvider::class,
      ),
    ], [MountProvider::class]);
  }

  protected function tearDown(): void
  {
    $this->connection->rollBack();
    Server::get(\OC\Files\Cache\StorageGlobal::class)->clearCache();
    Server::get(\OCP\Files\IMimeTypeLoader::class)->reset();
    parent::tearDown();
  }

  public static function wrappers(): array
  {
    return ['plain' => [false], 'availability wrapper' => [true]];
  }

  #[DataProvider('wrappers')]
  public function testOfflineScansPreserveEveryCacheRowAndFileId(bool $wrapped): void
  {
    $this->factory->expects($this->never())->method('create');
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->never())->method('error');
    $this->logger = $logger;
    if ($wrapped) {
      $this->loader->addStorageWrapper('availability', fn($path, $storage) => new Availability([
        'storage' => $storage,
        'config' => $this->config,
      ]));
    }

    $before = $this->snapshot();
    $mounts = $this->provider()->getMountsForUser($this->user, $this->loader);
    self::assertCount(1, $mounts);
    $mount = $mounts[0];
    self::assertSame($this->rootId, $mount->getStorageRootId());
    self::assertSame($this->storageId, $mount->getNumericStorageId());
    self::assertSame($this->mountPath, $mount->getMountPoint());
    self::assertNotInstanceOf(IMovableMount::class, $mount);
    self::assertTrue($mount->getStorage()->instanceOfStorage(FailedStorage::class));
    self::assertSame(Storage::idForUser($this->user->getUID()), $mount->getStorage()->getId());
    self::assertFalse($mount->getStorage()->getCache()->get(self::FILE_PATH));

    // Use upstream mount reconciliation, not a mock of registerMounts().
    $this->mountCache()->registerMounts($this->user, $mounts, [MountProvider::class]);
    $this->scanMounts($mounts);
    self::assertSame($before, $this->snapshot());
  }

  public function testCronScannerRetainsMountsWithoutOpeningTheDatabase(): void
  {
    $this->factory->expects($this->never())->method('create');
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->never())->method('error');
    $this->logger = $logger;
    $setup = $this->createMock(SetupManager::class);
    $setup->expects($this->exactly(2))->method('tearDown');
    $setupFactory = $this->createStub(SetupManagerFactory::class);
    $setupFactory->method('create')->willReturn($setup);
    $manager = new Manager($setupFactory);
    $setup->expects($this->once())->method('setupForUser')->with($this->user)->willReturnCallback(function() use ($manager): void {
      $mounts = $this->provider()->getMountsForUser($this->user, $this->loader);
      $this->mountCache()->registerMounts($this->user, $mounts, [MountProvider::class]);
      $manager->addMount($mounts[0]);
      $manager->addMount(new MountPoint(
        new FailedStorage(['exception' => new \RuntimeException('Test home storage is offline')]),
        '/' . $this->user->getUID(),
        null,
        $this->loader,
      ));
    });
    $job = new class(
      $this->config,
      $this->createStub(IEventDispatcher::class),
      $logger,
      $this->connection,
      $this->createStub(ITimeFactory::class),
      $setup,
      $this->userManager,
    ) extends ScanFiles {
      public function scanUser(string $userId): void
      {
        parent::runScanner($userId);
      }
    };

    $mountManager = new \ReflectionProperty(Filesystem::class, 'mounts');
    $previous = $mountManager->getValue();
    $before = $this->snapshot();
    try {
      $mountManager->setValue(null, $manager);
      $job->scanUser($this->user->getUID());
    } finally {
      $mountManager->setValue(null, $previous);
    }
    self::assertSame($before, $this->snapshot());
  }

  public function testAuthenticatedStorageReusesTheSameIdBasedLink(): void
  {
    $before = $this->snapshot();
    $offline = $this->provider()->getMountsForUser($this->user, $this->loader);
    $this->mountCache()->registerMounts($this->user, $offline, [MountProvider::class]);
    $this->scanMounts($offline);

    $this->session->method('isLoggedIn')->willReturn(true);
    $this->session->method('getUser')->willReturn($this->user);
    $entityManager = $this->createStub(EntityManager::class);
    $entityManager->method('connected')->willReturn(true);
    $this->factory->expects($this->once())->method('create')->with($this->user->getUID())->willReturn($entityManager);
    $mounts = $this->provider()->getMountsForUser($this->user, $this->loader);
    self::assertCount(1, $mounts);
    $mount = $mounts[0];
    self::assertInstanceOf(Storage::class, $mount->getStorage());
    self::assertInstanceOf(IMovableMount::class, $mount);
    $cache = $mount->getStorage()->getCache();
    self::assertSame($this->fileId, $cache->getId(self::FILE_PATH));
    self::assertSame(self::FILE_PATH, $cache->getPathById($this->fileId));
    self::assertSame($this->rootId, $mount->getStorageRootId());
    $this->mountCache()->registerMounts($this->user, $mounts, [MountProvider::class]);
    self::assertSame($before, $this->snapshot());
  }

  public function testAnotherUsersSessionDoesNotOpenTheDatabase(): void
  {
    $otherUser = $this->createStub(IUser::class);
    $otherUser->method('getUID')->willReturn('another-user');
    $this->session->method('isLoggedIn')->willReturn(true);
    $this->session->method('getUser')->willReturn($otherUser);
    $this->factory->expects($this->never())->method('create');
    self::assertInstanceOf(UnavailableMount::class, $this->provider()->getMountsForUser($this->user, $this->loader)[0]);
  }

  public function testMissingDecryptionKeyPreservesTheMount(): void
  {
    $this->session->method('isLoggedIn')->willReturn(true);
    $this->session->method('getUser')->willReturn($this->user);
    $this->factory->expects($this->once())->method('create')->willThrowException(new RowAccessTokenInvalidException('Cannot decrypt'));
    self::assertInstanceOf(UnavailableMount::class, $this->provider()->getMountsForUser($this->user, $this->loader)[0]);
  }

  public function testDatabaseOutagePreservesTheMount(): void
  {
    $this->session->method('isLoggedIn')->willReturn(true);
    $this->session->method('getUser')->willReturn($this->user);
    $entityManager = $this->createStub(EntityManager::class);
    $entityManager->method('connected')->willReturn(false);
    $this->factory->expects($this->once())->method('create')->willReturn($entityManager);
    self::assertInstanceOf(UnavailableMount::class, $this->provider()->getMountsForUser($this->user, $this->loader)[0]);
  }

  public function testRemovedTokenDoesNotResurrectTheCachedMount(): void
  {
    $this->session->method('isLoggedIn')->willReturn(true);
    $this->session->method('getUser')->willReturn($this->user);
    $this->factory->expects($this->once())->method('create')->willThrowException(new RowAccessTokenMissingException('Removed'));
    $mounts = $this->provider()->getMountsForUser($this->user, $this->loader);
    self::assertSame([], $mounts);
    // The server fork also checks the global login state before removing
    // authenticated mounts, independently of the provider's injected session.
    $session = Server::get(\OCP\ISession::class);
    $previousUser = $session->get('user_id');
    $previousStore = Server::get(IStore::class);
    $credentials = $this->createStub(ICredentials::class);
    $credentials->method('getUID')->willReturn($this->user->getUID());
    $store = $this->createStub(IStore::class);
    $store->method('getLoginCredentials')->willReturn($credentials);
    try {
      $session->set('user_id', $this->user->getUID());
      \OC::$server->registerService(IStore::class, static fn() => $store);
      $this->mountCache()->registerMounts($this->user, $mounts, [MountProvider::class]);
    } finally {
      \OC::$server->registerService(IStore::class, static fn() => $previousStore);
      if ($previousUser === null) {
        $session->remove('user_id');
      } else {
        $session->set('user_id', $previousUser);
      }
    }
    self::assertSame([], $this->mountCache()->getMountsForUser($this->user));
  }

  public function testNeverMountedUserGetsNoPlaceholder(): void
  {
    $user = $this->createStub(IUser::class);
    $user->method('getUID')->willReturn('never-mounted');
    $this->factory->expects($this->never())->method('create');
    self::assertSame([], $this->provider()->getMountsForUser($user, $this->loader));
  }

  public function testDirectStorageScanCannotModifyTheRealCache(): void
  {
    $this->factory->expects($this->never())->method('create');
    $storage = $this->provider()->getMountsForUser($this->user, $this->loader)[0]->getStorage();
    $before = $this->snapshot();
    $this->expectException(StorageNotAvailableException::class);
    try {
      $storage->getScanner()->scanFile(self::FILE_PATH);
    } finally {
      self::assertSame($before, $this->snapshot());
    }
  }

  public function testMismatchedCachedStorageAbortsInsteadOfRemovingTheMount(): void
  {
    $this->factory->expects($this->never())->method('create');
    $query = $this->connection->getQueryBuilder();
    $query->update('storages')
      ->set('id', $query->createNamedParameter('another-users-storage'))
      ->where($query->expr()->eq('numeric_id', $query->createNamedParameter($this->storageId)))
      ->executeStatement();
    $before = $this->snapshot();
    $this->expectException(StorageNotAvailableException::class);
    try {
      $this->provider()->getMountsForUser($this->user, $this->loader);
    } finally {
      self::assertSame($before, $this->snapshot());
    }
  }

  public function testMissingCachedRootAbortsInsteadOfInventingANewRoot(): void
  {
    $this->factory->expects($this->never())->method('create');
    $query = $this->connection->getQueryBuilder();
    $query->delete('filecache')
      ->where($query->expr()->eq('fileid', $query->createNamedParameter($this->rootId)))
      ->executeStatement();
    $this->expectException(StorageNotAvailableException::class);
    $this->provider()->getMountsForUser($this->user, $this->loader);
  }

  public function testOtherProvidersMountsAreNotRestored(): void
  {
    $this->factory->expects($this->never())->method('create');
    $query = $this->connection->getQueryBuilder();
    $query->update('mounts')
      ->set('mount_provider_class', $query->createNamedParameter('AnotherProvider'))
      ->where($query->expr()->eq('storage_id', $query->createNamedParameter($this->storageId)))
      ->executeStatement();
    self::assertSame([], $this->provider()->getMountsForUser($this->user, $this->loader));
  }

  public static function unavailableOperations(): array
  {
    return [
      'list' => ['opendir', ['']],
      'stat' => ['stat', [self::FILE_PATH]],
      'metadata' => ['getMetaData', [self::FILE_PATH]],
      'download' => ['file_get_contents', [self::FILE_PATH]],
      'stream' => ['fopen', [self::FILE_PATH, 'rb']],
      'local file' => ['getLocalFile', [self::FILE_PATH]],
      'write' => ['file_put_contents', [self::FILE_PATH, 'replacement']],
    ];
  }

  #[DataProvider('unavailableOperations')]
  public function testOfflineOperationsFailExplicitly(string $method, array $arguments): void
  {
    $this->factory->expects($this->never())->method('create');
    $storage = $this->provider()->getMountsForUser($this->user, $this->loader)[0]->getStorage();
    $this->expectException(StorageNotAvailableException::class);
    $storage->$method(...$arguments);
  }

  private function scanMounts(array $mounts): void
  {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->never())->method('error');
    $scanner = $this->getMockBuilder(Scanner::class)
      ->setConstructorArgs([
        $this->user,
        $this->connection,
        $this->createStub(IEventDispatcher::class),
        $logger,
        $this->createStub(SetupManager::class),
      ])
      ->onlyMethods(['getMounts'])
      ->getMock();
    $scanner->expects($this->exactly(3))->method('getMounts')->willReturn($mounts);
    $scanner->backgroundScan('/' . $this->user->getUID());
    $scanner->scan($this->mountPath);
    $scanner->scan($this->mountPath . self::FILE_PATH);
  }

  private function provider(): MountProvider
  {
    $l10n = $this->createStub(IFactory::class);
    $l10n->method('get')->willReturn($this->createStub(IL10N::class));
    return new MountProvider($this->factory, $this->config, $this->session, $this->logger, $l10n, $this->mountCache(), $this->connection);
  }

  private function mountCache(): UserMountCache
  {
    return new UserMountCache(
      $this->connection,
      $this->userManager,
      $this->logger,
      $this->createStub(IEventLogger::class),
      $this->createStub(IEventDispatcher::class),
    );
  }

  private function insert(string $table, array $values): int
  {
    $query = $this->connection->getQueryBuilder();
    $query->insert($table);
    foreach ($values as $column => $value) {
      $query->setValue($column, $query->createNamedParameter($value));
    }
    $query->executeStatement();
    return $query->getLastInsertId();
  }

  private function cacheEntry(string $path, int $parent, int $size, string $mimeType): int
  {
    $mimes = Server::get(\OCP\Files\IMimeTypeLoader::class);
    return $this->insert('filecache', [
      'storage' => $this->storageId,
      'path' => $path,
      'path_hash' => md5($path),
      'parent' => $parent,
      'name' => basename($path),
      'size' => $size,
      'mtime' => 123456,
      'storage_mtime' => 123456,
      'mimetype' => $mimes->getId($mimeType),
      'mimepart' => $mimes->getId(explode('/', $mimeType)[0]),
      'etag' => 'original-etag-' . md5($path),
      'permissions' => \OCP\Constants::PERMISSION_READ,
    ]);
  }

  private function snapshot(): array
  {
    $rows = [];
    foreach (['storages' => 'numeric_id', 'filecache' => 'storage', 'mounts' => 'storage_id'] as $table => $column) {
      $query = $this->connection->getQueryBuilder();
      $result = $query->select('*')->from($table)
        ->where($query->expr()->eq($column, $query->createNamedParameter($this->storageId)))
        ->orderBy($table === 'filecache' ? 'fileid' : $column)
        ->executeQuery();
      $rows[$table] = $result->fetchAllAssociative();
      $result->closeCursor();
    }
    return $rows;
  }
}
