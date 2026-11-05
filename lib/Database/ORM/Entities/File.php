<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2023, 2025, 2026 Claus-Justus Heine
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

use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumFileType;
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Types\Types as DBALTypes;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * An entity which modesl a file-system file. While it is not always
 * advisable to store file-system data in a data-base, we do so
 * nevertheless for selected small files.
 */
#[ORM\Table(name: 'PersonalizedFilesView')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: DBALTypes::ENUM, enumType: EnumFileType::class)]
#[ORM\DiscriminatorMap([
  EnumFileType::GENERIC->value => 'File',
  EnumFileType::ENCRYPTED->value => 'EncryptedFile',
  EnumFileType::IMAGE->value => 'Image',
])]
#[ORM\Entity]
class File implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;

  const PATH_SEPARATOR = '/';

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private $id;

  /**
   * @var string|null
   */
  #[ORM\Column(type: 'string', length: 512, nullable: true)]
  private $fileName;

  /**
   * @var string|null
   */
  #[ORM\Column(type: 'string', length: 128, nullable: false)]
  private $mimeType;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['default' => -1])]
  private $size = -1;

  /**
   * @var Collection
   */
  #[ORM\OneToMany(targetEntity: FileData::class, mappedBy: 'file', fetch: 'EXTRA_LAZY')]
  protected $fileData;

  /**
   * @var string|null
   */
  #[ORM\Column(type: 'string', length: 32, nullable: true, options: ['fixed' => true])]
  protected $dataHash;

  /**
   * @var \DateTimeImmutable
   */
  #[ORM\Column(type: 'datetime_immutable', nullable: true)]
  protected ?DateTimeInterface $updated;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->fileData = new ArrayCollection;
    $this->arrayCTOR();
  }
  // phpcs:enable

  /**
   * Get id.
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Get mimeType.
   *
   * @return string|null
   */
  public function getMimeType()
  {
    return $this->mimeType;
  }

  /**
   * Get fileName.
   *
   * @return string|null
   */
  public function getFileName()
  {
    return $this->fileName;
  }

  /**
   * Get the dir-part of the file-name
   *
   * @return null|string
   */
  public function getDirName():?string
  {
    return is_string($this->fileName) ? dirname($this->fileName) : null;
  }

  /**
   * Get the dir-part of the file-name.
   *
   * @param null|string $extension
   *
   * @return null|string
   */
  public function getBaseName(?string $extension = null):?string
  {
    return is_string($this->fileName) ? basename($this->fileName, $extension) : null;
  }

  /**
   * Get dataHash.
   *
   * @return string|null
   */
  public function getDataHash()
  {
    return $this->dataHash;
  }

  /**
   * Get $size.
   *
   * @return int
   */
  public function getSize():int
  {
    return $this->size;
  }

  /**
   * Get FileData.
   *
   * @return FileData
   */
  public function getFileData():FileData
  {
    return $this->fileData->first();
  }
}
