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

use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;

/**
 * ProjectWebPage
 */
#[ORM\Table(name: 'PersonalizedProjectWebPagesView')]
#[ORM\Entity]
class ProjectWebPage implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;

  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'webPages', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $project;

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['default' => '-1'])]
  #[ORM\Id]
  private $articleId = '-1';

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 128, nullable: false, options: ['default' => ''])]
  private $articleName = '';

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['default' => '-1'])]
  private $categoryId = '-1';

  /**
   * @var int
   */
  #[ORM\Column(type: 'integer', nullable: false, options: ['default' => '-1'])]
  private $priority = '-1';

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct()
  {
    $this->arrayCTOR();
  }
  // phpcs:enable

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
   * Get articleId.
   *
   * @return int
   */
  public function getArticleId():int
  {
    return $this->articleId;
  }

  /**
   * Get articleName.
   *
   * @return string
   */
  public function getArticleName():string
  {
    return $this->articleName;
  }

  /**
   * Get categoryId.
   *
   * @return int
   */
  public function getCategoryId():int
  {
    return $this->categoryId;
  }

  /**
   * Get priority.
   *
   * @return int
   */
  public function getPriority():int
  {
    return $this->priority;
  }
}
