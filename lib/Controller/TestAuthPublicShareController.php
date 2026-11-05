<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2023-2025 Claus-Justus Heine
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

use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Http\Attribute;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Share\IShare;

/**
 * Playground, test out the authenticated public share controller in order to
 * evaluate its usefulness.
 *
 * Ok, how this could be used for the registration process:
 *
 * - "Shares" are files in Nextcloud
 * - We could store the registration data as a file. Why not.
 * 1. User opens registration form
 *    - is logged in: redirect to the non-public registration end-point
 *      - fetch the registration data from the unique (per project and person) share
 * 2. User enters name and email
 *    - if we have data, then hint the user to not register twice
 *        - problems, inconveniences: we require a second factor for real users
 *      - if the person has no account, provide password recovery by email etc.
 *
 *
 */
class TestAuthPublicShareController extends AuthPublicShareController
{
  protected IShare $share;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    ISession $session,
    IURLGenerator $urlGenerator,
    protected ProjectRegistrationController $registrationController,
  ) {
    parent::__construct($appName, $request, $session, $urlGenerator);
  }
  // phpcs:enable

  /** {@inheritdoc} */
  #[Attribute\NoAdminRequired]
  #[Attribute\PublicPage]
  #[Attribute\NoCsRFRequired]
  public function showShare(): PublicTemplateResponse
  {
    return $this->registrationController->page();
  }

  /** {@inheritdoc} */
  #[PublicPage]
  #[NoCSRFRequired]
  public function showAuthenticate(): TemplateResponse
  {
    // $this->redirectIfOwned($this->share);

    $templateParameters = ['share' => $this->share];

    return new TemplateResponse('core', 'publicshareauth', $templateParameters, 'guest');
  }

  /** {@inheritdoc} */
  protected function getPasswordHash(): ?string
  {
    return null;
  }

  /** {@inheritdoc} */
  public function isValidToken(): bool
  {
    return true;
  }

  /** {@inheritdoc} */
  protected function isPasswordProtected(): bool
  {
    return true;
  }
}
