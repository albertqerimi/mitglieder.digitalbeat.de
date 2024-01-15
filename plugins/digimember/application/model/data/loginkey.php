<?php

class digimember_LoginkeyData extends ncore_BaseData
{
    public function getForUser( $user_id, $default_key=false )
    {
        $where = array( 'user_id' => $user_id );

        $row = $this->getWhere( $where );

        $ref_id   = ncore_retrieve( $row, 'ref_id' );
        $loginkey = ncore_retrieve( $row, 'loginkey' );

        if (!$ref_id)
        {
            $can_use_default = false;
            if ($default_key)
            {
                list( $ref_id, $loginkey ) = $this->explodeKey( $default_key );
                $can_use_default = $ref_id == $user_id && strlen($loginkey)>=16;
            }

            if (!$can_use_default)
            {
                $ref_id = $user_id;

                $this->api->load->helper( 'string' );
                $loginkey = ncore_randomString( 'alnum', 16 );
            }

            $this->api->load->helper( 'string' );
            $login_id_key_pair = $this->implodeKey( $ref_id, $loginkey );
            $this->setForUser( $user_id, $login_id_key_pair );
        }

        return $this->implodeKey( $ref_id, $loginkey );
    }

    public function getUserIdByKey( $login_id_key_pair )
    {
        list( $ref_id, $loginkey ) = $this->explodeKey( $login_id_key_pair );
        if (!$ref_id)
        {
            return false;
        }

        $where = array( 'ref_id' => $ref_id, 'loginkey' => $loginkey );

        $row = $this->getWhere( $where );

        return ncore_retrieve( $row, 'user_id', false );
    }

    public function clearForUser( $user_id )
    {
        $where = array( 'user_id' => $user_id );

        $all = $this->getAll( $where );

        foreach ($all as $one)
        {
            $this->delete( $one->id );
        }
    }

    public function setForUser( $user_id, $login_id_key_pair )
    {
        // Allow login keys from multiple sources (self generated for KlickTipp, Digistore24) - so the neyt line is commented out:
        // $this->clearForUser( $user_id );

        if (!$login_id_key_pair)
        {
            $this->api->load->helper( 'string' );
            $login_id_key_pair = $this->implodeKey( $user_id, ncore_randomString( 'alnum', 16 ) );
        }

        list( $ref_id, $loginkey ) = $this->explodeKey( $login_id_key_pair );
        if (!$ref_id)
        {
            return false;
        }

        $data = array(
            'user_id'  => $user_id,
            'ref_id'   => $ref_id,
            'loginkey' => $loginkey,
        );
        return $this->create( $data );
    }


    public function maybeAutoLogin( $login_id_key_pair )
    {
        $is_logged_in = ncore_isLoggedIn();
        if ($is_logged_in) {
            return false;
        }

        $user_id = $this->getUserIdByKey( $login_id_key_pair );
        if (!$user_id)
        {
            return false;
        }

        $user = ncore_getUserById( $user_id );
        if (!$user)
        {
            return false;
        }

        $model = $this->api->load->model( 'data/user' );
        $password = $model->getPassword( $user_id );
        if (!$password)
        {
            return false;
        }

        try
        {
            $logged_in = (bool) ncore_wp_login( $user->user_login, $password, $remember=true );

            if ($logged_in)
            {
                $counter_model = $this->api->load->model( 'data/counter' );
                $counter_model->countLogin( $user_id );
            }

            return $logged_in;


        }
        catch (Exception $e)
        {
            // password changed -> Login disabled
            return false;
        }
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'loginkey';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'user_id'  => 'id',
        'ref_id'   => 'id',
        'loginkey' => 'string[31]',
       );

       $indexes = array( 'ref_id', 'loginkey', 'user_id');

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }


    private function explodeKey( $login_id_key_pair )
    {
        list( $ref_id, $loginkey ) = ncore_retrieveList( '-', $login_id_key_pair );

        $is_valid = is_numeric($ref_id)
                  && $ref_id >= 1
                  && strlen( $loginkey ) >= 8
                  && str_replace( ' ', '', $loginkey ) == $loginkey;

        return $is_valid
               ? array( $ref_id, $loginkey )
               : array( false, false );
    }

    private function implodeKey( $ref_id, $loginkey )
    {
        $is_valid = is_numeric($ref_id)
                  && $ref_id >= 1
                  && strlen( $loginkey ) >= 8
                  && str_replace( ' ', '', $loginkey ) == $loginkey;

        return $is_valid
               ? "$ref_id-$loginkey"
               : '';
    }

}