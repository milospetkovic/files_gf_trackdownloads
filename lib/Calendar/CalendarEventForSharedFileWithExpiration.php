<?php


namespace OCA\FilesGFTrackDownloads\Calendar;


use OCA\DAV\CalDAV\CalDavBackend;
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
     *
     */
    public function __construct(CalDavBackend $calDavBackend)
    {
        $this->calDavBackend = $calDavBackend;
    }


    public function creteCalendarAndEventForUser()
    {
        $connection = \OC::$server->getDatabaseConnection();

        $stmt = $connection->prepare(
            'SELECT `id`, `share_with`, `expiration` FROM `*PREFIX*share` WHERE `elb_calendar_object_id` is null AND `expiration` is NOT null'
        );
        $stmt->execute();

        $shareRows = [];
        while ($row = $stmt->fetch()) {
            $shareRows[] = $row;
        }

        if (count($shareRows)) {

            $connection->beginTransaction();

            try {
                foreach ($shareRows as $ind => $elem) {
                    //if ($ind == 0) { // @TODO - remove this after successful implementation
                        $calendarID = $this->createCalendarForUserIfCalendarNotExists($elem['share_with']);
                        $this->createCalendarEvent($calendarID, $elem);
                    //}
                }
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                echo 'Exception: '.$e->getMessage();
                echo ' DB error: '.$connection->errorInfo();
                //var_dump('ERROR CREATING CALENDAR');
            }
        }

        //var_dump($shareRows);

        $stmt->closeCursor();
    }

    public function createCalendarEvent($calendarID, $shareData)
    {
        // uuid for .ics
        $uri = strtoupper(UUIDUtil::getUUID()).'.ics';

        // the datetime when calendar event object is created
        $createdDateTime = date('Ymd\THis\Z');

        // uuid for calendar object itself
        $calObjectUUID = strtolower(UUIDUtil::getUUID());

        // the name for calendar event
        $eventSummary = 'The name of calendar event -v2!';

        // set end datetime of calendar event depending on date set in share expiration field
        $endDateTimeOfEvent = date('Ymd\THis\Z', strtotime($shareData['expiration']));

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

        $response = $this->calDavBackend->createCalendarObject($calendarID, $uri, $calData);

        return $response;
    }

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

                        var_dump('Calendar postoji '.$arr['id']);
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
        } else {
            return $this->checkedIfCalendarExistsForUser[$calendarForUser];
        }

        return null;
    }
}