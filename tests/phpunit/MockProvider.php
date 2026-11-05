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

use OCA\RotDrop\Tests\AbstractMockProvider;

/** Provide a couple of important services, partially using mocked classes. */
class MockProvider extends AbstractMockProvider
{
  private static array $rowAccessTokens = [];

  /** @return array */
  protected static function getMockedServices(): array
  {
    return array_merge([], parent::getMockedServices());
  }

  /**
   * Return the table of row access tokens as associative array.
   *
   * @return array
   */
  public static function getRowAccessTokens(): array
  {
    if (!empty(self::$rowAccessTokens)) {
      return self::$rowAccessTokens;
    }
    $databaseProvider = \OCP\Server::get(DatabaseProvider::class);
    $connection = $databaseProvider->getConnection();
    $stmt = $connection->executeQuery(
      'SELECT * FROM MusicianRowAccessTokens',
    );
    while (($row = $stmt->fetchAssociative()) !== false) {
      self::$rowAccessTokens[$row['user_id']] = $row;
    }
    return self::$rowAccessTokens;
  }
}
