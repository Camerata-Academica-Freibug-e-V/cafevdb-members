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
use OCA\CAFeVDBMembers\Wrapped\Gedmo\Mapping\Annotation as Gedmo;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;

use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Database\DBAL\Types;

/**
 * Instruments
 */
#[ORM\Table(name: 'PersonalizedInstrumentsView')]
#[ORM\Entity]
#[Gedmo\TranslationEntity(class: 'TableFieldTranslation')]
class Instrument implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TranslatableTrait;
  use CAFEVDB\Traits\SoftDeleteableEntity;
  use CAFEVDB\Traits\UnusedTrait;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private ?int $id = null;

  /**
   * @var string
   */
  #[Gedmo\Translatable(untranslated: 'untranslatedName')]
  #[ORM\Column(type: 'string', length: 128, nullable: false)]
  private string $name;

  /**
   * @var string
   */
  private string $untranslatedName;

  /**
   * @var int
   */
  #[ORM\Column(type: 'smallint', nullable: false)]
  private int $sortOrder;

  #[ORM\JoinTable(name: 'PersonalizedInstrumentInstrumentFamilyView')]
  #[ORM\JoinColumn(referencedColumnName: 'id')]
  #[ORM\InverseJoinColumn(referencedColumnName: 'id')]
  #[ORM\ManyToMany(targetEntity: InstrumentFamily::class, inversedBy: 'instruments')]
  private $families;

  #[ORM\OneToMany(targetEntity: MusicianInstrument::class, mappedBy: 'instrument', fetch: 'EXTRA_LAZY')]
  private $musicianInstruments;

  #[ORM\OneToMany(targetEntity: ProjectInstrument::class, mappedBy: 'instrument', fetch: 'EXTRA_LAZY')]
  private $projectInstruments;

  #[ORM\OneToMany(targetEntity: ProjectInstrumentationNumber::class, mappedBy: 'instrument', fetch: 'EXTRA_LAZY')]
  private $projectInstrumentationNumbers;


  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->families = new ArrayCollection();
    $this->musicianInstruments = new ArrayCollection();
    $this->projectInstruments = new ArrayCollection();
    $this->projectInstrumentationNumbers = new ArrayCollection();
  }
  // phpcs:enable

  /**
   * Get id.
   *
   * @return int
   */
  public function getId():int
  {
    return $this->id;
  }

  /**
   * Get name.
   *
   * @return string
   */
  public function getName():string
  {
    return $this->name;
  }

  /**
   * Get untranslated name.
   *
   * @return string
   */
  public function getUntranslatedName():string
  {
    return $this->untranslatedName;
  }

  /**
   * Get families.
   *
   * @return Collection
   */
  public function getFamilies():Collection
  {
    return $this->families;
  }

  /**
   * Get sortOrder.
   *
   * @return int
   */
  public function getSortOrder():int
  {
    return $this->sortOrder;
  }

  /**
   * Get musicianInstruments.
   *
   * @return Collection
   */
  public function getMusicianInstruments():Collection
  {
    return $this->musicianInstruments;
  }

  /**
   * Get projectInstruments.
   *
   * @return Collection
   */
  public function getProjectInstruments():Collection
  {
    return $this->projectInstruments;
  }

  /**
   * Get projectInstrumentationNumbers.
   *
   * @return Collection
   */
  public function getProjectInstrumentationNumbers():Collection
  {
    return $this->projectInstrumentationNumbers;
  }

  /**
   * Get the usage count, i.e. the number of entity instances which use this
   * instrument.
   *
   * @return int
   */
  public function usage():int
  {
    return $this->musicianInstruments->count()
      /* + $this->projectInstruments->count()
         + $this->projectInstrumentationNumbers->count() */;
  }
}
