<?php

class ncore_UserData extends ncore_BaseData
{
    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }

    public function canAutoLogin( $user_obj_or_id )
    {
        $user_id = $this->resolveToId( $user_obj_or_id );

        return (bool) $this->getPassword( $user_id );
    }

    public function getPassword( $user_id, $default='' )
    {
        $wp_user = ncore_getUserById( $user_id );
        if (!$wp_user) {
            return $default;
        }

        $where   = array( 'user_id' => $user_id );
        $pw_data = $this->getWhere( $where );

        if (!$pw_data) {
            return $default;
        }

        $pw_valid = $this->validatePwHash( $wp_user->user_pass, $pw_data->pw_algo, $pw_data->pw_hash, $wp_user->ID );
        if (!$pw_valid) {
            return $default;
        }

        return $pw_data->pw_generated
               ? $pw_data->pw_generated
               : $default;
    }

    public function setPassword( $user_id, $password, $do_store_password, $do_update_wp_pw=true )
    {
        if (!$password) {
            return;
        }

        if ($do_update_wp_pw)
        {
            wp_set_password( $password, $user_id );
        }

        if ($do_store_password)
        {
            $wp_user = ncore_getUserById( $user_id );
            list( $algo, $password_hash ) = $this->computePwHash( $wp_user->user_pass );

            $data = array(
                'pw_generated' => $password,
                'pw_hash'      => $password_hash,
                'pw_algo'      => $algo,
            );
        }
        else
        {
            $data = array(
                'pw_generated' => '',
                'pw_hash'      => '',
                'pw_algo'      => '',
            );
        }

       $this->setData( $user_id, $data );
    }

    public function setName( $user_id, $first_name = false, $last_name = false )
    {
        if ($first_name && $first_name != '') {
            update_user_meta( $user_id, 'first_name', $first_name );
        }
        if ($last_name && $last_name != '') {
            update_user_meta( $user_id, 'last_name', $last_name );
        }
    }

    public function getByWpUserId( $user_id )
    {
        return $this->getData( $user_id );
    }

    public function getCurrent()
    {
        $user_id = ncore_userId();
        return $this->getData( $user_id );
    }

    public function deleteWpAccount( $user_id='current' )
    {
        $CAPABILITIES_THAT_PREVENT_DELETION_BY_USER = array( 'create_sites', 'activate_plugins', 'edit_pages', 'edit_posts' );

        $user = ncore_getUserBy( 'id', $user_id );
        if (!$user) {
            return _ncore( 'The user account already has been deleted.' );
        }

        foreach ($CAPABILITIES_THAT_PREVENT_DELETION_BY_USER as $capability)
        {
            if (user_can( $user, $capability ))
            {
                throw new Exception( _digi( 'The user account cannot be deleted, since special permissions are assigned to it. For account deletion please contact our support.' ) );
            }
        }


        $user_id = $this->resolveToId( $user_id );

        $this->delete( $user_id );

        ncore_deleteWpUser( $user_id );
    }

    public function resolveToId( $object_or_id )
    {
        if ($object_or_id === 'current')
        {
            $object_or_id = ncore_userId();
        }

        return $object_or_id;
    }

    public function maybeCreateForWpUser( $user_id )
    {
        if (!$user_id) {
            return;
        }

        global $DM_HANDLED_USER_IDS;

        if (empty($DM_HANDLED_USER_IDS)) {
            $DM_HANDLED_USER_IDS = array();
        }

        if (in_array( $user_id, $DM_HANDLED_USER_IDS ))
        {
            return;
        }

        $DM_HANDLED_USER_IDS[] = $user_id;

        $where   = array( 'user_id' => $user_id );
        $all = $this->getAll( $where );

        if (!$all) {
            $data[ 'user_id' ] = $user_id;
            $this->create( $data );
        }
    }


    //
    // protected
    //
    protected function sqlBaseTableName()
    {
        return 'user';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'user_id'       => 'int',
            'pw_generated'  => 'string[63]',
            'pw_hash'       => 'string[159]',
            'pw_algo'       => 'string[7]',
       );

       $indexes = array( 'user_id' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function isUniqueInBlog() {

        return true;
    }

    protected function setData( $user_id, $data )
    {
        $where   = array( 'user_id' => $user_id );
        $all = $this->getAll( $where );

        if ($all) {
            foreach ($all as $one)
            {
                $this->update( $one->id, $data );
            }
        }
        else {
            $data[ 'user_id' ] = $user_id;
            $this->create( $data );
        }
    }


    protected function subTableMetas()
    {
        $meta = array(
            'user_settings' => 'user_id',
        );

        return $meta;
    }

    private $cache = array();
    protected function getData( $user_id ) {

        if (!$user_id) {
            return false;
        }

        $data =& $this->cache[ $user_id ];

        if (!isset($data)) {

            $where = array( 'user_id' => $user_id );
            $order_by = 'id DESC';

            $data = $this->getWhere( $where, $order_by );
        }

        return $data;
    }

    private function encryptPassword( $password, $salt )
    {
        $password = trim( $password );
        return hash("sha512", 'uoxohf8B'.$salt.$password.$salt.'Yohg3Xah' );
    }

    private function computePwHash( $wp_user_pass )
    {
        $this->api->load->helper( 'encryption' );

        list( $algo, $hash ) = ncore_hash( $wp_user_pass );

        return array( $algo, $hash );
    }

    private function validatePwHash( $wp_user_pass, $stored_hash_algo, $stored_pw_hash, $wp_user_id )
    {
        list( $algo, $hash ) = $this->computePwHash( $wp_user_pass );

        $is_valid = ncore_hashCompare( $hash, $stored_pw_hash );

        if ($is_valid)
        {
            return true;
        }

        $must_warn_missing_php_extension = $algo=='md5';

        if ($must_warn_missing_php_extension)
        {
            $log_msg = 'Currently passwords are stored weekly in your database. Ask your administrator to install the PHP extension PECL hash Version 1.1 or higher';
            $this->api->logError('plugin', $log_msg );
        }

        return false;
    }



}

