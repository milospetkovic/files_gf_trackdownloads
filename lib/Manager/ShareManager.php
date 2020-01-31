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

namespace OCA\FilesGFTrackDownloads\Manager;


use OCP\IDBConnection;

class ShareManager
{

    /**
     * @var IDBConnection
     */
    private $connection;

    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function checkUpFileIDShareWithUser($fileID, $userID)
    {
        $error = 0;
        $error_msg = 0;

        if ($fileID) {

            $query = $this->connection->getQueryBuilder();

            $query->select('sh.id', 'sh.share_with', 'sh.file_source', 'sh.expiration')
                ->from('share', 'sh')
                ->where($query->expr()->eq('file_source', $query->createNamedParameter($fileID)));

            $fetchRes = $query->execute()->fetchAll();

            if (is_array($fetchRes) && count($fetchRes)) {

                $sharedWithCurrentUser = false;

                foreach ($fetchRes as $res) {
                    if ($res['share_with'] == $userID) {
                        $sharedWithCurrentUser = true;
                        break;
                    }
                }

                if (!$sharedWithCurrentUser) {
                    $error++;
                    $error_msg = 'File is not shared for confirmation';
                }

            } else {
                $error++;
                $error_msg = 'File is not shared for confirmation';
            }
        } else {
            $error++;
            $error_msg = 'Missing file ID';
        }
        return [
            'error'     => $error,
            'error_msg' => $error_msg
        ];
    }

}