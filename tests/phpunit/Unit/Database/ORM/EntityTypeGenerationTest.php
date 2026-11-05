<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Database\ORM;

use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Tests\MockProvider;
use OCA\RotDrop\Tests\DeprecationException;

/** Run the production generator with the server suite's database and services. */
#[Attributes\CoversNothing]
class EntityTypeGenerationTest extends TestCase
{
  use GenerateEntityManagerTrait;

  /** @return void */
  public function testEntityTypes(): void
  {
    DeprecationException::throwOnDeprecations(exclude: '/OCP\\\\IConfig\\:\\:(get|set|delete)AppValue/');
    try {
      $this->mockProvider = MockProvider::create($this);
      $tokens = $this->mockProvider->getRowAccessTokens();
      $this->generateEntityManager($tokens[MockProvider::CLOUD_USER_UID]['access_token_hash']);
      self::assertTrue($this->entityManager->connected());
      $this->mockProvider->registerClassInstance(EntityManager::class, $this->entityManager, true);
      self::assertSame($this->entityManager, \OCP\Server::get(EntityManager::class));

      $appDir = dirname(__DIR__, 5);
      $arguments = getenv('PHP_TO_TYPESCRIPT_ARGUMENTS');
      $argv = $arguments === false ? [
        $appDir . '/dev-scripts/php-to-typescript.php',
        '--source-dir=' . $appDir . '/lib',
        '--output-prefix=' . $appDir . '/build/artifacts/tests/phpunit/entity-types',
        '--ns-prefix=OCA\\CAFeVDBMembers',
        '--scoped-ns-prefix=Wrapped',
        '--as-modules',
        '--quiet',
      ] : json_decode($arguments, true, flags: JSON_THROW_ON_ERROR);
      if (!defined('ROT_DROP_TYPESCRIPT_TEST_BOOTSTRAPPED')) {
        define('ROT_DROP_TYPESCRIPT_TEST_BOOTSTRAPPED', true);
      }
      ob_start();
      try {
        self::assertSame(0, require $appDir . '/dev-scripts/php-to-typescript.php');
      } finally {
        ob_end_clean();
      }

      // The opt-in entry point must also support callers generating only DTOs or enums.
      if ($arguments !== false) {
        return;
      }
      $outputPrefix = $input->getOption('output-prefix');
      self::assertFileExists($outputPrefix . '/php-types.d.ts');
      self::assertFileExists($outputPrefix . '/php-modules/ROOT.ts');
      $metadataFolder = $outputPrefix . '/php-modules/Wrapped/OCA/RotDrop/Toolkit/Doctrine/ORM/EntityMetadata';
      $metadata = file_get_contents($metadataFolder . '/MusicianMetadata.ts');
      self::assertStringContainsString("export const ENTITY_NAME = 'Musician'", $metadata);
      self::assertStringContainsString("mapping: 'to-many'", $metadata);
      $translationMetadata = file_get_contents($metadataFolder . '/TableFieldTranslationMetadata.ts');
      self::assertStringContainsString("fieldName: 'locale'", $translationMetadata);
      self::assertStringContainsString("fieldName: 'foreignKey'", $translationMetadata);
    } finally {
      restore_error_handler();
    }
  }
}
