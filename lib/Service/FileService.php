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