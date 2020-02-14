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
use OCA\FilesGFTrackDownloads\Manager\FileCacheManager;
use OCA\FilesGFTrackDownloads\Manager\ShareManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

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
     * ShareService constructor.
     * @param CurrentUser $currentUser
     * @param IL10N $l
     * @param FileCacheManager $fileCacheManager
     * @param ShareManager $shareManager
     * @param ActivityService $activityService
     * @param IDBConnection $connection
     */
    public function __construct(CurrentUser $currentUser,
                                IL10N $l,
                                FileCacheManager $fileCacheManager,
                                ShareManager $shareManager,
                                ActivityService $activityService,
                                IDBConnection $connection)
    {
        $this->currentUser = $currentUser;
        $this->l = $l;
        $this->fileCacheManager = $fileCacheManager;
        $this->shareManager = $shareManager;
        $this->activityService = $activityService;
        $this->connection = $connection;
    }

    /**
     * Mark shared file as confirmed and save info about confirmation to the activity stream
     *
     * @param $shareID
     * @return false|string
     * @throws \OCP\Files\NotFoundException
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
     * @throws \OCP\Files\NotFoundException
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

}