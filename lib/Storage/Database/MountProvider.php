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

use OC\Files\Mount\MountPoint as CloudMountPoint;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use OCA\CAFeVDBMembers\AppInfo\Application;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;

/** Mount authorized database files in the logged-in user's Files tree. */
class MountProvider implements IMountProvider
{
  public const MOUNT_TYPE = 'cafevdb-database';
  public const DEFAULT_MOUNT_PATH = 'cafevdb';
  public const MOUNT_PATH_CONFIG_KEY = 'databaseStorageMountPath';

  public function __construct(
    private EntityManagerFactory $entityManagerFactory,
    private IConfig $config,
    private IUserSession $userSession,
    private LoggerInterface $logger,
    private IFactory $l10nFactory,
  ) {
  }

  /** {@inheritdoc} */
  public function getMountsForUser(IUser $user, IStorageFactory $loader)
  {
    if (!$this->userSession->isLoggedIn()
        || $this->userSession->getUser()?->getUID() !== $user->getUID()) {
      return [];
    }

    try {
      $entityManager = $this->entityManagerFactory->create($user->getUID());
      if (!$entityManager->connected()) {
        return [];
      }

      $storage = new Storage([
        'entityManager' => $entityManager,
        'userId' => $user->getUID(),
        'l10n' => $this->l10nFactory->get(
          Application::getAppName(),
          $this->config->getAppValue(
            Application::getOrchestraAppName(),
            ConfigConstants::ORCHESTRA_LOCALE_KEY,
            Application::DEFAULT_LOCALE,
          ),
        ),
      ]);

      $relativePath = $this->config->getUserValue(
        $user->getUID(),
        Application::getAppName(),
        self::MOUNT_PATH_CONFIG_KEY,
        self::DEFAULT_MOUNT_PATH,
      );
      $pathParts = explode('/', trim($relativePath, '/'));
      if ($relativePath === ''
          || in_array('', $pathParts, true)
          || in_array('.', $pathParts, true)
          || in_array('..', $pathParts, true)) {
        $this->logger->warning('Ignoring invalid database-storage mount path.', [
          'userId' => $user->getUID(),
        ]);
        $relativePath = self::DEFAULT_MOUNT_PATH;
      }
      $mountPath = '/' . $user->getUID() . '/files/' . trim($relativePath, '/') . '/';

      return [
        new class(
          $storage,
          $mountPath,
          null,
          $loader,
          [
            'filesystem_check_changes' => 1,
            'readonly' => true,
            'previews' => true,
            'enable_sharing' => false,
            'authenticated' => true,
          ],
          null,
          self::class,
          $user,
          $this->config,
        ) extends CloudMountPoint implements \OCP\Files\Mount\IMovableMount
        {
          public function __construct(
            $storage,
            string $mountpoint,
            ?array $arguments,
            IStorageFactory $loader,
            ?array $mountOptions,
            ?int $mountId,
            ?string $mountProvider,
            private IUser $user,
            private IConfig $config,
          ) {
            parent::__construct($storage, $mountpoint, $arguments, $loader, $mountOptions, $mountId, $mountProvider);
          }

          public function moveMount(string $target): bool
          {
            $prefix = '/' . $this->user->getUID() . '/files/';
            $target = \OC\Files\Filesystem::normalizePath($target);
            if (!str_starts_with($target, $prefix)) {
              return false;
            }

            $relativePath = trim(substr($target, strlen($prefix)), '/');
            $parts = explode('/', $relativePath);
            if ($relativePath === ''
                || in_array('', $parts, true)
                || in_array('.', $parts, true)
                || in_array('..', $parts, true)) {
              return false;
            }

            $this->config->setUserValue(
              $this->user->getUID(),
              Application::getAppName(),
              MountProvider::MOUNT_PATH_CONFIG_KEY,
              $relativePath,
            );
            $this->setMountPoint($prefix . $relativePath . '/');

            return true;
          }

          public function removeMount(): bool
          {
            return false;
          }

          public function getMountType()
          {
            return MountProvider::MOUNT_TYPE;
          }
        },
      ];
    } catch (Throwable $t) {
      $this->logger->error('Unable to mount authorized database storage.', [
        'exception' => $t,
        'userId' => $user->getUID(),
      ]);
      return [];
    }
  }
}
