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

}