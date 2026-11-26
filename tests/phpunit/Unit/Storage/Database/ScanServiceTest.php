<?php
/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Storage\Database;

use OC\Files\Cache\Storage as StorageCache;
use OCP\Files\ISetupManager;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use OCA\CAFeVDBMembers\AppInfo\Application;
use OCA\CAFeVDBMembers\Storage\Database\AuthenticatedScanner;
use OCA\CAFeVDBMembers\Storage\Database\MountProvider;
use OCA\CAFeVDBMembers\Storage\Database\ScanService;
use OCA\CAFeVDBMembers\Storage\Database\Storage;

#[CoversClass(ScanService::class)]
#[UsesClass(Storage::class)]
class ScanServiceTest extends TestCase
{
  private const UID = 'scan-service-test-user-with-a-long-id';

  private function session(bool $loggedIn = true): IUserSession
  {
    $user = $this->createStub(IUser::class);
    $user->method('getUID')->willReturn(self::UID);
    $session = $this->createStub(IUserSession::class);
    $session->method('isLoggedIn')->willReturn($loggedIn);
    $session->method('getUser')->willReturn($user);
    return $session;
  }

  private function mount(string $uid, IStorage $storage): IMountPoint
  {
    $mount = $this->createStub(IMountPoint::class);
    $mount->method('getMountProvider')->willReturn(MountProvider::class);
    $mount->method('getMountType')->willReturn(MountProvider::MOUNT_TYPE);
    $mount->method('getStorageId')->willReturn(StorageCache::adjustStorageId(Storage::idForUser($uid)));
    $mount->method('getStorage')->willReturn($storage);
    return $mount;
  }

  public function testScansOnlyCurrentUsersMovedMountAndThrottlesSuccess(): void
  {
    $session = $this->session();
    $setup = $this->createMock(ISetupManager::class);
    $setup->expects($this->exactly(2))->method('setupForUser')->with($session->getUser());
    $storage = $this->createStub(IStorage::class);
    $storage->method('instanceOfStorage')->willReturn(true);
    $mounts = $this->createMock(IMountManager::class);
    $mounts->expects($this->exactly(2))->method('findIn')->with('/' . self::UID . '/files/')->willReturn([
      $this->mount('another-user', $storage),
      $this->mount(self::UID, $storage),
    ]);
    $locking = $this->createMock(ILockingProvider::class);
    $lock = hash('sha256', 'cafevdbmembers:database-storage-scan:' . self::UID);
    $locking->expects($this->exactly(2))->method('acquireLock')->with($lock, ILockingProvider::LOCK_EXCLUSIVE);
    $locking->expects($this->exactly(2))->method('releaseLock')->with($lock, ILockingProvider::LOCK_EXCLUSIVE);
    $lastScan = '0';
    $config = $this->createMock(IConfig::class);
    $config->method('getUserValue')->willReturnCallback(static function() use (&$lastScan): string {
      return $lastScan;
    });
    $config->expects($this->once())->method('setUserValue')
      ->with(self::UID, Application::getAppName(), 'databaseStorageLastScan', self::callback('is_string'))
      ->willReturnCallback(static function($uid, $app, $key, $value) use (&$lastScan): void {
        $lastScan = $value;
      });
    $scanner = $this->createMock(AuthenticatedScanner::class);
    $scanner->expects($this->once())->method('scan')->with($storage);
    $service = new ScanService($session, $setup, $mounts, $locking, $config, $scanner);

    self::assertSame('scanned', $service->scan());
    self::assertSame('throttled', $service->scan());
  }

  public function testFailedScanReleasesLockAndCanBeRetried(): void
  {
    $storage = $this->createStub(IStorage::class);
    $storage->method('instanceOfStorage')->willReturn(true);
    $mounts = $this->createStub(IMountManager::class);
    $mounts->method('findIn')->willReturn([$this->mount(self::UID, $storage)]);
    $locking = $this->createMock(ILockingProvider::class);
    $locking->expects($this->exactly(2))->method('releaseLock');
    $config = $this->createMock(IConfig::class);
    $config->method('getUserValue')->willReturn('0');
    $config->expects($this->once())->method('setUserValue');
    $calls = 0;
    $scanner = $this->createMock(AuthenticatedScanner::class);
    $scanner->expects($this->exactly(2))->method('scan')->willReturnCallback(static function() use (&$calls): void {
      if (++$calls === 1) {
        throw new RuntimeException('Scan failed');
      }
    });
    $service = new ScanService($this->session(), $this->createStub(ISetupManager::class), $mounts, $locking, $config, $scanner);

    try {
      $service->scan();
      self::fail('The failed scan should throw.');
    } catch (RuntimeException $e) {
      self::assertSame('Scan failed', $e->getMessage());
    }
    self::assertSame('scanned', $service->scan());
  }

  public function testConcurrentRequestDoesNotScan(): void
  {
    $locking = $this->createMock(ILockingProvider::class);
    $locking->expects($this->once())->method('acquireLock')->willThrowException(new LockedException('test'));
    $locking->expects($this->never())->method('releaseLock');
    $scanner = $this->createMock(AuthenticatedScanner::class);
    $scanner->expects($this->never())->method('scan');
    $service = new ScanService(
      $this->session(),
      $this->createStub(ISetupManager::class),
      $this->createStub(IMountManager::class),
      $locking,
      $this->createStub(IConfig::class),
      $scanner,
    );
    self::assertSame('busy', $service->scan());
  }

  public function testMissingMountIsNotScannedOrThrottled(): void
  {
    $session = $this->session();
    $setup = $this->createMock(ISetupManager::class);
    $setup->expects($this->once())->method('setupForUser')->with($session->getUser());
    $mounts = $this->createStub(IMountManager::class);
    $mounts->method('findIn')->willReturn([]);
    $config = $this->createMock(IConfig::class);
    $config->expects($this->never())->method('getUserValue');
    $config->expects($this->never())->method('setUserValue');
    $scanner = $this->createMock(AuthenticatedScanner::class);
    $scanner->expects($this->never())->method('scan');
    $service = new ScanService(
      $session,
      $setup,
      $mounts,
      $this->createStub(ILockingProvider::class),
      $config,
      $scanner,
    );
    self::assertSame('no-mount', $service->scan());
  }

  public function testLoggedOutRequestIsRejected(): void
  {
    $service = new ScanService(
      $this->session(false),
      $this->createStub(ISetupManager::class),
      $this->createStub(IMountManager::class),
      $this->createStub(ILockingProvider::class),
      $this->createStub(IConfig::class),
      $this->createStub(AuthenticatedScanner::class),
    );
    $this->expectException(RuntimeException::class);
    $service->scan();
  }
}
