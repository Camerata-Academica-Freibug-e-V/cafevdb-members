<?php
/**
 * Member's data base connector for CAFEVDB orchestra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;

use OCA\CAFeVDBMembers\Controller\SettingsController;
use OCA\CAFeVDBMembers\Service\ConnectorConfiguration;
use OCA\CAFeVDBMembers\Service\ProjectGroupService;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Service\GroupFoldersService;

/** Exercise settings without requiring a database connection. */
#[Attributes\CoversClass(SettingsController::class)]
class SettingsControllerTest extends TestCase
{
  /** @return void */
  public function testInheritanceSettingDoesNotConnect(): void
  {
    $connector = $this->createStub(ConnectorConfiguration::class);
    $connector->method('inheritsDatabase')->willReturn(true);
    $container = $this->createMock(ContainerInterface::class);
    $container->expects(self::never())->method('get');
    $controller = new SettingsController(
      'cafevdbmembers',
      $this->createStub(IRequest::class),
      'admin',
      null,
      $this->createStub(LoggerInterface::class),
      $this->createStub(IL10N::class),
      $this->createStub(IConfig::class),
      $this->createStub(GroupFoldersService::class),
      $this->createStub(ProjectGroupService::class),
      $connector,
      $container,
    );

    self::assertSame(
      ['value' => 'on'],
      $controller->getAdmin(ConfigConstants::INHERIT_VIEWS_DATABASE_KEY)->getData(),
    );
  }
}
