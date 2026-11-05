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

use Exception;
use DateTimeInterface;
use DateTimeImmutable;
use DateTimeZone;

use Sabre\VObject;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\MaxInstancesExceededException;
use Sabre\VObject\Recur\NoInstancesException;
use Sabre\VObject\Component as VComponent;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Property\ICalendar as VProperty;

use Psr\Log\LoggerInterface;
use OCP\IL10N;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\IConfig as ICloudConfig;
use OCP\IDateTimeZone;
use OCP\IDateTimeFormatter;

use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumVCalendarType as VCalendarType;

use OCA\CAFeVDBMembers\Constants;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;
use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;

/**
 * More or less a service provider for a public page giving unauthenticated
 * access to rehearsal and concert events.
 *
 * This is a stripped down "clone" of OCA\CAFEVDB\Service\EventsService, with
 * some merged in support functions of the
 * OCA\CAFEVDB\Service\VCalendarService.
 */
class EventsService
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  private DateTimeZone $dateTimeZone;

  /**
   * @var array Cache the siblings of recurring events by calendar-id,
   * event-uid, sequence, recurrence-id. This cache contains calendar VEvent
   * instances.
   */
  private $eventSiblings;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private ?string $appName,
    protected LoggerInterface $logger,
    protected IL10N $l,
    private IL10NFactory $l10nFactory,
    IDateTimeZone $dateTimeZone,
    private IDateTimeFormatter $dateTimeFormatter,
    private ICloudConfig $cloudConfig,
    private ICalendarManager $calendarManager,
    private EntityManager $entityManager,
    private CalDavService $calDavService,
    private string $orchestraAppName,
  ) {
    $this->dateTimeZone = $dateTimeZone->getTimeZone();
  }
  // phpcs:enable

  /**
   * Install the given $locale.
   *
   * @param string|IL10N $locale
   *
   * @return EventsService
   */
  public function setLocale(string|IL10N $locale):EventsService
  {
    if (is_string($locale)) {
      $lang = locale_get_primary_language($locale);
      $region = locale_get_region($locale);

      $locale = $this->l10nFactory->get($this->appName, $lang, $lang . '_' . $region);
    }

    $this->l = $locale;

    return $this;
  }

  /**
   * @return IL10N Return the language service currently used by this
   * instance.
   */
  public function getL10N():IL10N
  {
    return $this->l;
  }

  /**
   * @param string|DateTimeZone $timezone
   *
   * @return EventsService
   */
  public function setTimezone(string|DateTimeZone $timezone):EventsService
  {
    if (is_string($timezone)) {
      $timezone = new DateTimeZone($timezone);
    }
    $this->dateTimeZone = $timezone;

    return $this;
  }

  /**
   * @return DateTimeZone Return the currently used DateTimeZone object.
   */
  public function getDateTimeZone():DateTimeZone
  {
    return $this->dateTimeZone;
  }

  /**
   * Return event data for given project id and calendar id. Used in
   * an API call from Redaxo.
   *
   * @param int|Entities\Project $projectOrId
   *
   * @param null|string|array $calendarUris null to get the events from all
   * calendars or the 'uri' component from
   * OCA\CAFEVDB\Settings\ConfigConstants::CALENDARS;
   *
   * @return array
   * ```
   * [
   *   [ 'events' => EVENT_DATA1 ],
   *   [ 'events' => EVENT_DATA2 ],
   *   ...
   * ]
   * ```
   */
  public function getProjectEventData(int|Entities\Project $projectOrId, ?array $calendarUris = null):array {

    if ($calendarUris === null) {
      $calendarUris = array_filter(array_map(fn(array $cal) => $cal['public'] ? $cal['uri'] : null, ConfigConstants::CALENDARS));
    }

    $events = $this->getProjectEvents($projectOrId, $calendarUris);

    $result = [];

    $shareOwner = $this->cloudConfig->getAppValue($this->orchestraAppName, ConfigConstants::SHARE_OWNER_KEY);
    if (empty($shareOwner)) {
      return $result;
    }
    $principalUri = 'principals/users/' . $shareOwner;

    $calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri, $calendarUris);

    /** @var ICalendar $calendar */
    foreach ($calendars as $calendar) {
      $result[$calendar->getUri()] = [
        'name' => $calendar->getDisplayName(),
        'events' => [],
      ];
    }

    foreach ($events as $event) {
      $calendarUri = $event['calendarUri'];
      $result[$calendarUri]['events'][] = $this->getBriefEventData($event);
    }

    return $result;
  }


  /**
   * @param bool $public Hide non-public calendars.
   *
   * @return array The IDs of the default calendars.
   *
   * @see ConfigConstants::CALENDARS
   */
  public function getDefaultCalendars(bool $public = false):array
  {
    $result = [];
    foreach (ConfigConstants::CALENDARS as $cal) {
      if ($public && !$cal['public']) {
        continue;
      }
      $result[$cal['uri']] = $this->getConfigValue($cal['uri'].'calendar'.'id');
    }
    return $result;
  }

  /**
   * Fetch the list of events associated with $projectId. This
   * functions fetches all the data, not only the pivot-table. Time
   * stamps from the data-base are converted to PHP DateTime()-objects
   * with UTC time-zone.
   *
   * @param int|Entities\Project $projectOrId Database entity or its id.
   *
   * @param null|array $calendarUris
   *
   * @return array Event-data as generated by self::makeEvent().
   */
  public function getProjectEvents(int|Entities\Project $projectOrId, ?array $calendarUris):array
  {
    $criteria = [
      'project' => $projectOrId,
      'type' => VCalendarType::VEVENT,
    ];
    if (!empty($calendarUris)) {
      $criteria['calendarUri'] = $calendarUris;
    }
    $projectEvents = $this->entityManager->getRepository(Entities\ProjectEvent::class)->findBy($criteria);

    $events = [];
    /** @var Entities\ProjectEvent $projectEvent */
    foreach ($projectEvents as $projectEvent) {
      $eventData = $this->getEventData($projectEvent);
      if (empty($eventData)) {
        continue;
      }
      $events[] = $eventData;
    }

    usort($events, function($a, $b) {
      return (($a['start'] == $b['start'])
              ? 0
              : (($a['start'] < $b['start']) ? -1 : 1));
    });

    return $events;
  }

  /**
   * Form an array with the most relevant event data.
   *
   * @param array $eventObject The corresponding event object from fetchEvent() or events().
   *
   * @return array
   * ```
   * [
   *   'times' => $this->getEventTimes($eventObject),
   *   'summary' => $eventObject['summary'],
   *   'location' => $eventObject['location'],
   *   'description' => $eventObject['description'],
   * ]
   * ```
   */
  private function getBriefEventData(array $eventObject):array
  {
    $times = $this->getEventTimes($eventObject);

    $quoted = array('\,' => ',', '\;' => ';');
    $summary = strtr($eventObject['summary'], $quoted);
    $location = strtr($eventObject['location'], $quoted);
    $description = strtr($eventObject['description'], $quoted);

    return [
      'times' => $times,
      'summary' => $summary,
      'location' => $location,
      'description' => $description
    ];
  }

  /**
   * Form start and end date and time in given timezone and locale.
   *
   * @param array $eventObject The corresponding event object from fetchEvent() or events().
   *
   * @return array
   * ```
   * [ 'start' => array('date' => ..., 'time' => ..., 'allday' => ...), 'end' => ... ]
   * ```
   *
   * @todo Perhaps convert to DateTime class instead of using strftime().
   */
  private function getEventTimes(array $eventObject):array
  {
    /** @var DateTimeInterface $start */
    $start = $eventObject['start'];
    /** @var DateTimeInterface $end */
    $end   = $eventObject['end'];
    $allDay = $eventObject['allday'];

    $startStamp = $start->getTimestamp();

    $endStamp = $end->getTimestamp();

    $startDate = $this->dateTimeFormatter->formatDate($start, 'short', $this->dateTimeZone, $this->l);
    $startTime = $start->format('H:i');
    $endTime = $end->format('H:i');
    if ($endTime == '00:00') {
      $altEndTime = $endTime;
      $altEndDate = $this->dateTimeFormatter->formatDate($endStamp, 'short', $this->dateTimeZone, $this->l);
      // make whole-day events a little more readable
      $endTime = '24:00';
      $endDate = $this->dateTimeFormatter->formatDate($endStamp - 1, 'short', $this->dateTimeZone, $this->l);
    } else {
      $endDate = $this->dateTimeFormatter->formatDate($endStamp, 'short', $this->dateTimeZone, $this->l);
      $altEndTime = $endTime;
      $altEndDate = $endDate;
    }

    return [
      'timezone' => $this->dateTimeZone->getName(),
      'locale' => $this->l->getLocaleCode(),
      'allday' => $allDay,
      'start' => [
        'stamp' => $startStamp,
        'date' => $startDate,
        'time' => $startTime,
      ],
      'end' => [
        'stamp' => $endStamp,
        'date' => $endDate,
        'time' => $endTime,
        'altDate' => $altEndDate,
        'altTime' => $altEndTime,
      ],
    ];
  }

  /**
   * Augment the database entity by calendar data.
   *
   * @param Entities\ProjectEvent $projectEvent Database entity.
   *
   * @return array|null Returns null if the calendar object cannot be
   * found, otherwise an array
   * ```
   * [
   *   'projectid' => PROJECT_ID,
   *   'uri' => EVENT_URI,
   *   'uid' => EVENT_UID,
   *   'calendarid' => CALENDAR_ID,
   *   'start' => \DateTime,
   *   'end' => \DateTime,
   *   'allday' => BOOL
   *   'summary' => SUMMARY,
   *   'description' => DESCRTION,
   *   'location' => LOCATION,
   * ]
   * ```
   */
  public function getEventData(Entities\ProjectEvent $projectEvent):?array
  {
    $event = [];
    $event['projectid'] = $projectEvent->getProject()->getId();
    $event['uri'] = $projectEvent->getEventUri();
    $event['uid'] = $projectEvent->getEventUid();
    $event['calendarid'] = $projectEvent->getCalendarId();
    $event['calendarId'] = $event['calendarid'];
    $event['calendarUri'] = $projectEvent->getCalendarUri();
    $event['sequence'] = $projectEvent->getSequence();
    $event['recurrenceId'] = $projectEvent->getRecurrenceId();
    $event['seriesUid'] = (string)$projectEvent->getSeriesUid();
    $absenceField = $projectEvent->getAbsenceField();
    $softDeleteableState = $this->entityManager->setFilterEnabled(EntityManager::SOFT_DELETEABLE_FILTER, false);
    $event['absenceField'] = !empty($absenceField) && $absenceField->getDeleted() == null ? $absenceField->getId() : 0;
    $this->entityManager->setFilterEnabled(EntityManager::SOFT_DELETEABLE_FILTER, $softDeleteableState);
    $calendarObject = $this->calDavService->getCalendarObject($event['calendarid'], $event['uri']);
    if (empty($calendarObject)) {
      $this->logDebug('Orphan project event found: ' . print_r($event, true) . (new Exception())->getTraceAsString());
      return null;
    }

    /** @var VCalendar $vCalendar */
    $vCalendar = $calendarObject['calendardata'];

    // /** @var VEvent $sibling */
    $siblings = $this->getVEventSiblings($event['calendarid'], $vCalendar);
    $vEvent = $siblings[$event['recurrenceId']] ?? null;
    if ($vEvent === null) {
      $this->logError('Unable to find the event-sibling for uri ' . $event['uri'] . ' and recurrence-id ' . $event['recurrenceId'] . ' ' . print_r(array_keys($siblings), true));
      return null;
    }
    $event['sibling'] = $vEvent;
    $this->fillEventDataFromVObject($vEvent, $event);

    $vObject = self::getVObject($vCalendar);
    $event['seriesStart'] = $vObject->DTSTART->getDateTime();


    return $event;
  }

  /**
   * Convert the given VEvent object to a simpler flat array structure.
   *
   * @param VEvent $vObject
   *
   * @param array $event Output array to fill.
   *
   * @return array Just return $event, with the following data filled in:
   * ```
   * [
   *   ...
   *   'start' => \DateTime,
   *   'end' => \DateTime,
   *   'allday' => BOOL
   *   'summary' => SUMMARY,
   *   'description' => DESCRTION,
   *   'location' => LOCATION,
   *   ...
   * ]
   * ```
   */
  private function fillEventDataFromVObject(VEvent $vObject, array &$event = []):array
  {
    $dtStart = $vObject->DTSTART;
    $dtEnd   = self::getDTEnd($vObject);

    $start = $dtStart->getDateTime();
    $end = $dtEnd->getDateTime();
    $allDay = !$dtStart->hasTime();

    if (!$allDay) {
      if ($dtStart->isFloating()) {
        $start = $start->setTimezone($this->dateTimeZone);
      }
      if ($dtEnd->isFloating()) {
        $end = $end->setTimezone($this->dateTimeZone);
      }
    } else {
      // ??
      $start = new DateTimeImmutable($start->format('Y-m-d H:i:s'), $this->dateTimeZone);
      $end = new DateTimeImmutable($end->format('Y-m-d H:i:s'), $this->dateTimeZone);
    }

    $event['start'] = $start;
    $event['end'] = $end;
    $event['allday'] = $allDay;

    // description + summary?
    $event['summary'] = (string)$vObject->SUMMARY;
    $event['description'] = (string)$vObject->DESCRIPTION;
    $event['location'] = (string)$vObject->LOCATION;
    $event['categories'] = self::getCategories($vObject);
    $recurrenceId = $vObject->{'RECURRENCE-ID'};
    if ($recurrenceId !== null) {
      $event['recurrenceId'] = $recurrenceId->getDateTime()->getTimestamp();
    }
    $sequence = $vObject->SEQUENCE;
    if ($sequence) {
      $event['sequence'] = (string)$sequence;
    }

    return $event;
  }

  /**
   * Use the EventIterator to generate all siblings of a recurring event. For
   * non-recurring events return a single element array containing just this
   * VEvent instance.
   *
   * @param int $calendarId
   *
   * @param VCalendar $vCalendar
   *
   * @return array<int, VEvent>
   */
  private function getVEventSiblings(int $calendarId, VCalendar $vCalendar):array
  {
    $vObject = self::getVObject($vCalendar);
    $uid = (string)$vObject->UID;
    $sequence = (int)(string)($vObject->SEQUENCE ?? 0);
    $siblings = $this->eventSiblings[$calendarId][$uid][$sequence] ?? null;
    if ($siblings !== null) {
      return $siblings;
    }
    if (!self::isEventRecurring($vObject)) {
      $siblings = [ 0 => $vObject ];
    } else {
      $vEvents = self::getAllVObjects($vCalendar);
      try {
        // there is also the expand() method on the VCalendar ...
        $siblings = [];
        $eventIterator = new EventIterator($vEvents);
        while ($eventIterator->valid()) {
          $sibling = $eventIterator->getEventObject();
          $recurrenceId = $sibling->{'RECURRENCE-ID'}->getDateTime()->getTimestamp();
          $siblings[$recurrenceId] = $sibling;
          $eventIterator->next();
        }
      } catch (NoInstancesException $e) {
        // This event is recurring, but it doesn't have a single
        // instance. We are skipping this event from the output
        // entirely.
        $siblings = [];
      } catch (MaxInstancesExceededException $e) {
        // hopefully happens never, but ... just live with the sequence we
        // have.
      }
    }
    $this->eventSiblings[$calendarId][$uid][$sequence] = $siblings;
    return $siblings;
  }

  /**
   * Return the "master" wrapped object, where the VCalendar object here is
   * rather not an entire calendar, but one item from the database. Still it
   * may contain more than one Component, e.g. for recurring events. In this
   * case the "master" item is returned.
   *
   * @param VCalendar $vCalendar VCalendar object.
   *
   * @return The inner object, or one of the inner objects.
   */
  private static function getVObject(VCalendar $vCalendar):VComponent
  {
    if (isset($vCalendar->VEVENT)) {
      $vObject = $vCalendar->VEVENT;
      foreach ($vObject as $instance) {
        if ($instance->{'RECURRENCE-ID'} === null) {
          break;
        }
        $instance = null;
      }
      if ($instance) {
        $vObject = $instance;
      }
    } elseif (isset($vCalendar->VTODO)) {
      $vObject = $vCalendar->VTODO;
    } elseif (isset($vCalendar->VJOURNAL)) {
      $vObject = $vCalendar->VJOURNAL;
    } elseif (isset($vCalendar->VCARD)) {
      $vObject = $vCalendar->VCARD;
    } else {
      throw new Exception('Called with empty or no VComponent');
    }
    return $vObject;
  }

  /**
   * Get all components of a given type from a VCalendar object and return
   * them as flat array.
   *
   * @param VCalendar $vCalendar VCalendar object.
   *
   * @param VCalendarType $type Defaults to 'VEVENT'
   *
   * @return array
   */
  private static function getAllVObjects(VCalendar $vCalendar, VCalendarType $type = VCalendarType::VEVENT):array
  {
    $vObjects = [];
    foreach ($vCalendar->children() as $child) {
      if (!($child instanceof VComponent)) {
        continue;
      }

      if ($child->name !== $type->value) {
        continue;
      }

      $vObjects[] = $child;
    }
    return $vObjects;
  }

  /**
   * Determine if the given event is recurring or not.
   *
   * @param VComponent $vComponent Sabre VCalendar or VEvent object.
   *
   * @return bool
   */
  private static function isEventRecurring(VComponent $vComponent):bool
  {
    if ($vComponent instanceof VCalendar) {
      // get the inner object
      $vObject = self::getVObject($vComponent);
    } else {
      $vObject = $vComponent;
    }
    return isset($vObject->RRULE) || isset($vObject->RDATE);
  }

  /**
   * @param VComponent $vObject VEvent or anything else with DTEND or DTSTART
   * + DURATION.
   *
   * @return mixed DTEND property if set, otherwise DTSTART + duration.
   */
  private static function getDTEnd(VComponent $vObject):VProperty\DateTime
  {
    if ($vObject->DTEND) {
      return $vObject->DTEND;
    }
    $dtEnd = clone $vObject->DTSTART;
    if ($vObject->DURATION) {
      $duration = strval($vObject->DURATION);
      $invert = 0;
      if ($duration[0] == '-') {
        $duration = substr($duration, 1);
        $invert = 1;
      }
      if ($duration[0] == '+') {
        $duration = substr($duration, 1);
      }
      $interval = new DateInterval($duration);
      $interval->invert = $invert;
      $dtEnd->getDateTime()->add($interval);
    }
    return $dtEnd;
  }

  /**
   * Return the category list for the given object
   *
   * @param VComponent $vComponent Sabre VCalendar or VEvent object.
   *
   * @return An array with the categories for the object.
   */
  private static function getCategories(VComponent $vComponent):array
  {
    if ($vComponent instanceof VCalendar) {
      // get the inner object
      $vObject = self::getVObject($vComponent);
    } else {
      $vObject = $vComponent;
    }
    return isset($vObject->CATEGORIES) ? $vObject->CATEGORIES->getParts() : [];
  }
}
