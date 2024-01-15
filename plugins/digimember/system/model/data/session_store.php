<?php

final class ncore_SessionStoreData extends ncore_BaseData
{
    public function cronDaily()
    {
        $table = $this->sqlTableName();

        $now = ncore_dbDate();

        $sql = "DELETE FROM $table
                WHERE expire < '$now'";

        $this->db()->query($sql);
    }

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_SESSION;
    }

    function store( $session_key, $session_data, $lifetime_hours=24 )
    {
        $table = $this->sqlTableName();
        $session_key    = ncore_washText( $session_key );
        $lifetime_hours = intval( $lifetime_hours );

        if (!$session_data)
        {
            $sql = "DELETE FROM $table
                    WHERE name = '$session_key'";
            $this->db()->query( $sql );
            return;
        }

        $expire = ncore_dbDate( time() + $lifetime_hours * 3600 );

        $session_data_serialized = serialize( $session_data );
        $session_data_esc = $this->db()->escape( $session_data_serialized );
        $sql = "UPDATE $table
                SET data_serialized = \"$session_data_esc\",
                    expire          = '$expire'
                WHERE name = '$session_key'";
        $this->db()->query( $sql );
        $modified = $this->db()->modified();

        if (!$modified) {

            $are_same_data_stored = (bool) $this->retrieve( $session_key, 0 );
            if (!$are_same_data_stored) {
                $data = array();
                $data[ 'name' ] = $session_key;
                $data[ 'data_serialized' ] = $session_data_serialized;
                $data[ 'expire' ] = $expire;
                $this->create( $data );
            }
        }
    }

    function retrieve( $session_key, $lifetime_hours=24)
    {
        $table = $this->sqlTableName();
        $session_key    = ncore_washText( $session_key );
        $lifetime_hours = intval( $lifetime_hours );

        $sql = "SELECT data_serialized,
                       expire
                FROM $table
                WHERE name = '$session_key'
                LIMIT 0,1";

        $rows = $this->db()->query( $sql );

        if (!$rows) {
            return array();
        }

        $row = $rows[0];

        $must_udpate_timestamp = $row->expire < ncore_dbDate( time() + 3600 * $lifetime_hours / 2 );
        if ($must_udpate_timestamp) {
            $expire = ncore_dbDate( time() + 3600 * $lifetime_hours );
            $sql = "UPDATE $table
                    SET expire = '$expire'
                    WHERE name= '$session_key'";
            $this->db()->query( $sql );
        }

        $data = unserialize( $row->data_serialized );

        return $data;
    }


    //
    // protected
    //
    protected function sqlBaseTableName()
    {
        return 'session_store';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name'            => 'string[47]',
            'data_serialized' => 'text',
            'expire'          => 'lock_date',
       );

       $indexes = array( 'name', 'expire' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function isUniqueInBlog() {

        return true;
    }

}