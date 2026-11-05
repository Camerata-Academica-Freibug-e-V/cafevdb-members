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
#[ORM\Table(name: 'PersonalizedInstrumentFamiliesView')]
#[ORM\Entity]
#[Gedmo\TranslationEntity(class: 'TableFieldTranslation')]
class InstrumentFamily implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TranslatableTrait;
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
  #[Gedmo\Translatable(untranslated: 'untranslatedFamily')]
  #[ORM\Column(type: 'string', length: 255, nullable: false, unique: true)]
  private string $family;

  /**
   * @var string
   */
  private string $untranslatedFamily;

  #[ORM\ManyToMany(targetEntity: Instrument::class, mappedBy: 'families')]
  private $instruments;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->instruments = new ArrayCollection();
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
   * Get family.
   *
   * @return string
   */
  public function getFamily():string
  {
    return $this->family;
  }

  /**
   * Get untranslated family.
   *
   * @return string
   */
  public function getUntranslatedFamily():string
  {
    return $this->untranslatedFamily;
  }

  /**
   * Get instruments.
   *
   * @return Collection
   */
  public function getInstruments():Collection
  {
    return $this->instruments;
  }

  /**
   * Get the usage count, i.e. the number of instruments which belong
   * to this family.
   *
   * @return int
   */
  public function usage():int
  {
    return $this->instruments->count();
  }
}
