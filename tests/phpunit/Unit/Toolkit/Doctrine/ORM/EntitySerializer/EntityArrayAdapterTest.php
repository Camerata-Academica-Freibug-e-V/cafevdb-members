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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\MockObject\MockObject;

use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Tests\MockProvider;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer\EntityArrayAdapter as TestedClass;
use OCA\RotDrop\Tests\DeprecationException;

/** Test aspects of the EntitySerializer\EntityArrayAdapter class. */
#[Attributes\CoversClass(EntitySerializer\EntitySerializer::class)]
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
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Service\AuthenticationService::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\AppInfo\AbstractApplication::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\AbstractDecimalRationalType::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\ArrayType::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\DecimalRationalMonetaryType::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\DBAL\Types\UuidType::class)]
#[Attributes\UsesClass(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager::class)]
#[Attributes\UsesTrait(\OCA\CAFeVDBMembers\Database\ORM\Traits\ArrayTrait::class)]
#[Attributes\UsesTrait(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\FindLikeTrait::class)]
class EntityArrayAdapterTest extends TestCase
{
  use \OCA\CAFeVDBMembers\Tests\Unit\Database\ORM\GenerateEntityManagerTrait;

  private TestedClass $entityArrayAdapter;

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

    $entitySerializer = new EntitySerializer\EntitySerializer(
      entityManager: $this->entityManager,
      l: $this->mockProvider->getL10N(),
      logger: $this->mockProvider->getLoggerInterface(),
    );

    $this->entityArrayAdapter = TestedClass::create(
      entity: $this->getMusician(),
      depth: 1,
      entitySerializer: $this->entitySerializer,
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
  public function testConstruction(): void
  {
  }
}
