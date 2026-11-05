<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2023-2026 Claus-Justus Heine
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

use DateTimeZone;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute as CoreAttributes;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface as ILogger;

use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Service\EventsService;

/**
 * Public API for project events. This is somehow "legacy" and could be
 * improved. It is used by the Redaxo CMS in order to automatically blend in
 * rehearsal and concert events.
 */
class ProjectEventsApiController extends ApiController
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  const INDEX_BY_PROJECT = 'byProject';
  const INDEX_BY_WEB_PAGE = 'byWebPage';

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    ?string $appName,
    IRequest $request,
    private IL10N $l,
    private IDateTimeZone $dateTimeZone,
    protected ILogger $logger,
    private EventsService $eventsService,
    private EntityManager $entityManager,
  ) {
    parent::__construct($appName, $request);
  }
  // phpcs:enable

  /**
   * @param string $indexObject
   *
   * @param int|string $objectId Numeric or textual identifier. If
   * $indexObject equal ProjectEventsApiController::INDEX_BY_PROJECT then the
   * project can as well be identified by its (unique) name.
   *
   * @param string $calendar
   *
   * @param string $timezone
   *
   * @param string $locale
   *
   * @return DataResponse
   */
  #[CoreAttributes\CORS]
  #[CoreAttributes\NoAdminRequired]
  #[CoreAttributes\NoCSRFRequired]
  #[CoreAttributes\PublicPage]
  #[CoreAttributes\FrontpageRoute(
    url: '/api/{apiVersion}/projects/events/{indexObject}/{objectId}/{calendar}/{timezone}/{locale}',
    verb: 'GET',
    defaults: [
      'calendar' => 'all',
      'timezone' => null,
      'locale' => null,
    ],
    requirements: [
      'apiVersion' => '0.1',
    ],
  )]
  public function serviceSwitch(
    string $indexObject,
    int|string $objectId,
    string $calendar,
    ?string $timezone,
    ?string $locale,
  ):DataResponse {
    // OC uses symphony which rawurldecodes the request URL. This
    // implies that in order to pass a slash / we caller must
    // urlencode that thingy twice, and Symphony consequently will
    // only deliver encoded data in this case.

    switch ($indexObject) {
      case self::INDEX_BY_PROJECT:
        $projectId = $objectId;
        if (is_int($projectId)) {
          $project = $this->entityManager->getRepository(Entities\Project::class)->find($projectId);
        } else {
          $project = $this->entityManager->getRepository(Entities\Project::class)->findOneBy([ 'name' => $projectId ]);
        }
        if (empty($project)) {
          $this->logError('NOT FOUND ' . $indexObject . '@' . $projectId);
          return new DataResponse([], Http::STATUS_NOT_FOUND);
        }

        $projectId = $project->getId();
        $projectName = $project->getName();

        if ($calendar == 'all') {
          $calendar = null;
        } else {
          $calendar = [ $calendar ];
        }

        $this->setTimezone($timezone);
        $this->setLocale($locale);

        $eventData = $this->eventsService->getProjectEventData($project, $calendar);
        return new DataResponse([ 'status' => 200, 'data' => [ $projectName => $eventData, ], ], Http::STATUS_OK);
      case self::INDEX_BY_WEB_PAGE:
        $articleId = $objectId;

        if ($calendar == 'all') {
          $calendar = null;
        } else {
          $calendar = [ $calendar ];
        }

        $this->setTimezone($timezone);
        $this->setLocale($locale);

        $articles = $this->entityManager->getRepository(Entities\ProjectWebPage::class)->findBy([
          'articleId' => $articleId
        ], [
          'project' => 'ASC', 'articleId' => 'ASC',
        ]);

        $data = [];
        /** @var Entities\ProjectWebPage $article */
        foreach ($articles as $article) {
          $project = $article->getProject();
          $data[$project->getName()] = $this->eventsService->getProjectEventData($project, $calendar);
        }
        return new DataResponse([ 'status' => 200, 'data' => $data, ], Http::STATUS_OK);
      default:
        return new DataResponse([], Http::STATUS_NOT_FOUND);
    }
  }

  /**
   * @param null|string $timezone
   *
   * @return void
   */
  private function setTimeZone(?string $timezone):void
  {
    if ($timezone === null) {
      $timezone = $this->dateTimeZone->getTimeZone();
    } else {
      $timezone = rawurldecode($timezone);
      $timezone = new DateTimeZone($timezone);
    }
    $this->eventsService->setTimezone($timezone);
  }

  /**
   * @param null|string $locale
   *
   * @return void
   */
  private function setLocale(?string $locale):void
  {
    if ($locale == null) {
      $locale = $this->l->getLocaleCode();
      $lang = locale_get_primary_language($locale);
      if ($lang == $locale) {
        $locale = $lang.'_'.strtoupper($lang);
      }
      if (strpos($locale, '.') === false) {
        $locale .= '.UTF-8';
      }
    } else {
      $locale = rawurldecode($locale);
    }
    $this->eventsService->setLocale($locale);
  }
}
