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
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;

use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Database\DBAL\Types;

#[ORM\Table(name: 'PersonalizedCompositePaymentsView')]
#[ORM\Entity]
class CompositePayment implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;
  use CAFEVDB\Traits\TimestampableEntity;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private $id;

  /**
   * @var float
   *
   * The total amount for the bank transaction. This must equal the
   * sum of the self:$projectPayments collection.
   *
   * @todo If this is always the sum and thus can be computed, why then this
   * field?
   */
  #[ORM\Column(type: 'decimal', precision: 7, scale: 2, nullable: false, options: ['default' => '0.00'])]
  private $amount = '0.00';

  /**
   * @var \DateTimeImmutable|null
   */
  #[ORM\Column(type: 'date_immutable', nullable: true)]
  private $dateOfReceipt;

  /**
   * @var string Subject of the bank transaction.
   */
  #[ORM\Column(type: 'string', length: 1024, nullable: false)]
  private $subject;

  /**
   * @var Collection
   */
  #[ORM\OneToMany(targetEntity: ProjectPayment::class, mappedBy: 'compositePayment', fetch: 'EXTRA_LAZY')]
  private $projectPayments;

  #[ORM\JoinColumn(name: 'musician_id', referencedColumnName: 'musician_id', nullable: false)]
  #[ORM\JoinColumn(name: 'bank_account_sequence', referencedColumnName: 'sequence', nullable: true)]
  #[ORM\ManyToOne(targetEntity: SepaBankAccount::class, inversedBy: 'payments', fetch: 'EXTRA_LAZY')]
  private $sepaBankAccount;

  /**
   * @var SepaDebitMandate
   */
  #[ORM\ManyToOne(targetEntity: SepaDebitMandate::class, inversedBy: 'payments', fetch: 'EXTRA_LAZY')]
  #[ORM\JoinColumn(name: 'musician_id', referencedColumnName: 'musician_id', nullable: false)]
  #[ORM\JoinColumn(name: 'debit_mandate_sequence', referencedColumnName: 'sequence', nullable: true)]
  private $sepaDebitMandate;

  /**
   * @var string
   *
   * This is the unique message id from the email sent to the payees.
   */
  #[ORM\Column(type: 'string', length: 512, nullable: true)]
  private $notificationMessageId;

  /**
   * @var Musician
   */
  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'payments', fetch: 'EXTRA_LAZY')]
  private $musician;

  /**
   * @var DatabaseStorageFile
   *
   * Optional. ATM only used for particular auto-generated monetary fields.
   *
   *
   * @todo Support more than one supporting document.
   */
  #[ORM\OneToOne(targetEntity: DatabaseStorageFile::class, fetch: 'EXTRA_LAZY')]
  private $supportingDocument;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->projectPayments = new ArrayCollection;
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
   * Get projectPayments.
   *
   * @return Collection
   */
  public function getProjectPayments():Collection
  {
    return $this->projectPayments;
  }

  /**
   * Get amount.
   *
   * @return float
   */
  public function getAmount():float
  {
    return $this->amount;
  }

  /**
   * Return the sum of the amounts of the individual payments, which
   * should sum up to $this->amount, of course.
   *
   * @return float
   */
  public function sumPaymentsAmount():float
  {
    $totalAmount = 0.0;
    /** @var ProjectPayment $payment */
    foreach ($this->payments as $payment) {
      $totalAmount += $payment->getAmount();
    }
    return $totalAmount;
  }

  /**
   * Get musician.
   *
   * @return Musician
   */
  public function getMusician()
  {
    return $this->musician;
  }

  /**
   * Get dateOfReceipt.
   *
   * @return \DateTime|null
   */
  public function getDateOfReceipt()
  {
    return $this->dateOfReceipt;
  }

  /**
   * Get subject.
   *
   * @return string
   */
  public function getSubject()
  {
    return $this->subject;
  }

  /**
   * Get debitNote.
   *
   * @return SepaDebitNote|null
   */
  public function getDebitNote()
  {
    return $this->debitNote;
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

  /**
   * Get notificationMessageId.
   *
   * @return string
   */
  public function getNotificationMessageId()
  {
    return $this->notificationMessageId;
  }

  /**
   * Get supportingDocument.
   *
   * @return null|DatabaseStorageFile
   */
  public function getSupportingDocument():?DatabaseStorageFile
  {
    return $this->supportingDocument;
  }

  /** {@inheritdoc} */
  public function jsonSerialize():array
  {
    return $this->toArray();
  }
}
