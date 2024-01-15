<?php

class digimember_UserProductData extends ncore_BaseData
{
    public function setNewOrderId( $order_id )
    {
        $this->new_order_id = $order_id;
    }

    public function legalRefundDays()
    {
        return 14;
    }

    public function getOwnersOf( $product_id_or_ids )
    {
        $product_ids = $this->_escapeProductIds( $product_id_or_ids );
        $table_name  = $this->sqlTableName();

        if (!$product_id_or_ids) {
            return array();
        }

        $sql = "SELECT user_id
                FROM $table_name
                WHERE product_id IN ($product_ids)
                  AND is_active = 'Y'";
        $rows = $this->db()->query( $sql );

        $result = array();
        foreach ($rows as $one)
        {
            $result[] = $one->user_id;
        }
        return $result;
    }

    public function getNonOwnersOf( $product_id_or_ids )
    {
        $this->api->load->model( 'data/user' );
        $product_ids = $this->_escapeProductIds( $product_id_or_ids );

        $user_product  = $this->sqlTableName();
        $user          = $this->api->user_data->sqlTableName();

        if (!$product_id_or_ids) {
            return array();
        }

        $sql = "SELECT u.id
                FROM $user u
                LEFT JOIN $user_product p
                    ON u.id = p.user_id
                  AND product_id IN ($product_ids)
                WHERE p.id IS NULL";
        $rows = $this->db()->query( $sql );

        $result = array();
        foreach ($rows as $one)
        {
            $result[] = $one->id;
        }
        return $result;
    }

    public function getExpiredForDay( $day=0, $product_id_comma_seperated = 'all' )
    {
        if (!$product_id_comma_seperated) {
            return array();
        }

        $product_id_comma_seperated = ncore_washText( $product_id_comma_seperated, ',' );
        $product_ids = explode( ',', $product_id_comma_seperated );
        $have_all = in_array( 'all', $product_ids );

        $sql_product_ids = $have_all
                         ? ''
                         : "AND p.id IN ('" . implode( "','", $product_ids) . "')";


        $day = ncore_washInt( $day );
        $now = ncore_dbDate( 'now', 'date' );

        $this->api->load->model( 'data/product' );
        $product_table = $this->api->product_data->sqlTableName();
        $user_table = $this->sqlTableName();

        $sql = "SELECT u.*
                FROM $product_table p
                INNER JOIN $user_table u
                    ON p.id = u.product_id
                WHERE (p.deleted IS NULL)
                  AND u.is_active = 'Y'
                  AND (u.deleted IS NULL)
                  AND p.access_granted_for_days > 0
                  AND DATE(u.last_pay_date + INTERVAL p.access_granted_for_days DAY) = DATE('$now' + INTERVAL $day DAY)
                  AND (access_starts_on IS NULL OR access_starts_on < NOW())
                  AND (access_stops_on  IS NULL OR access_stops_on  > NOW())
                  AND (p.published IS NOT NULL)
                  $sql_product_ids";

        return $this->getBySql( $sql );

    }

    protected function sqlExtraColumns()
    {
        return array(
            'is_right_of_rescission_waived'  => 'IF( right_of_rescission_waived_at IS NULL, "N", "Y" )',
        );
    }


    protected function defaultOrder()
    {
        return 'id DESC';
    }

    public function countMembers()
    {
        $count = ncore_cacheRetrieve( 'pay_member_count' );

        if ($count===false)
        {
            $table = $this->sqlTableName();

            $sql = "SELECT COUNT(DISTINCT user_id) as count
                    FROM $table
                    WHERE payment_provider_id>=1
                      AND last_pay_date >= NOW() - INTERVAL 1 YEAR
                      AND (access_starts_on IS NULL OR access_starts_on < NOW())
                      AND (access_stops_on  IS NULL OR access_stops_on  > NOW())";

            $rows = $this->db()->query( $sql );

            $count = (int) $rows[0]->count;

            ncore_cacheStore( 'pay_member_count', $count, 80000 );
        }

        return $count;
    }

    public function getUsernameAndPasswordOfThankyouPageVisitor()
    {
        if ($this->thankyou_page_user_id!==false)
        {
            return array( $this->thankyou_page_user_id, $this->thankyou_page_password );
        }

        $this->thankyou_page_user_id  = 0;
        $this->thankyou_page_password = '';

        $model = $this->api->load->model( 'data/user' );

        foreach ($this->authorized_objs as $one)
        {
            $user_id = $one->user_id;

            $this->thankyou_page_user_id  = $user_id;
            $this->thankyou_page_password = $model->getPassword( $user_id );
            break;
        }

        return array( $this->thankyou_page_user_id, $this->thankyou_page_password );
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
        $is_active = $obj->is_active == 'Y';
        if ($is_auth_key_valid && $is_active) {
            $this->authorized_objs[] = $obj;
        }
    }

    public function cronDaily()
    {
        $this->_resetQuantites();

        $this->_queueNewContentActions();
    }

    public function getForUser( $user_id='current', $order_by = 'id ASC' )
    {
        if ($user_id==='current')
        {
            $user_id = ncore_userId();
        }

        if (!$user_id)
        {
            return array();
        }

        static $cache;
        $products =& $cache[ $user_id ][ $order_by ];

        if (!isset($products))
        {
            $where = array( 'user_id' => $user_id, 'is_active' => 'Y' );

            $products = array();

            $limit = false;

            $rows = $this->getAll( $where, $limit, $order_by );

            foreach ($rows as $row)
            {
                $products[] = $row;
            }
        }

        return $products;
    }

    public function getForUserAndProduct( $user_id, $product_id )
    {
        $products = $this->getForUser( $user_id, 'order_date DESC' );

        foreach ($products as $one)
        {
            if ($one->product_id == $product_id)
            {
                return $one;
            }
        }

        return false;
    }

    public function hasProduct( $user_id, $product_id_or_ids )
    {
        if (!is_numeric($user_id)) {
            return false;
        }

        $product_ids = is_array( $product_id_or_ids )
                     ? $product_id_or_ids
                     : explode( ',', $product_id_or_ids );

        $do_search_for_any_product = in_array( 'all', $product_ids );

        $present_products = $this->getForUser( $user_id );
        foreach ($present_products as $one)
        {
            if (in_array( $one->product_id, $product_ids ) || $do_search_for_any_product) {
                return true;
            }
        }

        return false;
    }

    /**
     * function to create a list with all product links a user has currently access to
     * @param $user_id
     * @return array
     */
    public function getAccessableProductLinks( $user_id )
    {
        /** @var digimember_ProductData $product_model */
        $product_model = $this->api->load->model( 'data/product' );

        $products = $this->getForUser( $user_id );
        if (!$products)
        {
            return array();
        }

        $used_product_ids = array();

        $entries = array();
        $sort = array();

        $strtolower = function_exists( 'mb_strtolower' )
                    ? 'mb_strtolower'
                    : 'strtolower';

        foreach ($products as $one)
        {
            $product_id = $one->product_id;
            $product = $product_model->getCached( $product_id );
            if (!$product)
            {
                continue;
            }

            // fix if the product is currently inactive, it shouldn't be displayed in the list
            if ($product->status != 'published') {
                continue;
            }

            $is_used = in_array( $product_id, $used_product_ids );
            if ($is_used)
            {
                continue;
            }
            $used_product_ids[] = $product_id;
            $sort[] = $strtolower( $product->name );

            $url = ncore_retrieve( $product, 'shortcode_url' );
            if (!$url)
            {
                $url = $product->login_url;
            }
            if (!$url)
            {
                $url = $product->first_login_url;
            }

            $entry = new stdClass;
            $entry->label   = $product->name;
            $entry->id      = $product->id;
            $entry->url     = ncore_resolveUrl( $url );

            $entries[] = $entry;
        }

        array_multisort( $sort, $entries );

        return $entries;
    }

    public function giveToAll( $product_id )
    {
        $this->api->load->helper( 'array' );

        ignore_user_abort(true);

        $user_ids = $this->getAllUsers();

        $where = array( 'product_id' => $product_id );
        $all = $this->getAll( $where );

        $user_products = ncore_listToArray( $all, 'user_id', 'id' );

        $count = 0;

        $data = array(
            'product_id' => $product_id,
            'order_id' => _digi('by admin' ),
        );

        $user_id_col = 'ID';
        $created_col = 'user_registered';

        foreach ($user_ids as $obj)
        {
            $user_id = $obj->$user_id_col;

            $have_product = isset( $user_products[ $user_id ] );
            if ($have_product)
            {
                continue;
            }

            $data['user_id'] = $user_id;
            $data['order_date']    = $obj->$created_col;
            $data['last_pay_date'] = $obj->$created_col;


            $this->create( $data );

            $count++;
        }


        return $count;
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
        return 'user_product';
    }

    protected function sqlTableMeta()
    {
       $columns = array(

        'right_of_rescission_waived_at' => 'lock_date',
        'right_of_rescission_waived_by' => 'string[15]',

        'user_id'             => 'id',
        'product_id'          => 'id',
        'order_id'            => 'string[31]',
        'order_date'          => 'datetime',
        'last_pay_date'       => 'datetime',
        'is_active'           => 'yes_no_bit',
        'payment_provider_id' => 'id',
        'auth_key'            => 'string[31]',

        'billing_type'        => 'string[15]',

        'last_age_in_day_notified' => 'int',

        'has_visited_login_page' => 'yes_no_bit',

        'access_starts_on' => 'lock_date',
        'access_stops_on'  => 'lock_date',

        'quantity'                       => array( 'type' => 'int', 'default' => 1 ),
        'quantity_after_quantity_change' => 'int',
        'quantity_changes_at'            => 'lock_date',

        'ds24_full_access_logged'        => array( 'type' => 'yes_no_bit', 'default' => 'N' ),

        'ds24_upgrade_key'               => 'string[31]',
        'ds24_affiliate_name'            => 'string[31]',
        'ds24_purchase_key'              => 'string[31]',
        'ds24_refund_days'               => array( 'type' => 'int', 'default' => 14 ),

        'ds24_receipt_url'              => 'string[127]',
        'ds24_renew_url'                => 'string[127]',
        'ds24_add_url'                  => 'string[127]',
        'ds24_support_url'              => 'string[127]',
        'ds24_rebilling_stop_url'       => 'string[127]',
        'ds24_request_refund_url'       => 'string[127]',
        'ds24_become_affiliate_url'     => 'string[127]',

        'ds24_newsletter_choice'        => 'string[15]',
       );


//       if (ncore_haveProductTrackingCode())
//       {
//           $columns['is_order_tracked'] = 'yes_no_bit';
//       }

       $indexes = array( 'user_id', 'product_id' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function buildObject( $obj )
    {
        $is_new = empty( $obj->id );

        parent::buildObject( $obj );

        $obj->access_key = $is_new
                         ? ''
                         : 'u' . $obj->id . 'x' . $obj->auth_key;

        if ($is_new) {
            return;
        }

        $this->_maybeChangeQuantityAfterPlanSwitch( $obj );

        $obj->age_in_days       = $this->ageInDays( $obj );
        $obj->last_payment_days = $this->ageInDays( $obj, 'last_pay_date' );

        if ($obj->age_in_days < $obj->last_payment_days)
        {
            $obj->last_payment_days = $obj->age_in_days;
        }

        $must_fix_dm_1 = ($obj->access_stops_on  && $obj->access_stops_on[0] === '0');
        if ($must_fix_dm_1)
        {
            $table = $this->sqlTableName();
            $sql = "UPDATE $table SET access_stops_on=NULL WHERE access_stops_on='0000-00-00 00:00:00'";
            $this->db()->query( $sql );
            $obj->access_stops_on = NULL;
        }

        $now = ncore_dbDate( 'now', 'date' );

        $obj->is_access_too_early      = $obj->access_starts_on && $now <  ncore_dbDate( $obj->access_starts_on, 'date' );
        $obj->is_access_too_late       = $obj->access_stops_on  && $now >= ncore_dbDate( $obj->access_stops_on,  'date' );

        /** @var digimember_ProductData $model */
        $model = $this->api->load->model( 'data/product' );
        $product = $model->getCached( $obj->product_id );
        $access_granted_for_days = ncore_retrieve( $product, 'access_granted_for_days', 0 );

        $obj->is_access_expired = $access_granted_for_days > 0
                               && $obj->last_payment_days > $access_granted_for_days;

        $obj->is_access_granted = $obj->is_active == 'Y' && !$obj->is_access_too_early && !$obj->is_access_too_late && !$obj->is_access_expired;

        $must_deactivate = $obj->is_active == 'Y' && $obj->is_access_too_late;
        if ($must_deactivate)
        {
            $data = array( 'is_active' => 'N' );
            $this->update( $obj, $data );
        }

        if (!isset($obj->is_right_of_rescission_waived) || $obj->is_right_of_rescission_waived == 'N')
        {
            $is_not_appicable = $obj->ds24_refund_days > $this->legalRefundDays()+1;
            $is_expired       = $obj->age_in_days > $this->legalRefundDays();

            $is_right_of_withdrawal_expired = $is_not_appicable || $is_expired;
            if ($is_right_of_withdrawal_expired) {

                $reason = $is_not_appicable
                        ? 'guarantee'
                        : 'expired';

                $data = array(
                                'right_of_rescission_waived_at' => ncore_dbDate(),
                                'right_of_rescission_waived_by' => $reason
                );
                $this->update( $obj, $data );
            }
        }

    }

    public function rightOfRecissionWaiverReasons( $reason='all' )
    {
        $reasons = array(
                'guarantee' => _digi( 'a guarantee exceeding the legal right was given.' ),
                'expired'   => _digi( 'expired after %s days', $this->legalRefundDays() ),
                'user'      => _digi( 'by the user'),
                // 'admin'     => _digi( 'An admin set the right of revocation as waived for the user.'),
        );

        if ($reason === 'all')
        {
            return $reasons;
        }

        return ncore_retrieve( $reasons,$reason, $reason );
    }

    protected function onBeforeSave( &$data )
    {
        parent::onBeforeSave( $data );

        $order_date    = ncore_retrieve( $data, 'order_date' );
        $last_pay_date = ncore_retrieve( $data, 'last_pay_date' );

        if ($last_pay_date < $order_date)
        {
            $data[ 'last_pay_date' ] = $order_date;
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
        $values['quantity']                       = 1;
        $values['quantity_after_quantity_change'] = 0;
        $values['auth_key'] = ncore_randomString( 'alnum', 30 );
        $values['has_visited_login_page']        = 'N';
        $values['last_age_in_day_notified' ]     = 1;
        $vaules['is_right_of_rescission_waived'] = 'N';

        return $values;
    }

    protected function hasModified()
    {
        return true;
    }

    private $new_order_id = false;
    private $authorized_objs = array();
    private $thankyou_page_user_id  = false;
    private $thankyou_page_password = false;

    private function parseAccessKey( $access_key )
    {
        $access_key = trim($access_key);
        if (!$access_key) {
            return array( 0, '' );
        }

        $type = $access_key[0];
        if ($type!=='u') {
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

    private function ageInDays( $obj, $key= 'order_date' )
    {
        $order_date = ncore_retrieve( $obj, $key );
        if (!$order_date)
        {
            $order_date = ncore_retrieve( $obj, 'created' );
        }


        list( $order_date, ) = explode( ' ', $order_date );
        list( $now_date, ) = explode( ' ', ncore_dbDate() );

        $order_unix = strtotime( $order_date );
        $now_unix = strtotime( $now_date );

        $age_in_seconds = $now_unix - $order_unix;

        $age_in_days = floor( $age_in_seconds / 86400 );

        return $age_in_days;
    }

    private $tacking_checked = false;
    private function _trackingCodedChecked( $key )
    {
        if ($this->tacking_checked)
        {
            return true;
        }

        $this->tacking_checked = true;

        $key = ncore_washText( $key);
        if (!$key)
        {
            return true;
        }

        $cookie_name = "digimember_products_validated_$key";
        $is_validated = ncore_retrieve( $_COOKIE, $cookie_name );
        if ($is_validated)
        {
            return true;
        }

        ncore_setcookie( $cookie_name, 1, 0, '/' );
    }

    private function _trackingCode( $orders )
    {
        $product_ids = array();

        foreach ($orders as $one)
        {
            $is_tracked = $one->is_order_tracked == 'Y';
            if ($is_tracked)
            {
                continue;
            }

            $data = array( 'is_order_tracked' => 'Y' );
            $modified = $this->update( $one->id, $data );
            if (!$modified)
            {
                continue;
            }

            $product_ids[] = $one->product_id;
        }

        $html = '';
        $model = $this->api->load->model( 'data/product' );
        foreach ($product_ids as $product_id)
        {
            $product = $model->get( $product_id );
            $html .= ncore_retrieve( $product, 'tracking_code' );
        }

        return $html;
    }

    private function getAllUsers()
    {
        global $wpdb;
        return $wpdb->get_results( "SELECT $wpdb->users.ID, $wpdb->users.user_registered FROM $wpdb->users ORDER BY 1 ASC" );
    }

    private function _maybeChangeQuantityAfterPlanSwitch( $obj )
    {
        if (empty($obj->quantity_changes_at)) {
            return;
        }

        $must_switch = ncore_dbDate($obj->quantity_changes_at) <= ncore_dbDate( 'now' );

        if ($must_switch)
        {
            $obj->quantity = $obj->quantity_after_quantity_change;
            $obj->quantity_changes_at = null;

            $data = array();
            $data[ 'quantity' ] = $obj->quantity_after_quantity_change;
            $data[ 'quantity_after_quantity_change' ] = 0;
            $data[ 'quantity_changes_at' ] = null;

            $this->update( $obj, $data );
        }
    }

    private function _resetQuantites()
    {
        $table = $this->sqlTableName();
        $now   = ncore_dbDate();

        $sql = "UPDATE $table
                SET quantity = quantity_after_quantity_change,
                    quantity_after_quantity_change = 0,
                    quantity_changes_at = NULL
                WHERE (quantity_changes_at IS NOT NULL)
                  AND DATE( quantity_changes_at ) <= '$now'";

        $this->db()->query( $sql );
    }

    // private
    function _queueNewContentActions()
    {
        if (!DIGIMEMBER_HAVE_NEW_CONTENT_ACTION) {
            return;
        }

        $this->api->load->model( 'data/page_product' );
        $this->api->load->model( 'logic/action' );

        $table = $this->sqlTableName();
        $now   = ncore_dbDate();

        $sql = "SELECT id,
                       to_days( NOW() ) - to_days( order_date ) as age_in_days,
                       last_age_in_day_notified,
                       user_id,
                       product_id
                FROM $table
                WHERE is_active = 'Y'
                  AND deleted IS NULL
                  AND to_days( NOW() ) - to_days( order_date ) > last_age_in_day_notified";

        $rows = $this->db()->query( $sql );

        $handeled_users = array();

        foreach ($rows as $row)
        {
            $where = array(
                'product_id'    => $row->product_id,
                'unlock_day >'  => $row->last_age_in_day_notified,
                'unlock_day <=' => $row->age_in_days,
                'is_active'     => 'Y',
            );

            $have_new_content = (bool) $this->api->page_product_data->getAll( $where );
            if (!$have_new_content) {
                continue;
            }

            $sql = "UPDATE $table
                    SET last_age_in_day_notified = $row->age_in_days
                    WHERE id = $row->id
                     AND last_age_in_day_notified < $row->age_in_days";
            $this->db()->query( $sql );

            $is_modified = $this->db()->modified();
            $is_double   = in_array( $row->user_id, $handeled_users );

            if (!$is_double) {
                $handeled_users[] = $row->user_id;
            }

            $can_trigger = $is_modified && !$is_double;
            if ($can_trigger) {
                $this->api->action_logic->onNewContent( $row->user_id, $row->product_id );
            }
        }
    }

    private function _escapeProductIds( $product_id_or_ids )
    {
        $list = is_array($product_id_or_ids)
              ? $product_id_or_ids
              : (is_object($product_id_or_ids)
                 ? ncore_retrieve( $product_id_or_ids, 'id' )
                 : explode( ',', $product_id_or_ids ));

        $sql_product_ids = '';
        foreach ($list as $id)
        {
            $id = ncore_washInt( $id );
            if (!$id)
            {
                continue;
            }

            if ($sql_product_ids) {
                $sql_product_ids .= ',';
            }

            $sql_product_ids .= $id;
        }

        return $sql_product_ids;
    }

    public function getDeletedById($id) {
        $deleted = $this->getDeleted(0);
        foreach ($deleted as $userProduct) {
            if ($id == $userProduct->id) {
                return $userProduct;
            }
        }
    }
}
