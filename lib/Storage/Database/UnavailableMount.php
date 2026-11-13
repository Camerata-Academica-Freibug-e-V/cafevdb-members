<?php
/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Storage\Database;

use OC\Files\Cache\Storage as StorageCache;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\FailedStorage;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Storage\IStorageFactory;
use OCP\Files\StorageNotAvailableException;

/** Preserve an existing mount without exposing its cached filesystem. */
class UnavailableMount extends MountPoint
{
  /**
   * @param string $storageId
   * @param ICachedMountInfo $cachedMount
   * @param IStorageFactory $loader
   */
  public function __construct(string $storageId, ICachedMountInfo $cachedMount, IStorageFactory $loader)
  {
    // FailedStorage's cache deliberately hides entries and reports -1 for IDs.
    // Mount registration must instead retain the real IDs, without scanning.
    $this->storageId = StorageCache::adjustStorageId($storageId);
    $this->numericStorageId = $cachedMount->getStorageId();
    $this->rootId = $cachedMount->getRootId();

    $storage = new class([
      'storageId' => $storageId,
      'exception' => new StorageNotAvailableException('Database storage requires the user\'s authorization credentials.'),
    ]) extends FailedStorage {
      private string $storageId;

      /** {@inheritdoc} */
      public function __construct(array $parameters)
      {
        parent::__construct($parameters);
        $this->storageId = $parameters['storageId'];
      }

      /** {@inheritdoc} */
      public function getId(): string
      {
        return $this->storageId;
      }
    };

    parent::__construct(
      $storage,
      $cachedMount->getMountPoint(),
      null,
      $loader,
      [
        'readonly' => true,
        'previews' => false,
        'enable_sharing' => false,
        'authenticated' => true,
      ],
      $cachedMount->getMountId(),
      $cachedMount->getMountProvider(),
    );
  }

  /** {@inheritdoc} */
  public function getMountType()
  {
    return MountProvider::MOUNT_TYPE;
  }
}
