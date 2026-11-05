<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2024, 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Tests\Unit\Database\ORM;

use OCP\IConfig;
use Psr\Container\ContainerInterface;

use OCA\CAFeVDBMembers\Crypto\IEncryptionService;
use OCA\CAFeVDBMembers\Database\DBAL\Logging\CloudLogger;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Service\AuthenticationService;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;
use OCA\CAFeVDBMembers\Tests\MockProvider;
use OCA\CAFeVDBMembers\Tests\DatabaseProvider;
use OCA\RotDrop\Tests\EnumDatabasePurpose;

/** Construct an instance of the entity manager.  */
trait GenerateEntityManagerTrait
{
  private MockProvider $mockProvider;

  private EntityManager $entityManager;

  /**
   * @param string $token
   *
   * @return void
   */
  private function generateEntityManager(string $token): void
  {
    $this->mockProvider = $this->mockProvider ?? MockProvider::create($this);

    // Do not depend on a possibly installed orchestra app and the login session.
    $encryptionService = $this->createStub(IEncryptionService::class);
    $encryptionService->method('isSealedData')->willReturn(false);
    $encryptionService->method('encrypt')->willReturnArgument(0);
    $encryptionService->method('decrypt')->willReturnArgument(0);
    $this->mockProvider->registerClassInstance(IEncryptionService::class, $encryptionService, true);

    $appContainer = $this->mockProvider->getAppContainer();

    // install config values
    /** @var DatabaseProvider $databaseProvider */
    $databaseProvider = \OCP\Server::get(DatabaseProvider::class);
    $databaseName = $databaseProvider->databaseName(EnumDatabasePurpose::CLOUD_CONNECTOR);

    $appName = $this->mockProvider->appName;

    $cloudConfig = $this->mockProvider->getCloudConfig();
    $cloudConfig->setAppValue(
      $appName,
      ConfigConstants::USER_VIEWS_DATABASE_KEY,
      $databaseName,
    );
    $databaseConfig = $databaseProvider->getDatabaseConfig();
    $cloudConfig->setSystemValue('dbuser', DatabaseProvider::CLOUD_DB_USER);
    $cloudConfig->setSystemValue('dbpassword', $databaseConfig->databasePassword);
    $cloudConfig->setSystemValue('dbhost', $databaseConfig->databaseServer);

    $authenticationService = $this->getMockBuilder(AuthenticationService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $authenticationService
      ->expects($this->once())
      ->method('getRowAccessToken')
      ->willReturn($token);

    $this->entityManager = new EntityManager(
      appContainer: $appContainer,
      appName: $this->mockProvider->appName,
      authenticationService: $authenticationService,
      cloudConfig: $cloudConfig,
      l: $this->mockProvider->getL10N(),
      logger: $this->mockProvider->getLoggerInterface(),
      session: $this->mockProvider->getSession(),
      sqlLogger: $appContainer->get(CloudLogger::class),
      userId: MockProvider::CLOUD_USER_UID,
    );
  }
}
