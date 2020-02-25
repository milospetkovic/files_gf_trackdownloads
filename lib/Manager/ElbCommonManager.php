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


use Doctrine\DBAL\Driver\Statement;
use OCP\IDBConnection;

class ElbCommonManager
{
    /**
     * @var IDBConnection
     */
    private $connection;

    /**
     * ElbCommonManager constructor.
     * @param IDBConnection $connection
     */
    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $tbl_name
     * @param $fields
     * @param bool $transaction
     * @return Statement
     */
    public function insert($tbl_name, $fields)
    {
        $sql = "INSERT INTO ".$tbl_name." (";

        $fields_num=count($fields);
        $i = 0;
        foreach ($fields as $field_name => $field_val) {
            $sql.=" ".$field_name." ";
            if(++$i < $fields_num) $sql.=" , ";
        }
        $sql.= ") VALUES (";
        $i = 0;
        foreach ($fields as $field_name => $field_val) {
            (is_null($field_val)) ? $sql.="null" : $sql.=" '".$field_val."' ";
            if(++$i < $fields_num) $sql.=",";
        }
        $sql.= ")";

        return $this->connection->executeQuery($sql);
    }

}