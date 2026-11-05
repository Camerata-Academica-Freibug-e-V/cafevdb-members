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

use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumGeographicalScope;
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * InstrumentInsurance
 */
#[ORM\Table(name: 'PersonalizedInstrumentInsurancesView')]
#[ORM\Entity]
class InstrumentInsurance implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;
  use CAFEVDB\Traits\SoftDeleteableEntity;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private $id;

  /**
   * @var Musician
   */
  #[ORM\JoinColumn(nullable: false)]
  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'instrumentInsurances', fetch: 'EXTRA_LAZY')]
  private $musician;

  /**
   * @var bool
   */
  #[ORM\Column(type: 'boolean', nullable: false)]
  private $isDebitor;

  /**
   * @var bool
   */
  #[ORM\Column(type: 'boolean', nullable: false)]
  private $isHolder;

  /**
   * @var bool
   */
  #[ORM\Column(type: 'boolean', nullable: false)]
  private $isOwner;

  /**
   * @var InsuranceRate
   */
  #[ORM\ManyToOne(targetEntity: InsuranceRate::class, inversedBy: 'instrumentInsurances', fetch: 'EXTRA_LAZY')]
  #[ORM\JoinColumn(name: 'broker_id', referencedColumnName: 'broker_id', nullable: false)]
  #[ORM\JoinColumn(name: 'geographical_scope', referencedColumnName: 'geographical_scope', nullable: false)]
  private $insuranceRate;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: false)]
  private $object;

  /**
   * @var array
   */
  #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => false])]
  private $accessory = false;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: false)]
  private $manufacturer;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 64, nullable: false)]
  private $yearOfConstruction;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false)]
  private $insuranceAmount;

  /**
   * @var \DateTime
   */
  #[ORM\Column(type: 'date_immutable', nullable: false)]
  private $startOfInsurance;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
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
   * Get broker.
   *
   * @return InsuranceBroker
   */
  public function getBroker():InsuranceBroker
  {
    return $this->insuranceRate->getBroker();
  }

  /**
   * Get geographicalScope.
   *
   * @return EnumGeographicalScope
   */
  public function getGeographicalScope(): EnumGeographicalScope
  {
    return $this->insuranceRate->getGeographicalScope();
  }

  /**
   * Get object.
   *
   * @return string
   */
  public function getObject():string
  {
    return $this->object;
  }

  /**
   * Get accessory.
   *
   * @return bool
   */
  public function getAccessory():bool
  {
    return !empty($this->accessory);
  }

  /**
   * Get manufacturer.
   *
   * @return string
   */
  public function getManufacturer():string
  {
    return $this->manufacturer;
  }

  /**
   * Get yearOfConstruction.
   *
   * @return string
   */
  public function getYearOfConstruction()
  {
    return $this->yearOfConstruction;
  }

  /**
   * Get insuranceAmount.
   *
   * @return int
   */
  public function getInsuranceAmount():int
  {
    return $this->insuranceAmount;
  }

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
   * Get startOfInsurance.
   *
   * @return \DateTimeInterface
   */
  public function getStartOfInsurance():?\DateTimeInterface
  {
    return $this->startOfInsurance;
  }

  /**
   * Get insuranceRate.
   *
   * @return InsuranceRate
   */
  public function getInsuranceRate():InsuranceRate
  {
    return $this->insuranceRate;
  }

  /**
   * Get isDebitor.
   *
   * @return bool
   */
  public function getIsDebitor():bool
  {
    return $this->isDebitor;
  }

  /**
   * Get isHolder.
   *
   * @return bool
   */
  public function getIsHolder():bool
  {
    return $this->isHolder;
  }

  /**
   * Get isOwner.
   *
   * @return bool
   */
  public function getIsOwner():bool
  {
    return $this->isOwner;
  }
}
