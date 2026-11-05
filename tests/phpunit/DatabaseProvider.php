<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2024, 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Tests;

use OCA\CAFeVDBMembers\AppInfo\Application;
use OCA\RotDrop\Tests\EnumDatabasePurpose;

/** Override the database name. */
class DatabaseProvider extends \OCA\RotDrop\Tests\DatabaseProvider
{
  /**
   * @param EnumDatabasePurpose $which
   *
   * @return string
   */
  public function databaseName(EnumDatabasePurpose $which): string
  {
    $name = Application::getOrchestraAppName();
    if ($which == EnumDatabasePurpose::CLOUD_CONNECTOR) {
      $name .= '_cloud_connector';
    }
    return $name;
  }
}
