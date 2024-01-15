<?php


/**
* Constants
*/
define( 'DIGIPRO_SHOW_IN_MENU_IF_LOGGED_OUT',   1 << 0 );
define( 'DIGIPRO_SHOW_IN_MENU_IF_NOT_BOUGHT',   1 << 1 );
define( 'DIGIPRO_SHOW_IN_MENU_IF_LOCKED', 1 << 2 );

class digimember_ProductData extends ncore_BaseData
{
    public function setNextType( $product_type )
    {
        $this->next_type = $product_type;
    }

    public function unlockModeOptions()
    {
        return array(
            'order_date' => _digi( '... with order date' ) . ' (' . _digi( 'default' ) . ')',
            'fix_date'   => _digi( '... with the date entered below' ),
        );
    }

    public function productTypeOptions()
    {
        return array(
            'membership' => _digi( 'Membership' ),
            'download'   => _digi( 'download' ),
        );
    }

    public function unlockPolicyOptions()
    {
        return array(
            'all'   => _digi( '.. no content' ),
            'day'   => _digi( '... all content created before the day of purchase' ),
            'month' => _digi( '... all content created before the 1st of the month of purchase' ),
            'last_post' =>  _digi( '... last published post and all content created since the day of purchase' ),
        );
    }

    public function validateProduct( $product_obj_or_id )
    {
        $product = $this->resolveToObj( $product_obj_or_id );
        if (!$product) {
            return false;
        }

        $have_waiver = ncore_isTrue( $product->is_right_of_withdrawal_waiver_required );
        if (!$have_waiver)
        {
            return false;
        }

        $waiver_page_id = $product->right_of_withdrawal_waiver_page_id;
        if (!$waiver_page_id)
        {
            return _digi( 'Please select a page for %s.', '<strong>'._digi('Page with Waiver Declaration' ).'</strong>' );
        }

        /** @var digimember_PageProductData $pageProductData */
        $pageProductData = $this->api->load->model( 'data/page_product' );

        $is_protected = $pageProductData->isProtected( $waiver_page_id );

        if ($is_protected) {
            return _digi( 'The page selected for %s is protected and may not be accessible by the user.', '<strong>'._digi('Page with Waiver Declaration' ).'</strong>' );
        }

        return false;

    }

    /**
     * @param $row
     *
     * @return string
     */
    public function getProductUrl($row)
    {
        $url = ncore_retrieve( $row, 'shortcode_url' );
        if (!$url)
        {
            $url = $row->login_url;
        }
        if (!$url)
        {
            $url = $row->first_login_url;
        }

        return ncore_resolveUrl( $url );
    }

    /**
     * @param array $where
     *
     * @return array
     */
    public function getProductUrls($where = [])
    {
        $products = $this->getAll(array_merge([
            'type' => 'membership',
            'published !=' => null,
        ], $where));
        $ret = [];
        foreach ($products as $product) {
            $ret[$product->id] = $this->getProductUrl($product);
        }
        return $ret;
    }

    public function sqlTableName()
    {
        return parent::sqlTableName();
    }

    public function downloadMaxFilesize()
    {
        $hundred_mb = 104857600;

        return min( $hundred_mb, ncore_maxUploadFilesize() );
    }

    public function downloadTypeOptions()
    {
        return array(
            'url'    => _digi( 'Url' ),
            'upload' => _digi( 'Upload' ),
        );
    }

    public function options( $product_type='membership', $public_only=false, $id_column='id' )
    {
        $valid_types = array( 'membership', 'download', 'all' );

        assert( in_array( $product_type, $valid_types ) );

        $options =& $this->options[ $product_type . '_' . intval($public_only) . '_' . $id_column ];

        if (!isset($options)) {

            $where = $product_type === 'all'
                   ? array()
                   : array( 'type' => $product_type );

            if ($public_only) {
                $where[ 'published !=' ] = null;
            }

            $options = $this->asArray( 'name', $id_column, $where );
        }

        return $options;
    }

    public function optionsWithAuthKeys( $product_type='membership', $public_only=false )
    {
        return $this->options( $product_type, $public_only, 'access_key' );

    }

    public function resolveAccessKeys( $access_keys_as_list_or_comma_seperated, $public_only=true )
    {
        static $cache;

        $public_only = intval( $public_only );

        $access_key_id_map =& $cache[ $public_only ];

        if (!isset($access_key_id_map)) {

            $where = array();
            if ($public_only) {
                $where[ 'published !=' ] = null;
            }

            $all = $this->getAll( $where );

            foreach ($all as $one)
            {
                $access_key_id_map[ $one->access_key ]= $one->id;
            }
        }



        if (is_array($access_keys_as_list_or_comma_seperated)) {

            $list =& $access_keys_as_list_or_comma_seperated;

            $result = array();

            foreach ($list as $index => $access_keys )
            {
                $result[ $index ] = $this->resolveAccessKeys( $access_keys );
            }

            return $result;
        }
        elseif ($access_keys_as_list_or_comma_seperated)
        {
            $list = explode( ',', $access_keys_as_list_or_comma_seperated );

            $result = array();

            foreach ($list as $index => $access_keys )
            {
                $product_id = ncore_retrieve( $access_key_id_map, $access_keys );
                if ($product_id) {
                    $result[ $index ] = $product_id;
                }
            }

            return implode( ',', $result );
        }
    }

    public function getAllDS24ProductIdsCommaSeperated()
    {
        $all = $this->getAll();

        $ds24_product_ids = array();

        foreach ($all as $one)
        {
            if (!$one->published) {
                continue;
            }
            $one_ids = explode( ',', $one->ds24_product_ids );

            foreach ($one_ids as $id)
            {
                if (!$id) {
                    continue;
                }
                if ($id === 'all') {
                    return 'all';
                }

                $id = intval($id);

                if (!in_array( $id, $ds24_product_ids)) {
                    $ds24_product_ids[] = $id;
                }
            }
        }

        if (empty($ds24_product_ids)) {
            return 'none';
        }

        sort( $ds24_product_ids, SORT_NUMERIC );

        return implode( ',', $ds24_product_ids );
    }

    public function setupChecklistDone()
    {
        $where = array( 'published !=' => null );
        $all = $this->getAll( $where );
        return (bool) $all;
    }

    public function flags()
    {
        return array(
           DIGIPRO_SHOW_IN_MENU_IF_LOGGED_OUT => _digi( 'Show page in menu, if user is not logged in.' ),
           DIGIPRO_SHOW_IN_MENU_IF_NOT_BOUGHT => _digi( 'Show page in menu, if user is logged in and has not bought the product.' ),
           DIGIPRO_SHOW_IN_MENU_IF_LOCKED => _digi( 'Show page in menu, if user has bought the product, but the page is not yet unlocked.' ),
        );
    }

    public function flagsShort()
    {
        return array(
           DIGIPRO_SHOW_IN_MENU_IF_LOGGED_OUT => _digi( 'In menu if logged out' ),
           DIGIPRO_SHOW_IN_MENU_IF_NOT_BOUGHT => _digi( 'In menu if not bought' ),
           DIGIPRO_SHOW_IN_MENU_IF_LOCKED => _digi( 'In menu if not unlocked' ),
        );
    }

    public function propertyMetas()
    {
        if ($this->product_metas === false)
        {
            $this->product_metas = apply_filters( 'digimember_product_properties', $metas=array() );
        }

        return $this->product_metas;
    }

    public function status( $row )
    {
        $status = parent::status( $row );

        $is_deleted = $status == 'deleted';

        $is_published = (bool) ncore_retrieve( $row, 'published' );

        if ($is_deleted)
        {
            return 'deleted';
        }

        if ($is_published)
        {
            return 'published';
        }

        return $status;
    }

    public function statusLabels()
    {
        $labels = parent::statusLabels();

        $labels['published'] = _digi('published');
        $labels['created'] = _digi('draft');

        return $labels;
    }

    public function publish( $id )
    {
        $data = array(
            'published' => ncore_dbDate(),
        );

        return $this->update( $id, $data );
    }

    public function unpublish( $id )
    {
        $data = array(
            'published' => null,
        );

        return $this->update( $id, $data );
    }

    public function accessDeniedOptions()
    {
        return array(
            DIGIMEMBER_AD_LOGIN => _digi( 'Display login form' ),
            DIGIMEMBER_AD_PAGE  => _digi( 'Redirect to Wordpress page' ),
            DIGIMEMBER_AD_URL   => _digi( 'Redirect to external URL' ),
            DIGIMEMBER_AD_TEXT  => _digi( 'Display a text' ),
        );
    }

    public function accountLockOptions()
    {
        return array(
            DIGIMEMBER_AL_NONE   => _digi( 'No (this is the default)' ),
            DIGIMEMBER_AL_PAGE   => _digi( 'Yes, lock Wordpress account and redirect to page' ),
            DIGIMEMBER_AL_URL    => _digi( 'Yes, lock Wordpress account and redirect to URL' ),
            DIGIMEMBER_AL_TEXT   => _digi( 'Yes, lock Wordpress account and display message' ),
        );
    }

    public function contentLaterTypeOptions()
    {
        return array(
            DIGIMEMBER_AD_PAGE  => _digi( 'Redirect to Wordpress page' ),
            DIGIMEMBER_AD_URL   => _digi( 'Redirect to external URL' ),
            DIGIMEMBER_AD_TEXT  => _digi( 'Display a text' ),
        );
    }


    public function accountLockDefaultMessage()
    {
        return _digi( 'Your account has been locked, because a payment was cancelled.' );
    }

    public function update( $obj_or_id, $data, $where = array() )
    {
        $have_properties = isset( $data['properties'] );
        if ($have_properties)
        {
            $properties = $data['properties'];
            $data['properties_serialized'] = @serialize( $properties );
        }
        return parent::update( $obj_or_id, $data, $where );
    }

    public function addProperties( $id, $new_properties )
    {
        if (empty($new_properties) || !is_array($new_properties))
        {
            return;
        }

        $product = $this->get( $id );

        $properties = $product->properties;
        foreach ($new_properties as $key => $value)
        {
            $properties[ $key ] = $value;
        }
        $data = array( 'properties' => $properties );
        $this->update( $id, $data );
    }

    //
    // protected section
    //
    protected function notCopiedColumns()
    {
        $columns = parent::notCopiedColumns();
        $columns[] = 'published';
        $columns[] = 'is_ds24_sync_enabled';
        $columns[] = 'ds24_last_sync_at';
        $columns[] = 'ds24_sync_product_id';
        $columns[] = 'ds24_sync_payplan_id';
        $columns[] = 'ds24_sync_user_id';
        $columns[] = 'ds24_sync_user_name';
        return $columns;
    }

    protected function callOnUpdateDiff() {
        return true;
    }

    protected function onUpdateDiff( $new_object, $old_object ) {

        $old_ds24_product_ids = ncore_retrieve( $old_object, 'ds24_product_ids', '' );
        $new_ds24_product_ids = ncore_retrieve( $new_object, 'ds24_product_ids', '' );

        $is_modified = $old_ds24_product_ids != $new_ds24_product_ids;
        if ($is_modified) {
            /** @var ncore_EventSubscriberLogic $model */
            $model = $this->api->load->model( 'logic/event_subscriber' );
            $model->call( 'dm_ds24_product_ids_changed' );
        }
    }

    protected function sqlBaseTableName()
    {
        return 'product';
    }

    protected function subModelsToCopy()
    {
        $models = parent::subModelsToCopy();

        $models[ 'page_product' ] = 'product_id';

        return $models;
    }

    protected function defaultOrder()
    {
        return 'name ASC, id ASC';
    }

    protected function hasTrash()
    {
        return true;
    }

    protected function hasModified()
    {
        return true;
    }

    protected function sqlTableMeta()
    {
       $columns = array(

        'unlock_mode'       =>  array( 'type' => 'string[15]', 'default' => 'order_date' ),
        'unlock_start_date' =>  array( 'type' => 'datetime',       'default' => ncore_dbDate() ),

        'is_right_of_withdrawal_waiver_required' => array( 'type' => 'yes_no_bit', 'default' => 'N' ),
        'right_of_withdrawal_waiver_page_id'     => 'id',

        'name' => 'string[63]',

        'auth_key' => 'string[15]',

        'type' => array( 'type' => 'string[15]', 'default' => 'membership' ),

        'access_denied_type' => 'string[15]',
        'access_denied_priority' => array( 'type' => 'yes_no_bit', 'default' => 'N' ),
        'access_denied_url'  => 'string[255]',
        'access_denied_page' => 'int',
        'access_denied_text' => 'text',

        'unlock_policy' => array( 'type' => 'string[15]', 'default' => 'all' ),

        'are_comments_protected' => array( 'type' => 'yes_no_bit', 'default' => 'N' ),

        'lock_type' => 'string[15]',
        'lock_url'  => 'string[255]',
        'lock_page' => 'int',
        'lock_text' => 'text',

        'first_login_url' => 'string[255]',
        'login_url'       => 'string[255]',
        'shortcode_url'   => 'string[255]',
        'flags'           => 'int',
        'published'       => 'lock_date',

        'access_granted_for_days' => 'int',
        'max_download_times'      => 'int',

        'sales_letter'          => 'text',

        'content_later_type'    => array( 'type' => 'string[15]', 'default' => DIGIMEMBER_AD_TEXT ),
        'content_later_url'     => 'string[255]',
        'content_later_page'     => 'int',
        'content_later_msg'     => 'text',

        'properties_serialized' => 'text',
        'ds24_product_ids'      => 'text',

        'is_ds24_sync_enabled' => 'yes_no_bit',

        'ds24_sync_user_id'    => 'int',
        'ds24_sync_user_name'  => 'string[31]',
        'ds24_sync_product_id' => 'int',
        'ds24_sync_payplan_id' => 'int',
        'ds24_sync_image_id'   => 'string[15]',
        'ds24_sync_image_url'  => 'string[255]',
        'ds24_last_sync_at'    => 'datetime',

        'ds24_description'          => 'text',
        'ds24_currency'             => 'string[7]',
        'ds24_first_amount'         => 'decimal',
        'ds24_other_amounts'        => 'decimal',
        'ds24_affiliate_commission' => 'decimal',
        'ds24_image_url'            => 'string[255]',

        'ds24_salespage'            => 'string[255]',
        'ds24_thankyoupage'         => 'string[255]',

        'ds24_approval_status'      => 'string[15]',
        'ds24_approval_status_msg'  => 'string[31]',

       );

       $metas = $this->propertyMetas();
       foreach ($metas as $one)
       {
           $name = ncore_retrieve( $one, 'name' );
           $type = ncore_retrieve( $one, 'type' );

           $columns[ $name ] = $type;
       }

       $indexes = array( 'login_url', 'type' );

       return array(
        'columns' => $columns,
        'indexes' => $indexes,
       );
    }

    protected function subTableMetas()
    {
        return array(
            'user_product' => 'product_id',
            'page_product' => 'product_id',
        );
    }

    protected function buildObject( $object )
    {
        $is_new = empty( $object->id );

        parent::buildObject( $object );

        if ($is_new)
        {
            $object->access_key = '';
        }
        else
        {
            if (isset($object->auth_key) && !$object->auth_key) {
                $this->api->load->helper( 'string' );
                $auth_key = ncore_randomString( 'alnum', 15 );

                $table = $this->sqlTableName();
                $sql = "UPDATE `$table` SET auth_key='$auth_key' WHERE id=$object->id";
                $this->db()->query( $sql );

                $object->auth_key = $auth_key;
            }
            $object->access_key = empty($object->auth_key)
                                ? ''
                                : 'p' . $object->id . 'x' . $object->auth_key;
        }


        $flags = $object->flags;

        $object->show_in_menu_if_logged_out = (bool) ($flags & DIGIPRO_SHOW_IN_MENU_IF_LOGGED_OUT);
        $object->show_in_menu_if_not_bought = (bool) ($flags & DIGIPRO_SHOW_IN_MENU_IF_NOT_BOUGHT);
        $object->show_in_menu_if_locked     = (bool) ($flags & DIGIPRO_SHOW_IN_MENU_IF_LOCKED);

        $serialized = ncore_retrieve( $object, 'properties_serialized' );
        if ($serialized)
        {
            $object->properties = @unserialize( $serialized );
        }
        if (empty($object->properties))
        {
            $object->properties = array();
        }

        if (empty($object->ds24_approval_status)) {
            $object->ds24_approval_status = 'new';
        }
    }

    public function productAccessGranted($product, $fromDate) {
        $accessRestriction = ncore_retrieve( $product, 'access_granted_for_days', 0 );
        if ($accessRestriction > 0) {
            list( $fromDate, ) = explode( ' ', $fromDate );
            list( $now_date, ) = explode( ' ', ncore_dbDate() );
            $order_unix = strtotime( $fromDate );
            $now_unix = strtotime( $now_date );
            $age_in_seconds = $now_unix - $order_unix;
            $age_in_days = floor( $age_in_seconds / 86400 );
            if ($age_in_days > $accessRestriction) {
                return false;
            }
        }
        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values['type'] = $this->next_type;
        $values['flags'] = 0;
        $values['published'] = null;
        $values['name'] = _digi('new product');
        $values['access_denied_type'] = DIGIMEMBER_AD_LOGIN;
        $values['access_denied_url'] = '/';
        $vaules['lock_type'] = DIGIMEMBER_AL_NONE;
        $vaules['lock_url'] = '/';
        $vaules['lock_text'] = $this->accountLockDefaultMessage();
        $values['published'] = ncore_dbDate();
        $values['sales_letter']        = $this->defaultSalesLetter($this->next_type);
        $values['access_denied_text']  = $this->defaultSalesLetter($this->next_type);
        $values['content_later_type' ] = DIGIMEMBER_AD_TEXT;
        $values['content_later_msg' ]  = _digi( 'This content will be unlocked %s on %s.', '[IN_DAYS]', '[DATE]' );

        $values['are_comments_protected'] = 'Y';

        $values['is_ds24_sync_enabled']   = 'N';
        $values['ds24_last_sync_at'] = '2000-01-01 00:00:00';
        $values['ds24_approval_status' ] = 'new';
        $values['ds24_first_amount' ] = 27;
        $values[ 'is_right_of_withdrawal_waiver_required' ] = 'N';

        $metas = $this->propertyMetas();
        foreach ($metas as $one)
        {
           $name    = ncore_retrieve( $one, 'name' );
           $default = ncore_retrieve( $one, 'default' );

           $values[ $name ] = $default;
        }

        $this->api->load->helper( 'string' );
        $values[ 'auth_key' ] = ncore_randomString( 'alnum', 15 );

        return $values;
    }

    private $options = array();
    private $product_metas = false;
    private $next_type = 'membership';

    private function defaultSalesLetter( $type='membership' )
    {
        switch ($type)
        {
            case 'membership':
                /** @var digimember_ShortCodeController $controller */
                $controller = $this->api->load->controller( 'shortcode' );
                $shortcode = $controller->shortcode( 'login' );

                $shortcode = "[$shortcode hidden_if_logged_in stay_on_same_page]";

                return _digi( "This is protected content. Please login to view it." )
                       . "\n\n<p>$shortcode</p>";

            case 'download':
            default:
                return '';
        }
    }

    public function addPriotizingIfNeeded() {
        $row = false;
        global $wpdb;
        $table_name = $this->sqlTableName();
        $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$table_name."' AND column_name = 'access_denied_priority'"  );
        if ( is_array($row) && count($row) < 1 ) {
            $initCore = $this->api->init();
            $initCore->forceUpgrade();
            return true;
        }
        return false;
    }



}
