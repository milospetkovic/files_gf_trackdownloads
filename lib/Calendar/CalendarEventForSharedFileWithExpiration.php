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
            'SELECT `id`, `share_with` FROM `*PREFIX*share` WHERE `elb_calendar_object_id` is null AND `expiration` is NOT null'
        );
        $stmt->execute();

        $shareRows = [];
        while ($row = $stmt->fetch()) {
            $shareRows[] = $row;
        }

        if (count($shareRows)) {

            $connection->beginTransaction();

            try {
                foreach ($shareRows as $elem) {
                    $calendarID = $this->createCalendarForUserIfCalendarNotExists($elem['share_with']);

                    $this->createCalendarEvent($calendarID, $elem);

                }
                $connection->commit();
                //var_dump('ALL OK!');
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

    public function createCalendarEvent($calendarID, $elem)
    {
        $uri = UUIDUtil::getUUID();

        $calData = <<<'EOD'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:ownCloud Calendar
BEGIN:VEVENT
CREATED;VALUE=DATE-TIME:20190910T125139Z
UID:47d15e3ec8
LAST-MODIFIED;VALUE=DATE-TIME:20190910T125139Z
DTSTAMP;VALUE=DATE-TIME:20190910T125139Z
SUMMARY:Test Event
DTSTART;VALUE=DATE-TIME:20190912T130000Z
DTEND;VALUE=DATE-TIME:20190912T140000Z
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR
EOD;

        $response = $this->calDavBackend->createCalendarObject($calendarID, $uri, $calData);
        var_dump($response);
//        var_dump($response);
//        die('stoppe');

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