<?php
/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Storage\Database;

use OC\Files\Cache\Scanner;
use OC\Files\Storage\Storage as CloudStorage;
use OCP\Files\Cache\IScanner;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use OCP\Server;
use RuntimeException;
use Throwable;

/** Reconcile only the authenticated database storage with the Files cache. */
class AuthenticatedScanner
{
  public function __construct(private IDBConnection $connection)
  {
  }

  public function scan(CloudStorage $storage): void
  {
    if ($this->connection->inTransaction()) {
      throw new RuntimeException('Cannot scan database storage inside an existing transaction.');
    }
    $scanner = new class($storage) extends Scanner {
      public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true, $data = null)
      {
        try {
          return parent::scanFile($file, $reuseExisting, $parentId, $cacheData, $lock, $data);
        } catch (\Doctrine\DBAL\Exception $e) {
          throw new RuntimeException('Unable to update the database-storage file cache.', 0, $e);
        }
      }
    };
    $scanner->setUseTransactions(false);
    $this->connection->beginTransaction();
    try {
      $result = $scanner->scan('', IScanner::SCAN_RECURSIVE);
      if ($result === null || ($result['size'] ?? -1) === -1) {
        throw new RuntimeException('The database-storage scan did not complete.');
      }
      $this->connection->commit();
    } catch (Throwable $e) {
      try {
        $this->connection->rollBack();
      } finally {
        Server::get(IMimeTypeLoader::class)->reset();
      }
      throw $e;
    }
  }
}
