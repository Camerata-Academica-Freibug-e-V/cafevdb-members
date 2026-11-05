<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2023, 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Database\ORM\Entities;

use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumDirEntryType as DirEntryType;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * Folder entry for a database-backed file.
 */
#[ORM\Entity]
class DatabaseStorageFolder extends DatabaseStorageDirEntry
{
  /** @var string */
  protected static $type = DirEntryType::FOLDER;

  /**
   * @var Collection
   */
  #[ORM\OneToMany(targetEntity: DatabaseStorageDirEntry::class, mappedBy: 'parent')]
  protected $directoryEntries;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->directoryEntries = new ArrayCollection;
  }
  // phpcs:enable

  /** @return The directory entries which are files. */
  public function getDocuments():Collection
  {
    return $this->directoryEntries->filter(fn(DatabaseStorageDirEntry $dirEntry) => $dirEntry instanceof DatabaseStorageFile);
  }

  /** @return The directory entries which are directories. */
  public function getSubFolders():Collection
  {
    return $this->directoryEntries->filter(fn(DatabaseStorageDirEntry $dirEntry) => $dirEntry instanceof DatabaseStorageFolder);
  }


  /** @return Collection */
  public function getDirectoryEntries():Collection
  {
    return $this->directoryEntries;
  }

  /**
   * @param string $name
   *
   * @return null|DatabaseStorageDirEntry
   */
  public function getEntryByName(string $name):?DatabaseStorageDirEntry
  {
    $matches = $this->directoryEntries->matching(DBUtil::criteriaWhere([ 'name' => $name ]));

    return empty($matches) ? null : $matches->first();
  }

  /**
   * @param string $name
   *
   * @return null|DatabaseStorageFile
   */
  public function getFileByName(string $name):?DatabaseStorageFile
  {
    $matches = $this->directoryEntries
      ->matching(DBUtil::criteriaWhere([ 'name' => $name ]))
      ->filter(fn(DatabaseStorageDirEntry $dirEntry) => $dirEntry instanceof DatabaseStorageFile);

    return $matches->count() == 0 ? null : $matches->first();
  }

  /**
   * @param string $name
   *
   * @return null|DatabaseStorageFolder
   */
  public function getFolderByName(string $name):?DatabaseStorageFolder
  {
    $name = trim($name, Constants::PATH_SEP);
    $matches = $this->directoryEntries
      ->matching(DBUtil::criteriaWhere([ 'name' => $name ]))
      ->filter(fn(DatabaseStorageDirEntry $dirEntry) => $dirEntry instanceof DatabaseStorageFolder);

    return $matches->count() == 0 ? null : $matches->first();
  }

  /** @return bool Whether this folder is empty. */
  public function isEmpty():bool
  {
    return $this->directoryEntries->count() == 0;
  }
}
