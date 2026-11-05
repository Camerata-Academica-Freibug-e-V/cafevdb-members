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

use DateTimeImmutable;
use DateTimeInterface;

use OCA\CAFeVDBMembers\Database\DBAL\Types;
use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumParticipationContext as ParticipationContext;
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Types\Types as DBALTypes;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * Projects
 */
#[ORM\Table(name: 'PersonalizedProjectsView')]
#[ORM\Entity(repositoryClass: \OCA\CAFeVDBMembers\Database\ORM\Repositories\ProjectsRepository::class)]
class Project implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;
  use CAFEVDB\Traits\SoftDeleteableEntity;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private int $id;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
  private int $year;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 64, nullable: false)]
  private string $name;

  #[ORM\Column(type: DBALTypes::ENUM, nullable: false)]
  private Types\EnumProjectTemporalType $type;

  /**
   * @var \DateTimeImmutable
   *
   * Optional registration start date. If not set then the online registration
   * is NOT available.
   */
  #[ORM\Column(type: 'date_immutable', nullable: true)]
  private ?DateTimeImmutable $registrationStartDate = null;

  /**
   * @var DateTimeImmutable
   *
   * Optional registration deadline. If null then the date one day before the
   * first rehearsal is used, if set. Otherwise no registration dead-line is
   * imposed.
   */
  #[ORM\Column(type: 'date_immutable', nullable: true)]
  private ?DateTimeImmutable $registrationDeadline = null;

  #[ORM\OneToMany(targetEntity: ProjectInstrumentationNumber::class, mappedBy: 'project', fetch: 'EXTRA_LAZY')]
  private Collection $instrumentationNumbers;

  /**
   * @var bool
   */
  #[ORM\Column(type: 'boolean')]
  private bool $clubMembers;

  /**
   * @var bool
   */
  #[ORM\Column(type: 'boolean')]
  private bool $executiveBoard;

  #[ORM\OneToMany(targetEntity: ProjectInstrument::class, mappedBy: 'project')]
  private Collection $participantInstruments;

  #[ORM\OneToMany(targetEntity: ProjectEvent::class, mappedBy: 'project')]
  private Collection $calendarEvents;

  #[ORM\OneToMany(targetEntity: ProjectParticipant::class, mappedBy: 'project')]
  private Collection $participants;

  #[ORM\OneToMany(targetEntity: ProjectApplication::class, mappedBy: 'project', indexBy: 'id')]
  private Collection $applications;

  #[ORM\OneToMany(targetEntity: ProjectParticipantField::class, mappedBy: 'project', indexBy: 'id', fetch: 'EXTRA_LAZY')]
  #[ORM\OrderBy(['displayOrder' => 'DESC'])]
  private Collection $participantFields;

  #[ORM\OneToMany(targetEntity: ProjectParticipantFieldDatum::class, mappedBy: 'project', fetch: 'EXTRA_LAZY')]
  private Collection $participantFieldsData;

  #[ORM\OneToMany(targetEntity: SepaDebitMandate::class, mappedBy: 'project')]
  private Collection $sepaDebitMandates;

  #[ORM\OneToMany(targetEntity: ProjectPayment::class, mappedBy: 'project')]
  private Collection $payments;

  #[ORM\OneToMany(targetEntity: ProjectWebPage::class, mappedBy: 'project', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
  private Collection $webPages;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->applications = new ArrayCollection();
    $this->calendarEvents = new ArrayCollection();
    $this->instrumentationNumbers = new ArrayCollection();
    $this->participantFields = new ArrayCollection();
    $this->participantFieldsData = new ArrayCollection();
    $this->participantInstruments = new ArrayCollection();
    $this->participants = new ArrayCollection();
    $this->payments = new ArrayCollection();
    $this->sepaDebitMandates = new ArrayCollection();
    $this->type = Types\EnumProjectTemporalType::TEMPORARY;
    $this->webPages = new ArrayCollection();
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
   * Get year.
   *
   * @return int
   */
  public function getYear()
  {
    return $this->year;
  }

  /**
   * Get name.
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Get type.
   *
   * @return EnumProjectTemporalType
   */
  public function getType(): Types\EnumProjectTemporalType
  {
    return $this->type;
  }

  /**
   * Get applications.
   *
   * @return Collection
   */
  public function getApplications():Collection
  {
    return $this->applications;
  }

  /**
   * Get participants.
   *
   * @return Collection
   */
  public function getParticipants():Collection
  {
    return $this->participants;
  }

  /**
   * Get participantFields.
   *
   * @return Collection
   */
  public function getParticipantFields():Collection
  {
    return $this->participantFields;
  }

  /**
   * Get participantFieldsData.
   *
   * @return Collection
   */
  public function getParticipantFieldsData():Collection
  {
    return $this->participantFieldsData;
  }

  /**
   * Get payments.
   *
   * @return ArrayCollection
   */
  public function getPayments():Collection
  {
    return $this->payments;
  }

  /**
   * Get sepaDebitMandates.
   *
   * @return Collection
   */
  public function getSepaDebitMandates():Collection
  {
    return $this->sepaDebitMandates;
  }

  /**
   * Returns registrationStartDate.
   *
   * @return DateTimeImmutable
   */
  public function getRegistrationStartDate():?DateTimeInterface
  {
    return $this->registrationStartDate;
  }

  /**
   * Returns registrationDeadline.
   *
   * @return DateTimeImmutable
   */
  public function getRegistrationDeadline():?DateTimeInterface
  {
    return $this->registrationDeadline;
  }

  /**
   * Get calendarEvents.
   *
   * @param bool $includeDeleted
   *
   * @return Collection
   */
  public function getCalendarEvents(bool $includeDeleted = false):Collection
  {
    if ($includeDeleted) {
      return $this->calendarEvents;
    }
    return $this->calendarEvents->filter(fn(ProjectEvent $event) => $event->getDeleted() === null);
  }

  /**
   * Get instrumentationNumbers.
   *
   * @return Collection
   */
  public function getInstrumentationNumbers()
  {
    return $this->instrumentationNumbers;
  }

  /**
   * Get participantInstruments.
   *
   * @return Collection
   */
  public function getParticipantInstruments():Collection
  {
    return $this->participantInstruments;
  }

  /**
   * Get webPages.
   *
   * @return Collection
   */
  public function getWebPages()
  {
    return $this->webPages;
  }

  /**
   * Get clubMembers.
   *
   * @return bool
   */
  public function getClubMembers():bool
  {
    return $this->clubMembers;
  }

  /**
   * Get executiveBoard.
   *
   * @return bool
   */
  public function getExecutiveBoard():bool
  {
    return !empty($this->executiveBoard);
  }
}
