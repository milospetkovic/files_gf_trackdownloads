<?php


namespace OCA\FilesGFTrackDownloads\Calendar;


use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarObject;
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
     * CalendarEventForSharedFileWithExpiration constructor.
     * @param CalDavBackend $calDavBackend
     */
    public function __construct(CalDavBackend $calDavBackend)
    {
        $this->calDavBackend = $calDavBackend;
        $this->connection = \OC::$server->getDatabaseConnection();
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

    public function createCalendarEvent($calendarID, $shareData)
    {
        // uuid for .ics
        $uri = strtoupper(UUIDUtil::getUUID()).'.ics';

        $userInitiatorForShare = $shareData['uid_initiator'];

        $shareTarget = $shareData['file_target'];

        // the datetime when calendar event object is created
        $createdDateTime = date('Ymd\THis\Z');

        // uuid for calendar object itself
        $calObjectUUID = strtolower(UUIDUtil::getUUID());

        // the name for calendar event
        $eventSummary = "User $userInitiatorForShare shared '$shareTarget' with you.";

        // set end datetime of calendar event depending on date set in share expiration field
        $endDateTimeOfEvent = date('Ymd\THis\Z', strtotime($shareData['expiration']));

        // populate calendar event with data
        $calData = <<<EOD
BEGIN:VCALENDAR
VERSION:2.0
PRODID:ownCloud Calendar
BEGIN:VEVENT
CREATED;VALUE=DATE-TIME:$createdDateTime
UID:$calObjectUUID
LAST-MODIFIED;VALUE=DATE-TIME:$createdDateTime
DTSTAMP;VALUE=DATE-TIME:$createdDateTime
SUMMARY:$eventSummary
DTSTART;VALUE=DATE-TIME:$createdDateTime
DTEND;VALUE=DATE-TIME:$endDateTimeOfEvent
CLASS:PUBLIC
END:VEVENT
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
                $newCalendarID =  $this->calDavBackend->createCalendar(self::CALENDAR_PRINCIPAL_URI_PREFIX . $calendarForUser, self::CALENDAR_URI, ['{DAV:}displayname' => $this->calendarDisplayName]);
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