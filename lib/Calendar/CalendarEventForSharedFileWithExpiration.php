<?php


namespace OCA\FilesGFTrackDownloads\Calendar;


use OCA\DAV\CalDAV\CalDavBackend;
use Sabre\DAV\Exception;

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
                    $this->createCalendarForUserIfCalendarNotExists($elem['share_with']);
                }
                $connection->commit();
                var_dump('ALL OK!');
            } catch (\Exception $e) {
                $connection->rollBack();
                echo 'Exception: '.$e->getMessage();
                echo ' DB error: '.$connection->errorInfo();
                var_dump('ERROR CREATING CALENDAR');
            }
        }

        var_dump($shareRows);

        $stmt->closeCursor();
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
                        $this->checkedIfCalendarExistsForUser[$calendarForUser] = $calendarForUser;
                        return $arr['id'];
                    }
                }
            }

            // calendar doesn't exist -> create a calendar for the user
            try {
                return $this->calDavBackend->createCalendar(self::CALENDAR_PRINCIPAL_URI_PREFIX . $calendarForUser, self::CALENDAR_URI, ['{DAV:}displayname' => $this->calendarDisplayName]);
            } catch (Exception $e) {
                return null;
            }
        } else {
            return $this->checkedIfCalendarExistsForUser[$calendarForUser];
        }

        return null;
    }
}