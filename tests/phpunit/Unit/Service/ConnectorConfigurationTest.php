<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Service;

use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use OCA\CAFeVDBMembers\Service\ConnectorConfiguration;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;

#[CoversClass(ConnectorConfiguration::class)]
class ConnectorConfigurationTest extends TestCase
{
  public function testInheritanceSeesProducerChanges(): void
  {
    $values = [];
    $descriptor = $this->descriptor('first');
    $service = $this->service($values, $descriptor);
    self::assertTrue($service->inheritsDatabase());
    self::assertSame('first', $service->resolve()['database']);
    $descriptor = $this->descriptor('second');
    self::assertSame('second', $service->resolve()['database']);
    self::assertSame('second', $service->resolve()['descriptor']['revision']);
  }

  public function testOverrideMismatch(): void
  {
    $values = [ConfigConstants::USER_VIEWS_DATABASE_KEY => 'legacy'];
    $descriptor = $this->descriptor('producer');
    $service = $this->service($values, $descriptor);
    self::assertFalse($service->inheritsDatabase());
    $status = $service->resolve();
    self::assertSame('legacy', $status['database']);
    self::assertCount(1, $status['warnings']);
    $values[ConfigConstants::INHERIT_VIEWS_DATABASE_KEY] = 'on';
    self::assertSame('producer', $service->resolve()['database']);
    self::assertSame('legacy', $values[ConfigConstants::USER_VIEWS_DATABASE_KEY]);
  }

  public function testMissingMetadataKeepsOverride(): void
  {
    $values = [
      ConfigConstants::USER_VIEWS_DATABASE_KEY => 'legacy',
      ConfigConstants::INHERIT_VIEWS_DATABASE_KEY => 'on',
    ];
    $descriptor = '';
    $service = $this->service($values, $descriptor);
    self::assertSame('legacy', $service->resolve()['database']);
    self::assertNotEmpty($service->resolve()['warnings']);
    $descriptor = '{"version":2}';
    self::assertNull($service->resolve()['descriptor']);
    $descriptor = 'invalid json';
    self::assertNull($service->resolve()['descriptor']);
  }

  public function testDisabledProducerMetadata(): void
  {
    $values = [ConfigConstants::USER_VIEWS_DATABASE_KEY => 'legacy'];
    $descriptor = $this->descriptor('stale');
    $service = $this->service($values, $descriptor, enabled: false);
    self::assertNull($service->resolve()['descriptor']);
    self::assertSame('legacy', $service->resolve()['database']);
  }

  public function testAuthenticatedAccountHost(): void
  {
    $values = [];
    $descriptor = $this->descriptor('views');
    $service = $this->service($values, $descriptor);
    $status = $service->resolve();
    self::assertSame([], $service->compareAccount($status, 'connector@%')['warnings']);
    self::assertCount(1, $service->compareAccount($status, 'connector@localhost')['warnings']);
    $unverified = $service->compareAccount($status, null);
    self::assertNull($unverified['actualAccount']);
    self::assertCount(1, $unverified['warnings']);
  }

  public function testDisabledViewsAndMissingDatabase(): void
  {
    $values = [];
    $descriptor = $this->descriptor('views', enabled: false);
    $service = $this->service($values, $descriptor);
    self::assertCount(1, $service->resolve()['warnings']);
    $descriptor = '';
    self::assertSame('', $service->resolve()['database']);
    self::assertCount(2, $service->resolve()['warnings']);
  }

  /**
   * @param string $database
   * @param bool $enabled
   *
   * @return string
   */
  private function descriptor(string $database, bool $enabled = true): string
  {
    return json_encode([
      'version' => 1,
      'database' => $database,
      'account' => ['user' => 'connector', 'accountHost' => '%'],
      'personalizedViewsEnabled' => $enabled,
      'revision' => $database,
    ], JSON_THROW_ON_ERROR);
  }

  /**
   * @param array $values
   * @param string $descriptor
   * @param bool $enabled
   *
   * @return ConnectorConfiguration
   */
  private function service(array &$values, string &$descriptor, bool $enabled = true): ConnectorConfiguration
  {
    $config = $this->createMock(IConfig::class);
    $config->method('getAppValue')->willReturnCallback(
      static function(string $app, string $key, string $default = '') use (&$values, &$descriptor): string {
        if ($key === 'enabled') {
          return 'yes';
        }
        return $key === ConfigConstants::CONNECTOR_DESCRIPTOR_KEY ? $descriptor : ($values[$key] ?? $default);
      },
    );
    $config->expects($this->never())->method('setAppValue');
    $l = $this->createStub(IL10N::class);
    $l->method('t')->willReturnArgument(0);
    $manager = $this->createStub(IAppManager::class);
    $manager->method('isInstalled')->willReturn($enabled);
    return new ConnectorConfiguration($config, $l, $manager, 'cafevdbmembers');
  }
}
