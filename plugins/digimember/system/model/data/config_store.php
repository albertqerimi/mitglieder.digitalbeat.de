<?php

class ncore_ConfigStoreData extends ncore_BaseData
{
    public function cronDaily()
    {
        $table = $this->sqlTableName();

        $now = ncore_dbDate();

        $sql = "DELETE FROM $table
                WHERE expire < '$now'";

        $this->db()->query($sql);
    }

    public function validateFrameworkSetup()
    {
        $max_length = 35;

        $prefix = $this->db()->tableNamePrefix();

        $length = strlen( $prefix );

        $too_long = $length > $max_length;
        if ($too_long)
        {
            return "The database table name prefix is too long. The prefix '$prefix' has $length characters, but allowed are only $max_length characters. Please rename your database tables, so that they have a shorter name prefix. Then adjust your Wordpress config accordingly.";
        }

        return '';
    }

    //
    // protected
    //
    protected function sqlBaseTableName()
    {
        return 'config_store';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name'                  => 'string[63]',
            'value'                 => 'text',
            'expire'                => 'lock_date',
            'type'                  => 'string[7]',
       );

       $indexes = array( 'name', 'expire' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

}