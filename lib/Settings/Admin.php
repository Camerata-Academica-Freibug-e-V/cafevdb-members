<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2025 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\IDelegatedSettings;

use OCA\CAFeVDBMembers\Service\AssetService;
use OCA\CAFeVDBMembers\Constants;

/** Admin settings implementation. */
class Admin implements IDelegatedSettings
{
  const TEMPLATE = "admin-settings";
  const ASSET_NAME = self::TEMPLATE;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private string $appName,
    private AssetService $assetService,
  ) {
  }
  // phpcs:enable

  /** {@inheritdoc} */
  public function getForm()
  {
    return new TemplateResponse(
      $this->appName,
      self::TEMPLATE, [
        'appName' => $this->appName,
        'assets' => [
          Constants::JS => $this->assetService->getJSAsset(self::ASSET_NAME),
          Constants::CSS => $this->assetService->getCSSAsset(self::ASSET_NAME),
        ],
      ],
      'blank');
  }

  /** {@inheritdoc} */
  public function getSection()
  {
    return $this->appName;
  }

  /** {@inheritdoc} */
  public function getPriority()
  {
    // @todo could be made a configure option.
    return 50;
  }

  /** {@inheritdoc} */
  public function getName():?string
  {
    return null;
  }

  /** {@inheritdoc} */
  public function getAuthorizedAppConfig():array
  {
    return [];
  }
}
