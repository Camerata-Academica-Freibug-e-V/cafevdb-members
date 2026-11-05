<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2023 Claus-Justus Heine
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

use Exception;

use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;

use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;
use OCA\CAFeVDBMembers\Wrapped\Gedmo\Mapping\Annotation as Gedmo;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Utils\Uuid;

/**
 * ProjectParticipantFieldsDataOptions
 */
#[ORM\Table(name: 'PersonalizedProjectParticipantFieldsDataOptionsView')]
#[ORM\Entity]
#[Gedmo\TranslationEntity(class: 'TableFieldTranslation')]
class ProjectParticipantFieldDataOption implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;
  use CAFEVDB\Traits\SoftDeleteableEntity;
  use CAFEVDB\Traits\TranslatableTrait;

  /**
   * @var ProjectParticipantField
   *
   * Link back to ProjectParticipantField
   */
  #[ORM\ManyToOne(targetEntity: ProjectParticipantField::class, inversedBy: 'dataOptions')]
  #[ORM\Id]
  private $field;

  /**
   * @var \Ramsey\Uuid\UuidInterface
   */
  #[ORM\Column(type: 'uuid_binary')]
  #[ORM\Id]
  private $key;

  /**
   * @var string
   */
  #[Gedmo\Translatable(untranslated: 'untranslatedLabel')]
  #[ORM\Column(type: 'string', length: 128, nullable: true)]
  private $label;

  /**
   * @var string
   *
   * Untranslated variant of self:$label, filled automatically by
   * Gedmo\Translatable
   */
  private $untranslatedLabel;

  /**
   * Multi-purpose field. For Multiplicity::RECURRING the PHP class
   * name of the generator class.
   *
   * @var string
   */
  #[ORM\Column(type: 'string', length: 1024, nullable: true)]
  private $data;

  /**
   * @var float
   * Optional value of a deposit for monetary options.
   */
  #[ORM\Column(type: 'float', nullable: true)]
  private $deposit;

  /**
   * @var int Limit on number of group members for
   * Multiplicity::GROUPSOFPEOPLE, Multiplicity::GROUPOFPEOPLE
   * fields. Misused as starting date for recurring receivables
   * generators.
   */
  #[ORM\Column(type: 'bigint', nullable: true)]
  private $limit;

  /**
   * @var string
   */
  #[Gedmo\Translatable]
  #[ORM\Column(type: 'string', length: 4096, nullable: true)]
  private $tooltip;

  #[ORM\OneToMany(targetEntity: ProjectParticipantFieldDatum::class, mappedBy: 'dataOption', indexBy: 'musician_id', fetch: 'EXTRA_LAZY')]
  private $fieldData;

  /**
   * @var ProjectPayment
   */
  #[ORM\OneToMany(targetEntity: ProjectPayment::class, mappedBy: 'receivableOption')]
  private $payments;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->__wakeup();
    $this->fieldData = new ArrayCollection();
    $this->payments = new ArrayCollection();
    $this->key = null;
    $this->field = null;
  }
  // phpcs:enable

  /** {@inheritdoc} */
  public function __clone()
  {
    if (empty($this->field) || empty($this->key)) {
      return;
    }
    $oldKey = $this->key;
    $this->__construct();
    $this->key = $oldKey == Uuid::nil()
               ? $oldKey
               : Uuid::create();
  }

  /** {@inheritdoc} */
  public function __wakeup()
  {
    $this->arrayCTOR();
  }

  /**
   * Get field.
   *
   * @return ProjectParticipantField
   */
  public function getField():ProjectParticipantField
  {
    return $this->field;
  }

  /**
   * Get key.
   *
   * @return UuidInterface
   */
  public function getKey()
  {
    return $this->key;
  }

  /**
   * Get label.
   *
   * @return string|null
   */
  public function getLabel():?string
  {
    return $this->label;
  }

  /**
   * Get untranslatedLabel.
   *
   * @return string|null
   */
  public function getUntranslatedLabel():?string
  {
    return $this->untranslatedLabel;
  }

  /**
   * Get data.
   *
   * @return null|string
   */
  public function getData():?string
  {
    return $this->data;
  }

  /**
   * Get deposit.
   *
   * @return null|float
   */
  public function getDeposit():?float
  {
    return $this->deposit;
  }

  /**
   * Get tooltip.
   *
   * @return string
   */
  public function getTooltip():?string
  {
    return $this->tooltip;
  }

  /**
   * Get limit.
   *
   * @return int
   */
  public function getLimit()
  {
    return $this->limit;
  }

  /**
   * Get fieldData.
   *
   * @return Collection
   */
  public function getFieldData():Collection
  {
    return $this->fieldData;
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
   * Filter field-data by musician.
   *
   * @param Musician $musician
   *
   * @todo Why does this return a collection? There should be zero or one data
   * item.
   *
   * @return Collection
   */
  public function getMusicianFieldData(Musician $musician):Collection
  {
    return $this->fieldData->matching(
      DBUtil::criteriaWhere([ 'musician' => $musician ])
    );
  }
}
