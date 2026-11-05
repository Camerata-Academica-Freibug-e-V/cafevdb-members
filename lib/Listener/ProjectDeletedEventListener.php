<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2024 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Listener;

use Psr\Container\ContainerInterface;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

use OCA\CAFEVDB\Events\BeforeProjectDeletedEvent as HandledEvent;

use OCA\CAFeVDBMembers\Service\ProjectGroupService;

/** Remove the group-shared folder after deleting the project. */
class ProjectDeletedEventListener implements IEventListener
{
  const EVENT = HandledEvent::class;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(private ContainerInterface $appContainer)
  {
  }
  // phpcs:enable

  /** {@inheritdoc} */
  public function handle(Event $event):void
  {
    if (!($event instanceof HandledEvent)) {
      return;
    }
    /** @var ProjectGroupService $service */
    $service = $this->appContainer->get(ProjectGroupService::class);
    $service->handleProjectDeletedEvent($event);
  }
}
