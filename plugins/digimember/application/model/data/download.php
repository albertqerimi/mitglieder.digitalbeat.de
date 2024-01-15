<?php

class digimember_DownloadData extends ncore_BaseData
{
    public function downloadPageUrl( $obj_or_id, $arg_sep='&', $url_encode=false )
    {
        $obj = $this->resolveToObj( $obj_or_id );
        if (!$obj) {
            return '';
        }

        $model = $this->api->load->model( 'data/product' );
        $product = $model->get( $obj->product_id );
        if (!$product) {
            return '';
        }

        $url = ncore_resolveUrl( $product->login_url );
        if (!$url)
        {
            return '';
        }

        $args = array( DIGIMEMBER_THANKYOUKEY_GET_PARAM => $obj->access_key );
        $url = ncore_addArgs( $url, $args, $arg_sep, $url_encode );

        return $url;
    }

    public function downloadsLeft( $url )
    {
        if (!$this->authorized_objs) {
            return 0;
        }

        $key = md5($url);

        $min_count = false;

        foreach ($this->authorized_objs as $obj)
        {
            $is_unlimited = $obj->max_download_times<=0;
            if ($is_unlimited) {
                return false;
            }

            $count = ncore_retrieve( $obj->data, $key, 0 );



            $downloads_left = max( 0, $obj->max_download_times - $count) ;

            if ($min_count === false || $min_count > $downloads_left)
            {
                $min_count = $downloads_left;
            }
        }

        return $min_count;
    }

    public function countDownload( $url )
    {
        if (!$this->authorized_objs) {
            return false;
        }

        $model = $this->api->load->model( 'logic/session' );

        $key = md5($url);
        $time_counted_unix = $model->get( $key, false );

        $grace_seconds = NCORE_DEBUG
                       ? 86400
                       : 600;

        if ($time_counted_unix && $time_counted_unix>time()-$grace_seconds) {
            return;
        }
        $model->set( $key, time() );

        foreach ($this->authorized_objs as $obj)
        {
            $count = ncore_retrieve( $obj->data, $key, 0 );

            $obj->data[ $key ] = $count+1;

            $data = array( 'data' => $obj->data );

            $this->update( $obj, $data );
        }
    }

    public function checkAccess()
    {
        $is_authorized = (bool) $this->authorized_objs;
        if ($is_authorized) {
            return;
        }

        $page_id = get_the_ID();
        $where = array( 'type' => 'download', 'login_url' => $page_id );

        $model = $this->api->load->model( 'data/product' );
        $products = $model->getAll( $where );

        if (!$products) {
            return;
        }

        $product = $products[0];
        $this->_accessDenied( $product );
    }


    public function authenticate( $access_key )
    {
        list( $id, $auth_key ) = $this->parseAccessKey( $access_key );
        if (!$id) {
            return;
        }

        $this->api->load->helper( 'encryption' );

        $obj = $this->get( $id );
        if (!$obj) {
            return;
        }

        $is_auth_key_valid = ncore_hashCompare( $obj->auth_key, $auth_key );
        $is_expired = $obj->expires
                   && $obj->expires < ncore_dbDate();
        $is_inactive = $obj->is_active != 'Y';
        if (!$is_auth_key_valid || $is_expired || $is_inactive) {
            $model = $this->api->load->model( 'data/product' );
            $product = $model->get( $obj->product_id );
            $this->_accessDenied( $product );
            return;
        }

        $this->authorized_objs[] = $obj;
    }

    public function getAuthorizedDownloads()
    {
        return $this->authorized_objs;
    }

    public function setAuthorizedDownloads( $ids )
    {
        if (!$ids) {
            return;
        }

        if (!is_array($ids)) {
            $ids = explode( ',', $ids );
        }

        foreach ($ids as $id)
        {
            $id = ncore_washInt( $id );
            if (!$id) {
                continue;
            }

            $obj = $this->get( $id );
            if (!$obj) {
                continue;
            }

            $this->authorized_objs[] = $obj;
        }
    }

    public function setNewOrderId( $order_id )
    {
        $this->new_order_id = $order_id;
    }

    public function createTestUrl( $email, $product_obj_or_id )
    {
        $this->api->load->model( 'data/product' );
        $product = $this->api->product_data->resolveToObj( $product_obj_or_id );
        if (!$product) {
            return false;
        }

        $data = array();
        $data[ 'payment_provider_id' ] = 0;
        $data[ 'order_id' ]            = 'test';
        $data[ 'product_id' ]          = $product->id;
        $data[ 'email' ]               = $email;
        $data[ 'max_download_times' ]  = $product->max_download_times;

        if ($product->access_granted_for_days>0)
        {
            $expire = ncore_unixDate() + 86400 * $product->access_granted_for_days;
            $data[ 'expires' ] = ncore_dbDate( $expire, 'date' );
        }

        $id = $this->create( $data );

        return $id;
    }

    public function grant( $payment_provider_id, $order_id, $email, $product_obj_or_id )
    {
        $this->api->load->model( 'data/product' );
        $product = $this->api->product_data->resolveToObj( $product_obj_or_id );
        if (!$product) {
            return false;
        }

        $data = array();
        $data[ 'payment_provider_id' ] = $payment_provider_id;
        $data[ 'order_id' ]            = $order_id;
        $data[ 'product_id' ]          = $product->id;
        $data[ 'email' ]               = $email;
        $data[ 'max_download_times' ]  = $product->max_download_times;

        if ($product->access_granted_for_days>0)
        {
            $expire = ncore_unixDate() + 86400 * $product->access_granted_for_days;
            $data[ 'expires' ] = ncore_dbDate( $expire, 'date' );
        }

        $id = $this->create( $data );

        return $id;
    }

    public function revoke( $payment_provider_id, $order_id, $reason )
    {
        $payment_provider_id = ncore_washInt( $payment_provider_id );
        $order_id = ncore_washText( $order_id );

        $table = $this->sqlTableName();
        $sql = "UPDATE $table
                SET is_active = 'N'
                WHERE payment_provider_id = $payment_provider_id
                  AND order_id = '$order_id'";
        $this->db()->query( $sql );
    }


    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'download';
    }

    protected function serializedDataMeta()
    {
        return array(
            //'data_serialized' => 'counter_',
        );
    }

    protected function serializedColumns()
    {
        return array(
            'data',
        );
    }


    protected function sqlTableMeta()
    {
       $columns = array(
        'product_id'          => 'id',
        'order_id'            => 'string[31]',
        'is_active'           => 'yes_no_bit',
        'payment_provider_id' => 'id',
        'email'               => 'string[127]',
        'auth_key'            => 'string[31]',
        'expires'             => 'lock_date',
        'max_download_times'  => 'int',
       );

       $indexes = array( 'order_id', 'product_id', 'email' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function buildObject( $obj )
    {
        parent::buildObject( $obj );

        $obj->access_key = empty( $obj->id)
                         ? ''
                         : 'd' . $obj->id . 'x' . $obj->auth_key;

        if (empty($obj->counter_url)) {
            $obj->counter_url = array();
        }
    }


    protected function hasTrash()
    {
        return true;
    }

    protected function defaultValues()
    {
        $this->api->load->helper( 'string' );

        $values = parent::defaultValues();

        $order_id = $this->new_order_id
                  ? $this->new_order_id
                  : ncore_randomString( 'alnum_lower', 12 );

        $values['order_id'] = $order_id;
        $values['is_active'] = 'Y';
        $values['auth_key'] = ncore_randomString( 'alnum', 30 );

        return $values;
    }

    protected function hasModified()
    {
        return true;
    }

    private $new_order_id = false;
    private $authorized_objs = array();

    private function parseAccessKey( $access_key )
    {
        $access_key = trim($access_key);
        if (!$access_key) {
            return array( 0, '' );
        }

        $type = $access_key[0];
        if ($type!=='d') {
            return array( 0, '' );
        }

        $pos = strpos( $access_key, 'x' );
        if ($pos===false || $pos<2)
        {
            return array( 0, '' );
        }

        $id  = substr( $access_key, 1, $pos-1 );
        $key = substr( $access_key, $pos+1 );

        if (!is_numeric($id) || !$key)
        {
            return array( 0, '' );
        }

        return array( $id, $key );
    }

    private function _accessDenied( $product )
    {
        switch ($product->access_denied_type)
        {
            case DIGIMEMBER_AD_URL:
                $url = $product->access_denied_url;
                break;

            case DIGIMEMBER_AD_PAGE:
                $url = $product->access_denied_page;
                break;

            default:
                $url = '';
        }

        $this->api->load->helper( 'url' );
        $url = ncore_resolveUrl( $url );

        if (!$url) {
            $url = ncore_siteUrl();
        }

        ncore_redirect( $url );
    }
}
