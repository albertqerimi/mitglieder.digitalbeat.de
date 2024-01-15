<?php

// LEGACY CODE - REMOVE FILE in 2018

class digimember_IpLogData extends ncore_BaseData
{
    public function cronDaily()
    {
        $db = $this->db();

        $table = $this->sqlTableName();

        $db->query("DELETE FROM `$table`");
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'ip_log';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'user_id' => 'int',
       );

       $indexes = array();

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

}