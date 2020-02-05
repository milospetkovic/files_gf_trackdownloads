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

namespace OCA\FilesGFTrackDownloads\Activity;

use OC\Files\Filesystem;
use OCA\FilesGFTrackDownloads\CurrentUser;
use OCA\GroupFolders\AppInfo\Application;
use OCA\GroupFolders\Folder\FolderManager;
use OCP\Activity\IManager;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IGroupManager;
use OCA\FilesGFTrackDownloads\Manager\GroupFolderManager;

class Listener {
    /** @var IRequest */
    protected $request;
    /** @var IManager */
    protected $activityManager;
    /** @var IURLGenerator */
    protected $urlGenerator;
    /** @var IRootFolder */
    protected $rootFolder;
    /** @var CurrentUser */
    protected $currentUser;
    /** @var ILogger */
    protected $logger;
    /** @var IGroupManager */
    protected $groupManager;
    /** @var GroupFolderManager */
    protected $groupFolderManager;

    /**
     * @param IRequest $request
     * @param IManager $activityManager
     * @param IURLGenerator $urlGenerator
     * @param IRootFolder $rootFolder
     * @param CurrentUser $currentUser
     * @param ILogger $logger
     * @param IGroupManager $groupManager
     * @param FolderManager $groupFolderManager
     */
    public function __construct(IRequest $request, IManager $activityManager, IURLGenerator $urlGenerator, IRootFolder $rootFolder, CurrentUser $currentUser, ILogger $logger, IGroupManager $groupManager, GroupFolderManager $groupFolderManager) {
        $this->request = $request;
        $this->activityManager = $activityManager;
        $this->urlGenerator = $urlGenerator;
        $this->rootFolder = $rootFolder;
        $this->currentUser = $currentUser;
        $this->logger = $logger;
        $this->groupManager = $groupManager;
        $this->groupFolderManager = $groupFolderManager;
    }

    /**
     * Store the update hook events
     * @param string $path Path of the file that has been read
     */
    public function readFile($path) {

        $appFilePath = ltrim($path, '/');

        $expAppFilePath = explode('/', $appFilePath);

        $firstFolderForAppFilePath = $expAppFilePath[0];

        $groupFolderID = $this->groupFolderManager->getGroupFolderIdByGroupFolderName($firstFolderForAppFilePath);

        // fetch
        if ($groupFolderID > 0) {
            $assignedGroups = $this->groupFolderManager->getAssignedGroupsIdsToGroupFolderId($groupFolderID);

            $defaultAdminGroup = 'admin';

            if (!in_array($defaultAdminGroup, $assignedGroups)) {
                $assignedGroups[] = $defaultAdminGroup;
            }

            // Do not add activities for .part-files
            if (substr($path, -5) === '.part') {
                return;
            }

            if ($this->currentUser->getUID() === null) {
                // User is not logged in, this download is handled by the files_sharing app
                return;
            }

            try {
                list($filePath, $owner, $fileId, $isDir) = $this->getSourcePathAndOwner($path);
            } catch (NotFoundException $e) {
                return;
            } catch (InvalidPathException $e) {
                return;
            }

//        if ($this->currentUser->getUID() === $owner) {
//            // Could not find the file for the owner ...
//            return;
//        }

            $client = 'web';
            if ($this->request->isUserAgent([IRequest::USER_AGENT_CLIENT_DESKTOP])) {
                $client = 'desktop';
            } else if ($this->request->isUserAgent([IRequest::USER_AGENT_CLIENT_ANDROID, IRequest::USER_AGENT_CLIENT_IOS])) {
                $client = 'mobile';
            }

            // Check if Current User is Guest
            if ($this->currentUser->getUserIdentifier() === '') {
                $requestor = 'Anonymous ' . $_SERVER['REMOTE_ADDR'];
            } else {
                $requestor = $this->currentUser->getUserIdentifier();
            }

            $subjectParams = [[$fileId => $filePath], $requestor, $client];

            if ($isDir) {
                $subject = Provider::SUBJECT_SHARED_GF_FOLDER_DOWNLOADED;
                $linkData = [
                    'dir' => $filePath,
                ];
            } else {
                $subject = Provider::SUBJECT_SHARED_GF_FILE_DOWNLOADED;
                $parentDir = (substr_count($filePath, '/') === 1) ? '/' : dirname($filePath);
                $fileName = basename($filePath);
                $linkData = [
                    'dir' => $parentDir,
                    'scrollto' => $fileName,
                ];
            }

            $timeStamp = time();

            if (is_array($assignedGroups) && count($assignedGroups)) {

                foreach ($assignedGroups as $assignedGroupName) {

                    $usersInGroup = $this->groupManager->get($assignedGroupName)->getUsers();

                    if (is_array($usersInGroup) && count($usersInGroup)) {
                        foreach ($usersInGroup as $user) {

//                            if ($user->getUID() === $owner) {
//                                continue;
//                            }

                            try {
                                $event = $this->activityManager->generateEvent();
                                $event->setApp('files_gf_trackdownloads')
                                    ->setType('file_gf_downloaded')
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
    }

    /**
     * @param string $path
     * @return array
     * @throws NotFoundException
     * @throws InvalidPathException
     */
    protected function getSourcePathAndOwner($path) {
        $currentUserId = $this->currentUser->getUID();
        $userFolder = $this->rootFolder->getUserFolder($currentUserId);
        $node = $userFolder->get($path);
        $owner = $node->getOwner()->getUID();

        if ($owner !== $currentUserId) {
            $storage = $node->getStorage();
            if (!$storage->instanceOfStorage('OCA\Files_Sharing\External\Storage')) {
                Filesystem::initMountPoints($owner);
            } else {
                // Probably a remote user, let's try to at least generate activities
                // for the current user
                $owner = $currentUserId;
            }

            $ownerFolder = $this->rootFolder->getUserFolder($owner);
            $nodes = $ownerFolder->getById($node->getId());

            if (empty($nodes)) {
                throw new NotFoundException($node->getPath());
            }

            $node = $nodes[0];
            $path = substr($node->getPath(), strlen($ownerFolder->getPath()));
        }

        return [
            $path,
            $owner,
            $node->getId(),
            $node instanceof Folder/* || $node instanceof GroupFolder*/
        ];
    }
}
