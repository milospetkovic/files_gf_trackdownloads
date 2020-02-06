<?php


namespace OCA\FilesGFTrackDownloads\Calendar;


use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarObject;
use OCP\Activity\IEvent;
use OCP\IL10N;
use Sabre\DAV\Exception;
use Sabre\DAV\UUIDUtil;

class CalendarEventForSharedFileWithExpiration
{
    const CALENDAR_PRINCIPAL_URI_PREFIX = 'principals/users/';

    CONST CALENDAR_URI = 'cal-for-expiration-shared-files';

    /**
     * @var CalDavBackend
     */
    private $calDavBackend;

    private $checkedIfCalendarExistsForUser = [];

    private $calendarDisplayName = 'Shared files with expiration date';
    /**
     * @var \OCP\IDBConnection
     */
    private $connection;
    /**
     * @var IL10N
     */
    private $l;

    /**
     * CalendarEventForSharedFileWithExpiration constructor.
     * @param CalDavBackend $calDavBackend
     */
    public function __construct(CalDavBackend $calDavBackend, IL10N $l)
    {
        $this->calDavBackend = $calDavBackend;
        $this->connection = \OC::$server->getDatabaseConnection();
        $this->l = $l;
    }

    private function getCalendarDisplayName()
    {
        return $this->l->t($this->calendarDisplayName);
    }

    /**
     * Create a calendar which will hold all events for shared files with expiration date for user (if the calendar doesn't exist)
     * and create event(s) in the calendar
     */
    public function creteCalendarAndEventForUser()
    {
        // get all shared files with expiration date which don't have created calendar event
        $stmt = $this->connection->prepare(
            'SELECT `id`, `share_with`, `expiration`, `file_target`, `uid_initiator`
                 FROM `*PREFIX*share` WHERE `elb_calendar_object_id` is null AND `expiration` is NOT null'
        );
        $stmt->execute();

        // place all fetched data into the array
        $shareRows = [];
        while ($row = $stmt->fetch()) {
            $shareRows[] = $row;
        }

        // itterate throw fetched share records
        if (count($shareRows)) {

            // start transaction
            $this->connection->beginTransaction();

            try {
                foreach ($shareRows as $ind => $elem) {
                    $calendarID = $this->createCalendarForUserIfCalendarNotExists($elem['share_with']);
                    $this->createCalendarEvent($calendarID, $elem);
                }
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();
                echo 'Exception: '.$e->getMessage();
                echo ' DB error: '.$this->connection->errorInfo();
            }
        }

        $stmt->closeCursor();
    }



    private function translateSharedFileCalenderEvent($subject, array $parameters)
    {
        foreach ($parameters as $paramKey => $paramVal) {
            $subject = str_replace('{'.$paramKey.'}', $paramVal, $subject);
        }
        return $subject;
    }

    public function createCalendarEvent($calendarID, $shareData)
    {
        // uuid for .ics
        $uri = strtoupper(UUIDUtil::getUUID()).'.ics';

        $userInitiatorForShare = $shareData['uid_initiator'];

        $shareTarget = trim($shareData['file_target'], '/');

        // the datetime when calendar event object is created
        $createdDateTime = date('Ymd\THis\Z');

        // uuid for calendar object itself
        $calObjectUUID = strtolower(UUIDUtil::getUUID());

        // the name for calendar event
        $eventSummaryRaw = $this->l->t("User {user} shared {file} with you");
        $eventSummary = $this->translateSharedFileCalenderEvent($eventSummaryRaw, ['user' => $userInitiatorForShare, 'file' => $shareTarget]);

        // set end datetime of calendar event depending on date set in share expiration field
        $endDateTimeOfEvent = date('Ymd\THis\Z', strtotime($shareData['expiration']));

        $timeZone = 'Europe/Belgrade';

        // populate calendar event with data
        $calData = <<<EOD
BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 2.0.1//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:$createdDateTime
DTSTAMP:$createdDateTime
LAST-MODIFIED:$createdDateTime
SEQUENCE:2
UID:$calObjectUUID
DTSTART;TZID=$timeZone:$createdDateTime
DTEND;TZID=$timeZone:$endDateTimeOfEvent
LAST-MODIFIED;VALUE=DATE-TIME:$createdDateTime
DTSTAMP;VALUE=DATE-TIME:$createdDateTime
SUMMARY:$eventSummary
END:VEVENT
BEGIN:VTIMEZONE
TZID:$timeZone
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
END:VCALENDAR
EOD;

        $calendarType = CalDavBackend::CALENDAR_TYPE_CALENDAR;

        // call method which executes creating calendar object
        $response = $this->calDavBackend->createCalendarObject($calendarID, $uri, $calData, $calendarType);

        if (strlen($response)) {
            // fetch newly created calendar event
            $event = $this->calDavBackend->getCalendarObject($calendarID, $uri, $calendarType);
            if (is_array($event)) {
                $eventID = $event['id'];
                return $this->setCalendarEventIdToTheShareRecord($shareData['id'], $eventID);
            }
        }

        return false;
    }

    /**
     * Create a calendar for shared files which have expiration date for user (if the calendar doesn't exist, otherwise return ID of the existing calendar.
     *
     * @param $calendarForUser
     * @return int|mixed|null
     */
    public function createCalendarForUserIfCalendarNotExists($calendarForUser)
    {
        if (!array_key_exists($calendarForUser, $this->checkedIfCalendarExistsForUser)) {

            // fetch existing calendars for user
            $existingCalendarsForUser = $this->calDavBackend->getCalendarsForUser(self::CALENDAR_PRINCIPAL_URI_PREFIX.$calendarForUser);

            // check up if calendar already exists (return id of calendar in that case)
            if (is_array($existingCalendarsForUser) && count($existingCalendarsForUser)) {
                foreach ($existingCalendarsForUser as $ind => $arr) {
                    if ($arr['uri'] == self::CALENDAR_URI) {
                        $this->checkedIfCalendarExistsForUser[$calendarForUser] = $arr['id'];
                        return $arr['id'];
                    }
                }
            }

            // calendar doesn't exist -> create a calendar for the user
            try {
                $newCalendarID =  $this->calDavBackend->createCalendar(self::CALENDAR_PRINCIPAL_URI_PREFIX . $calendarForUser, self::CALENDAR_URI, ['{DAV:}displayname' => $this->getCalendarDisplayName()]);
                $this->checkedIfCalendarExistsForUser[$calendarForUser] = $newCalendarID;
                return $newCalendarID;
            } catch (Exception $e) {
                return null;
            }
        }
        return $this->checkedIfCalendarExistsForUser[$calendarForUser];
    }

    /**
     * Set ID of created calender event to it's linked share table record
     *
     * @param $shareRecordID
     * @param $calendarEventObjectID
     * @return bool
     */
    public function setCalendarEventIdToTheShareRecord($shareRecordID, $calendarEventObjectID)
    {
        $stmt = $this->connection->prepare(
            'UPDATE  `*PREFIX*share` SET `elb_calendar_object_id`='.$calendarEventObjectID.' WHERE `id`='.$shareRecordID
        );
        return $stmt->execute();
    }

}