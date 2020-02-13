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

class GroupFolderManager
{
    /**
     * @var IDBConnection
     */
    private $connection;

    /**
     * GroupFolderManager constructor.
     * @param IDBConnection $connection
     */
    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get group folder's id searching group folder by it's name
     *
     * @param null $groupFolderName
     * @return mixed|null
     */
    public function getGroupFolderIdByGroupFolderName($groupFolderName=null)
    {
        if ($groupFolderName) {

            $query = $this->connection->getQueryBuilder();

            $query->select('folder_id')
                ->from('group_folders', 'f')
                ->where($query->expr()->eq('mount_point', $query->createNamedParameter($groupFolderName)));

            $fetchRes = $query->execute()->fetch();

            if (is_array($fetchRes) && count($fetchRes)) {
                return $fetchRes['folder_id'];
            }
        }

        return null;
    }

    /**
     * Get assigned user groups ids which are assigned to the group folder ID
     *
     * @param null $groupFolderID
     * @return array
     */
    public function getAssignedGroupsIdsToGroupFolderId($groupFolderID=null)
    {
        $ret = [];

        if ($groupFolderID) {

            $query = $this->connection->getQueryBuilder();

            $query->select('group_id')
                ->from('group_folders_groups', 'f')
                ->where($query->expr()->eq('folder_id', $query->createNamedParameter($groupFolderID)));

            $fetchRes = $query->execute()->fetchAll();

            if (is_array($fetchRes) && count($fetchRes)) {
                foreach ($fetchRes as $row) {
                    if (!in_array($row['group_id'], $ret)) {
                        $ret[] = $row['group_id'];
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Get id of group folder of file by file's path
     *
     * @param $path
     * @return mixed|null
     */
    public function getGroupFolderIDByFilePath($path)
    {
        $appFilePath = ltrim($path, '/');
        $expAppFilePath = explode('/', $appFilePath);
        $firstFolderForAppFilePath = $expAppFilePath[0];
        return $this->getGroupFolderIdByGroupFolderName($firstFolderForAppFilePath);
    }

}