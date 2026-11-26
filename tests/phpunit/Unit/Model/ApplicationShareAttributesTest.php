<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Model;

use OCP\Share\IAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use OCA\CAFeVDBMembers\Model\ApplicationShareAttributes;

/** Test application-share attribute storage. */
#[CoversClass(ApplicationShareAttributes::class)]
class ApplicationShareAttributesTest extends TestCase
{
  /**
   * Ensure attributes retain their values and serialization format.
   *
   * @return void
   */
  public function testAttributesCanBeSetReadAndSerialized(): void
  {
    $attributes = new ApplicationShareAttributes();

    self::assertInstanceOf(IAttributes::class, $attributes);
    self::assertNull($attributes->getAttribute('sharing', 'enabled'));
    self::assertSame($attributes, $attributes->setAttribute('sharing', 'enabled', true));
    $attributes->setAttribute('sharing', 'note', 'reviewed');
    $attributes->setAttribute('permissions', 'roles', ['viewer', 'editor']);

    self::assertTrue($attributes->getAttribute('sharing', 'enabled'));
    self::assertSame('reviewed', $attributes->getAttribute('sharing', 'note'));
    self::assertSame([
      ['scope' => 'sharing', 'key' => 'enabled', 'value' => true],
      ['scope' => 'sharing', 'key' => 'note', 'value' => 'reviewed'],
      ['scope' => 'permissions', 'key' => 'roles', 'value' => ['viewer', 'editor']],
    ], $attributes->toArray());
  }
}
