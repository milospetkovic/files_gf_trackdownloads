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


use DateTime;
use Doctrine\DBAL\Driver\Statement;
use Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
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

    /**
     * ShareManager constructor.
     * @param IDBConnection $connection
     * @param IL10N $l
     */
    public function __construct(IDBConnection $connection,
                                IL10N $l)
    {
        $this->connection = $connection;
        $this->l = $l;
    }

    /**
     * Check if file is shared with user
     *
     * @param $fileID
     * @param $userID
     * @return array
     */
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

    /**
     * Get all files shared with user which have defined expiration date and which are NOT confirmed
     *
     * @param $userID
     * @return mixed[]
     */
    public function getSharedFilesWithUserWithConfirmationDateButNotConfirmed($userID)
    {
        $query = $this->connection->getQueryBuilder();

        $query->select('sh.id', 'sh.share_with', 'sh.file_source', 'sh.expiration',
            'sh.stime', 'sh.uid_initiator', 'sh.file_target', 'fc.fileid',
            'u.displayname')
            ->from('share', 'sh')
            ->where($query->expr()->eq('sh.share_with', $query->createNamedParameter($userID)))
            ->andWhere($query->expr()->isNotNull('sh.expiration'))
            ->andWhere($query->expr()->isNull('sh.elb_confirmed'))
            ->leftJoin('sh', 'filecache', 'fc', $query->expr()->eq('fc.fileid', 'sh.file_source'))
            ->leftJoin('sh', 'users', 'u', $query->expr()->eq('u.uid', 'sh.uid_initiator'));

        return $query->execute()->fetchAll();
    }

    /**
     * Get all files shared with user which have defined expiration date and which are confirmed
     *
     * @param $userID
     * @return mixed[]
     */
    public function getSharedFilesWithUserWithConfirmationDateWhichAreConfirmed($userID)
    {
        $query = $this->connection->getQueryBuilder();

        $query->select('sh.id', 'sh.share_with', 'sh.file_source', 'sh.expiration',
            'sh.elb_confirmed', 'sh.stime', 'sh.uid_initiator', 'sh.file_target', 'fc.fileid',
            'u.displayname')
            ->from('share', 'sh')
            ->where($query->expr()->eq('sh.share_with', $query->createNamedParameter($userID)))
            ->andWhere($query->expr()->isNotNull('sh.expiration'))
            ->andWhere($query->expr()->isNotNull('sh.elb_confirmed'))
            ->leftJoin('sh', 'filecache', 'fc', $query->expr()->eq('fc.fileid', 'sh.file_source'))
            ->leftJoin('sh', 'users', 'u', $query->expr()->eq('u.uid', 'sh.uid_initiator'));

        return $query->execute()->fetchAll();
    }

    /**
     * Get all files shared with other users which have defined expiration date and which are NOT confirmed
     *
     * @param $userID
     * @return mixed[]
     */
    public function getSharedFilesWithOtherUsersWithConfirmationDateWhichAreNotConfirmed($userID)
    {
        $query = $this->connection->getQueryBuilder();

        $query->select('sh.id', 'sh.share_with', 'sh.file_source', 'sh.expiration',
            'sh.elb_confirmed', 'sh.stime', 'sh.uid_initiator', 'sh.file_target', 'fc.fileid',
            'u.displayname')
            ->from('share', 'sh')
            ->where($query->expr()->eq('sh.uid_initiator', $query->createNamedParameter($userID)))
            ->andWhere($query->expr()->isNotNull('sh.expiration'))
            ->andWhere($query->expr()->isNull('sh.elb_confirmed'))
            ->leftJoin('sh', 'filecache', 'fc', $query->expr()->eq('fc.fileid', 'sh.file_source'))
            ->leftJoin('sh', 'users', 'u', $query->expr()->eq('u.uid', 'sh.share_with'));

        return $query->execute()->fetchAll();
    }

    /**
     * Get all files shared with other users which have defined expiration date and which are confirmed
     *
     * @param $userID
     * @return mixed[]
     */
    public function getSharedFilesWithOtherUsersWithConfirmationDateWhichAreConfirmed($userID)
    {
        $query = $this->connection->getQueryBuilder();

        $query->select('sh.id', 'sh.share_with', 'sh.file_source', 'sh.expiration',
            'sh.elb_confirmed', 'sh.stime', 'sh.uid_initiator', 'sh.file_target', 'fc.fileid',
            'u.displayname')
            ->from('share', 'sh')
            ->where($query->expr()->eq('sh.uid_initiator', $query->createNamedParameter($userID)))
            ->andWhere($query->expr()->isNotNull('sh.expiration'))
            ->andWhere($query->expr()->isNotNull('sh.elb_confirmed'))
            ->leftJoin('sh', 'filecache', 'fc', $query->expr()->eq('fc.fileid', 'sh.file_source'))
            ->leftJoin('sh', 'users', 'u', $query->expr()->eq('u.uid', 'sh.share_with'));

        return $query->execute()->fetchAll();
    }

    /**
     * Get share record by share id
     *
     * @param $id
     * @return mixed
     */
    public function getRawShare($id)
    {
        $qb = $this->connection->getQueryBuilder();
        $qb->select('*')
            ->from('share')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

        $cursor = $qb->execute();
        $data = $cursor->fetch();
        $cursor->closeCursor();

        return $data;
    }

    /**
     * Check up if shared file is confirmed
     *
     * @param $shareID
     * @return mixed|null
     */
    public function checkUpIfFileOrFolderIsAlreadyConfirmed($shareID)
    {
        $ret = null;

        if ($shareID) {

            $query = $this->connection->getQueryBuilder();

            $query->select('elb_confirmed')
                ->from('share')
                ->where($query->expr()->eq('id', $query->createNamedParameter($shareID)));

            $fetchRes = $query->execute()->fetch();

            if (is_array($fetchRes) && count($fetchRes)) {
                return $fetchRes['elb_confirmed'];
            }
        }
        return $ret;
    }

    /**
     * Mark shared file as confirmed
     *
     * @param $shareID
     * @return Statement|int
     * @throws Exception
     */
    public function markSharedFileIDAsConfirmed($shareID)
    {
        $query = $this->connection->getQueryBuilder();

        $now = new DateTime();
        $nowFormat = $now->format('Y-m-d H:i:s');

        $query->update('share')
            ->set('elb_confirmed', $query->createNamedParameter($nowFormat))
            ->where($query->expr()->eq('id', $query->createNamedParameter($shareID, IQueryBuilder::PARAM_INT)));
        return $query->execute();
    }

    /**
     * Get share record for user and file id
     *
     * @param $userID
     * @param $fileID
     * @return mixed
     */
    public function getShareRecordForUserAndForFileID($userID, $fileID)
    {
        $qb = $this->connection->getQueryBuilder();
        $qb->select('*')
            ->from('share')
            ->where($qb->expr()->eq('file_source', $qb->createNamedParameter($fileID)))
            ->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($userID)));

        $cursor = $qb->execute();
        $data = $cursor->fetch();
        $cursor->closeCursor();

        return $data;
    }

    /**
     * Method counts files for user by share type (shared with the user or user shared with others)
     * and by confirmation status of shared files
     *
     * @param $userID
     * @return array
     */
    public function getCountOfFilesForUserPerShareTypeAndConfirmationStatus($userID)
    {
        return [
            'index' => count($this->getSharedFilesWithUserWithConfirmationDateButNotConfirmed($userID)),
            'yourconfirmedfiles' => count($this->getSharedFilesWithUserWithConfirmationDateWhichAreConfirmed($userID)),
            'yoursharednotconfirmed' => count($this->getSharedFilesWithOtherUsersWithConfirmationDateWhichAreNotConfirmed($userID)),
            'yoursharedandconfirmed' => count($this->getSharedFilesWithOtherUsersWithConfirmationDateWhichAreConfirmed($userID))
        ];
    }

}