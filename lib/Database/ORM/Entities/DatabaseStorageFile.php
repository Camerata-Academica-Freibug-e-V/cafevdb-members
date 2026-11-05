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

use DateTimeInterface;

use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumDirEntryType as DirEntryType;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * File-name entry for a database-backed file.
 */
#[ORM\Entity]
class DatabaseStorageFile extends DatabaseStorageDirEntry
{
  /** @var string */
  protected static $type = DirEntryType::FILE;

  /**
   * @var EncryptedFile
   */
  #[ORM\ManyToOne(targetEntity: EncryptedFile::class, inversedBy: 'databaseStorageDirEntries')]
  protected $file;

  /** {@inheritdoc} */
  public function __construct()
  {
    parent::__construct();
  }

  /** @return null|EncryptedFile */
  public function getFile():?EncryptedFile
  {
    return $this->file;
  }

  /** @return DateTimeInterface */
  public function getUpdated():?DateTimeInterface
  {
    if (empty($this->file)) {
      return self::ensureDate($this->updated);
    }
    return max(self::ensureDate($this->updated), self::ensureDate($this->file->getUpdated()));
  }

  const FILE_METHODS = [
    'getSize',
    'getFileData',
    'getMimeType',
    'getNumberOfLinks',
  ];

  /**
   * {@inheritdoc}
   *
   * Pass through to wrapped file.
   */
  public function __call($method, $args)
  {
    if (array_search($method, self::FILE_METHODS) !== false
        && is_callable([ $this->file, $method ])) {
      return call_user_func_array([ $this->file, $method ], $args);
    }
    throw new Exceptions\DatabaseException('Undefined method - ' . __CLASS__ . '::' . $method);
  }

  /** @return null|string */
  public function getFileName():?string
  {
    return $this->getName();
  }

  /**
   * Get the extension-part of the file-name.
   *
   * @param null|string $extension
   *
   * @return null|string
   */
  public function getExtension(?string $extension = null):?string
  {
    return is_string($this->name) ? pathinfo($this->name, PATHINFO_EXTENSION) : null;
  }
}
