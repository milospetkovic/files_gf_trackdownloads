<?php


namespace OCA\FilesGFTrackDownloads\Cron;


use OC\BackgroundJob\TimedJob;
use OCA\FilesGFTrackDownloads\Manager\CalendarManager;
use OCA\FilesGFTrackDownloads\Service\ShareService;

class CreateSharePerUserAssignedToUserGroup extends TimedJob
{
    /**
     * @var ShareService
     */
    private $shareService;

    /**
     * CreateSharePerUserAssignedToUserGroup constructor.
     * @param ShareService $shareService
     */
    public function __construct(ShareService $shareService)
    {
        $this->setInterval(0);
        $this->shareService = $shareService;
        $this->lastRun = 0;
    }

    /**
     * Makes the background job do its work
     *
     * @param array $argument unused argument
     */
    public function run($argument)
    {
        $this->shareService->createSharesForUsersInUserGroup();
    }

}