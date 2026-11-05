<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2023, 2025 Claus-Justus Heine
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

use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;

use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Database\DBAL\Types;

/** Model for a file with encrypted data. */
#[ORM\Entity]
class EncryptedFile extends File
{
  /**
   * @var Collection
   */
  #[ORM\ManyToMany(targetEntity: Musician::class, mappedBy: 'encryptedFiles', indexBy: 'id', fetch: 'EXTRA_LAZY')] // The list of owners which in addition to the members of the management
  private Collection $owners;

  /**
   * @var Collection
   */
  #[ORM\OneToMany(targetEntity: DatabaseStorageFile::class, mappedBy: 'file', cascade: ['persist'])]
  private Collection $databaseStorageDirEntries;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    parent::__construct();
    $this->owners = new ArrayCollection;
    $this->databaseStorageDirEntries = new ArrayCollection;
  }
  // phpcs:enable

  /**
   * Get Owners.
   *
   * @return Collection
   */
  public function getOwners():Collection
  {
    return $this->owners;
  }

  /**
   * Get databaseStorageDirEntries.
   *
   * @return Collection
   */
  public function getDatabaseStorageDirEntries():Collection
  {
    return $this->databaseStorageDirEntries;
  }
}
