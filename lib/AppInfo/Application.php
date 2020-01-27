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

namespace OCA\FilesGFTrackDownloads\AppInfo;

use OCA\FilesGFTrackDownloads\Activity\Listener;
use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

    public function __construct() {
        parent::__construct('files_gf_trackdownloads');
    }

    /**
     * Register all hooks and listeners
     */
    public function register() {
        Util::connectHook('OC_Filesystem', 'read', $this, 'listenReadFile2');
    }

    /**
     * @param array $params
     */
    public function listenReadFile2($params) {
        /** @var Listener $hooks */
        $hooks = $this->getContainer()->query(Listener::class);
        $hooks->readFile($params['path']);
    }
}
