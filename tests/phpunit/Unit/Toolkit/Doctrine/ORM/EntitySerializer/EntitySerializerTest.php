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

namespace OCA\CAFeVDBMembers\Tests\Unit\Toolkit\Doctrine\ORM\EntitySerializer;

use ReflectionClass;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\MockObject\MockObject;

use Doctrine\Common\Collections\Collection;

use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Tests\MockProvider;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer\EntitySerializer as TestedClass;
use OCA\RotDrop\Tests\DeprecationException;

/** Test aspects of the EntitySerializer. */
#[Attributes\CoversClass(EntitySerializer\CollectionEntityReference::class)]
#[Attributes\CoversClass(EntitySerializer\EntityReference::class)]
#[Attributes\CoversClass(EntitySerializer\EntityReferenceCollection::class)]
#[Attributes\CoversClass(EntitySerializer\EntityResponse::class)]
#[Attributes\CoversClass(TestedClass::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\AppInfo\Application::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Database\DBAL\Logging\CloudLogger::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Database\ORM\Connection::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Database\ORM\Entities\Musician::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Database\ORM\EntityManager::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Crypto\OrchestraEncryptionService::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Database\ORM\Listeners\Encryption::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\L10N\OrchestraTranslator::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Database\ORM\Listeners\GedmoTranslatableListener::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Database\ORM\Repositories\EntityRepository::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\AppInfo\AbstractApplication::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\AbstractDecimalRationalType::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\ArrayType::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\DecimalRationalMonetaryType::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\UuidType::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager::class)]
#[Attributes\UsesTrait(\OCA\CAFeVDBMembers\Database\ORM\Traits\ArrayTrait::class)]
#[Attributes\UsesTrait(\OCA\CAFeVDBMembers\Database\ORM\Traits\CreatedAt::class)]
#[Attributes\UsesTrait(\OCA\CAFeVDBMembers\Database\ORM\Traits\UpdatedAt::class)]
#[Attributes\UsesTrait(\OCA\CAFeVDBMembers\Database\ORM\Traits\UuidTrait::class)]
#[Attributes\UsesTrait(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\FindLikeTrait::class)]
class EntitySerializerTest extends TestCase
{
  use \OCA\CAFeVDBMembers\Tests\Unit\Database\ORM\GenerateEntityManagerTrait;

  private TestedClass $entitySerializer;

  /** {@inheritdoc} */
  public function setup(): void
  {
    error_reporting(E_ALL);
    DeprecationException::throwOnDeprecations(exclude: '/OCP\\\\IConfig\\:\\:(get|set|delete)AppValue/');

    $this->mockProvider = $this->mockProvider ?? MockProvider::create($this);

    $rowAccessTokens = $this->mockProvider->getRowAccessTokens();
    $this->assertNotNull($rowAccessTokens[MockProvider::CLOUD_USER_UID]['access_token_hash'] ?? null);
    $this->assertEquals(
      128, // sha512 hexified: one nibble per char.
      strlen($rowAccessTokens[MockProvider::CLOUD_USER_UID]['access_token_hash']),
    );
    $rowAccessToken = $rowAccessTokens[MockProvider::CLOUD_USER_UID]['access_token_hash'];

    $this->generateEntityManager($rowAccessToken);

    $this->entitySerializer = new TestedClass(
      entityManager: $this->entityManager,
      l: $this->mockProvider->getL10N(),
      logger: $this->mockProvider->getLoggerInterface(),
    );
  }

  /** @return void */
  public function tearDown(): void
  {
    restore_error_handler();
  }

  /** @return Entities\Musician */
  private function getMusician(): Entities\Musician
  {
    $musicians = $this->entityManager->getRepository(Entities\Musician::class)->findAll();
    $this->assertEquals(1, count($musicians));

    return $musicians[0];
  }

  /** @return void */
  public function testAddEntity(): void
  {
    $this->entitySerializer->addEntity($this->getMusician());
  }

  /** @return void */
  public function testExport(): void
  {
    $this->entitySerializer->addEntity($this->getMusician());
    $exportData = $this->entitySerializer->export();
    json_encode($exportData, JSON_PRETTY_PRINT);
    $this->assertInstanceOf(EntitySerializer\EntityResponse::class, $exportData);
    $this->assertArrayHasKey(Entities\Musician::class, $exportData->entities);
    $this->assertArrayHasKey(Entities\Musician::class, $exportData->repositories);
    $this->assertArrayHasKey(Entities\SepaBankAccount::class, $exportData->repositories);
    $this->assertArrayHasKey(Entities\ProjectParticipant::class, $exportData->repositories);
  }

  /** @return void */
  public function testExportWithShortNames(): void
  {
    $musician = $this->getMusician();
    $this->entitySerializer->reset();
    $nameSpaceName = new ReflectionClass(Entities\Musician::class)->getNamespaceName();
    $this->entitySerializer->setCommonPrefix($nameSpaceName);
    $this->entitySerializer->addEntity($musician);
    $exportData = $this->entitySerializer->export();
    $jsonData = json_encode($exportData, JSON_PRETTY_PRINT);
    $this->assertGreaterThan(0, strlen($jsonData));
    $this->assertInstanceOf(EntitySerializer\EntityResponse::class, $exportData);
    $this->assertArrayHasKey(new ReflectionClass(Entities\Musician::class)->getShortName(), $exportData->entities);
    $this->assertArrayHasKey(new ReflectionClass(Entities\Musician::class)->getShortName(), $exportData->repositories);
    $this->assertArrayHasKey(new ReflectionClass(Entities\SepaBankAccount::class)->getShortName(), $exportData->repositories);
    $this->assertArrayHasKey(new ReflectionClass(Entities\ProjectParticipant::class)->getShortName(), $exportData->repositories);

    // Test for index-by stringification of UUIDs
    foreach ($musician->getProjectParticipantFieldsData()->getKeys() as $key) {
      $uuidInstance = Uuid::fromBytes($key);
      $uuidString = (string)$uuidInstance;
      $this->assertArrayHasKey(
        $uuidString,
        $exportData->repositories['Musician'][$musician->getId()]['projectParticipantFieldsData']->entities,
      );
    }

    $data = json_decode(json_encode($exportData), true);
    array_walk_recursive(
      $data,
      function(mixed $value, mixed $key) use ($nameSpaceName) {
        if (is_string($value)) {
          $this->assertFalse(str_starts_with($value, $nameSpaceName), "Array value {$value} for key {$key} should not start with {$nameSpaceName}");
        }
        if (is_string($key)) {
          $this->assertFalse(str_starts_with($key, $nameSpaceName), "Array key {$key} with value {$value} should not start with {$nameSpaceName}");
        }
      },
    );
  }

  /** @return void */
  public function testDuplicateEntities(): void
  {
    $musician = $this->getMusician();
    $this->entitySerializer->addEntity($musician);
    $this->entitySerializer->addEntity($musician);
    $exportData = $this->entitySerializer->export();
    $this->assertEquals(1, count($exportData->entities[Entities\Musician::class]));
    $this->assertEquals(1, count($exportData->repositories[Entities\Musician::class]));
    $this->assertEquals(1, count($exportData->repositories[Entities\ProjectParticipant::class]));
    $this->assertEquals(1, count($exportData->repositories[Entities\SepaBankAccount::class]));
  }

  /** @return void */
  public function testDeepen(): void
  {
    $musician = $this->getMusician();
    $this->entitySerializer->addEntity($musician, depth: 1);
    $this->entitySerializer->addEntity($musician, depth: 2);
    $exportData = $this->entitySerializer->export();
    $this->assertEquals(1, count($exportData->entities[Entities\Musician::class]));
    $this->assertArrayHasKey(Entities\Musician::class, $exportData->entities);
    $this->assertArrayHasKey(Entities\Musician::class, $exportData->repositories);
    $this->assertArrayHasKey(Entities\Project::class, $exportData->repositories);
    $this->assertArrayHasKey(Entities\ProjectParticipant::class, $exportData->repositories);
    $this->assertArrayHasKey(Entities\SepaBankAccount::class, $exportData->repositories);
    $this->assertArrayHasKey(Entities\MusicianEmailAddress::class, $exportData->repositories);
    // $json = json_encode($exportData, JSON_PRETTY_PRINT);
  }
}
