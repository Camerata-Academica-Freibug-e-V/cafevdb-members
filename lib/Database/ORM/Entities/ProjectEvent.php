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

use OCA\CAFeVDBMembers\Database\DBAL\Types;
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Utils\Uuid;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Types\Types as DBALTypes;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;
use OCA\CAFeVDBMembers\Wrapped\Gedmo\Mapping\Annotation as Gedmo;
use OCA\CAFeVDBMembers\Wrapped\Ramsey\Uuid\UuidInterface;

/**
 * ProjectEvents
 */
#[ORM\Table(name: 'PersonalizedProjectEventsView')]
#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deleted')]
class ProjectEvent implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\SoftDeleteableEntity;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;

  /**
   * @var int
   *
   * While it would be tempting to just use the calendar URI and event UID and
   * perhaps the recurrence id as composite key it turns out that this is
   * complicated for repeating events: calendar apps may choose to split
   * existing series into two when changing "this event and future" events and
   * at that point one needs to match the recurrence ids in order to "find"
   * the correct new old event. The event will then be part of a new event
   * series with a new UID.
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private $id;

  /**
   * @var Project
   */
  #[ORM\JoinColumn(nullable: false)]
  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'calendarEvents', fetch: 'EXTRA_LAZY')]
  private $project;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  private $calendarId;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 764, nullable: false, options: ['collation' => 'ascii_bin'])]
  private $calendarUri;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 255, nullable: false, options: ['collation' => 'ascii_general_ci'])]
  private $eventUid;

  /**
   * @var UuidInterface
   *
   * A unique identifier which links RELATED-TO events. This occurs if
   * recurring event series are split but applying changes to "this and
   * future" events.
   */
  #[ORM\Column(type: 'uuid_binary', nullable: true)]
  private $seriesUid;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 764, nullable: false, options: ['collation' => 'ascii_bin'])]
  private $eventUri;

  /**
   * @var int
   *
   * The recurrence-id of the event instance as Unix timestamp. Non-recurring
   * events have an id of 0.
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
  private $recurrenceId;

  /**
   * @var int
   * The SEQUENCE number tied to the event. We always use the highest
   * sequence, but technically this is part of the id.
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
  private $sequence;

  #[ORM\Column(type: DBALTypes::ENUM, nullable: false)]
  private Types\EnumVCalendarType $type;

  /**
   * @var ProjectParticipantField
   * Linked ProjectParticipantField entities which can be used to record
   * asence from rehearsals or other calendar events. As calendar events are
   * possibly repeating or we need a list of linked fields in order to record
   * the participation for each event instance.
   */
  #[ORM\OneToOne(targetEntity: ProjectParticipantField::class, inversedBy: 'projectEvent', fetch: 'EXTRA_LAZY')]
  private $absenceField;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
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
   * Get project.
   *
   * @return Project|null
   */
  public function getProject():?Project
  {
    return $this->project;
  }

  /**
   * Get calendarId.
   *
   * @return int
   */
  public function getCalendarId()
  {
    return $this->calendarId;
  }

  /**
   * Get calendarUri.
   *
   * @return string
   */
  public function getCalendarUri():string
  {
    return $this->calendarUri;
  }

  /**
   * Get eventUri.
   *
   * @return string|null
   */
  public function getEventUri()
  {
    return $this->eventUri;
  }

  /**
   * Get eventUid.
   *
   * @return string|null
   */
  public function getEventUid():?string
  {
    return $this->eventUid;
  }

  /**
   * Get seriesUid.
   *
   * @return UuidInterface
   */
  public function getSeriesUid():?UuidInterface
  {
    return $this->seriesUid;
  }

  /**
   * Get sequence.
   *
   * @return int
   */
  public function getSequence():int
  {
    return $this->sequence;
  }

  /**
   * Get recurrenceId.
   *
   * @return int
   */
  public function getRecurrenceId():int
  {
    return $this->recurrenceId;
  }

  /**
   * Get type.
   *
   * @return Types\EnumVCalendarType|null
   */
  public function getType(): Types\EnumVCalendarType
  {
    return $this->type;
  }

  /**
   * Get absenceField.
   *
   * @return ProjectParticipantField
   */
  public function getAbsenceField():?ProjectParticipantField
  {
    return $this->absenceField;
  }
}
