<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2025, 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute as  CoreAttributes;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

use OCA\CAFeVDBMembers\AppInfo\Application;
use OCA\CAFeVDBMembers\Service\AssetService;

/** AJAX endpoint for generating the main page of the app. */
class PageController extends Controller
{
  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    private AssetService $assetService,
  ) {
    parent::__construct($appName, $request);
  }
  // phpcs:enable

  /**
   * Render default template
   *
   * @return TemplateResponse
   *
   * @todo Why NoCSRF?
   */
  #[CoreAttributes\NoAdminRequired]
  #[CoreAttributes\NoCSRFRequired]
  #[CoreAttributes\FrontpageRoute(verb: 'GET', url: '/')]
  #[CoreAttributes\FrontPageRoute(
    verb: 'GET',
    url: '/f/{path}',
    requirements: [ 'path' => '.+' ],
    postfix: 'front',
  )]
  public function index():TemplateResponse
  {
    Util::addScript($this->appName, $this->assetService->getJSAsset('main'));
    Util::addStyle($this->appName, $this->assetService->getCSSAsset('main'));

    return new TemplateResponse($this->appName, 'main');
  }
}
