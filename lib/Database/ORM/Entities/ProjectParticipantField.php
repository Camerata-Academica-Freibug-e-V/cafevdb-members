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

use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumParticipantFieldDataType as FieldDataType;
use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumParticipantFieldMultiplicity as FieldMultiplicity;
use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumParticipationContext as ParticipationContext;
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Utils\Uuid;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Types\Types as DBALTypes;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;
use OCA\CAFeVDBMembers\Wrapped\Gedmo\Mapping\Annotation as Gedmo;

/**
 * ProjectParticipantFields
 */
#[ORM\Table(name: 'PersonalizedProjectParticipantFieldsView')]
#[ORM\Entity]
#[Gedmo\TranslationEntity(class: 'TableFieldTranslation')]
class ProjectParticipantField implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;
  use CAFEVDB\Traits\SoftDeleteableEntity;
  use CAFEVDB\Traits\GetByUuidTrait;
  use CAFEVDB\Traits\TranslatableTrait;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer')]
  #[ORM\Id]
  #[ORM\GeneratedValue]
  private $id;

  /**
   * @var Project
   */
  #[ORM\JoinColumn(nullable: false)]
  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'participantFields', fetch: 'EXTRA_LAZY')]
  private $project;

  /**
   * @var string
   */
  #[Gedmo\Translatable(untranslated: 'untranslatedName')]
  #[ORM\Column(type: 'string', length: 128, nullable: false)]
  private $name;

  /**
   * @var string
   *
   * Untranslated variant of self:$name, filled automatically by
   * Gedmo\Translatable
   */
  private $untranslatedName;

  #[ORM\Column(type: DBALTypes::ENUM, nullable: false)]
  private FieldMultiplicity $multiplicity;

  #[ORM\Column(type: DBALTypes::ENUM, nullable: false)]
  private FieldDataType $dataType;

  /**
   * @var Collection
   */
  #[ORM\OneToMany(targetEntity: ProjectParticipantFieldDataOption::class, mappedBy: 'field', indexBy: 'key')]
  #[ORM\OrderBy(['label' => 'ASC', 'key' => 'ASC'])]
  private $dataOptions;

  /**
   * @var \DateTimeImmutable
   */
  #[ORM\Column(type: 'date_immutable', nullable: true, options: ['comment' => 'Due-date for financial fields.'])]
  private $dueDate = null;

  /**
   * @var \DateTimeImmutable
   */
  #[ORM\Column(type: 'date_immutable', nullable: true, options: ['comment' => 'Due-date of deposit for financial fields.'])]
  private $depositDueDate = null;

  /**
   * @var null|ProjectParticipantFieldDataOption
   */
  #[ORM\OneToOne(targetEntity: ProjectParticipantFieldDataOption::class)]
  #[ORM\JoinColumn(name: 'id', referencedColumnName: 'field_id')]
  #[ORM\JoinColumn(name: 'default_value', referencedColumnName: 'key', nullable: true)]
  private $defaultValue = null;

  /**
   * @var string
   */
  #[Gedmo\Translatable]
  #[ORM\Column(type: 'string', length: 4096, nullable: true)]
  private $tooltip = null;

  /**
   * @var string
   */
  #[Gedmo\Translatable(untranslated: 'untranslatedTab')]
  #[ORM\Column(type: 'string', length: 256, nullable: true, options: ['comment' => 'Tab to display the field in. If empty, then the project tab is used.'])]
  private $tab = null;

  /**
   * @var string
   *
   * Untranslated variant of self:$tab, filled automatically by
   * Gedmo\Translatable
   */
  private $untranslatedTab;

  /**
   * @var int|null
   */
  #[ORM\Column(type: 'integer', nullable: true)]
  private $displayOrder = null;

  /**
   * @var bool|null
   */
  #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => '0'])]
  private $encrypted = false;

  /**
   * @var Collection
   */
  #[ORM\OneToMany(targetEntity: ProjectParticipantFieldDatum::class, mappedBy: 'field', fetch: 'EXTRA_LAZY')]
  private $fieldData;

  /**
   * @var null|ProjectEvent
   */
  #[ORM\OneToOne(targetEntity: ProjectEvent::class, mappedBy: 'absenceField')]
  private $projectEvent;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->__wakeup();
    $this->id = null;
    $this->project = null;
    $this->defaultValue = null;
    $this->fieldData = new ArrayCollection();
    $this->dataOptions = new ArrayCollection();
  }
  // phpcs:enable

  /** {@inheritdoc} */
  public function __wakeup()
  {
    $this->arrayCTOR();
  }

  /**
   * Get id.
   *
   * @return null|int
   */
  public function getId():?int
  {
    return $this->id;
  }

  /**
   * Get project.
   *
   * @return Project
   */
  public function getProject():Project
  {
    return $this->project;
  }

  /**
   * Get dataOption.
   *
   * @return Collection
   */
  public function getDataOptions():Collection
  {
    return $this->dataOptions;
  }

  /**
   * Get the options without UUID zero
   *
   * @param bool $includeDeleted Whether or not to include soft-deleted options.
   *
   * @return Collection
   */
  public function getSelectableOptions(bool $includeDeleted = false):Collection
  {
    // this unfortunately just does not work.
    // return $this->dataOptions->matching(DBUtil::criteriaWhere([ '!key' => Uuid::NIL, 'deleted' => null, ]));
    return $this->dataOptions->filter(function($option) use ($includeDeleted) {
      /** @var ProjectParticipantFieldDataOption $option */
      return ($includeDeleted || empty($option->getDeleted()))
        && $option->getKey() != Uuid::nil();
    });
  }

  /**
   * Get the special option holding management data if present.
   *
   * @return null|ProjectParticipantFieldDataOption
   */
  public function getManagementOption():?ProjectParticipantFieldDataOption
  {
    return $this->getDataOption(Uuid::NIL);
  }

  /**
   * Get one specific option
   *
   * @param mixed $key Everything which can be converted to an UUID by
   * Uuid::asUuid().
   *
   * @return null|ProjectParticipantFieldDataOption
   */
  public function getDataOption(mixed $key):?ProjectParticipantFieldDataOption
  {
    return $this->getByUuid($this->dataOptions, $key, 'key');
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
   * Return the number of data items associated with this field.
   *
   * @return int
   */
  public function usage():int
  {
    return $this->dataOptions->count();
  }

  /**
   * Get displayOrder.
   *
   * @return int|null
   */
  public function getDisplayOrder()
  {
    return $this->displayOrder;
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
   * Get untranslatedName.
   *
   * @return string
   */
  public function getUntranslatedName()
  {
    return $this->untranslatedName;
  }

  /**
   * Get multiplicity.
   *
   * @return EnumParticipantFieldMultiplicity
   */
  public function getMultiplicity(): FieldMultiplicity
  {
    return $this->multiplicity;
  }

  /**
   * Get dataType.
   *
   * @return EnumParticipantFieldDataType
   */
  public function getDataType(): FieldDataType
  {
    return $this->dataType;
  }

  /**
   * Get dueDate.
   *
   * @return \DateTimeImmutable|null
   */
  public function getDueDate():?\DateTimeImmutable
  {
    return $this->dueDate;
  }

  /**
   * Get depositDueDate.
   *
   * @return \DateTimeImmutable|null
   */
  public function getDepositDueDate():?\DateTimeImmutable
  {
    return $this->depositDueDate;
  }

  /**
   * Get defaultValue.
   *
   * @return null|ProjectParticipantFieldDataOption
   */
  public function getDefaultValue():?ProjectParticipantFieldDataOption
  {
    return $this->defaultValue;
  }

  /**
   * Get tooltip.
   *
   * @return string
   */
  public function getTooltip()
  {
    return $this->tooltip;
  }

  /**
   * Get tab.
   *
   * @return string
   */
  public function getTab()
  {
    return $this->tab;
  }

  /**
   * Get untranslatedTab.
   *
   * @return string
   */
  public function getUntranslatedTab()
  {
    return $this->untranslatedTab;
  }

  /**
   * Get encrypted.
   *
   * @return bool|null
   */
  public function getEncrypted()
  {
    return $this->encrypted;
  }

  /**
   * Get projectEvent.
   *
   * @return null|ProjectEvent
   */
  public function getProjectEvent():?ProjectEvent
  {
    return $this->projectEvent;
  }
}
