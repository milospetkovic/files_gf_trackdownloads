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

namespace OCA\FilesGFTrackDownloads\Controller;

use OCA\FilesGFTrackDownloads\Manager\FileCacheManager;
use OCA\FilesGFTrackDownloads\Manager\ShareManager;
use OCA\FilesGFTrackDownloads\Service\ActivityService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class ActionController extends Controller
{
    private $UserId;
    private $config;
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

    public function __construct(IConfig $config,$AppName,
                                IRequest $request,
                                string $UserId,
                                IL10N $l,
                                FileCacheManager $fileCacheManager,
                                ShareManager $shareManager,
                                ActivityService $activityService)
    {
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->UserId = $UserId;
        $this->l = $l;
        $this->fileCacheManager = $fileCacheManager;
        $this->shareManager = $shareManager;
        $this->activityService = $activityService;
    }

    public function confirm($fileID)
    {
        $error = 0;
        $error_msg = '';

        // check up if file is already confirmed
        $alreadyConfirmed = $this->fileCacheManager->checkUpIfFileOrFolderIsAlreadyConfirmed($fileID);
        if ($alreadyConfirmed) {
            $error++;
            $error_msg = $this->l->t('File is already confirmed');
        }

        // check up if file/folder is shared with user and check up expiration date
        if (!$error) {
            $result = $this->shareManager->checkUpFileIDIsSharedWithUser($fileID, $this->UserId);
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
                $error_msg = 'Error marking file as confirmed';
            }
        }

        if (!$error) {
            $this->activityService->saveFileConfirmationToActivity();
        }

        $response = [
            'error' => $error,
            'error_msg' => $error_msg
        ];

        return json_encode($response);
    }

}
