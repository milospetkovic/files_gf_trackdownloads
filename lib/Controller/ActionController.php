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

use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class ActionController extends Controller
{

//    private $userId;
//
//    public function __construct($AppName, IRequest $request, $UserId){
//        parent::__construct($AppName, $request);
//        $this->userId = $UserId;
//    }

    private $UserId;
    private $config;
    private $l;
    public function __construct(IConfig $config,$AppName, IRequest $request, string $UserId, IL10N $l){
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->UserId = $UserId;
        $this->l = $l;
        //header("Content-type: application/json");
    }

    public function confirm($nameOfFile, $directory=null, $external=null, $shareOwner = null)
    {
        $response = [
            'data' => 'ovde info o podacima'
        ];

        return json_encode($response);
    }

}
