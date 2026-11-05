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
 * ProjectPayments
 */
#[ORM\Table(name: 'PersonalizedProjectPaymentsView')]
#[ORM\Entity]
class ProjectPayment implements \ArrayAccess, \JsonSerializable
{
  use CAFEVDB\Traits\ArrayTrait;

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
  #[ORM\Column(type: 'decimal', precision: 7, scale: 2, nullable: false, options: ['default' => '0.00'])]
  private $amount = '0.00';

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 1024, nullable: false)]
  private $subject;

  #[ORM\ManyToOne(targetEntity: ProjectParticipantFieldDatum::class, inversedBy: 'payments')]
  #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'field_id', nullable: false)]
  #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'project_id', nullable: false)]
  #[ORM\JoinColumn(name: 'musician_id', referencedColumnName: 'musician_id', nullable: false)]
  #[ORM\JoinColumn(name: 'receivable_key', referencedColumnName: 'option_key', nullable: false)]
  private $receivable;

  #[ORM\ManyToOne(targetEntity: ProjectParticipantFieldDataOption::class, inversedBy: 'payments')]
  #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'field_id', nullable: false)]
  #[ORM\JoinColumn(name: 'receivable_key', referencedColumnName: 'key', nullable: false)]
  private $receivableOption;

  #[ORM\ManyToOne(targetEntity: CompositePayment::class, inversedBy: 'projectPayments', fetch: 'EXTRA_LAZY')]
  private $compositePayment;

  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'payments', fetch: 'EXTRA_LAZY')]
  private $project;

  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'payments', fetch: 'EXTRA_LAZY')]
  private $musician;

  #[ORM\ManyToOne(targetEntity: ProjectParticipant::class, inversedBy: 'payments', fetch: 'EXTRA_LAZY')]
  #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'project_id', nullable: false)]
  #[ORM\JoinColumn(name: 'musician_id', referencedColumnName: 'musician_id', nullable: false)]
  private $projectParticipant;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
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
  public function getId():?int
  {
    return $this->id;
  }

  /**
   * Get compositePayment.
   *
   * @return CompositePayment
   */
  public function getCompositePayment()
  {
    return $this->compositePayment;
  }

  /**
   * Get projectParticipant.
   *
   * @return ProjectParticipant
   */
  public function getProjectParticipant()
  {
    return $this->projectParticipant;
  }

  /**
   * Get project.
   *
   * @return Project
   */
  public function getProject()
  {
    return $this->project;
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
   * Get amount.
   *
   * @return float
   */
  public function getAmount():float
  {
    return $this->amount;
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
   * Get receivable.
   *
   * @return ProjectParticipantFieldDatum
   */
  public function getReceivable():ProjectParticipantFieldDatum
  {
    return $this->receivable;
  }

  /**
   * Get receivableOption.
   *
   * @return ProjectParticipantFieldDataOption
   */
  public function getReceivableOption()
  {
    return $this->receivableOption;
  }

  /** {@inheritdoc} */
  public function jsonSerialize():array
  {
    return $this->toArray();
  }
}
