<?php

class ncore_IpLockData extends ncore_BaseData
{
    const store_log_days = 7;

    public function isLocked( $key, $limit_count=10, $limit_time_seconds=86400, $do_set_locked=true, $ip='current' )
    {
        $key = ncore_washText( $key );

        $ip = $this->resolveIp( $ip );

        $count = $this->count( $key, $limit_time_seconds, $ip );

        $is_locked = $count > $limit_count;

        if ($do_set_locked)
        {
            $data = array();
            $data[ 'ip']       = $ip;
            $data[ 'code' ]    = $key;
            $data[ 'created' ] = ncore_dbDate();
            $this->create( $data );
        }

        return $is_locked;
    }

    private function count( $key, $limit_time_seconds=86400, $ip='current' )
    {
        $key = ncore_washText( $key );

        $db = $this->db();

        $ip = $this->resolveIp( $ip );

        $table = $this->sqlTableName();

        $from = ncore_dbDate( time()-$limit_time_seconds );

        $sql = "SELECT COUNT(1) AS count
                FROM `$table`
                WHERE ip = '$ip'
                  AND created >= '$from'
                  AND code     = '$key'";

        $query = $db->query( $sql );

        $row = $query[0];

        return $row->count;
    }

    public function cronDaily()
    {
        $db = $this->db();

        $table = $this->sqlTableName();

        $days = self::store_log_days;

        $from = ncore_dbDate( time()-86400 );

        $sql = "DELETE FROM `$table` WHERE created < '$from'";

        $db->query( $sql );
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'ip_lock';
    }

    protected function isUniqueInBlog()
    {
        return true;
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'ip'   => 'string[47]',
        'code' => 'string[15]',
       );

       $indexes = array( 'ip' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function protectedColumns( $method )
    {
        $columns = parent::protectedColumns( $method );

        $index = array_search( 'created', $columns );

        if ($index !== false)
        {
            unset( $columns[ $index ] );
            $columns = array_values( $columns );
        }

        return $columns;
    }

    //
    // private
    //
    private function resolveIp( $ip )
    {
        if ($ip === 'current')
        {
            $ip = ncore_clientIp( 'localhost' );
        }

        $ip = ncore_washText( $ip );

        return $ip;
    }

}