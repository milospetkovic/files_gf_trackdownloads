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
use OCP\IL10N;

class ShareManager
{

    /**
     * @var IDBConnection
     */
    private $connection;
    /**
     * @var IL10N
     */
    private $l;

    public function __construct(IDBConnection $connection, IL10N $l)
    {
        $this->connection = $connection;
        $this->l = $l;
    }

    public function checkUpFileIDIsSharedWithUser($fileID, $userID)
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
                    $error_msg = $this->l->t('File is not shared for Your confirmation');
                }

            } else {
                $error++;
                $error_msg = $this->l->t('File is not shared for confirmation');
            }
        } else {
            $error++;
            $error_msg = $this->l->t('Missing file ID');
        }
        return [
            'error'     => $error,
            'error_msg' => $error_msg
        ];
    }

    public function getSharedFilesWithConfirmationDateNotConfirmed($userID)
    {
        $query = $this->connection->getQueryBuilder();

        $query->select('sh.id', 'sh.share_with', 'sh.file_source', 'sh.expiration',
            'sh.stime', 'sh.uid_initiator', 'sh.file_target', 'fc.fileid')
            ->from('share', 'sh')
            ->where($query->expr()->eq('sh.share_with', $query->createNamedParameter($userID)))
            ->andWhere($query->expr()->isNotNull('sh.expiration'))
            ->andWhere($query->expr()->isNull('fc.file_confirmed'))
            ->leftJoin('sh', 'filecache', 'fc', $query->expr()->eq('fc.fileid', 'sh.file_source'));

        $fetchRes = $query->execute()->fetchAll();
//        var_dump($fetchRes);
//        die();

        return $fetchRes;
    }

}