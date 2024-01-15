<?php

class ncore_ServerCookieData extends ncore_BaseData
{
    public function __construct( ncore_ApiCore $api, $file='', $dir='' )
    {
        parent::__construct( $api, $file, $dir );

        $this->access_key();
    }

    function set( $name, $value, $lifetime_seconds='default' )
    {
        if ($lifetime_seconds==='default')
        {
            $lifetime_seconds = 86400*$this->cookie_lifetime_days();
        }
        $this->_set( $name, $value, $lifetime_seconds );
    }

    function get( $name, $default = '' )
    {
        return $this->_get( $name, $default );
    }

    function cookie_lifetime_days()
    {
        return 200;
    }

    function cronWeekly()
    {
        $this->_purge();
    }


    //
    // protected
    //
    protected function sqlBaseTableName()
    {
        return 'server_cookie';
    }

    protected function isUniqueInBlog()
    {
        return true;
    }

    protected function sqlTableMeta()
    {
        $key_length = self::access_key_length;

        $columns = array(
            'access_key' => "string[$key_length]",
            'name'       => 'string[31]',
            'value'      => 'string[255]',
            'expire_at'  => 'datetime',
        );

        $indexes = array();

        $uniques = array(
                    array( 'access_key', 'name' ),
                   );

        $meta = array(
          'columns' => $columns,
          'indexes' => $indexes,
          'uniques' => $uniques,
        );

        return $meta;
    }

    //
    // private
    //
    private $cookies;
    const access_key_length = 31;

    protected function access_key( $existing_only = false )
    {
        static $access_key;

        if (empty($access_key))
        {
            $headers_sent = headers_sent();

            // if (NCORE_DEBUG && $headers_sent) {
            //     $this->api->load->helper('cron');
            //     if (!ncore_isCronjob()) {
            //         trigger_error( "Must create server cookie model instance before http headers are sent" );
            //     }
            // }

            $key_length = self::access_key_length;

            $access_key = ncore_washText( ncore_retrieve( $_COOKIE, 'settings') );

            $is_valid = strlen( $access_key ) == $key_length;

            if (!$is_valid)
            {
                if ($existing_only) {
                    return false;
                }

                $this->api->load->helper( 'string' );
                $access_key = ncore_randomString( 'alnum', $key_length );
            }

            if (!$headers_sent)
            {
                $expire = time() + $this->cookie_lifetime_days() * 86400;
                ncore_setcookie( 'settings', $access_key, $expire, '/' );

                $_COOKIE['settings'] = $access_key;
            }
        }

        return $access_key;
    }

    private function _set( $name, $value, $lifetime_seconds )
    {
        $access_key = $this->access_key();

        $name  = ncore_washText( $name );
        $value_esc = $this->db()->escape( $value );

        $expire_unix = $lifetime_seconds < 100000000
                     ? time() + $lifetime_seconds
                     : $lifetime_seconds;

        $table_name = $this->sqlTableName();
        $expire = ncore_dbDate( $expire_unix );

        $now = ncore_dbDate();

        $sql = "REPLACE INTO $table_name
                (access_key, name, value, created, expire_at)
                VALUES
                ('$access_key','$name', \"$value_esc\", '$now', '$expire' )";

        $this->db()->query( $sql );

        if (isset($this->cookies))
        {
            $this->cookies[ $name ] = $value;
        }
    }

    private function _get( $name, $default )
    {
        if (!isset($this->cookies))
        {
            $this->cookies = array();

            $access_key = $this->access_key();

            $table_name = $this->sqlTableName();

            $now = ncore_dbDate();

            $sql = "SELECT name,
                           value
                    FROM $table_name
                    WHERE access_key = '$access_key'
                      AND expire_at >= '$now'";

            $rows = $this->db()->query( $sql );

            foreach ($rows as $row)
            {
                $this->cookies[ $row->name ] = $row->value;
            }
        }

        return ncore_retrieve( $this->cookies, $name, $default );
    }

    private function _purge(){

        $table_name = $this->sqlTableName();
        $now = ncore_dbDate();

        $sql = "DELETE FROM $table_name
                WHERE expire_at < '$now'";

        $this->db()->query( $sql );
    }

}