<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2023 Claus-Justus Heine
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
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;

/**
 * ProjectInstrumentationNumber
 *
 * This is almost only a pivot table (i.e. a join table between
 * instruments and projects) but for the "Quantity" column which
 * states how many instruments are needed.
 */
#[ORM\Table(name: 'PersonalizedProjectInstrumentationNumbersView')]
#[ORM\Entity]
class ProjectInstrumentationNumber implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;

  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'instrumentationNumbers', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $project;

  #[ORM\ManyToOne(targetEntity: Instrument::class, inversedBy: 'projectInstrumentationNumbers', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $instrument;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', options: ['default' => '0', 'comment' => 'Voice specification if applicable, set to 0 if separation by voice is not needed'])]
  #[ORM\Id]
  private $voice = ProjectInstrument::UNVOICED;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['default' => '1', 'comment' => 'Number of required musicians for this instrument'])]
  private $quantity = '1';

  #[ORM\OneToMany(targetEntity: ProjectInstrument::class, mappedBy: 'instrumentationNumber', fetch: 'EXTRA_LAZY', indexBy: 'musician_id')]
  private $instruments;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->instruments = new ArrayCollection();
  }
  // phpcs:enable

  /**
   * Get instrument.
   *
   * @return Instrument
   */
  public function getInstrument():Instrument
  {
    return $this->instrument;
  }

  /**
   * Get project.
   *
   * @return int
   */
  public function getProject():Project
  {
    return $this->project;
  }

  /**
   * Get voice.
   *
   * @return int
   */
  public function getVoice():int
  {
    return $this->voice;
  }

  /**
   * Get quantity.
   *
   * @return int
   */
  public function getQuantity():int
  {
    return $this->quantity;
  }

  /**
   * Get instruments.
   *
   * @return int
   */
  public function getInstruments():Collection
  {
    return $this->instruments;
  }
}
