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
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;

/**
 * SepaDebitMandate
 */
#[ORM\Table(name: 'PersonalizedSepaDebitMandatesView')]
#[ORM\Entity]
class SepaDebitMandate implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;
  use CAFEVDB\Traits\SoftDeleteableEntity;

  /**
   * @var Musician
   */
  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'sepaDebitMandates', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $musician;

  /**
   * @var int
   *
   * This is a POSITIVE per-musician sequence count. It currently is
   * incremented using
   * \OCA\CAFEVDB\Database\Doctrine\ORM\Traits\PerMusicianSequenceTrait
   */
  #[ORM\Column(type: 'integer')]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')] // _AT_ORM\GeneratedValue(strategy="CUSTOM")
  private $sequence;

  /**
   * @var SepaBankAccount
   *
   * Debit-mandates can expire, so many debit-mandates may refer the
   * same bank-account.
   */
  #[ORM\JoinColumn(name: 'musician_id', referencedColumnName: 'musician_id', nullable: false)]
  #[ORM\JoinColumn(name: 'bank_account_sequence', referencedColumnName: 'sequence', nullable: false)]
  #[ORM\ManyToOne(targetEntity: SepaBankAccount::class, inversedBy: 'sepaDebitMandates')]
  private $sepaBankAccount;

  /**
   * All debit-mandates are tied to a specific project. The convention
   * is that debit-mandates tied to the member's project are permanent
   * and can be used for all other projects as well. We do not make
   * this field an id as the sequence-id is a running index per
   * musician and joins are more difficult to define.
   *
   * The ProjectPayment entity, e.g., has to reference either a
   * mandate for its own project or a mandate from the member's
   * project.
   */
  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'sepaDebitMandates', fetch: 'EXTRA_LAZY')]
  private $project;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 35, options: ['collation' => 'ascii_general_ci'])]
  private $mandateReference;

  /**
   * @var bool
   */
  #[ORM\Column(type: 'boolean', nullable: false)]
  private $nonRecurring;

  /**
   * @var \DateTimeImmutable
   */
  #[ORM\Column(type: 'date_immutable', nullable: true)]
  private $mandateDate;

  /**
   * @var int
   *
   * Pre-notification dead-line in calendar days. Normally 14, may be
   * shorter, e.g. 7 calendar days but at least 5 business days.
   */
  #[ORM\Column(type: 'integer')]
  private $preNotificationCalendarDays;

  /**
   * @var int
   *
   * Pre-notification dead-line in TARGET2 days. Normally unset.
   */
  #[ORM\Column(type: 'integer', nullable: true)]
  private $preNotificationBusinessDays = null;

  /**
   * @var \DateTimeImmutable|null
   */
  #[ORM\Column(type: 'date_immutable', nullable: true)]
  private $lastUsedDate;

  /**
   * @var DatabaseStorageFile
   */
  #[ORM\OneToOne(targetEntity: DatabaseStorageFile::class, cascade: ['all'], orphanRemoval: true)]
  private $writtenMandate;

  /**
   * @var CompositePayment
   *
   * Linke to the payments table.
   */
  #[ORM\OneToMany(targetEntity: CompositePayment::class, mappedBy: 'sepaDebitMandate', fetch: 'EXTRA_LAZY')]
  private $payments;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->payments = new ArrayCollection();
  }
  // phpcs:enable

  /**
   * Get sequence.
   *
   * @return null|int
   */
  public function getSequence():?int
  {
    return $this->sequence;
  }

  /**
   * Get musician.
   *
   * @return int|Musician
   */
  public function getMusician()
  {
    return $this->musician;
  }

  /**
   * Get sepaBankAccount.
   *
   * @return SepaBankAccount
   */
  public function getSepaBankAccount():SepaBankAccount
  {
    return $this->sepaBankAccount;
  }

  /**
   * Get mandateReference.
   *
   * @return null|string
   */
  public function getMandateReference():?string
  {
    return $this->mandateReference;
  }

  /**
   * Get project.
   *
   * @return Project|int
   */
  public function getProject()
  {
    return $this->project;
  }

  /**
   * Get mandateDate.
   *
   * @return \DateTimeInterface
   */
  public function getMandateDate():\DateTimeInterface
  {
    return $this->mandateDate;
  }

  /**
   * Get preNotificationCalendarDays.
   *
   * @return int
   */
  public function getPreNotificationCalendarDays():int
  {
    return $this->preNotificationCalendarDays;
  }

  /**
   * Get preNotificationBusinessDays.
   *
   * @return int|null
   */
  public function getPreNotificationBusinessDays():?int
  {
    return $this->preNotificationBusinessDays;
  }

  /**
   * Get lastUsedDate.
   *
   * @return \DateTimeInterface
   */
  public function getLastUsedDate():?\DateTimeInterface
  {
    return $this->lastUsedDate;
  }

  /**
   * Get nonRecurring.
   *
   * @return bool
   */
  public function getNonRecurring():bool
  {
    return $this->nonRecurring;
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
   * Get writtenMandate.
   *
   * @return null|DatabaseStorageFile
   */
  public function getWrittenMandate():?DatabaseStorageFile
  {
    return $this->writtenMandate;
  }
}
