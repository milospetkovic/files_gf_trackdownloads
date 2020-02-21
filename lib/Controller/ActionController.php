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

use OCA\FilesGFTrackDownloads\Service\ShareService;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\Share\Exceptions\ShareNotFound;

class ActionController extends Controller
{
    private $UserId;
    private $config;
    private $l;

    /**
     * @var ShareService
     */
    private $shareService;

    /**
     * ActionController constructor.
     * @param IConfig $config
     * @param $AppName
     * @param IRequest $request
     * @param string $UserId
     * @param IL10N $l
     * @param ShareService $shareService
     */
    public function __construct(IConfig $config,
                                $AppName,
                                IRequest $request,
                                string $UserId,
                                IL10N $l,
                                ShareService $shareService)
    {
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->UserId = $UserId;
        $this->l = $l;
        $this->shareService = $shareService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param $fileID
     * @return false|string
     * @throws NotFoundException
     * @throws ShareNotFound
     */
    public function confirm($fileID)
    {
        return $this->shareService->confirmSharedFileByFileID($fileID);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param $files
     * @return false|string
     * @throws NotFoundException
     */
    public function confirmSelectedSharedFiles($files)
    {
        return $this->shareService->confirmSelectedSharedFiles($files);
    }

}
