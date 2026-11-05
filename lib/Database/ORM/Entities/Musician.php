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

use OCA\CAFeVDBMembers\Database\DBAL\Types;
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Types\Types as DBALTypes;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * Musician
 */
#[ORM\Table(name: 'PersonalizedMusiciansView')]
#[ORM\Entity]
class Musician implements \ArrayAccess, \JsonSerializable
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;
  use CAFEVDB\Traits\UuidTrait;
  use CAFEVDB\Traits\GetByUuidTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private $id;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: false)]
  private $surName;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: false)]
  private $firstName;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: true)]
  private $nickName;

  /**
   * @var string
   *
   * Display name, replaces default "$surName, $firstName"
   */
  #[ORM\Column(type: 'string', length: 256, nullable: true)]
  private $displayName;

  #[ORM\Column(type: DBALTypes::ENUM, nullable: true)]
  private ?Types\EnumGender $gender = null;

  /**
   * @var string
   *
   * This should look like a suitable user id, e.g.
   *
   * "official":  firstName.surName, nickName.surName
   * "personal":  firstName or firstname-FIRSTLETER_OF_SURNAME, e.g kathap, kathid
   *
   * We use the semi-official nickName.surName, e.g. katha.puff.
   */
  #[ORM\Column(type: 'string', length: 256, unique: true, nullable: true)]
  private $userIdSlug;

  /**
   * @var string
   *
   * Meant for per-user authentication which might be used for future
   * extensions.
   */
  #[ORM\Column(type: 'string', length: 256, unique: false, nullable: true)]
  private $userPassphrase;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: true)]
  private $city;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: true)]
  private $street;

  /**
   * @var string
   *
   * The street-number. I may actually be alpha-numeric like "2a" or something, so it is a string.
   */
  #[ORM\Column(type: 'string', length: 32, nullable: true)]
  private $streetNumber;

  /**
   * @var string
   *
   * Additional address information, like "Appartment 200" or c/o.
   */
  #[ORM\Column(type: 'string', length: 128, nullable: true)]
  private $addressSupplement;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 2, nullable: true)]
  private $country;

  /**
   * @var int|null
   */
  #[ORM\Column(type: 'string', length: 32, nullable: true)]
  private $postalCode;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 2, nullable: true)]
  private $language;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: true)]
  private $mobilePhone;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: true)]
  private $fixedLinePhone;

  /**
   * @var \DateTime|null
   */
  #[ORM\Column(type: 'date_immutable', nullable: true)]
  private $birthday;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 256, nullable: false)]
  private $email;

  /**
   * @var Collection All email addresses.
   */
  #[ORM\OneToMany(targetEntity: MusicianEmailAddress::class, mappedBy: 'musician', indexBy: 'address')]
  private $emailAddresses;

  #[ORM\Column(type: DBALTypes::ENUM, nullable: false)]
  private Types\EnumParticipationStatus $defaultParticipationStatus;

  /**
   * @var string|null
   */
  #[ORM\Column(type: 'string', length: 1024, nullable: true)]
  private $remarks;

  /**
   * @var bool|null
   *
   * Set to true if for whatever reason the user remains undeleted in the
   * musician-database but its cloud-account needs to be deactivated, for
   * instance to prevent abuse after a password breach or things like that.
   *
   * This only affects the cloud-account of DB-musicians. It can be set by
   * admins and group-admins through the cloud admin UI.
   */
  #[ORM\Column(type: 'boolean', nullable: true)]
  private $cloudAccountDeactivated;

  /**
   * @var bool|null
   *
   * Set to true if the cloud user-account should not be generated at
   * all. This differs from $cloudAccountDeactivated in that with
   * "...Disabled" the musician is not even exported as user account, while
   * the "...Deactivated" flag can be changed by the cloud administrator.
   *
   * Not that deleted users are also not exported to the cloud.
   */
  #[ORM\Column(type: 'boolean', nullable: true)]
  private $cloudAccountDisabled;

  #[ORM\OneToMany(targetEntity: ProjectApplication::class, mappedBy: 'musician', indexBy: 'project_id', orphanRemoval: true, fetch: 'EXTRA_LAZY')]
  private $projectApplications;

  #[ORM\OneToMany(targetEntity: MusicianInstrument::class, mappedBy: 'musician', fetch: 'EXTRA_LAZY')]
  private $instruments;

  #[ORM\OneToMany(targetEntity: ProjectParticipant::class, mappedBy: 'musician', indexBy: 'project_id', fetch: 'EXTRA_LAZY')]
  private $projectParticipation;

  #[ORM\OneToMany(targetEntity: ProjectInstrument::class, mappedBy: 'musician', fetch: 'EXTRA_LAZY')]
  private $projectInstruments;

  #[ORM\OneToMany(targetEntity: ProjectParticipantFieldDatum::class, mappedBy: 'musician', indexBy: 'option_key', fetch: 'EXTRA_LAZY')]
  private $projectParticipantFieldsData;

  #[ORM\OneToMany(targetEntity: SepaBankAccount::class, mappedBy: 'musician', fetch: 'EXTRA_LAZY')]
  private $sepaBankAccounts;

  #[ORM\OneToMany(targetEntity: SepaDebitMandate::class, mappedBy: 'musician', fetch: 'EXTRA_LAZY')]
  private $sepaDebitMandates;

  #[ORM\OneToMany(targetEntity: CompositePayment::class, mappedBy: 'musician', fetch: 'EXTRA_LAZY')]
  private $payments;

  #[ORM\OneToMany(targetEntity: InstrumentInsurance::class, mappedBy: 'musician', fetch: 'EXTRA_LAZY')]
  private $instrumentInsurances;

  /**
   * @var Collection
   */
  #[ORM\JoinTable(name: 'PersonalizedEncryptedFileOwnersView')]
  #[ORM\ManyToMany(targetEntity: EncryptedFile::class, inversedBy: 'owners', indexBy: 'id', fetch: 'EXTRA_LAZY')]
  private $encryptedFiles;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->__wakeup();
    $this->defaultParticipationStatus = Types\EnumParticipationStatus::REGULAR();
    $this->instruments = new ArrayCollection;
    $this->payments = new ArrayCollection();
    $this->projectApplications = new ArrayCollection();
    $this->projectInstruments = new ArrayCollection();
    $this->projectParticipantFieldsData = new ArrayCollection();
    $this->projectParticipation = new ArrayCollection();
    $this->sepaBankAccounts = new ArrayCollection;
    $this->sepaDebitMandates = new ArrayCollection;
  }
  // phpcs:enable

  /** {@inheritdoc} */
  public function __wakeup()
  {
    $this->arrayCTOR();
    $this->keys[] = 'publicName';
  }

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
   * Get surName.
   *
   * @return string
   */
  public function getSurName()
  {
    return $this->surName;
  }

  /**
   * Get firstName.
   *
   * @return string
   */
  public function getFirstName()
  {
    return $this->firstName;
  }

  /**
   * Get city.
   *
   * @return string
   */
  public function getCity()
  {
    return $this->city;
  }

  /**
   * Get street.
   *
   * @return string
   */
  public function getStreet()
  {
    return $this->street;
  }

  /**
   * Get streetNumber.
   *
   * @return string
   */
  public function getStreetNumber()
  {
    return $this->streetNumber;
  }

  /**
   * Get addressSupplement.
   *
   * @return string
   */
  public function getAddressSupplement()
  {
    return $this->addressSupplement;
  }

  /**
   * Get postalCode.
   *
   * @return int|null
   */
  public function getPostalCode()
  {
    return $this->postalCode;
  }

  /**
   * Get country.
   *
   * @return string
   */
  public function getCountry()
  {
    return $this->country;
  }

  /**
   * Get language.
   *
   * @return string
   */
  public function getLanguage()
  {
    return $this->language;
  }

  /**
   * Get mobilePhone.
   *
   * @return string
   */
  public function getMobilePhone()
  {
    return $this->mobilePhone;
  }

  /**
   * Get fixedLinePhone.
   *
   * @return string
   */
  public function getFixedLinePhone()
  {
    return $this->fixedLinePhone;
  }

  /**
   * Get birthday.
   *
   * @return \DateTimeInterface|null
   */
  public function getBirthday():?\DateTimeInterface
  {
    return $this->birthday;
  }

  /**
   * Get email.
   *
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  }

  /**
   * Get emailAddresses.
   *
   * @return Collection
   */
  public function getEmailAddresses():Collection
  {
    return $this->emailAddresses;
  }

  /**
   * Get defaultParticipationStatus.
   *
   * @return EnumParticipationStatus
   */
  public function getDefaultParticipationStatus(): Types\EnumParticipationStatus
  {
    return $this->defaultParticipationStatus;
  }

  /**
   * Get gender.
   *
   * @return EnumGender
   */
  public function getGender(): ?Types\EnumGender
  {
    return $this->gender;
  }

  /**
   * Get remarks.
   *
   * @return string|null
   */
  public function getRemarks():?string
  {
    return $this->remarks;
  }

  /**
   * Get cloudAccountDisabled.
   *
   * @return null|bool
   */
  public function getCloudAccountDisabled():?bool
  {
    return $this->cloudAccountDisabled;
  }

  /**
   * Get cloudAccountDeactivated.
   *
   * @return null|bool
   */
  public function getCloudAccountDeactivated():?bool
  {
    return $this->cloudAccountDeactivated;
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
   * Get encryptedFiles.
   *
   * @return Collection
   */
  public function getEncryptedFiles():Collection
  {
    return $this->encryptedFiles;
  }

  /**
   * Get displayName.
   *
   * @return string
   */
  public function getDisplayName():?string
  {
    return $this->displayName;
  }

  /**
   * Get nickName.
   *
   * @return string
   */
  public function getNickName():?string
  {
    return $this->nickName;
  }


  /**
   * Get the cooked display-name, taking nick-name into account and
   * just using $displayName if set.
   *
   * @param bool $firstNameFirst
   *
   * @return string
   */
  public function getPublicName(bool $firstNameFirst = false):string
  {
    $firstName = empty($this->nickName) ? $this->firstName : $this->nickName;
    if ($firstNameFirst) {
      return $firstName . ' ' . $this->surName;
    }
    if (!empty($this->displayName)) {
      return $this->displayName;
    }
    return $this->surName.', '.$firstName;
  }

  /**
   * Get userPassphrase.
   *
   * @return string
   */
  public function getUserPassphrase():?string
  {
    return $this->userPassphrase;
  }

  /**
   * Get userIdSlug.
   *
   * @return string
   */
  public function getUserIdSlug():?string
  {
    return $this->userIdSlug;
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
   * Get projectApplications.
   *
   * @return Collection
   */
  public function getProjectApplications():Collection
  {
    return $this->projectApplications;
  }

  /**
   * Get projectParticipation.
   *
   * @return Collection
   */
  public function getProjectParticipation():Collection
  {
    return $this->projectParticipation;
  }

  /**
   * Check whether the given project is contained in projectParticipation.
   *
   * @param int|Project $projectOrId
   *
   * @return bool
   */
  public function isMemberOf($projectOrId):bool
  {
    return !empty($this->getProjectParticipantOf($projectOrId));
  }

  /**
   * Return the project-participant entity for the given project or null
   *
   * @param int|Project $projectOrId
   *
   * @return null|ProjectParticipant
   */
  public function getProjectParticipantOf($projectOrId):?ProjectParticipant
  {
    $projectId = ($projectOrId instanceof Project) ? $projectOrId->getId() : $projectOrId;
    $participant = $this->projectParticipation->get($projectId);
    if (!empty($participant)) {
      return $participant;
    }
    // $matching = $this->projectParticipation->matching(DBUtil::criteriaWhere([
    //   'project' => $projectId,
    // ]));
    //
    // The infamous
    //
    // Cannot match on
    // OCA\CAFEVDB\Database\Doctrine\ORM\Entities\ProjectParticipant::project
    // with a non-object value. Matching objects by id is not
    // compatible with matching on an in-memory collection, which
    // compares objects by reference.
    //
    // Oh no.

    $matching = $this->projectParticipation->filter(function($participant) use ($projectId) {
      return $participant->getProject()->getId() == $projectId;
    });
    if ($matching->count() == 1) {
      return $matching->first();
    }
    return null;
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
   * Get sepaBankAccounts.
   *
   * @return Collection
   */
  public function getSepaBankAccounts():Collection
  {
    return $this->sepaBankAccounts;
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

  /** {@inheritdoc} */
  public function jsonSerialize():array
  {
    return $this->toArray();
    return array_merge($this->toArray(), [
      'publicName' => $this->getPublicName(true),
    ]);
  }

  /**
   * Get projectParticipantFieldsData.
   *
   * @return Collection
   */
  public function getProjectParticipantFieldsData():Collection
  {
    return $this->projectParticipantFieldsData;
  }

  /**
   * Get one specific participant-field datum indexed by its key
   *
   * @param mixed $key Everything which can be converted to an UUID by
   * Uuid::asUuid().
   *
   * @return null|ProjectParticipantFieldDatum
   */
  public function getProjectParticipantFieldsDatum(mixed $key):?ProjectParticipantFieldDatum
  {
    return $this->getByUuid($this->projectParticipantFieldsData, $key, 'optionKey');
  }

  /**
   * Get instrumentInsurances.
   *
   * @return Collection
   */
  public function getInstrumentInsurances():Collection
  {
    return $this->instrumentInsurances;
  }
}
