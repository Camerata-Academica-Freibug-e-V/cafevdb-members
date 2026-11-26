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

use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumProjectTemporalType;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorageFile;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorage;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorageFolder;
use OCA\CAFeVDBMembers\Database\ORM\Entities\Project;
use OCA\CAFeVDBMembers\Database\ORM\Repositories\EntityRepository;
use OCA\CAFeVDBMembers\Storage\Database\Storage;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;

#[CoversClass(Storage::class)]
#[UsesClass(DatabaseStorage::class)]
#[UsesClass(DatabaseStorageFile::class)]
#[UsesClass(DatabaseStorageFolder::class)]
#[UsesClass(Project::class)]
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

    foreach (['2051', '2051/ProjectName', 'Finance', 'Finance/Invoices'] as $path) {
      self::assertTrue($storage->file_exists($path), $path);
      self::assertTrue($storage->is_dir($path), $path);
      self::assertFalse($storage->is_file($path), $path);
      self::assertSame('dir', $storage->filetype($path));
      self::assertSame(0, $storage->filesize($path));
      self::assertSame(['size' => 0, 'mtime' => 1], $storage->stat($path));
      self::assertSame('httpd/unix-directory', $storage->getMimeType($path));
      self::assertSame(Constants::PERMISSION_READ, $storage->getPermissions($path));
    }

    self::assertSame(['2051', 'Finance'], $this->directoryNames($storage, ''));
    self::assertSame(['ProjectName'], $this->directoryNames($storage, '2051'));
    self::assertSame([], $this->directoryNames($storage, '2051/ProjectName'));
    self::assertSame(['Invoices'], $this->directoryNames($storage, 'Finance'));
    self::assertSame([], $this->directoryNames($storage, 'Finance/Invoices'));
    self::assertFalse($storage->fopen('2051', 'rb'));

    foreach (['missing', '2051/UnknownProject', '2051/../Finance'] as $path) {
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

    self::assertTrue($storage->file_exists('2051'));
    $sources = [];
    self::assertTrue($storage->hasUpdated('', 1));
    self::assertFalse($storage->file_exists('2051'));
    self::assertFalse($storage->stat('2051'));
    self::assertFalse($storage->opendir('2051'));
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
    self::assertSame(['2051'], $this->directoryNames($storage, ''));
    self::assertTrue($storage->hasUpdated('2051', PHP_INT_MAX));
    $sources = [];
    self::assertTrue($storage->hasUpdated('', 1));
    self::assertSame([], $this->directoryNames($storage, ''));
    self::assertTrue($storage->hasUpdated('2051', PHP_INT_MAX));
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
    $entityManager->expects($this->exactly(2))->method('getRepository')
      ->with(DatabaseStorage::class)->willReturn($repository);
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
  private function storageForSources(array &$sources, ?IL10N $l = null, ?array $projects = null): Storage
  {
    $repository = $this->getMockBuilder(EntityRepository::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['findAll'])
      ->getMock();
    $repository->expects($this->atLeastOnce())->method('findAll')->willReturnCallback(static function() use (&$sources): array {
      return $sources;
    });

    $projectRepository = $this->createStub(EntityRepository::class);
    $projectRepository->method('findAll')->willReturn($projects ?? [
      $this->project('ProjectName', EnumProjectTemporalType::TEMPORARY, 2051),
      $this->project('Invoices', EnumProjectTemporalType::PERMANENT, 2051),
    ]);

    $entityManager = $this->getMockBuilder(EntityManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getRepository', 'clear'])
      ->getMock();
    $entityManager->expects($this->atLeastOnce())->method('getRepository')
      ->willReturnCallback(static fn(string $class) => match ($class) {
        DatabaseStorage::class => $repository,
        Project::class => $projectRepository,
      });

    return new Storage(['entityManager' => $entityManager, 'userId' => 'member-id', 'l10n' => $l ?? $this->translator()]);
  }

  private function project(string $name, EnumProjectTemporalType $type, int $year): Project
  {
    $project = $this->createStub(Project::class);
    $project->method('getName')->willReturn($name);
    $project->method('getType')->willReturn($type);
    $project->method('getYear')->willReturn($year);
    return $project;
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
    $storage = $this->storageForSources($sources, $this->translator(['Invoices' => 'LocalizedInvoices']));
    self::assertSame(['Invoices'], $this->directoryNames($storage, ''));
    self::assertTrue($storage->is_dir('Invoices'));
    self::assertSame(0, $storage->filesize('Invoices'));
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
      'finance/invoices/',
      $this->translator(['Finance' => '../Private']),
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

  public function testParticipantProjectsUseTheirActualTypeAndYear(): void
  {
    $file = $this->createStub(DatabaseStorageFile::class);
    $file->method('getName')->willReturn('Document');
    $root = $this->createStub(DatabaseStorageFolder::class);
    $root->method('getDirectoryEntries')->willReturn(new ArrayCollection([$file]));
    $sources = [
      (new DatabaseStorage())->setStorageId('project/participant/Concert/member-id/')->setRoot($root),
      (new DatabaseStorage())->setStorageId('project/participant/Board2051/member-id/')->setRoot(new DatabaseStorageFolder()),
      (new DatabaseStorage())->setStorageId('project/participant/Another/member-id/')->setRoot(new DatabaseStorageFolder()),
    ];
    $storage = $this->storageForSources($sources, projects: [
      $this->project('Concert', EnumProjectTemporalType::TEMPORARY, 2051),
      $this->project('Board2051', EnumProjectTemporalType::PERMANENT, 2051),
      $this->project('Another', EnumProjectTemporalType::TEMPORARY, 2052),
    ]);
    self::assertSame(['2051', '2052', 'Board2051'], $this->directoryNames($storage, ''));
    self::assertSame(['Concert'], $this->directoryNames($storage, '2051'));
    self::assertSame(['Document'], $this->directoryNames($storage, '2051/Concert'));
    self::assertTrue($storage->is_dir('Board2051'));
    self::assertTrue($storage->is_dir('2052/Another'));
    self::assertFalse($storage->file_exists('Concert'));
    self::assertFalse($storage->file_exists('Projects'));
  }

  public function testMissingProjectIsReported(): void
  {
    $sources = [
      (new DatabaseStorage())->setStorageId('project/participant/Missing/member-id/')
        ->setRoot(new DatabaseStorageFolder()),
    ];
    $storage = $this->storageForSources($sources, projects: []);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No project found for database storage: project/participant/Missing/member-id');
    $storage->opendir('');
  }

  public function testProjectYearIsRefreshedAfterDirectoryChangeCheck(): void
  {
    $sources = [
      (new DatabaseStorage())->setStorageId('project/participant/Concert/member-id/')
        ->setRoot(new DatabaseStorageFolder()),
    ];
    $project = $this->project('Concert', EnumProjectTemporalType::TEMPORARY, 2051);
    $storageRepository = $this->createStub(EntityRepository::class);
    $storageRepository->method('findAll')->willReturn($sources);
    $projectRepository = $this->createStub(EntityRepository::class);
    $projectRepository->method('findAll')->willReturnCallback(static function() use (&$project): array {
      return [$project];
    });
    $entityManager = $this->createStub(EntityManager::class);
    $entityManager->method('getRepository')->willReturnCallback(static fn(string $class) => match ($class) {
      DatabaseStorage::class => $storageRepository,
      Project::class => $projectRepository,
    });
    $storage = new Storage(['entityManager' => $entityManager, 'userId' => 'member-id', 'l10n' => $this->translator()]);

    self::assertTrue($storage->is_dir('2051/Concert'));
    $project = $this->project('Concert', EnumProjectTemporalType::TEMPORARY, 2052);
    self::assertTrue($storage->hasUpdated('', 1));
    self::assertFalse($storage->file_exists('2051'));
    self::assertTrue($storage->is_dir('2052/Concert'));
  }

  public function testUnsupportedProjectTypeIsReported(): void
  {
    $project = $this->project('Template', EnumProjectTemporalType::TEMPLATE, 2051);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unsupported project type');
    Storage::visiblePathForStorageId('project/participant/Template/member-id/', $this->translator(), ['Template' => $project]);
  }

  public function testInvalidTemporaryProjectYearIsReported(): void
  {
    $project = $this->project('Concert', EnumProjectTemporalType::TEMPORARY, 0);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid project year');
    Storage::visiblePathForStorageId('project/participant/Concert/member-id/', $this->translator(), ['Concert' => $project]);
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
    self::assertFalse(Storage::visiblePathForStorageId('project/participant/ProjectName', $this->translator()));
  }
}
