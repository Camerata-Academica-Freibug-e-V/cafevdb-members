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

use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Common\RationalNumber;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Common\DecimalRationalP4S4 as RateNumberType;
use OCA\CAFeVDBMembers\Database\DBAL\Types;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\DecimalRationalP4S4Type as RateDatabaseType;
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Types\Types as DBALTypes;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * InsuranceRate
 */
#[ORM\Table(name: 'PersonalizedInsuranceRatesView')]
#[ORM\Entity]
class InsuranceRate implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;

  #[ORM\JoinColumn(referencedColumnName: 'short_name')]
  #[ORM\ManyToOne(targetEntity: InsuranceBroker::class, inversedBy: 'insuranceRates', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $broker;

  /**
   * @var Types\EnumGeographicalScope
   */
  #[ORM\Column(type: DBALTypes::ENUM, nullable: false)]
  #[ORM\Id]
  private Types\EnumGeographicalScope $geographicalScope;

  #[ORM\Column(type: RateDatabaseType::NAME_BASE . '_' . RateNumberType::PRECISION . '_' . RateNumberType::SCALE, nullable: false, options: ['unsigned' => true, 'comment' => 'fraction, not percentage, excluding taxes'])]
  private RateNumberType $rate;

  /**
   * @var \DateTimeImmutable
   */
  #[ORM\Column(type: 'date_immutable', nullable: false, options: ['comment' => 'start of the yearly insurance period'])]
  private $dueDate;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private $policyNumber;

  /**
   * @var Collection
   */
  #[ORM\OneToMany(targetEntity: InstrumentInsurance::class, mappedBy: 'insuranceRate', fetch: 'EXTRA_LAZY')]
  private $instrumentInsurances;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
    $this->instrumentInsurances = new ArrayCollection();
  }
  // phpcs:enable

  /**
   * Get broker.
   *
   * @return string
   */
  public function getBroker():InsuranceBroker
  {
    return $this->broker;
  }

  /**
   * Get geographicalScope.
   *
   * @return Types\EnumGeographicalScope
   */
  public function getGeographicalScope(): Types\EnumGeographicalScope
  {
    return $this->geographicalScope;
  }

  /**
   * Get rate.
   *
   * @return ?RateNumberType
   */
  public function getRate(): ?RateNumberType
  {
    return $this->rate ?? null;
  }

  /**
   * Get dueDate.
   *
   * @return \DateTimeImmutable
   */
  public function getDueDate():\DateTimeImmutable
  {
    return $this->dueDate;
  }

  /**
   * Get policyNumber.
   *
   * @return string
   */
  public function getPolicyNumber():?string
  {
    return $this->policyNumber;
  }

  /**
   * Get instrumentInsurances.
   *
   * @return ArrayCollection
   */
  public function getInstrumentInsurances():Collection
  {
    return $this->instrumentInsurances;
  }
}
