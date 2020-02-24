<?php
/**
 * @copyright Copyright (c) 2020 Milos Petkovic <milos.petkovic@elb-solutions.com>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FilesGFTrackDownloads\Manager;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IDBConnection;
use OCP\IL10N;
use Sabre\DAV\Exception;
use Sabre\DAV\UUIDUtil;

class CalendarManager
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
     * @var UserGroupManager
     */
    private $userGroupManager;

    /**
     * CalendarManager constructor.
     * @param CalDavBackend $calDavBackend
     * @param IL10N $l
     * @param IDBConnection $connection
     * @param UserGroupManager $userGroupManager
     */
    public function __construct(CalDavBackend $calDavBackend,
                                IL10N $l,
                                IDBConnection $connection,
                                UserGroupManager $userGroupManager)
    {
        $this->calDavBackend = $calDavBackend;
        $this->l = $l;
        $this->connection = $connection;
        $this->userGroupManager = $userGroupManager;
    }

    /**
     * Translation for calendar's name
     *
     * @return string
     */
    private function getCalendarDisplayName()
    {
        return $this->l->t($this->calendarDisplayName);
    }

    /**
     * Create a calendar which will hold all events for shared files with expiration date for a user (if the calendar doesn't exist)
     * and create event(s) in the calendar
     */
    public function creteCalendarAndEventForUser()
    {
        // get all shared files with expiration date which don't have created calendar event
        $rows  = $this->getSharedFilesWithExpDateWithoutLinkedCalendarEventWhichAreNotConfirmed();

        // itterate throw fetched share records
        if (count($rows)) {

            // start transaction
            $this->connection->beginTransaction();

            try {
                foreach ($rows as $ind => $elem) {
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
    }

    /**
     * Get shared files which have expiration date and which are without it's linked calendar event
     *
     * @return array
     */
    public function getSharedFilesWithExpDateWithoutLinkedCalendarEventWhichAreNotConfirmed()
    {
        $stmt = $this->connection->prepare(
            'SELECT `sh`.`id`, `sh`.`share_type`, `sh`.`share_with`, `sh`.`expiration`, `sh`.`file_target`, `sh`.`uid_initiator`
                 FROM `*PREFIX*share` as sh
                 LEFT JOIN `*PREFIX*filecache` as fc on `fc`.`fileid`=`sh`.`file_source` 
                 WHERE `sh`.`elb_calendar_object_id` is null 
                 AND `sh`.`expiration` is NOT null
                 and `sh`.`elb_confirmed` is null'
        );
        $stmt->execute();

        // place all fetched data into the array
        $shareRows = [];
        while ($row = $stmt->fetch()) {
            if ($row['share_type'] == 0) { // shared for user
                $shareRows[] = $row;
            } elseif($row['share_type'] == 1) { // shared for user group
                $getUsersInUserGroup = $this->userGroupManager->getUsersIDsPlacedInUserGroupID($row['share_with']);
                if (is_array($getUsersInUserGroup) && count($getUsersInUserGroup)) {
                    foreach($getUsersInUserGroup as $resInd => $userID) {
                        $row['share_with'] = $getUsersInUserGroup[$resInd]['uid'];
                        $shareRows[] = $row;
                    }
                }
            }
        }
        $stmt->closeCursor();

        return $shareRows;
    }

    /**
     * Replace placeholders with data from parameters variable
     *
     * @param $subject
     * @param array $parameters
     * @return string|string[]
     */
    private function translateSharedFileCalenderEvent($subject, array $parameters)
    {
        foreach ($parameters as $paramKey => $paramVal) {
            $subject = str_replace('{'.$paramKey.'}', $paramVal, $subject);
        }
        return $subject;
    }

    /**
     * Create calendar event for shared file with expiration date
     *
     * @param $calendarID
     * @param $shareData
     * @return bool
     * @throws Exception\BadRequest
     */
    public function createCalendarEvent($calendarID, $shareData)
    {
        // uuid for .ics
        $calDataUri[0] = strtoupper(UUIDUtil::getUUID()) . '.ics';

        $userInitiatorForShare = $shareData['uid_initiator'];

        $shareTarget = trim($shareData['file_target'], '/');

        $currentTimeFormat = date('Ymd');

        // the datetime when calendar event object is created
        $createdDateTime = date('Ymd\THis\Z');

        // the start date time of calendar event
        $startDateTimeOfEvent = $createdDateTime;

        // uuid for calendar object itself
        $calObjectUUID[0] = strtolower(UUIDUtil::getUUID());

        // the name for calendar event
        $eventSummaryRaw = $this->l->t("User {user} shared {file} with you");
        $eventSummary = $this->translateSharedFileCalenderEvent($eventSummaryRaw, ['user' => $userInitiatorForShare, 'file' => $shareTarget]);

        // the end datetime of calendar event
        $endDateTimeOfEvent = $startDateTimeOfEvent;

        $endDateTimeFormat = date('Ymd', strtotime($shareData['expiration']));

        // check up if event should be splited
        $splitEvent = $currentTimeFormat < $endDateTimeFormat;

        $timeZone = 'Europe/Belgrade';

        // populate start calendar event with data
        $calData[0] = <<<EOD
BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 2.0.1//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:$createdDateTime
DTSTAMP:$createdDateTime
LAST-MODIFIED:$createdDateTime
SEQUENCE:2
UID:$calObjectUUID[0]
DTSTART;TZID=$timeZone:$startDateTimeOfEvent
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

        // the second event for the same file (the last date reminder)
        if ($splitEvent) {

            $calDataUri[1] = strtoupper(UUIDUtil::getUUID()) . '.ics';

            // uuid for calendar object itself
            $calObjectUUID[1] = strtolower(UUIDUtil::getUUID());

            // the start date time of calendar event
            $lastEventDateTime = date('Y-m-d 09:i:s', strtotime($shareData['expiration']));

            $startDateTimeOfEvent = $endDateTimeOfEvent = date('Ymd\THis\Z', strtotime($lastEventDateTime));;

            // populate end calendar event with data
            $calData[1] = <<<EOD
BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 2.0.1//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:$createdDateTime
DTSTAMP:$createdDateTime
LAST-MODIFIED:$createdDateTime
SEQUENCE:2
UID:$calObjectUUID[1]
DTSTART;TZID=$timeZone:$startDateTimeOfEvent
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
        }

        $calendarType = CalDavBackend::CALENDAR_TYPE_CALENDAR;

        // call method which executes creating calendar event(s)
        foreach ($calData as $ind => $calEventData) {

            $response = $this->calDavBackend->createCalendarObject($calendarID, $calDataUri[$ind], $calEventData, $calendarType);

            if (strlen($response)) {

                // save the latest calendar event id to the share record
                // in case when there's only the start event (confirmation is shared for current day)
                // then the start event id will be saved to the share record
                if (count($calData) == ($ind + 1)) {
                    // fetch newly created calendar event
                    $event = $this->calDavBackend->getCalendarObject($calendarID, $calDataUri[$ind], $calendarType);
                    if (is_array($event)) {
                        $eventID = $event['id'];
                        $res = $this->setCalendarEventIdToTheShareRecord($shareData['id'], $eventID);
                        if (!$res) {
                            return false; // error during saving cal. event id to the share record
                        }
                    }
                }
            } else {
                return false; // error during saving cal. event
            }
        }

        return true;
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