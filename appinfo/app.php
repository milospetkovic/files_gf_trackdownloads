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

use OCA\FilesGFTrackDownloads\AppInfo\Application;
use OCP\Util;

$application = new Application();
$application->register();

$eventDispatcher = OC::$server->getEventDispatcher();

$includes = [
    'Files' => 'files',
    'Files_Sharing' => 'files',
];

// pages where additional javascript/css should be inserted
foreach ($includes as $app => $include) {
    $eventDispatcher->addListener(
        'OCA\\'.$app.'::loadAdditionalScripts',
        function () use ($include) {
            Util::addScript('files_gf_trackdownloads', 'main');
            Util::addStyle('files_gf_trackdownloads', 'style');
        }
    );
}
