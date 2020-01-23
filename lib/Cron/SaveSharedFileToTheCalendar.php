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


namespace OCA\FilesGFTrackDownloads\Cron;


use OC\BackgroundJob\TimedJob;
//use OCP\ILogger;
//use OCA\DAV\CalDAV\CalDavBackend;
use OCA\FilesGFTrackDownloads\Calendar\CalendarEventForSharedFileWithExpiration;

class SaveSharedFileToTheCalendar extends TimedJob
{
    /**
     * @var CalDavBackend
     */
    //private $calDavBackend;
    /**
     * @var OCA\FilesGFTrackDownloads\Calendar\CalendarEventForSharedFileWithExpiration
     */
    private $calEventForFileWithExpiration;

    /**
     *
     */
    public function __construct(CalendarEventForSharedFileWithExpiration $calEventForFileWithExpiration)
    {
        $this->setInterval(-43200); // sets the correct interval for this timed job

        //die('ulazi');

        //$this->calDavBackend = $calDavBackend;
        $this->calEventForFileWithExpiration = $calEventForFileWithExpiration;
    }

    /**
     * Makes the background job do its work
     *
     * @param array $argument unused argument
     */
    public function run($argument)
    {
        //die('ulazi 2');

        return $this->calEventForFileWithExpiration->creteCalendarAndEventForUser();
    }

}