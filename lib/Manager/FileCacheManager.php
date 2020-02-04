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

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class FileCacheManager
{
    const FILE_IS_CONFIRMED = 1;

    /**
     * @var IDBConnection
     */
    private $connection;

    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function checkUpIfFileOrFolderIsAlreadyConfirmed($fileCacheID)
    {
        $ret = null;

        if ($fileCacheID) {

            $query = $this->connection->getQueryBuilder();

            $query->select('file_confirmed')
                ->from('filecache')
                ->where($query->expr()->eq('fileid', $query->createNamedParameter($fileCacheID)));

            $fetchRes = $query->execute()->fetch();

            if (is_array($fetchRes) && count($fetchRes)) {
                return $fetchRes['file_confirmed'];
            }
        }
        return $ret;
    }

    public function markFileIDAsConfirmed($fileID)
    {
        $query = $this->connection->getQueryBuilder();

        $query->update('filecache')
            ->set('file_confirmed', $query->createNamedParameter(self::FILE_IS_CONFIRMED))
            ->where($query->expr()->eq('fileid', $query->createNamedParameter($fileID, IQueryBuilder::PARAM_INT)));
        return $query->execute();
    }

}