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

namespace OCA\CAFeVDBMembers\Storage\Database;

use DateTimeInterface;
use RuntimeException;

use OC\Files\Storage\Common as AbstractStorage;
use OCP\Constants;
use OCP\Files\ForbiddenException;

use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorage;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorageDirEntry;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorageFile;
use OCA\CAFeVDBMembers\Database\ORM\Entities\DatabaseStorageFolder;

/** Read-only aggregate view over authorized database storage roots. */
class Storage extends AbstractStorage
{
  private EntityManager $entityManager;
  private string $userId;

  public function __construct(array $parameters)
  {
    parent::__construct($parameters);
    $this->entityManager = $parameters['entityManager'];
    $this->userId = $parameters['userId'];
  }

  public function getId()
  {
    return 'cafevdbmembers::database-storage::' . $this->userId;
  }

  /** @return array<array{path: list<string>, root: DatabaseStorageFolder}> */
  private function getSources(): array
  {
    $this->entityManager->clear();
    $storages = $this->entityManager->getRepository(DatabaseStorage::class)->findAll();
    $sources = [];
    foreach ($storages as $storage) {
      $root = $storage->getRoot();
      $storageId = trim((string)$storage->getStorageId(), '/');
      if (!$root || $storageId === '') {
        continue;
      }

      $path = self::visiblePathForStorageId($storageId);
      if (is_array($path) && $path !== []) {
        $sources[] = [ 'path' => $path, 'root' => $root ];
      }
    }

    return $sources;
  }

  /** @return list<string>|false */
  public static function visiblePathForStorageId(string $storageId): array|false
  {
    $parts = explode('/', trim($storageId, '/'));
    if ($parts === [] || in_array('', $parts, true)
        || in_array('.', $parts, true) || in_array('..', $parts, true)) {
      return false;
    }
    if (($parts[0] ?? null) === 'project'
        && ($parts[1] ?? null) === 'participant'
        && !empty($parts[2])) {
      return [ 'projects', $parts[2] ];
    }
    return $parts;
  }

  /** @return list<string>|false */
  private static function pathParts(string $path): array|false
  {
    $path = trim($path, '/');
    if ($path === '' || $path === '.') {
      return [];
    }
    $parts = explode('/', $path);
    if (in_array('', $parts, true) || in_array('.', $parts, true) || in_array('..', $parts, true)) {
      return false;
    }
    return $parts;
  }

  private static function isPrefix(array $prefix, array $path): bool
  {
    return count($prefix) <= count($path)
      && array_slice($path, 0, count($prefix)) === $prefix;
  }

  /**
   * @param list<string> $path
   *
   * @return array{synthetic: bool, folders: list<DatabaseStorageFolder>}|null
   */
  private function resolveDirectory(array $path, array $sources): ?array
  {
    $folders = [];
    $synthetic = false;
    foreach ($sources as $source) {
      if (self::isPrefix($path, $source['path']) && count($path) < count($source['path'])) {
        $synthetic = true;
        continue;
      }
      if (!self::isPrefix($source['path'], $path)) {
        continue;
      }

      $nodes = [ $source['root'] ];
      foreach (array_slice($path, count($source['path'])) as $name) {
        $matches = [];
        foreach ($nodes as $node) {
          foreach ($node->getDirectoryEntries() as $entry) {
            if ($entry->getName() === $name) {
              $matches[] = $entry;
            }
          }
        }
        if ($matches === [] || array_filter($matches, static fn($entry) => !($entry instanceof DatabaseStorageFolder))) {
          $nodes = [];
          break;
        }
        $nodes = $matches;
      }
      array_push($folders, ...$nodes);
    }

    if ($folders !== []) {
      return [ 'synthetic' => $synthetic, 'folders' => $folders ];
    }
    return $synthetic ? [ 'synthetic' => true, 'folders' => [] ] : null;
  }

  /** @return array<string, list<DatabaseStorageDirEntry|null>>|false */
  private function children(string $path): array|false
  {
    $parts = self::pathParts($path);
    if ($parts === false) {
      return false;
    }
    $sources = $this->getSources();
    $directory = $this->resolveDirectory($parts, $sources);
    if ($directory === null) {
      return false;
    }

    $entries = [];
    foreach ($sources as $source) {
      if (self::isPrefix($parts, $source['path']) && count($parts) < count($source['path'])) {
        $name = $source['path'][count($parts)];
        $entries[$name][] = null;
      }
    }
    foreach ($directory['folders'] as $folder) {
      foreach ($folder->getDirectoryEntries() as $entry) {
        $entries[$entry->getName()][] = $entry;
      }
    }
    foreach ($entries as $name => $candidates) {
      $types = [];
      foreach ($candidates as $candidate) {
        $types[] = $candidate === null ? DatabaseStorageFolder::class : $candidate::class;
      }
      $types = array_values(array_unique($types));
      if (count($types) > 1) {
        throw new RuntimeException('Ambiguous database-storage entry: ' . implode('/', [...$parts, $name]));
      }
      if ($types[0] === DatabaseStorageFile::class) {
        $fileIds = [];
        foreach ($candidates as $candidate) {
          $fileIds[] = $candidate->getFile()?->getId();
        }
        if (count(array_unique($fileIds)) > 1) {
          throw new RuntimeException('Conflicting database-storage files: ' . implode('/', [...$parts, $name]));
        }
        $entries[$name] = [ $candidates[0] ];
      }
    }

    ksort($entries, SORT_NATURAL | SORT_FLAG_CASE);
    return $entries;
  }

  /** @return DatabaseStorageDirEntry|null|false Null denotes a synthetic folder. */
  private function findEntry(string $path): DatabaseStorageDirEntry|null|false
  {
    $parts = self::pathParts($path);
    if ($parts === false || $parts === []) {
      return false;
    }
    $name = array_pop($parts);
    $children = $this->children(implode('/', $parts));
    if ($children === false || !isset($children[$name])) {
      return false;
    }
    return $children[$name][0];
  }

  public function opendir($path)
  {
    $children = $this->children($path);
    if ($children === false) {
      return false;
    }
    return \Icewind\Streams\IteratorDirectory::wrap(array_keys($children));
  }

  public function filetype($path)
  {
    if (self::pathParts($path) === []) {
      return 'dir';
    }
    $entry = $this->findEntry($path);
    if ($entry === null || $entry instanceof DatabaseStorageFolder) {
      return 'dir';
    }
    if ($entry instanceof DatabaseStorageFile && $entry->getFile() !== null) {
      return 'file';
    }
    return false;
  }

  public function is_dir(string $path): bool
  {
    return $this->filetype($path) === 'dir';
  }

  public function is_file(string $path): bool
  {
    return $this->filetype($path) === 'file';
  }

  public function file_exists(string $path): bool
  {
    return $this->filetype($path) !== false;
  }

  public function stat($path)
  {
    $parts = self::pathParts($path);
    if ($parts === false) {
      return false;
    }
    if ($this->is_dir($path)) {
      $date = null;
      foreach ($this->resolveDirectory($parts, $this->getSources())['folders'] ?? [] as $folder) {
        $date = $this->latestDate($date, $folder->getUpdated());
      }
      return [ 'size' => 0, 'mtime' => $date?->getTimestamp() ?? 1 ];
    }
    $entry = $this->findEntry($path);
    if (!$entry instanceof DatabaseStorageFile || !$entry->getFile()) {
      return false;
    }
    return [
      'size' => $entry->getFile()->getSize(),
      'mtime' => $entry->getUpdated()?->getTimestamp() ?? 1,
    ];
  }

  private function latestDate(?DateTimeInterface $current, ?DateTimeInterface $candidate): ?DateTimeInterface
  {
    if ($candidate === null || ($current !== null && $candidate <= $current)) {
      return $current;
    }
    return $candidate;
  }

  public function filesize($path): int|float|false
  {
    if ($this->is_dir($path)) {
      return 0;
    }
    $entry = $this->findEntry($path);
    return $entry instanceof DatabaseStorageFile && $entry->getFile()
      ? $entry->getFile()->getSize()
      : false;
  }

  public function filemtime(string $path): int|false
  {
    $stat = $this->stat($path);
    return is_array($stat) ? $stat['mtime'] : false;
  }

  public function getMimeType(string $path): string|false
  {
    if ($this->is_dir($path)) {
      return 'httpd/unix-directory';
    }
    $entry = $this->findEntry($path);
    return $entry instanceof DatabaseStorageFile ? ($entry->getFile()?->getMimeType() ?? false) : false;
  }

  public function fopen($path, $mode)
  {
    if ($mode !== 'r' && $mode !== 'rb') {
      return false;
    }
    $entry = $this->findEntry($path);
    if (!$entry instanceof DatabaseStorageFile || !$entry->getFile()) {
      return false;
    }
    $data = $entry->getFile()->getFileData()->getData();
    if (!is_string($data)) {
      throw new RuntimeException('Unable to read database-storage file data.');
    }
    $stream = fopen('php://memory', 'w+');
    if ($stream === false || fwrite($stream, $data) === false) {
      if (is_resource($stream)) {
        fclose($stream);
      }
      throw new RuntimeException('Unable to open database-storage file data.');
    }
    rewind($stream);
    return $stream;
  }

  public function isReadable(string $path): bool
  {
    return $this->file_exists($path);
  }

  public function isUpdatable(string $path): bool
  {
    return false;
  }

  public function isCreatable(string $path): bool
  {
    return false;
  }

  public function isDeletable(string $path): bool
  {
    return false;
  }

  public function isSharable(string $path): bool
  {
    return false;
  }

  public function getPermissions(string $path): int
  {
    return $this->isReadable($path) ? Constants::PERMISSION_READ : 0;
  }

  public function mkdir(string $path)
  {
    return false;
  }

  public function rmdir($path)
  {
    return false;
  }

  public function unlink(string $path): bool
  {
    return false;
  }

  public function rename(string $source, string $target): bool
  {
    return false;
  }

  public function copy(string $source, string $target): bool
  {
    return false;
  }

  public function touch(string $path, ?int $mtime = null): bool
  {
    return false;
  }

  public function writeStream(string $path, $stream, ?int $size = null): int
  {
    throw new ForbiddenException('Database storage is read-only.', false);
  }

  public function hasUpdated(string $path, int $time): bool
  {
    $mtime = $this->filemtime($path);
    return $mtime === false || $mtime > $time;
  }

  public function test(): bool
  {
    return $this->entityManager->connected();
  }
}
