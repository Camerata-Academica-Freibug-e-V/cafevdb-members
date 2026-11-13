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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use OCP\Constants;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorage;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorageFolder;
use OCA\CAFeVDBMembers\Database\ORM\Repositories\EntityRepository;
use OCA\CAFeVDBMembers\Storage\Database\Storage;

#[CoversClass(Storage::class)]
#[UsesClass(DatabaseStorage::class)]
#[UsesClass(DatabaseStorageFolder::class)]
class StorageTest extends TestCase
{
  public function testSyntheticDirectoriesSupportMetadataAndTraversal(): void
  {
    $root = (new DatabaseStorageFolder())->setUpdated(new \DateTimeImmutable('@1'));
    $sources = [
      (new DatabaseStorage())->setStorageId('project/participant/ProjectName/member-id/')->setRoot($root),
      (new DatabaseStorage())->setStorageId('finance/invoices/')->setRoot($root),
    ];
    $storage = $this->storageForSources($sources);

    foreach (['projects', 'projects/ProjectName', 'finance', 'finance/invoices'] as $path) {
      self::assertTrue($storage->file_exists($path), $path);
      self::assertTrue($storage->is_dir($path), $path);
      self::assertFalse($storage->is_file($path), $path);
      self::assertSame('dir', $storage->filetype($path));
      self::assertSame(0, $storage->filesize($path));
      self::assertSame(['size' => 0, 'mtime' => 1], $storage->stat($path));
      self::assertSame('httpd/unix-directory', $storage->getMimeType($path));
      self::assertSame(Constants::PERMISSION_READ, $storage->getPermissions($path));
    }

    self::assertSame(['finance', 'projects'], $this->directoryNames($storage, ''));
    self::assertSame(['ProjectName'], $this->directoryNames($storage, 'projects'));
    self::assertSame([], $this->directoryNames($storage, 'projects/ProjectName'));
    self::assertSame(['invoices'], $this->directoryNames($storage, 'finance'));
    self::assertSame([], $this->directoryNames($storage, 'finance/invoices'));
    self::assertFalse($storage->fopen('projects', 'rb'));

    foreach (['missing', 'projects/UnknownProject', 'projects/../finance'] as $path) {
      self::assertFalse($storage->file_exists($path), $path);
      self::assertFalse($storage->stat($path));
      self::assertFalse($storage->filesize($path));
      self::assertFalse($storage->opendir($path));
    }
  }

  public function testSyntheticDirectoriesDisappearWhenTheirSourcesAreRemoved(): void
  {
    $sources = [
      (new DatabaseStorage())
        ->setStorageId('project/participant/ProjectName/member-id/')
        ->setRoot(new DatabaseStorageFolder()),
    ];
    $storage = $this->storageForSources($sources);

    self::assertTrue($storage->file_exists('projects'));
    $sources = [];
    self::assertFalse($storage->file_exists('projects'));
    self::assertFalse($storage->stat('projects'));
    self::assertFalse($storage->opendir('projects'));
  }

  /** @param list<DatabaseStorage> $sources */
  private function storageForSources(array &$sources): Storage
  {
    $repository = $this->getMockBuilder(EntityRepository::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['findAll'])
      ->getMock();
    $repository->expects($this->atLeastOnce())->method('findAll')->willReturnCallback(static function() use (&$sources): array {
      return $sources;
    });

    $entityManager = $this->getMockBuilder(EntityManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getRepository', 'clear'])
      ->getMock();
    $entityManager->expects($this->atLeastOnce())->method('getRepository')
      ->with(DatabaseStorage::class)->willReturn($repository);

    return new Storage(['entityManager' => $entityManager, 'userId' => 'member-id']);
  }

  /** @return list<string> */
  private function directoryNames(Storage $storage, string $path): array
  {
    $directory = $storage->opendir($path);
    self::assertIsResource($directory);
    $names = [];
    try {
      while (($name = readdir($directory)) !== false) {
        $names[] = $name;
      }
    } finally {
      closedir($directory);
    }
    return $names;
  }

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
