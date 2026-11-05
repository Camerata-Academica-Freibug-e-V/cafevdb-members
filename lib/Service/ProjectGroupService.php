<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Service;

use InvalidArgumentException;
use RuntimeException;

use BackedEnum;

use OCP\EventDispatcher\Event;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\Constants;
use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumProjectTemporalType as ProjectType;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Service\GroupFoldersService;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Service\SimpleSharingService;

/** Manage the shared project-group folders. */
class ProjectGroupService
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  const MANAGEMENT_PERMISSIONS = GroupFoldersService::PERMISSION_ALL;
  const GROUP_LEAF_PERMISSIONS = GroupFoldersService::PERMISSION_DELETE|GroupFoldersService::PERMISSION_WRITE;
  const GROUP_PARENT_PERMISSIONS = GroupFoldersService::PERMISSION_READ;
  // Same scheme as used by the orchestra app: "<APP_NAME>_<PROJECT_ID>".
  const GROUP_ID_SEPARATOR = '_';

  private string $groupIdPrefix;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private GroupFoldersService $groupFoldersService,
    private IGroupManager $groupManager,
    private SimpleSharingService $sharingService,
    private string $appManagementGroup,
    private string $memberRootFolder,
    private string $orchestraAppName,
    protected IAppConfig $appConfig,
    protected IL10N $l,
    protected LoggerInterface $logger,
    protected IRootFolder $rootFolder,
  ) {
    $this->groupIdPrefix = $this->getProjectGroupId(1117);
    $this->groupIdPrefix = substr($this->groupIdPrefix, 0, strpos($this->groupIdPrefix, '1117'));
  }
  // phpcs:enable

  /**
   * Make sure all folders for all projects exist.
   *
   * @param string $gid
   *
   * @return void
   */
  public function synchronizeFolderStructure(string $gid):void
  {
    if (!$this->isProjectGroup($gid)) {
      throw new InvalidArgumentException(
        $this->l->t('Group %1$s does not start with the correct prefix "%2$s".', [
          $gid, $this->groupIdPrefix,
        ]));
    }
    $group = $this->groupManager->get($gid);

    $this->logDebug('Should handle ' . $group->getGID() . ' / ' . $group->getDisplayName());
    $this->ensureProjectFolder($group);

    // remove empty "year" folders
    $this->removeOrphanFolders();
  }

  /**
   * @param string $gid
   *
   * @return bool
   */
  private function isProjectGroup(string $gid):bool
  {
    return str_starts_with($gid, $this->groupIdPrefix);
  }

  /**
   * Returns the list of the cafevdb created user groups.
   *
   * @return array
   */
  public function getProjectGroups():array
  {
    $groups = [];
    /** @var IGroup $group */
    foreach ($this->groupManager->search('') as $group) {
      if ($this->isProjectGroup($group->getGID())) {
        $groups[] = $group;
      }
    }
    return $groups;
  }

  /**
   * Compute the leaf mount point for the given project name.
   *
   * @param string $projectName
   *
   * @param array $parentMounts
   *
   * @return string
   */
  public function getProjectFolderMountPoint(string $projectName, ?array &$parentMounts = null):string
  {
    $projectYear = substr($projectName, -4);
    if (preg_match('/^\d{4}$/', $projectYear) !== 1) {
      $parentMounts = [ $this->memberRootFolder ];
    } else {
      $parentMounts = [ $this->memberRootFolder, $this->l->t('projects'), $projectYear ];
    }
    $leafMountPoint = implode('/', $parentMounts) . '/' . $projectName;
    $previousMounts = [];
    foreach ($parentMounts as &$parentMount) {
      $previousMounts[] = $parentMount;
      $parentMount = implode('/', $previousMounts);
    }
    return $leafMountPoint;
  }

  /**
   * Ensure that the group folder is also r/o link-shared.
   *
   * @param string $leafMountPoint
   *
   * @param string $projectName
   *
   * @return null|array The absolute URLs for the share or null.
   * ```
   * [ 'files_sharing': URL, 'webdav' => DAV_URL, 'share' => ISHARE_INSTANCE ]
   * ```
   */
  private function ensureProjectFolderLinkShare(string $leafMountPoint, string $projectName):?array
  {
    $shareOwner = $this->appConfig->getValueString($this->orchestraAppName, ConfigConstants::SHARE_OWNER_KEY);
    if (empty($shareOwner)) {
      return null;
    }
    $leafFolder = $this->rootFolder->getUserFolder($shareOwner)->get($leafMountPoint);
    $result = $this->sharingService->linkShare(
      $leafFolder,
      $shareOwner,
      sharePerms: \OCP\Constants::PERMISSION_READ|\OCP\Constants::PERMISSION_SHARE,
      password: $projectName,
    );
    if ($result === null) {
      $this->logError('Unable to generate link-share for ' . $leafMountPoint);
    } else {
      $this->logInfo('Link share ensured: ' . $result['files_sharing'] . ' ' . $result['share']->getId());
      $result['mount_point'] = '/' . $leafMountPoint;
    }
    return $result;
  }

  /**
   * Return and possibly create a read-only link-share of the group folder for
   * the project.
   *
   * @param int $projectId
   *
   * @return null|array
   * ```
   * [ 'files_sharing': URL, 'webdav' => DAV_URL, 'share' => ISHARE_INSTANCE ]
   * ```
   */
  public function getProjectFolderLinkShare(int $projectId):?array
  {
    // check if the folder structure is actually there
    $groupId = $this->getProjectGroupId($projectId);
    $group = $this->groupManager->get($groupId);
    if (empty($group)) {
      return null;
    }
    return $this->ensureProjectFolder($group);
  }

  /**
   * @param int $projectId
   *
   * @return string
   */
  public function getProjectGroupId(int $projectId): string
  {
    return $this->orchestraAppName . self::GROUP_ID_SEPARATOR . $projectId;
  }

  /**
   * Make sure a shared folder exists for the given group.
   *
   * @param IGroup $group
   *
   * @param null|string $forcedFolderName A forced folder-name. This
   * is primarily used to make sure that the new group display name is
   * used while renaming projects. If null the display name of the
   * group is used.
   *
   * @return null|array
   * ```
   * [ 'files_sharing': URL, 'webdav' => DAV_URL, 'share' => ISHARE_INSTANCE ]
   * ```
   */
  public function ensureProjectFolder(IGroup $group, ?string $forcedFolderName = null):?array
  {
    $groupId = $group->getGID();
    $groupName = $forcedFolderName ?? $group->getDisplayName();
    $leafMountPoint = $this->getProjectFolderMountPoint($groupName, $parentMounts);

    // ensure all parent mounts exist and are readable by the respective project-group
    foreach ($parentMounts as $parentMount) {
      $parentFolder = $this->groupFoldersService->getFolder($parentMount);
      if ($parentFolder === null) {
        $this->groupFoldersService->createFolder(
          $parentMount, [
            $groupId => self::GROUP_PARENT_PERMISSIONS,
            $this->appManagementGroup => self::MANAGEMENT_PERMISSIONS,
          ], [
            $this->appManagementGroup => GroupFoldersService::MANAGER_TYPE_GROUP
          ]);
      } else {
        // add read-access to the parent-folder if not already
        $this->logDebug('ADD ' . $groupId);
        $this->groupFoldersService->addGroupToFolder(
          $parentMount,
          $groupId,
          self::GROUP_PARENT_PERMISSIONS,
          canManage: false);
        $this->logDebug('ADD ' . $this->appManagementGroup);
        $this->groupFoldersService->addGroupToFolder(
          $parentMount,
          $this->appManagementGroup,
          self::MANAGEMENT_PERMISSIONS,
          canManage: true);
      }
    }

    // finally ensure the leaf-folder is there and is writable by the project group
    $leafFolder = $this->groupFoldersService->getFolder($leafMountPoint);
    if ($leafFolder === null) {
      $groupFolders = $this->searchWritableGroupFolders($group);
      if (count($groupFolders) == 1) {
        // just rename it
        $groupFolder = array_shift($groupFolders);
        $this->groupFoldersService->changeMountPoint($groupFolder->mountPoint, $leafMountPoint, moveChildren: true);
      } else {
        $this->groupFoldersService->createFolder(
          $leafMountPoint, [
            $groupId => self::GROUP_LEAF_PERMISSIONS,
            $this->appManagementGroup => self::MANAGEMENT_PERMISSIONS,
          ], [
            $this->appManagementGroup => GroupFoldersService::MANAGER_TYPE_GROUP
          ]);
      }
    }
    $leafFolder = $this->groupFoldersService->getFolder($leafMountPoint);
    if ($leafFolder === null) {
      throw new RuntimeException($this->l->t('Unable to make sure the the group-shared folder "%1$s" for group "%2$s" exists.', [ $leafMountPoint, $group->getDisplayName() ]));
    }
    if ($leafFolder->groups[$groupId]['permissions'] != self::GROUP_LEAF_PERMISSIONS) {
      // add write-access to the leaf-folder, this lazily just performs the necessary steps.
      $this->groupFoldersService->addGroupToFolder(
        $leafMountPoint,
        $groupId,
        self::GROUP_LEAF_PERMISSIONS,
        canManage: false);
    }
    if ($leafFolder->groups[$this->appManagementGroup]['permissions'] != self::MANAGEMENT_PERMISSIONS) {
      // add write-access to the leaf-folder, this lazily just performs the necessary steps.
      $this->groupFoldersService->addGroupToFolder(
        $leafMountPoint,
        $this->appManagementGroup,
        self::MANAGEMENT_PERMISSIONS,
        canManage: true);
    }

    // finally remove left-over entries
    foreach ($this->groupFoldersService->searchFolders($groupId, GroupFoldersService::SEARCH_TOPIC_GROUP) as $folderInfo) {
      $mountPoint =  $folderInfo->mountPoint;
      if ($mountPoint == $this->memberRootFolder) {
        // skip root-folder
        continue;
      }
      if (!str_starts_with($mountPoint, $this->memberRootFolder)) {
        continue;
      }
      if ($mountPoint !== $leafMountPoint && array_search($mountPoint, $parentMounts) === false) {
        $this->groupFoldersService->removeGroupFromFolder($mountPoint, $groupId);
        // maybe the contents should be moved to the current mount
      }
    }

    // Finally, generate a share link with a trivial password protection.
    return $this->ensureProjectFolderLinkShare($leafMountPoint, $groupName);
  }

  /**
   * Search all folders for which the given group has write-access. Ideally,
   * this is just one. If so, this folder can simply be renamed when changing
   * the internal folder structure.
   *
   * @param IGroup $group
   *
   * @return array
   */
  private function searchWritableGroupFolders(IGroup $group):array
  {
    $groupId = $group->getGID();
    $groupFolders = [];
    foreach ($this->groupFoldersService->searchFolders($groupId, GroupFoldersService::SEARCH_TOPIC_GROUP) as $folderInfo) {
      if (($folderInfo->groups[$groupId]['permissions'] & self::GROUP_LEAF_PERMISSIONS) != GroupFoldersService::PERMISSION_READ) {
        $groupFolders[] = $folderInfo;

      }
    }
    return $groupFolders;
  }

  /**
   * Remove all "orphan" folders, i.e. year-folders without projects.
   *
   * @return void
   */
  public function removeOrphanFolders():void
  {
    $allFolders = $this->groupFoldersService->searchFolders('|^' . $this->memberRootFolder . '.*$|');
    $cleanList = [];
    $parents = [];
    foreach ($allFolders as $index => $folderInfo) {
      if (empty($folderInfo->groups)) {
        $cleanList[] = $folderInfo;
        unset($allFolders[$index]);
        continue;
      }
      $parents[dirname($folderInfo->mountPoint)] = true;
    }
    foreach ($allFolders as $index => $folderInfo) {
      $mountPoint = $folderInfo->mountPoint;
      $groupYear = substr($mountPoint, -5);
      if (preg_match('|^/\d{4}$|', $groupYear) !== 1) {
        continue;
      }
      if (!isset($parents[$mountPoint])) {
        $cleanList[] = $folderInfo;
      }
    }
    foreach ($cleanList as $folderInfo) {
      $this->groupFoldersService->deleteFolders($folderInfo->mountPoint);
    }
  }

  /**
   * Project events are emitted by the orchestra app. They are handled
   * duck-typed in order to avoid a hard dependency on its classes.
   *
   * @param mixed $projectType Enum instance or its backing value.
   *
   * @return null|string
   */
  private static function projectTypeValue(mixed $projectType):?string
  {
    return $projectType instanceof BackedEnum ? $projectType->value : $projectType;
  }

  /**
   * @param Event $event An event with the interface of the orchestra app's
   * PostProjectUpdatedEvent.
   *
   * @return void
   */
  public function handleProjectRenamed(Event $event):void
  {
    $groupId = $this->getProjectGroupId($event->getProjectId());
    $oldData = $event->getOldData();
    $newData = $event->getNewData();
    $oldType = self::projectTypeValue($oldData['type']);
    $newType = self::projectTypeValue($newData['type']);
    $template = ProjectType::TEMPLATE->value;
    if ($oldType == $template && $newType == $template) {
      return;
    } elseif ($oldType == $template) {
      $this->ensureProjectGroupFolder($newData['id'], $newData['name']);
      return;
    } elseif ($newType == $template) {
      $this->deleteProjectGroupFolder($newData['name']);
      return;
    }

    if ($oldData['name'] != $newData['name']) {
      $oldMountPoint = $this->getProjectFolderMountPoint($oldData['name']);
      $newMountPoint = $this->getProjectFolderMountPoint($newData['name']);
      $folderInfo = $this->groupFoldersService->getFolder($oldMountPoint);
      if (!empty($folderInfo)) {
        $this->groupFoldersService->changeMountPoint($oldMountPoint, $newMountPoint);
      } else {
        $this->logInfo('No folder info for old mount-point ' . $oldMountPoint);
      }
      $group = $this->groupManager->get($groupId);
      if (!empty($group)) {
        if ($group->getDisplayName() != $newData['name']) {
          $this->logWarn('GROUP NAME IS STILL ' . $group->getDisplayName() . ' vs ' . $newData['name']);
        }
        $this->ensureProjectFolder($group, forcedFolderName: $newData['name']);
      } else {
        $this->logError('Cloud-group "' . $groupId . '" for project "' . $newData['name'] . ' does not exist.');
      }
    } else {
      // check if the folder structure is actually there
      $groupId = $this->getProjectGroupId($event->getProjectId());
      $projectName = $event->getProjectName();
      $group = $this->groupManager->get($groupId);
      if (!empty($group)) {
        $this->ensureProjectFolder($group);
      } else {
        $this->logError('Cloud-group "' . $groupId . '" for project "' . $projectName . ' does not exist.');
      }
    }
  }

  /**
   * @param Event $event An event with the interface of the orchestra app's
   * BeforeProjectDeletedEvent.
   *
   * @return void
   */
  public function handleProjectDeletedEvent(Event $event)
  {
    $this->deleteProjectGroupFolder($event->getProjectName());
  }

  /**
   * @param string $projectName
   *
   * @return void
   */
  private function deleteProjectGroupFolder(string $projectName):void
  {
    $mountPoint = $this->getProjectFolderMountPoint($projectName);
    $this->groupFoldersService->deleteFolders($mountPoint);
  }

  /**
   * @param Event $event An event with the interface of the orchestra app's
   * ProjectCreatedEvent.
   *
   * @return void
   */
  public function handleProjectCreatedEvent(Event $event)
  {
    $this->ensureProjectGroupFolder($event->getProjectId(), $event->getProjectName());
  }

  /**
   * @param int $projectId
   *
   * @param string $projectName
   *
   * @return void
   */
  private function ensureProjectGroupFolder(int $projectId, string $projectName):void
  {
    $groupId = $this->getProjectGroupId($projectId);
    $group = $this->groupManager->get($groupId);
    if (!empty($group)) {
      $this->ensureProjectFolder($group);
    } else {
      $this->logError('Cloud-group "' . $groupId . '" for project "' . $projectName . ' does not exist.');
    }
  }
}
