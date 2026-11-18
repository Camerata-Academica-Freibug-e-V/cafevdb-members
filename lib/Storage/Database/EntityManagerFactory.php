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

use OCP\IConfig;
use OCP\IL10N;
use OCP\ISession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\AppInfo\Application;
use OCA\CAFeVDBMembers\Crypto\IEncryptionService;
use OCA\CAFeVDBMembers\Database\DBAL\Logging\CloudLogger;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Service\AuthenticationService;

/** Create an ORM connection authenticated as the requested cloud user. */
class EntityManagerFactory
{
  public function __construct(
    private IEncryptionService $encryptionService,
    private CloudLogger $sqlLogger,
    private IConfig $cloudConfig,
    private ISession $session,
    private ContainerInterface $appContainer,
    private IL10N $l,
    private LoggerInterface $logger,
  ) {
  }

  public function create(string $userId): EntityManager
  {
    $authenticationService = new AuthenticationService(
      $userId,
      $this->encryptionService,
      $this->logger,
      $this->l,
    );
    $authenticationService->getRowAccessToken();

    return new EntityManager(
      userId: $userId,
      authenticationService: $authenticationService,
      sqlLogger: $this->sqlLogger,
      cloudConfig: $this->cloudConfig,
      session: $this->session,
      appName: Application::getAppName(),
      appContainer: $this->appContainer,
      l: $this->l,
      logger: $this->logger,
    );
  }
}
