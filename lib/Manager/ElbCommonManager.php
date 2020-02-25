<?php


namespace OCA\FilesGFTrackDownloads\Manager;


use OCP\IDBConnection;

class ElbCommonManager
{
    /**
     * @var IDBConnection
     */
    private $connection;

    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

//    public function execute($sql,$transaction=true)
//    {
//        if ($transaction) {
//            // start transaction
//            $this->connection->beginTransaction();
//        }
//
//        $result = $this->connection->executeQuery($sql);
//
//        if ($result) {
//            if ($transaction) $db->commit();
//            return true;
//        } else {
//            if($transaction) $db->rollback();
//            return false;
//        }
//    }

    public function insert($tbl_name, $fields, $transaction=false)
    {
        if ($transaction) {
            $this->connection->beginTransaction();
        }

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

        $result = $this->connection->executeQuery($sql);

//        if ($result) {
//            $obj->id = $db->last_insert_id(MAIN_DB_PREFIX.$tbl_name);
//            if($transaction) $db->commit();
//            return $obj->id;
//        }
//        else
//        {
//            if($transaction) $db->rollback();
//            $obj->error=$db->lasterror();
//            return -1;
//        }
    }




}
