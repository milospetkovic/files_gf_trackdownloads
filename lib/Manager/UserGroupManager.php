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

class UserGroupManager
{
    /**
     * @var IDBConnection
     */
    private $connection;

    /**
     * UserGroupManager constructor.
     * @param IDBConnection $connection
     */
    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return array with user ids placed in user group id
     *
     * @param $userGroupID
     * @return mixed[]
     */
    public function getUsersIDsPlacedInUserGroupID($userGroupID)
    {
        $query = $this->connection->getQueryBuilder();

        $query->select('gu.uid')
            ->from('group_user', 'gu')
            ->where($query->expr()->eq('gu.gid', $query->createNamedParameter($userGroupID)));

        return $query->execute()->fetchAll();
    }

}