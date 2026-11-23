<?php
/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Listener;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

use OCA\CAFeVDBMembers\AppInfo\Application;
use OCA\CAFeVDBMembers\Service\AssetService;

/** Add the scan trigger to authenticated interactive pages. */
class DatabaseStorageScanScriptListener implements IEventListener
{
  public const EVENT = BeforeTemplateRenderedEvent::class;

  public function __construct(private AssetService $assets)
  {
  }

  public function handle(Event $event): void
  {
    if ($event instanceof BeforeTemplateRenderedEvent && $event->isLoggedIn()) {
      Util::addScript(Application::getAppName(), $this->assets->getJSAsset('database-storage-scan'));
    }
  }
}
