<?php


namespace OCA\FilesGFTrackDownloads\Service;


use OCA\Activity\CurrentUser;
use OCA\FilesGFTrackDownloads\Manager\FileCacheManager;
use OCA\FilesGFTrackDownloads\Manager\ShareManager;
use OCP\IDBConnection;
use OCP\IL10N;

class FileService
{
    /**
     * @var IL10N
     */
    private $l;
    /**
     * @var FileCacheManager
     */
    private $fileCacheManager;
    /**
     * @var ShareManager
     */
    private $shareManager;
    /**
     * @var ActivityService
     */
    private $activityService;
    /**
     * @var IDBConnection
     */
    private $connection;
    /**
     * @var string
     */
    private $currentUser;

    /**
     * FileService constructor.
     * @param CurrentUser $UserId
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
                                IDBConnection $connection
                                )
    {
        $this->currentUser = $currentUser;
        $this->l = $l;
        $this->fileCacheManager = $fileCacheManager;
        $this->shareManager = $shareManager;
        $this->activityService = $activityService;
        $this->connection = $connection;
    }

    /**
     * Mark file as confirmed and save info about confirmation to the activity stream
     *
     * @param $fileID
     * @return false|string
     * @throws \OCP\Files\NotFoundException
     */
    public function confirm($fileID, $jsonResponse=true)
    {
        $error = 0;
        $error_msg = '';

        $this->connection->beginTransaction();

        // check up if file is already confirmed
        $alreadyConfirmed = $this->fileCacheManager->checkUpIfFileOrFolderIsAlreadyConfirmed($fileID);
        if ($alreadyConfirmed) {
            $error++;
            $error_msg = $this->l->t('File is already confirmed');
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
            $result = $this->fileCacheManager->markFileIDAsConfirmed($fileID);
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
     * Mark provided array of files as confirmed and save info about confirmation to the activity stream
     *
     * @param $files
     * @return false|string
     * @throws \OCP\Files\NotFoundException
     */
    public function confirmSelectedFiles($files)
    {
        $error = 0;
        $error_msg = '';

        if (is_array($files) && count($files)) {
            foreach ($files as $fileID) {
                $res = $this->confirm($fileID, false);
                if ($res['error']) {
                    $error++;
                    $error_msg = $this->l->t('Error marking file as confirmed').': "'.$fileID.'"';
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