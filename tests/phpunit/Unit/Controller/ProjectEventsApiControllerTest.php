<?php
/**
 * Member's data base connector for CAFEVDB orchestra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Controller;

use DateTimeZone;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\IRequest;

use OCA\CAFeVDBMembers\Controller\ProjectEventsApiController;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Database\ORM\Repositories\EntityRepository;
use OCA\CAFeVDBMembers\Service\EventsService;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;

/** Cover project-event API routing through the controller. */
#[Attributes\CoversClass(ProjectEventsApiController::class)]
class ProjectEventsApiControllerTest extends TestCase
{
  /** @return void */
  public function testByWebPageForwardsCalendarAndReturnsProjectEvents():void
  {
    $project = $this->createMock(Entities\Project::class);
    $project->method('getName')->willReturn('Spring concert');

    $article = $this->createMock(Entities\ProjectWebPage::class);
    $article->method('getProject')->willReturn($project);

    $repository = $this->getMockBuilder(EntityRepository::class)
      ->disableOriginalConstructor()
      ->onlyMethods([ 'findBy' ])
      ->getMock();
    $repository->expects($this->once())
      ->method('findBy')
      ->with(
        [ 'articleId' => '42' ],
        [ 'project' => 'ASC', 'articleId' => 'ASC' ],
      )
      ->willReturn([ $article ]);

    $entityManager = $this->getMockBuilder(EntityManager::class)
      ->disableOriginalConstructor()
      ->onlyMethods([ 'getRepository' ])
      ->getMock();
    $entityManager->expects($this->once())
      ->method('getRepository')
      ->with(Entities\ProjectWebPage::class)
      ->willReturn($repository);

    $eventsService = $this->createMock(EventsService::class);
    $eventsService->expects($this->once())
      ->method('setTimezone')
      ->with(new DateTimeZone('Europe/Berlin'));
    $eventsService->expects($this->once())
      ->method('setLocale')
      ->with('de_DE');
    $eventsService->expects($this->once())
      ->method('getProjectEventData')
      ->with($project, [ ConfigConstants::REHEARSALS_CALENDAR_URI ])
      ->willReturn([ ConfigConstants::REHEARSALS_CALENDAR_URI => [ 'events' => [] ] ]);

    $timeZone = $this->createStub(IDateTimeZone::class);
    $timeZone->method('getTimeZone')->willReturn(new DateTimeZone('UTC'));

    $controller = new ProjectEventsApiController(
      'cafevdbmembers',
      $this->createStub(IRequest::class),
      $this->createStub(IL10N::class),
      $timeZone,
      $this->createStub(LoggerInterface::class),
      $eventsService,
      $entityManager,
    );

    $response = $controller->serviceSwitch(
      ProjectEventsApiController::INDEX_BY_WEB_PAGE,
      '42',
      ConfigConstants::REHEARSALS_CALENDAR_URI,
      'Europe%2FBerlin',
      'de_DE',
    );

    self::assertInstanceOf(DataResponse::class, $response);
    self::assertSame(Http::STATUS_OK, $response->getStatus());
    self::assertSame([
      'status' => Http::STATUS_OK,
      'data' => [
        'Spring concert' => [
          ConfigConstants::REHEARSALS_CALENDAR_URI => [ 'events' => [] ],
        ],
      ],
    ], $response->getData());
  }
}
