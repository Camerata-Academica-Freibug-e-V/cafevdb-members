<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023 Claus-Justus Heine
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
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Database\DBAL\Types;

/**
 * Multiple email addressses for each person.
 */
#[ORM\Table(name: 'PersonalizedMusicianEmailAddressesView')]
#[ORM\Entity]
class MusicianEmailAddress implements \ArrayAccess
{
  use CAFEVDB\Traits\ArrayTrait;
  use CAFEVDB\Traits\TimestampableEntity;

  /**
   * @var string
   */
  #[ORM\Column(type: 'string', length: 254, nullable: false, options: ['collation' => 'ascii_general_ci'])]
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private $address;

  /**
   * @var Musician
   */
  #[ORM\ManyToOne(targetEntity: Musician::class, inversedBy: 'emailAddresses', fetch: 'EXTRA_LAZY')]
  #[ORM\Id]
  private $musician;

  /**
   * @param null|string $address
   *
   * @param null|Musician $musician
   */
  public function __construct()
  {
    $this->arrayCTOR();
  }

  /**
   * @return string
   */
  public function getAddress():string
  {
    return strtolower($this->address);
  }

  /** {@inheritdoc} */
  public function __toString():string
  {
    return $this->musician->getPublicName(firstNameFirst: true) . ' <' . $this->address . '>';
  }

  /**
   * @return Musician
   */
  public function getMusician():Musician
  {
    return $this->musician;
  }

  /**
   * Check whether this is the primary address of $this->musician.
   *
   * @return bool
   */
  public function isPrimaryAddress():bool
  {
    return $this->musician->getEmail() == $this->address;
  }
}
