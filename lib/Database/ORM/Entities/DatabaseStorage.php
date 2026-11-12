<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
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

use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * Connect a database storage identifier to its root directory entry.
 */
#[ORM\Table(name: 'PersonalizedDatabaseStoragesView')]
#[ORM\UniqueConstraint(columns: ['storage_id'])]
#[ORM\UniqueConstraint(columns: ['root_id'])]
#[ORM\Entity]
class DatabaseStorage implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  protected $id;

  /**
   * @var string The storage identifier without the common storage-ID prefix.
   */
  #[ORM\Column(type: 'string', length: 512, nullable: false)]
  protected $storageId;

  /**
   * @var DatabaseStorageFolder
   */
  #[ORM\OneToOne(targetEntity: DatabaseStorageFolder::class, inversedBy: 'storage')]
  #[ORM\JoinColumn(name: 'root_id', referencedColumnName: 'id')]
  protected $root;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
  }
  // phpcs:enable

  /** @return null|int */
  public function getId():?int
  {
    return $this->id;
  }

  /** @return null|string */
  public function getStorageId():?string
  {
    return $this->storageId;
  }

  /** @param null|string $storageId */
  public function setStorageId(?string $storageId):self
  {
    $this->storageId = $storageId;

    return $this;
  }

  /** @return null|DatabaseStorageFolder */
  public function getRoot():?DatabaseStorageFolder
  {
    return $this->root;
  }

  /** @param null|DatabaseStorageFolder $root */
  public function setRoot(?DatabaseStorageFolder $root):self
  {
    $this->root = $root;

    return $this;
  }

  /** {@inheritdoc} */
  public function __toString():string
  {
    return $this->storageId;
  }
}
