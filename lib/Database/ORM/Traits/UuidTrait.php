<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2025, 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Database\ORM\Traits;

use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;
use OCA\CAFeVDBMembers\Wrapped\Ramsey\Uuid\UuidInterface;

/** Helper for entities having a uuid-field. */
trait UuidTrait
{
  /**
   * @var \Ramsey\Uuid\UuidInterface
   */
  #[ORM\Column(type: 'uuid_binary', unique: true)]
  private $uuid;

  /**
   * Get uuid.
   *
   * @return null|\Ramsey\Uuid\UuidInterface
   */
  public function getUuid():?UuidInterface
  {
    return $this->uuid ?? null;
  }
}
