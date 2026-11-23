<?php
/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Storage\Database;

use OC\Files\Storage\Storage as CloudStorage;
use OC\Files\Cache\Storage as StorageCache;
use OC\Lock\NoopLockingProvider;
use OCP\Files\ISetupManager;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use RuntimeException;

use OCA\CAFeVDBMembers\AppInfo\Application;

/** Run a throttled scan for the current user's own database storage. */
class ScanService
{
  private const INTERVAL = 600;
  private const LAST_SCAN_KEY = 'databaseStorageLastScan';

  public function __construct(
    private IUserSession $session,
    private ISetupManager $setupManager,
    private IMountManager $mountManager,
    private ILockingProvider $locking,
    private IConfig $config,
    private AuthenticatedScanner $scanner,
  ) {
  }

  /** @return string One of scanned, throttled, busy, no-mount, or unavailable. */
  public function scan(): string
  {
    $user = $this->session->getUser();
    if (!$this->session->isLoggedIn() || $user === null) {
      throw new RuntimeException('An authenticated user is required to scan database storage.');
    }
    if ($this->locking instanceof NoopLockingProvider) {
      throw new RuntimeException('File locking must be enabled to scan database storage.');
    }
    $uid = $user->getUID();
    $lock = hash('sha256', 'cafevdbmembers:database-storage-scan:' . $uid);
    try {
      $this->locking->acquireLock($lock, ILockingProvider::LOCK_EXCLUSIVE);
    } catch (LockedException $e) {
      return 'busy';
    }
    try {
      $lastScan = (int)$this->config->getUserValue($uid, Application::getAppName(), self::LAST_SCAN_KEY, '0');
      if ($lastScan > 0 && time() - $lastScan < self::INTERVAL) {
        return 'throttled';
      }
      $this->setupManager->setupForUser($user);
      $matches = [];
      foreach ($this->mountManager->findIn('/' . $uid . '/files/') as $mount) {
        if ($mount->getMountProvider() === MountProvider::class
            && $mount->getMountType() === MountProvider::MOUNT_TYPE
            && $mount->getStorageId() === StorageCache::adjustStorageId(Storage::idForUser($uid))) {
          $matches[] = $mount;
        }
      }
      if (count($matches) > 1) {
        throw new RuntimeException('Multiple database-storage mounts found for the user.');
      }
      if ($matches === []) {
        return 'no-mount';
      }
      if ($matches[0] instanceof UnavailableMount) {
        return 'unavailable';
      }
      $storage = $matches[0]->getStorage();
      if (!$storage instanceof CloudStorage || !$storage->instanceOfStorage(Storage::class)) {
        throw new RuntimeException('The database-storage mount is unavailable or has an invalid storage.');
      }
      $this->scanner->scan($storage);
      $this->config->setUserValue($uid, Application::getAppName(), self::LAST_SCAN_KEY, (string)time());
      return 'scanned';
    } finally {
      $this->locking->releaseLock($lock, ILockingProvider::LOCK_EXCLUSIVE);
    }
  }
}
