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
 * MusicianInstruments
 *
 * This is almost only a pivot table (i.e. a join table between
 * instruments and musicians) but for the "ranking" column which codes
 * a loose ranking like "primary instrument", i.e. the preference of
 * instruments of the given musician.
 */
#[ORM\Table(name: 'PersonalizedMusicianInstrumentsView')]
#[ORM\Entity]
class MusicianInstrument implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;
  use CAFEVDB\Traits\SoftDeleteableEntity;
  use CAFEVDB\Traits\UnusedTrait;

  /**
   * @var Musician
   */
  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'instruments')]
  #[ORM\Id]
  private $musician;

  /**
   * @var Instrument
   */
  #[ORM\ManyToOne(targetEntity: Instrument::class, inversedBy: 'musicianInstruments')]
  #[ORM\Id]
  private $instrument;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  private $ranking = 1;

  /** @var Collection */
  #[ORM\OneToMany(targetEntity: ProjectInstrument::class, mappedBy: 'musicianInstrument')]
  private Collection $projectInstruments;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->__wakeup();
    $this->projectInstruments = new ArrayCollection();
  }
  // phpcs:enable

  /**
   * Get musician.
   *
   * @return Musician
   */
  public function getMusician():Musician
  {
    return $this->musician;
  }

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
   * Get ranking.
   *
   * @return int
   */
  public function getRanking():int
  {
    return $this->ranking;
  }

  /**
   * Return the number of project instrumentation slots the associated
   * musician is registered with.
   *
   * @return int
   */
  public function usage():int
  {
    return 0; /* $this->projectInstruments->count(); */
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
}
