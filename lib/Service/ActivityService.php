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

namespace OCA\FilesGFTrackDownloads\Service;


use OCA\Activity\CurrentUser;
use OCA\Activity\Data;
use OCP\Activity\IManager;
use OCP\IDBConnection;
use OCP\ILogger;

class ActivityService
{
    /** @var \OCP\Activity\IManager */
    protected $manager;
    /**
     * @var IManager
     */
    private $activityManager;
    /**
     * @var IDBConnection
     */
    private $connection;
    /**
     * @var ILogger
     */
    private $logger;
    /**
     * @var CurrentUser
     */
    private $currentUser;
    /**
     * @var Data
     */
    private $activityData;

    /**
     * ActivityService constructor.
     * @param IManager $activityManager
     * @param IDBConnection $connection
     * @param ILogger $logger
     * @param CurrentUser $currentUser
     */
    public function __construct(IManager $activityManager,
                                IDBConnection $connection,
                                ILogger $logger,
                                CurrentUser $currentUser,
                                Data $activityData)
    {
        $this->activityManager = $activityManager;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->currentUser = $currentUser;
        $this->activityData = $activityData;
    }

    public function saveFileConfirmationToActivity()
    {
        $event = $this->activityManager->generateEvent();

        $app = 'files_gf_trackdownloads';
        $type = 'file_gf_confirmed';

        $user = $this->currentUser->getUID();

        $subject = 'test';
        $subjectParams = ['subjectParams'];
        $objectType = 'files';
        $fileId = 706;
        $path = '/';
        $link = 'here link to the file';


        try {
            $event->setApp($app)
                ->setType($type)
                ->setAffectedUser($user)
                ->setTimestamp(time())
                ->setSubject($subject, $subjectParams)
                ->setObject($objectType, $fileId, $path)
                ->setLink($link);

            if ($this->currentUser->getUID() !== null) {
                // Allow this to be empty for guests
                $event->setAuthor($this->currentUser->getUID());
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->logException($e);
        }

        // Add activity to stream
        if (true) {
            $this->activityData->send($event);
        }


    }

}