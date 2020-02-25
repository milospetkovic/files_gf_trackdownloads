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
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FilesGFTrackDownloads\Manager\ElbCommonManager;
use OCA\FilesGFTrackDownloads\Manager\FileCacheManager;
use OCA\FilesGFTrackDownloads\Manager\ShareManager;
use OCA\FilesGFTrackDownloads\Manager\UserGroupManager;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Share\IShare as ShareTypeConstants;

class ShareService
{
    /**
     * @var CurrentUser
     */
    private $currentUser;
    /**
     * @var IL10N
     */
    private $l;
    /**
     * @var FileCacheManager
     */
    private $fileCacheManager;
    /**
     * @var ActivityService
     */
    private $activityService;
    /**
     * @var ShareManager
     */
    private $shareManager;
    /**
     * @var IDBConnection
     */
    private $connection;
    /**
     * @var UserGroupManager
     */
    private $userGroupManager;
    /**
     * @var FederatedShareProvider
     */
    private $federatedShareProvider;
    /**
     * @var ElbCommonManager
     */
    private $elbCommonManager;

    /**
     * ShareService constructor.
     * @param CurrentUser $currentUser
     * @param IL10N $l
     * @param FileCacheManager $fileCacheManager
     * @param ShareManager $shareManager
     * @param ActivityService $activityService
     * @param IDBConnection $connection
     * @param UserGroupManager $userGroupManager
     * @param FederatedShareProvider $federatedShareProvider
     * @param ElbCommonManager $elbCommonManager
     */
    public function __construct(CurrentUser $currentUser,
                                IL10N $l,
                                FileCacheManager $fileCacheManager,
                                ShareManager $shareManager,
                                ActivityService $activityService,
                                IDBConnection $connection,
                                UserGroupManager $userGroupManager,
                                FederatedShareProvider $federatedShareProvider,
                                ElbCommonManager $elbCommonManager)
    {
        $this->currentUser = $currentUser;
        $this->l = $l;
        $this->fileCacheManager = $fileCacheManager;
        $this->shareManager = $shareManager;
        $this->activityService = $activityService;
        $this->connection = $connection;
        $this->userGroupManager = $userGroupManager;
        $this->federatedShareProvider = $federatedShareProvider;
        $this->elbCommonManager = $elbCommonManager;
    }

    /**
     * Mark shared file as confirmed and save info about confirmation to the activity stream
     *
     * @param $shareID
     * @param bool $jsonResponse
     * @return array (option for json format)
     * @throws NotFoundException
     * @throws \Exception
     */
    public function confirm($shareID, $jsonResponse=true)
    {
        $error = 0;
        $error_msg = '';

        $this->connection->beginTransaction();

        // get share record
        $getShareRecord = $this->shareManager->getRawShare($shareID);
        if (!(is_array($getShareRecord) && count($getShareRecord))) {
            $error++;
            $error_msg = $this->l->t('Error fetching share record with id: ').$shareID;
        }

        if (!$error) {
            // get file id from shared record
            $fileID = $getShareRecord['file_source'];
            if (!($fileID > 0)) {
                $error++;
                $error_msg = $this->l->t('File id has not been found');
            }
        }

        // check up if shared file is already confirmed
        if (!$error) {
            $alreadyConfirmed = $this->shareManager->checkUpIfFileOrFolderIsAlreadyConfirmed($shareID);
            if ($alreadyConfirmed) {
                $error++;
                $error_msg = $this->l->t('File is already confirmed');
            }
        }

        // check up if file/folder is shared with user and check up expiration date
        if (!$error) {
            $result = $this->shareManager->checkUpFileIDIsSharedWithUser($fileID, $this->currentUser->getUID());
            if ($result['error']) {
                $error++;
                $error_msg = $result['error_msg'];
            }
        }

        // mark file as confirmed
        if (!$error) {
            $result = $this->shareManager->markSharedFileIDAsConfirmed($shareID);
            if (!($result > 0)) {
                $error++;
                $error_msg = $this->l->t('Error marking file as confirmed');
            }
        }

        // save confirmation to the activity
        if (!$error) {
            $this->activityService->saveFileConfirmationToActivity($fileID);
        }

        $response = [
            'error' => $error,
            'error_msg' => $error_msg
        ];

        ($error) ? $this->connection->rollBack() : $this->connection->commit();

        if ($jsonResponse) {
            return json_encode($response);
        }

        return $response;
    }

    /**
     * Mark provided array of shared files as confirmed and save info about confirmation to the activity stream
     *
     * @param $files
     * @return false|string
     * @throws NotFoundException
     */
    public function confirmSelectedSharedFiles($files)
    {
        $error = 0;
        $error_msg = '';

        if (is_array($files) && count($files)) {
            foreach ($files as $shareID) {
                $res = $this->confirm($shareID, false);
                if ($res['error']) {
                    $error++;
                    $error_msg = $this->l->t('Error marking file as confirmed').': "'.$shareID.'"';
                    break;
                }
            }
        } else {
            $error++;
            $error_msg = $this->l->t('Select at least one file for confirmation');
        }

        $response = [
            'error' => $error,
            'error_msg' => $error_msg
        ];

        return json_encode($response);
    }

    /**
     * Confirm shared file with user by sending shared file id for confirming
     *
     * @param $fileID
     * @return bool|false|string
     * @throws NotFoundException
     */
    public function confirmSharedFileByFileID($fileID)
    {
        $getShareRecordForUser = $this->shareManager->getShareRecordForUserAndForFileID($this->currentUser->getUID(), $fileID);
        if ((is_array($getShareRecordForUser))) {
            return $this->confirm($getShareRecordForUser['id']);
        }
        return json_encode([
            'error' => 1,
            'error_msg' => $this->l->t('Shared file is not found or it is not shared with you')]);
    }

    public function createSharesForUsersInUserGroup()
    {
        $shareForUserGroup = $this->shareManager->getSharePerUserGroupWithoutLinkedShareForUsers();
        if (is_array($shareForUserGroup) && count($shareForUserGroup)) {
            foreach ($shareForUserGroup as $ind => $data) {
                $shareID = $data['id'];
                $userGroupID = $data['share_with'];
                $getUsersInUserGroup = $this->userGroupManager->getUsersIDsPlacedInUserGroupID($userGroupID);
                if (is_array($getUsersInUserGroup) && count($getUsersInUserGroup)) {
                    foreach ($getUsersInUserGroup as $arr) {
                        $userID = $arr['uid'];
                        $getShare = $this->shareManager->getRawShare($shareID);
                        unset($getShare['id']);
                        $getShare['share_type'] = ShareTypeConstants::TYPE_USER;
                        $getShare['share_with'] = $userID;
                        $getShare['elb_share_for_user_group'] = $shareID;

                        $createShareRecordForUser = $this->elbCommonManager->insert('oc_share', $getShare);

                        $test1 = 'test1';
                        $test2 = 'test2';

                        //$res = $this->federatedShareProvider->createShareObject($arr);

                    }
                }
            }
        }
        var_dump($shareForUserGroup);
        die('ok ovde prekini!');
    }

}