<?php
/**
 * Member's data base connector for CAFEVDB orchestra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Service;

use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\IConfig;

use OCA\CAFeVDBMembers\Service\EventsService;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;

/** Cover calendar selection in the public project events API service. */
#[Attributes\CoversClass(EventsService::class)]
class EventsServiceTest extends TestCase
{
  /** @return void */
  public function testDefaultCalendarSelectionIncludesPublicCalendarsOnly():void
  {
    $this->assertCalendarSelection(
      null,
      [
        ConfigConstants::CONCERTS_CALENDAR_URI,
        ConfigConstants::REHEARSALS_CALENDAR_URI,
        ConfigConstants::OTHER_CALENDAR_URI,
      ],
    );
  }

  /** @return void */
  public function testExplicitCalendarSelectionIsPreserved():void
  {
    $this->assertCalendarSelection(
      [ ConfigConstants::MANAGEMENT_CALENDAR_URI ],
      [ ConfigConstants::MANAGEMENT_CALENDAR_URI ],
    );
  }

  /**
   * @param null|array $requestedUris
   * @param array $expectedUris
   *
   * @return void
   */
  private function assertCalendarSelection(?array $requestedUris, array $expectedUris):void
  {
    $calendarUris = $requestedUris ?? array_combine($expectedUris, $expectedUris);

    $calendarManager = $this->createMock(ICalendarManager::class);
    $calendarManager->expects($this->once())
      ->method('getCalendarsForPrincipal')
      ->with('principals/users/orchestra', $calendarUris)
      ->willReturn(array_map(function(string $uri):ICalendar {
        $calendar = $this->createMock(ICalendar::class);
        $calendar->method('getUri')->willReturn($uri);
        $calendar->method('getDisplayName')->willReturn($uri);
        return $calendar;
      }, $calendarUris));

    $cloudConfig = $this->createMock(IConfig::class);
    $cloudConfig->expects($this->once())
      ->method('getAppValue')
      ->with('cafevdb', ConfigConstants::SHARE_OWNER_KEY)
      ->willReturn('orchestra');

    $eventsService = $this->getMockBuilder(EventsService::class)
      ->disableOriginalConstructor()
      ->onlyMethods([ 'getProjectEvents' ])
      ->getMock();
    $eventsService->expects($this->once())
      ->method('getProjectEvents')
      ->with(42, $calendarUris)
      ->willReturn([]);

    $this->setPrivateProperty($eventsService, 'calendarManager', $calendarManager);
    $this->setPrivateProperty($eventsService, 'cloudConfig', $cloudConfig);
    $this->setPrivateProperty($eventsService, 'orchestraAppName', 'cafevdb');

    $result = $eventsService->getProjectEventData(42, $requestedUris);

    $expected = [];
    foreach ($expectedUris as $uri) {
      $expected[$uri] = [ 'name' => $uri, 'events' => [] ];
    }
    self::assertSame($expected, $result);
  }

  /**
   * @param object $object
   * @param string $name
   * @param mixed $value
   *
   * @return void
   */
  private function setPrivateProperty(object $object, string $name, mixed $value):void
  {
    $property = new \ReflectionProperty(EventsService::class, $name);
    $property->setValue($object, $value);
  }
}
