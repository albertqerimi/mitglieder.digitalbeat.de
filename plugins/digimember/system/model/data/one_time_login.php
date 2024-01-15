<?php

class ncore_OneTimeLoginData extends ncore_BaseData
{
    public function setOneTimeLogin( $user_id, $redirect_url, $arg_sep='&', $url_encode=false )
    {
        $this->api->load->helper('url');
        $this->api->load->helper('array');
        $this->api->load->helper('string');

        $key = ncore_randomString( 'alnum', 30 );

        $data = array(
            'key' => $key,
            'user_id' => $user_id,
            'redirect_url' => $redirect_url,
        );

        $id = $this->create( $data );

        // $base_url = $this->api->pluginUrl( 'login.php' );
        $base_url = ncore_siteUrl();

        $args = array(
            DIGIMEMBER_ONE_TIME_LOGINKEY_GET_PARAM => $id . '-' . $key,
        );

        return ncore_addArgs( $base_url, $args, $arg_sep, $url_encode );
    }

    public function performOneTimeLogin( $auth_key )
    {
        $tokens = explode( '-', $auth_key );

        if (count($tokens) >= 2)
        {
            list( $id, $key ) = $tokens;
        }
        else
        {
            $id = 0;
            $key = '';
        }

        $row = $id
               ? $this->get( $id )
               : false;

        $age_seconds = ncore_serverTime() - ncore_unixDate( ncore_retrieve( $row, 'created') );
        $too_old = $age_seconds > 2*86400;

        $is_valid = $row && $row->key && $row->key==$key && !$too_old;



        $url = false;
        if ($is_valid)
        {
            $url = $row->redirect_url;

            $user_id = $row->user_id;

            if ($user_id) {
                ncore_setSessionUser( $user_id );
            }
            else {
                wp_clear_auth_cookie();

            }
        }

        if ($url==='ajax')
        {
            return;
        }

        if (!$url)
        {
            $url = ncore_siteUrl();
        }

        $this->api->load->helper('url');
        ncore_redirect( $url );
    }

    public function cronDaily()
    {
        $db = $this->db();

        $table = $this->sqlTableName();

        $now = ncore_dbDate();

        $query = $db->query("DELETE FROM `$table`
                             WHERE created < '$now' - INTERVAL 2 DAY");
    }


    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'one_time_login';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'key' => 'string[31]',
        'user_id' => 'int',
        'redirect_url' => 'string[255]'
       );

       $indexes = array( 'key' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function isUniqueInBlog() {

        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        return $values;
    }


}