<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Tests\Unit\Storage\Database;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use OCA\CAFeVDBMembers\Storage\Database\Storage;

#[CoversClass(Storage::class)]
class StorageTest extends TestCase
{
  public function testParticipantStoragesAreFlattenedToTheirProjectName(): void
  {
    self::assertSame(
      [ 'projects', 'ProjectName' ],
      Storage::visiblePathForStorageId('project/participant/ProjectName/member-id/'),
    );
  }

  public function testOtherStoragePathsArePreserved(): void
  {
    self::assertSame(
      [ 'finance', 'invoices' ],
      Storage::visiblePathForStorageId('finance/invoices/'),
    );
  }

  public function testInvalidStoragePathComponentsAreRejected(): void
  {
    self::assertFalse(Storage::visiblePathForStorageId('../private'));
    self::assertFalse(Storage::visiblePathForStorageId('finance//invoices'));
  }
}
