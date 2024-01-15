<?php

class digimember_UserData extends ncore_UserData
{
    public function getUserData( $user_obj_or_id='current' )
    {
        $digistore_authentication_keys = [
            'user_product' => [ 'ds24_', 'auth_key', 'access_key' ],
        ];
        
        $user_id = ncore_userId( $user_obj_or_id );
        
        $metas = $this->subTableMetas();
        
        $metas[ 'user_product' ] = 'user_id';
        
        $data = array();
        
        foreach ($metas as $table => $id_column)
        {
            $remove_keys = ncore_retrieve( $digistore_authentication_keys, $table, array() );
            
            $where     = array( $id_column => $user_id );
            
            $model = $this->api->load->model( "data/$table" );
            
            $all = $model->getAll( $where );
            
            foreach ($all as $one)
            {
                foreach ( (array) $one as $key => $value)
                {
                    foreach ($remove_keys as $prefix)
                    {
                        $must_remove = ncore_stringStartsWith( $key, $prefix );
                        if ($must_remove) {
                            unset( $one->$key );
                        }
                    }
                }
            }
            
            $data[ $table ] = $all;
        }
        
        return $data;
    }    
    
    public function getByFacebookId( $fb_id )
    {
        if (!$fb_id) {
            return false;
        }

        $where = array( 'fb_id' => $fb_id );
        $all = $this->getAll( $where, $limit='0,1', $order_by='id ASC' );

        return $all
               ? $all[0]
               : false;
    }

    public function getFbUserDataByWpUserId( $wp_user_obj_or_id )
    {
        $wp_user_id = is_object( $wp_user_obj_or_id )
                    ? $wp_user_obj_or_id->ID
                    : $wp_user_obj_or_id;

        $where = array( 'user_id' => $wp_user_id );

        return $this->getFbUserDataByWhere( $where );
    }

    public function getFbUserDataByWhere( $where )
    {
        $user = $this->getWhere( $where );

        if (empty($user)
            || empty($user->fb_auth_token)
            || $user->fb_expires_at < ncore_dbDate()) {

                return array( $fb_user_id=false, $fb_auth_token=false, $fb_scopes='', $fb_is_posting_active='N' );
        }

        return array( $user->fb_id, $user->fb_auth_token, $user->fb_scopes, $user->fb_is_posting_active );
    }

    public function setFacebookData( $user_id, $fb_id, $fb_email, $access_token, $access_lifetime, $granted_scopes)
    {
        $data = array(
            'fb_id'         => $fb_id,
            'fb_email'      => $fb_email,
            'fb_auth_token' => $access_token,
            'fb_expires_at' => ncore_dbDate( $access_lifetime < 100000000 ? time()+$access_lifetime : $access_lifetime ),
            'fb_scopes'     => $granted_scopes,
         );

//         $lib = $this->api->load->library( 'facebook_connector' );
//         if ($lib && $lib->canPostFeed( $granted_scopes ))
//         {
//             $data[ 'fb_is_posting_active' ] = 'Y';
//         }

         $this->setData( $user_id, $data );
    }

    public function setFbPosting( $user_id, $is_enabled){

        $data = array();
        $data[ 'fb_is_posting_active' ] = ncore_toYesNoBit( $is_enabled );

        $this->setData( $user_id, $data );
    }

    protected function sqlTableMeta()
    {
        $meta = parent::sqlTableMeta();

        $columns =& $meta['columns'];
        $indexes =& $meta['indexes'];

        $columns[ 'fb_id' ]                = 'string[23]';
        $columns[ 'fb_email' ]             = 'string[127]';
        $columns[ 'fb_auth_token' ]        = 'string[255]';
        $columns[ 'fb_expires_at' ]        = 'datetime';
        $columns[ 'fb_scopes' ]            = 'text';
        $columns[ 'fb_is_posting_active' ] = 'yes_no_bit';

        $indexes[] = 'fb_id';

        return $meta;
    }
    
    protected function subTableMetas()
    {
        $meta = array_merge(
            parent::subTableMetas(),
            array(
                // 'user_product' => 'user_id', -> keep orders of deleted users!
                'ip_counter'   => 'user_id',
                // 'loginkey'     => 'user_id', -> loginkey uses the wordpress user_id (wp_digimember_user.user_id), not wp_digimember_user.id
                'exam_answer'  => 'user_id',
                'counter'      => 'user_id',
            )
        );
        
        return $meta;
    }
    
}