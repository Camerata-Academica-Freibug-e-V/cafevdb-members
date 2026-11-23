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
use OCP\IL10N;

use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorageFile;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorage;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorageFolder;
use OCA\CAFeVDBMembers\Database\ORM\Repositories\EntityRepository;
use OCA\CAFeVDBMembers\Storage\Database\Storage;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;

#[CoversClass(Storage::class)]
#[UsesClass(DatabaseStorage::class)]
#[UsesClass(DatabaseStorageFile::class)]
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

    foreach (['Projects', 'Projects/ProjectName', 'Finance', 'Finance/Invoices'] as $path) {
      self::assertTrue($storage->file_exists($path), $path);
      self::assertTrue($storage->is_dir($path), $path);
      self::assertFalse($storage->is_file($path), $path);
      self::assertSame('dir', $storage->filetype($path));
      self::assertSame(0, $storage->filesize($path));
      self::assertSame(['size' => 0, 'mtime' => 1], $storage->stat($path));
      self::assertSame('httpd/unix-directory', $storage->getMimeType($path));
      self::assertSame(Constants::PERMISSION_READ, $storage->getPermissions($path));
    }

    self::assertSame(['Finance', 'Projects'], $this->directoryNames($storage, ''));
    self::assertSame(['ProjectName'], $this->directoryNames($storage, 'Projects'));
    self::assertSame([], $this->directoryNames($storage, 'Projects/ProjectName'));
    self::assertSame(['Invoices'], $this->directoryNames($storage, 'Finance'));
    self::assertSame([], $this->directoryNames($storage, 'Finance/Invoices'));
    self::assertFalse($storage->fopen('Projects', 'rb'));

    foreach (['missing', 'Projects/UnknownProject', 'Projects/../Finance'] as $path) {
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

    self::assertTrue($storage->file_exists('Projects'));
    $sources = [];
    self::assertTrue($storage->hasUpdated('', 1));
    self::assertFalse($storage->file_exists('Projects'));
    self::assertFalse($storage->stat('Projects'));
    self::assertFalse($storage->opendir('Projects'));
  }

  public function testEmptyRootRemainsListableAndDirectoriesAreRechecked(): void
  {
    $sources = [];
    $storage = $this->storageForSources($sources);

    self::assertSame([], $this->directoryNames($storage, ''));
    self::assertTrue($storage->hasUpdated('', 1));

    $sources = [
      (new DatabaseStorage())->setStorageId('project/participant/ProjectName/member-id/')
        ->setRoot(new DatabaseStorageFolder()),
    ];
    self::assertTrue($storage->hasUpdated('', 1));
    self::assertSame(['Projects'], $this->directoryNames($storage, ''));
    self::assertTrue($storage->hasUpdated('Projects', PHP_INT_MAX));
    $sources = [];
    self::assertTrue($storage->hasUpdated('', 1));
    self::assertSame([], $this->directoryNames($storage, ''));
    self::assertTrue($storage->hasUpdated('Projects', PHP_INT_MAX));
  }

  public function testSourcesAreMemoizedUntilDirectoryChangeCheck(): void
  {
    $sources = [
      (new DatabaseStorage())->setStorageId('finance/invoices/')
        ->setRoot(new DatabaseStorageFolder()),
    ];
    $repository = $this->getMockBuilder(EntityRepository::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['findAll'])
      ->getMock();
    $repository->expects($this->exactly(2))->method('findAll')
      ->willReturnCallback(static function() use (&$sources): array {
        return $sources;
      });
    $entityManager = $this->getMockBuilder(EntityManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getRepository', 'clear'])
      ->getMock();
    $entityManager->expects($this->exactly(2))->method('clear');
    $entityManager->method('getRepository')->with(DatabaseStorage::class)->willReturn($repository);
    $storage = new Storage([
      'entityManager' => $entityManager,
      'userId' => 'member-id',
      'l10n' => $this->translator(),
    ]);

    self::assertTrue($storage->file_exists('Finance'));
    self::assertSame(['Finance'], $this->directoryNames($storage, ''));
    self::assertSame(['Invoices'], $this->directoryNames($storage, 'Finance'));
    self::assertTrue($storage->hasUpdated('', 1));
    self::assertTrue($storage->file_exists('Finance'));
    self::assertSame(['Finance'], $this->directoryNames($storage, ''));
  }

  public function testFileFolderCollisionsAreRejectedForListingsAndDirectTraversal(): void
  {
    $file = $this->createStub(DatabaseStorageFile::class);
    $file->method('getName')->willReturn('Shared');
    $folder = $this->createStub(DatabaseStorageFolder::class);
    $folder->method('getName')->willReturn('Shared');
    $folder->method('getDirectoryEntries')->willReturn(new ArrayCollection());

    $fileRoot = $this->createStub(DatabaseStorageFolder::class);
    $fileRoot->method('getDirectoryEntries')->willReturn(new ArrayCollection([$file]));
    $folderRoot = $this->createStub(DatabaseStorageFolder::class);
    $folderRoot->method('getDirectoryEntries')->willReturn(new ArrayCollection([$folder]));
    $sources = [
      (new DatabaseStorage())->setStorageId('finance/invoices/')->setRoot($fileRoot),
      (new DatabaseStorage())->setStorageId('finance/invoices/')->setRoot($folderRoot),
    ];
    $storage = $this->storageForSources($sources);

    foreach (['Finance/Invoices', 'Finance/Invoices/Shared'] as $path) {
      try {
        $storage->opendir($path);
        self::fail('A file/folder collision must not expose a partial directory.');
      } catch (\RuntimeException $e) {
        self::assertStringContainsString('Ambiguous database-storage entry', $e->getMessage());
      }
    }
  }

  /** @param list<DatabaseStorage> $sources */
  private function storageForSources(array &$sources, ?IL10N $l = null): Storage
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

    return new Storage(['entityManager' => $entityManager, 'userId' => 'member-id', 'l10n' => $l ?? $this->translator()]);
  }

  private function translator(array $translations = []): IL10N
  {
    $l = $this->createStub(IL10N::class);
    $l->method('t')->willReturnCallback(static fn(string $text): string => $translations[$text] ?? $text);
    return $l;
  }

  public function testTranslatedFoldersSupportTraversalWithoutTranslatingProjectNames(): void
  {
    $sources = [
      (new DatabaseStorage())->setStorageId('project/participant/Invoices/member-id/')
        ->setRoot((new DatabaseStorageFolder())->setUpdated(new \DateTimeImmutable('@1'))),
    ];
    $storage = $this->storageForSources($sources, $this->translator(['Projects' => 'LocalizedProjects']));
    self::assertSame(['LocalizedProjects'], $this->directoryNames($storage, ''));
    self::assertSame(['Invoices'], $this->directoryNames($storage, 'LocalizedProjects'));
    self::assertTrue($storage->is_dir('LocalizedProjects/Invoices'));
    self::assertSame(0, $storage->filesize('LocalizedProjects'));
    self::assertFalse($storage->file_exists('Projects'));

    self::assertSame(
      ['LocalizedFinance', 'LocalizedBalances', 'LocalizedProjects', 'invoices'],
      Storage::visiblePathForStorageId(
        'finance/balances/projects/invoices/',
        $this->translator([
          'Finance' => 'LocalizedFinance',
          'Balances' => 'LocalizedBalances',
          'Projects' => 'LocalizedProjects',
          'Invoices' => 'LocalizedInvoices',
        ]),
      ),
    );
  }

  public function testTranslationCannotIntroducePathTraversal(): void
  {
    $this->expectException(\RuntimeException::class);
    Storage::visiblePathForStorageId(
      'project/participant/ProjectName/member-id/',
      $this->translator(['Projects' => '../Private']),
    );
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
      [ 'Projects', 'ProjectName' ],
      Storage::visiblePathForStorageId('project/participant/ProjectName/member-id/', $this->translator()),
    );
  }

  public function testOtherStoragePathsArePreserved(): void
  {
    self::assertSame(
      [ 'Finance', 'Invoices' ],
      Storage::visiblePathForStorageId('finance/invoices/', $this->translator()),
    );
  }

  public function testKnownVirtualFolderLabelsAreTranslatable(): void
  {
    $translations = [
      'Finance' => 'LocalizedFinance',
      'Invoices' => 'LocalizedInvoices',
      'BankTransactions' => 'LocalizedTransactions',
      'Receipts' => 'LocalizedReceipts',
      'DonationReceipts' => 'LocalizedDonations',
      'TaxExemptionNotices' => 'LocalizedTaxNotices',
    ];
    $l = $this->translator($translations);
    foreach ([
      'finance/invoices/' => ['LocalizedFinance', 'LocalizedInvoices'],
      'finance/transactions/' => ['LocalizedFinance', 'LocalizedTransactions'],
      'finance/receipts/donations/' => ['LocalizedFinance', 'LocalizedReceipts', 'LocalizedDonations'],
      'finance/tax-exemption-notices/' => ['LocalizedFinance', 'LocalizedTaxNotices'],
    ] as $storageId => $expected) {
      self::assertSame($expected, Storage::visiblePathForStorageId($storageId, $l));
    }
  }

  public function testInvalidStoragePathComponentsAreRejected(): void
  {
    self::assertFalse(Storage::visiblePathForStorageId('../private', $this->translator()));
    self::assertFalse(Storage::visiblePathForStorageId('finance//invoices', $this->translator()));
  }
}
