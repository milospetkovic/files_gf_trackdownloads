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


use OC\Files\View;
use OCA\Activity\CurrentUser;
use OCA\Activity\Data;
use OCA\FilesGFTrackDownloads\Activity\Setting;
use OCA\FilesGFTrackDownloads\Manager\GroupFolderManager;
use OCP\Activity\IManager;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\ILogger;
use OCA\FilesGFTrackDownloads\Activity\Provider;
use OC\Files\Filesystem;
use OCP\IURLGenerator;

class ActivityService
{
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
     * @var Setting
     */
    private $activitySetting;
    /**
     * @var View
     */
    private $view;
    /**
     * @var GroupFolderManager
     */
    private $groupFolderManager;
    /**
     * @var IURLGenerator
     */
    private $urlGenerator;
    /**
     * @var IGroupManager
     */
    private $groupManager;

    /**
     * populate array to avoid saving to user's activity
     * if activity is already saved for the user
     * (case when one use is placed in more than one user group)
     *
     * @var array
     */
    private $activitySavedForUserID = [];

    /**
     * ActivityService constructor.
     * @param IManager $activityManager
     * @param IDBConnection $connection
     * @param ILogger $logger
     * @param CurrentUser $currentUser
     * @param Data $activityData
     * @param Setting $activitySetting
     * @param View $view
     * @param IGroupManager $groupManager
     * @param GroupFolderManager $groupFolderManager
     * @param IURLGenerator $urlGenerator
     */
    public function __construct(IManager $activityManager,
                                IDBConnection $connection,
                                ILogger $logger,
                                CurrentUser $currentUser,
                                Data $activityData,
                                Setting $activitySetting,
                                View $view,
                                IGroupManager $groupManager,
                                GroupFolderManager $groupFolderManager,
                                IURLGenerator $urlGenerator)
    {
        $this->activityManager = $activityManager;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->currentUser = $currentUser;
        $this->activityData = $activityData;
        $this->activitySetting = $activitySetting;
        $this->view = $view;
        $this->groupManager = $groupManager;
        $this->groupFolderManager = $groupFolderManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Save confirmation of file to the activity
     *
     * @param $fileID
     * @return bool
     * @throws NotFoundException
     */
    public function saveFileConfirmationToActivity($fileID)
    {
        $app = 'files_gf_trackdownloads';
        $type = $this->activitySetting->getIdentifier();

        $this->resetActivitySavedForUserID();

        $currentUserID = $this->currentUser->getUID();

        $subject = Provider::SUBJECT_GF_FILE_CONFIRMED;
        $objectType = 'files';
        $fileId = $fileID;
        $path = Filesystem::getPath($fileID);

        $linkData = [
            'dir' => $path
        ];

        $link = $this->urlGenerator->linkToRouteAbsolute('files.view.index', $linkData); // here link to the file
        $subjectParams = [[$fileId => $path], $this->currentUser->getUserIdentifier()];

        try {
            $event = $this->activityManager->generateEvent();
            $event->setApp($app)
                ->setType($type)
                ->setAffectedUser($currentUserID)
                ->setTimestamp(time())
                ->setSubject($subject, $subjectParams)
                ->setObject($objectType, $fileId, $path)
                ->setLink($link);

            if ($currentUserID !== null) {
                // Allow this to be empty for guests
                $event->setAuthor($currentUserID);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->logException($e);
        }

        // Add activity to stream to the user
        $res = $this->activityData->send($event);
        if (!$res) {
            return false;
        }

        $this->activitySavedForUserID[] = $currentUserID;

        // check up if confirmed file is placed in group folder
        $groupFolderID = $this->groupFolderManager->getGroupFolderIDByFilePath($path);

        if ($groupFolderID > 0) {

            // get assigned user groups to the group folder
            $assignedGroups = $this->groupFolderManager->getAssignedGroupsIdsToGroupFolderId($groupFolderID);

            // save activity to each user from assigned user group(s) for group folder
            if (is_array($assignedGroups) && count($assignedGroups)) {

                foreach ($assignedGroups as $assignedGroupName) {

                    // get all users in user group
                    $usersInGroup = $this->groupManager->get($assignedGroupName)->getUsers();

                    if (is_array($usersInGroup) && count($usersInGroup)) {
                        foreach ($usersInGroup as $user) {

                            if (in_array($user->getUID(), $this->activitySavedForUserID)) {
                                continue;
                            }

                            $this->activitySavedForUserID[] = $user->getUID();

                            try {
                                $event = $this->activityManager->generateEvent();
                                $event->setApp($app)
                                    ->setType($type)
                                    ->setAffectedUser($user->getUID())
                                    ->setTimestamp(time())
                                    ->setSubject($subject, $subjectParams)
                                    ->setObject($objectType, $fileId, $path)
                                    ->setLink($link);

                                if ($currentUserID !== null) {
                                    // Allow this to be empty for guests
                                    $event->setAuthor($currentUserID);
                                }
                                $this->activityManager->publish($event);
                            } catch (\InvalidArgumentException $e) {
                                $this->logger->logException($e, [
                                    'app' => 'files_gf_trackdownloads',
                                ]);
                            } catch (\BadMethodCallException $e) {
                                $this->logger->logException($e, [
                                    'app' => 'files_gf_trackdownloads',
                                ]);
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Save to the activity download of file from group folder for each user assigned to the group folder
     *
     * @param $assignedGroups
     * @param $subject
     * @param $subjectParams
     * @param $fileId
     * @param $filePath
     * @param $linkData
     */
    public function saveToActivityDownloadOfFileInGroupFolder($assignedGroups, $subject, $subjectParams, $fileId, $filePath, $linkData)
    {
        // current timestamp
        $timeStamp = time();

        $this->resetActivitySavedForUserID();

        // save activity to each user from assigned user group(s) for group folder
        if (is_array($assignedGroups) && count($assignedGroups)) {
            foreach ($assignedGroups as $assignedGroupName) {

                // get all users in user group
                $usersInGroup = $this->groupManager->get($assignedGroupName)->getUsers();

                if (is_array($usersInGroup) && count($usersInGroup)) {
                    foreach ($usersInGroup as $user) {

                        if (in_array($user->getUID(), $this->activitySavedForUserID)) {
                            continue;
                        }

                        $this->activitySavedForUserID[] = $user->getUID();

                        try {
                            $event = $this->activityManager->generateEvent();
                            $event->setApp('files_gf_trackdownloads')
                                ->setType('file_gf')
                                ->setAffectedUser($user->getUID())
                                //->setAuthor($this->currentUser->getUID())
                                ->setTimestamp($timeStamp)
                                ->setSubject($subject, $subjectParams)
                                ->setObject('files', $fileId, $filePath)
                                ->setLink($this->urlGenerator->linkToRouteAbsolute('files.view.index', $linkData));
                            $this->activityManager->publish($event);
                        } catch (\InvalidArgumentException $e) {
                            $this->logger->logException($e, [
                                'app' => 'files_gf_trackdownloads',
                            ]);
                        } catch (\BadMethodCallException $e) {
                            $this->logger->logException($e, [
                                'app' => 'files_gf_trackdownloads',
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function resetActivitySavedForUserID()
    {
        $this->activitySavedForUserID = [];
    }

}