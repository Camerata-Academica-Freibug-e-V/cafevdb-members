<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2023, 2023, 2025 Claus-Justus Heine
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
 * Entity for project participants.
 */
#[ORM\Table(name: 'PersonalizedProjectParticipantsView')]
#[ORM\Entity]
class ProjectParticipant implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;
  use CAFEVDB\Traits\SoftDeleteableEntity;

  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'participants', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $project;

  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'projectParticipation', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $musician;

  /**
   * @var bool
   */
  #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => '0', 'comment' => 'Participant has confirmed the registration.'])]
  private $registration = '0';

  /**
   * Link to payments
   */
  #[ORM\OneToMany(targetEntity: ProjectPayment::class, mappedBy: 'projectParticipant')]
  private $payments;

  /**
   * Link to extra fields data
   */
  #[ORM\OneToMany(targetEntity: ProjectParticipantFieldDatum::class, indexBy: 'option_key', mappedBy: 'projectParticipant', fetch: 'EXTRA_LAZY')]
  private $participantFieldsData;

  /**
   * Link in the project instruments, may be more than one per participant.
   */
  #[ORM\OneToMany(targetEntity: ProjectInstrument::class, mappedBy: 'projectParticipant')]
  private $projectInstruments;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->payments = new ArrayCollection();
    $this->participantFieldsData = new ArrayCollection();
    $this->projectInstruments = new ArrayCollection();
  }
  // phpcs:enable

  /**
   * Get project.
   *
   * @return Project
   */
  public function getProject():?Project
  {
    return $this->project;
  }

  /**
   * Get musician.
   *
   * @return Musician
   */
  public function getMusician():?Musician
  {
    return $this->musician;
  }

  /**
   * Get registration.
   *
   * @return bool
   */
  public function getRegistration()
  {
    return $this->registration;
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
   * Get participantFieldsData.
   *
   * @return Collection
   */
  public function getParticipantFieldsData():Collection
  {
    return $this->participantFieldsData;
  }

  /**
   * Get one specific participant-field datum indexed by its key
   *
   * @param mixed $key Everything which can be converted to an UUID by
   * Uuid::asUuid().
   *
   * @return null|ProjectParticipantFieldDatum
   */
  public function getParticipantFieldsDatum(mixed $key):?ProjectParticipantFieldDatum
  {
    return $this->getByUuid($this->participantFieldsData, $key, 'optionKey');
  }

  /**
   * Get payments.
   *
   * @return Collection
   */
  public function getPayments():Collection
  {
    return $this->payments;
  }

  /**
   * Get sepaBankAccount.
   *
   * @return SepaBankAccount|null
   */
  public function getSepaBankAccount():?SepaBankAccount
  {
    return $this->sepaBankAccount;
  }

  /**
   * Get sepaDebitMandate.
   *
   * @return SepaDebitMandate|null
   */
  public function getSepaDebitMandate():?SepaDebitMandate
  {
    return $this->sepaDebitMandate;
  }
}
