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

use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;

use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * ProjectInstruments
 *
 * One musician may master more than one instrument. Hence one
 * musician may be employed to play more than one instrument in a
 * specific project. Still the ProjectParticipants table just links
 * real persons to projects. This is where this table plugs in: here
 * we record the instruments (where "looking after other's childs" is
 * also an instrument :) ) which are employed in each project for each
 * musician.
 *
 * Of course: the generic case is that a layman just plays one
 * instrument. Still we need to handle the more fabular cases for fun
 * -- and otherwise they imply ugly kludges and conventions in the frontend usage.
 */
#[ORM\Table(name: 'PersonalizedProjectInstrumentsView')]
#[ORM\Entity]
class ProjectInstrument implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;

  const UNVOICED = 0;

  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'participantInstruments', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $project;

  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'projectInstruments', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $musician;

  #[ORM\ManyToOne(targetEntity: Instrument::class, inversedBy: 'projectInstruments', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $instrument;

  /**
   * @var int|null
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['default' => '0', 'comment' => 'Voice specification if applicable, set to 0 if separation by voice is not needed'])]
  #[ORM\Id]
  private $voice = self::UNVOICED;

  /**
   * @var bool
   */
  #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => '0'])]
  private $sectionLeader = false;

  #[ORM\ManyToOne(targetEntity: ProjectParticipant::class, inversedBy: 'projectInstruments', fetch: 'EXTRA_LAZY')]
  #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'project_id', onDelete: 'cascade')]
  #[ORM\JoinColumn(name: 'musician_id', referencedColumnName: 'musician_id', onDelete: 'cascade')]
  private $projectParticipant;

  #[ORM\ManyToOne(targetEntity: MusicianInstrument::class, inversedBy: 'projectInstruments', fetch: 'EXTRA_LAZY')]
  #[ORM\JoinColumn(name: 'musician_id', referencedColumnName: 'musician_id')]
  #[ORM\JoinColumn(name: 'instrument_id', referencedColumnName: 'instrument_id')]
  private $musicianInstrument;

  #[ORM\ManyToOne(targetEntity: ProjectInstrumentationNumber::class, inversedBy: 'instruments', fetch: 'EXTRA_LAZY')]
  #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'project_id')]
  #[ORM\JoinColumn(name: 'instrument_id', referencedColumnName: 'instrument_id')]
  #[ORM\JoinColumn(name: 'voice', referencedColumnName: 'voice')]
  private $instrumentationNumber;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
  }
  // phpcs:enable

  /**
   * Get project.
   *
   * @return int
   */
  public function getProject()
  {
    return $this->project;
  }

  /**
   * Get musician.
   *
   * @return int
   */
  public function getMusician()
  {
    return $this->musician;
  }

  /**
   * Get instrument.
   *
   * @return int
   */
  public function getInstrument()
  {
    return $this->instrument;
  }

  /**
   * Get voice.
   *
   * @return int|null
   */
  public function getVoice()
  {
    return $this->voice;
  }

  /**
   * Get sectionLeader.
   *
   * @return bool
   */
  public function getSectionLeader()
  {
    return $this->sectionLeader;
  }

  /**
   * Get projectParticipant.
   *
   * @return int
   */
  public function getProjectParticipant()
  {
    return $this->projectParticipant;
  }

  /**
   * Get musicianInstrument.
   *
   * @return int
   */
  public function getMusicianInstrument()
  {
    return $this->musicianInstrument;
  }

  /**
   * Get instrumentationNumber.
   *
   * @return ProjectInstrumentationNumber
   */
  public function getInstrumentationNumber():ProjectInstrumentationNumber
  {
    return $this->instrumentationNumber;
  }
}
