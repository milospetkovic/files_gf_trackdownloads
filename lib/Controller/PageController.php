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

use OCA\FilesGFTrackDownloads\Manager\ShareManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller
{
    /**
     * @var
     */
	private $userId;

    /**
     * @var ShareManager
     */
    private $shareManager;

    /**
     * PageController constructor.
     *
     * @param $AppName
     * @param IRequest $request
     * @param $UserId
     * @param ShareManager $shareManager
     */
    public function __construct($AppName, IRequest $request, $UserId, ShareManager $shareManager)
    {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
        $this->shareManager = $shareManager;
    }

	/**
     * Index page - show user's unconfirmed files
     *
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index()
    {
        $response = new TemplateResponse(
            'files_gf_trackdownloads',
            'index',
            [   'data' => $this->shareManager->getSharedFilesWithUserWithConfirmationDateButNotConfirmed($this->userId),
                'active_route' => $this->getCurrentPageRoute(),
                'count_files' => $this->shareManager->getCountOfFilesForUserPerShareTypeAndConfirmationStatus($this->userId) ]);

        $policy = new ContentSecurityPolicy();
        $policy->allowEvalScript(true);
        $response->setContentSecurityPolicy($policy);

		return $response;
	}

    /**
     * Page shows user's confirmed files
     *
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function yourconfirmedfiles()
    {
        $response = new TemplateResponse(
            'files_gf_trackdownloads',
            'yourconfirmedfiles',
            [   'data' => $this->shareManager->getSharedFilesWithUserWithConfirmationDateWhichAreConfirmed($this->userId),
                'active_route' => $this->getCurrentPageRoute(),
                'count_files' => $this->shareManager->getCountOfFilesForUserPerShareTypeAndConfirmationStatus($this->userId) ]);

        return $response;
    }

    /**
     * Page shows user's shared files with others and which are NOT confirmed
     *
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function yoursharednotconfirmed()
    {
        $response = new TemplateResponse(
            'files_gf_trackdownloads',
            'yoursharednotconfirmed',
            [   'data' => $this->shareManager->getSharedFilesWithOtherUsersWithConfirmationDateWhichAreNotConfirmed($this->userId),
                'active_route' => $this->getCurrentPageRoute(),
                'count_files' => $this->shareManager->getCountOfFilesForUserPerShareTypeAndConfirmationStatus($this->userId) ]);

        return $response;
    }

    /**
     * Page shows user's shared files with others and which are confirmed
     *
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function yoursharedandconfirmed()
    {
        $response = new TemplateResponse(
            'files_gf_trackdownloads',
            'yoursharedandconfirmed',
            [   'data' => $this->shareManager->getSharedFilesWithOtherUsersWithConfirmationDateWhichAreConfirmed($this->userId),
                'active_route' => $this->getCurrentPageRoute(),
                'count_files' => $this->shareManager->getCountOfFilesForUserPerShareTypeAndConfirmationStatus($this->userId) ]);

        return $response;
    }

    /**
     * Get app's full current route
     *
     * @return string
     */
    private function getCurrentRoute()
    {
        return $this->request->parameters['_route'];
    }

    /**
     * Pull out app's current page route only
     *
     * @return string
     */
    private function getCurrentPageRoute()
    {
        $arr = explode('.', $this->getCurrentRoute());
        return end($arr);
    }

}
