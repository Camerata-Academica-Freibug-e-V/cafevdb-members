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

namespace OCA\CAFeVDBMembers\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute as CoreAttributes;
use OCP\AppFramework\Http\Response;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Service\AuthenticationService;
use OCA\CAFeVDBMembers\Service\MemberDataService;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Exceptions\EnduserNotificationException;

/** Downloads of files belonging to the logged-in member. */
class MemberFileController extends Controller
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\UtilTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\ResponseTrait;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    private ?string $userId,
    protected IL10N $l,
    protected LoggerInterface $logger,
    private MemberDataService $dataService,
    private AuthenticationService $authenticationService,
    private EntityManager $entityManager,
  ) {
    parent::__construct($appName, $request);
  }

  /**
   * @param string $optionKey The UUID of the corresponding field-datum.
   *
   * @return Response
   */
  #[CoreAttributes\NoAdminRequired]
  #[CoreAttributes\FrontpageRoute(
    url: '/download/member/{optionKey}',
    verb: 'GET',
  )]
  public function download(string $optionKey): Response
  {
    try {
      $this->authenticationService->getRowAccessToken();
    } catch (\Throwable $t) {
      throw new EnduserNotificationException(
        $this->l->t('Access to the member-data is not authorized: %s', $t->getMessage()),
        previous: $t,
      );
    }

    $musicians = $this->entityManager->getRepository(Entities\Musician::class)->findAll();
    if (count($musicians) == 0) {
      throw new EnduserNotificationException($this->l->t('No member-data found for user-id "%s".', $this->userId));
    } elseif (count($musicians) > 1) {
      throw new EnduserNotificationException($this->l->t('More than one musician found for user-id "%s".', $this->userId));
    }
    /** @var Entities\Musician $musician */
    $musician = $musicians[0];
    $fieldDatum = $musician->getProjectParticipantFieldsDatum($optionKey);
    if (empty($fieldDatum)) {
      throw new EnduserNotificationException($this->l->t('Unable to find data for the option-uuid "%s".', $optionKey));
    }
    $pathInfo = $this->dataService->participantFileInfo($fieldDatum);
    if (empty($pathInfo)) {
      throw new EnduserNotificationException($this->l->t('The option "%s" does not have any associated files.', $fieldDatum->getDataOption()->getLabel()));
    }

    /** @var Entities\File $file */
    $file = $pathInfo['file'];
    return $this->dataDownloadResponse($file->getFileData()->getData(), $pathInfo['baseName'], $file->getMimeType());
  }
}
