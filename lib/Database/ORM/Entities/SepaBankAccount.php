<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2023, 2026 Claus-Justus Heine
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
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;
use OCA\CAFeVDBMembers\Wrapped\MediaMonks\Doctrine\Mapping as MediaMonks;

/**
 * SepaBankAccount.
 */
#[ORM\Table(name: 'PersonalizedSepaBankAccountsView')]
#[ORM\Entity]
class SepaBankAccount implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;
  use CAFEVDB\Traits\SoftDeleteableEntity;

  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'sepaBankAccounts', fetch: 'EXTRA_LAZY')]
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
   * @var string
   *
   * Encryption with 16 bytes iv, 64 bytes HMAC (sha512) and perhaps more data for a multi-user seal.
   *
   * For now calculate wtih 256 bytes for the encrypted data itself + another
   * 256 bytes for each multi-user seal. Using 2k of data should be plenty
   * given that we probably only need two users: the management board with a
   * shared encryption key and the respective orchestra member with its own key.
   */
  #[ORM\Column(type: 'string', length: 2048, nullable: false, options: ['collation' => 'ascii_bin'])]
  #[MediaMonks\Transformable(name: 'encrypt', context: 'encryptionContext')]
  private $iban;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 2048, nullable: false, options: ['collation' => 'ascii_bin'])]
  #[MediaMonks\Transformable(name: 'encrypt', context: 'encryptionContext')]
  private $bic;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 2048, nullable: false, options: ['collation' => 'ascii_bin'])]
  #[MediaMonks\Transformable(name: 'encrypt', context: 'encryptionContext')]
  private $blz;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 2048, nullable: false, options: ['collation' => 'ascii_bin'])]
  #[MediaMonks\Transformable(name: 'encrypt', context: 'encryptionContext')]
  private $bankAccountOwner;

  /**
   * @var array
   *
   * In memory encryption context to support multi user encryption.
   */
  private $encryptionContext = [];

  /**
   * @var Collection
   *
   * Link to the attached debit mandates. Can be more than one at a
   * given time, even more than one active.
   */
  #[ORM\OneToMany(targetEntity: SepaDebitMandate::class, mappedBy: 'sepaBankAccount', fetch: 'EXTRA_LAZY')]
  private $sepaDebitMandates;

  /**
   * @var Collection
   */
  #[ORM\OneToMany(targetEntity: CompositePayment::class, mappedBy: 'sepaBankAccount', fetch: 'EXTRA_LAZY')]
  private $payments;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->sepaDebitMandates = new ArrayCollection();
    $this->payments = new ArrayCollection();
  }
  // phpcs:enable

  /**
   * Get iban.
   *
   * @return string
   */
  public function getIban()
  {
    return $this->iban;
  }

  /**
   * Get bic.
   *
   * @return string
   */
  public function getBic()
  {
    return $this->bic;
  }

  /**
   * Get blz.
   *
   * @return string
   */
  public function getBlz()
  {
    return $this->blz;
  }

  /**
   * Get bankAccountOwner.
   *
   * @return string
   */
  public function getBankAccountOwner():string
  {
    return $this->bankAccountOwner;
  }

  /**
   * Get musician.
   *
   * @return Musician|null
   */
  public function getMusician():?Musician
  {
    return $this->musician;
  }

  /**
   * Get sequence.
   *
   * @return int|null
   */
  public function getSequence():?int
  {
    return $this->sequence;
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
   * Get sepaDebitMandates.
   *
   * @return Collection
   */
  public function getSepaDebitMandates():Collection
  {
    return $this->sepaDebitMandates;
  }

  /**
   * Get encryptionContext.
   *
   * @return array
   */
  public function getEncryptionContext():array
  {
    return $this->encryptionContext;
  }
}
