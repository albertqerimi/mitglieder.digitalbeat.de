<?php

class digimember_PaymentHandler_PluginDigistoreApi extends digimember_PaymentHandler_PluginBase
{
    public function instructions()
    {
       $instructions = parent::instructions();

       return $instructions;
    }

    public function formMetas()
    {
        return array(
          array(),
        );
    }

    public function label()
    {
        return 'Digistore24-Api';
    }

    public function getEventType()
    {
        $raw_event = $this->getRequestArg( 'event', 'default' );

        $events_with_actions = array( 'eticket', 'customform' );
        $is_event_with_action = in_array( $raw_event, $events_with_actions );
        if ($is_event_with_action)
        {
            $action = $this->getParam('action', $required = false );

            if ($action)
            {
                $refund_actions = array( 'revoke' );

                $is_refund_action = in_array( $action, $refund_actions );

                return $is_refund_action
                       ? EVENT_REFUND
                       : EVENT_SALE;
            }
        }

        return parent::getEventType();
    }

    public function reportSuccess( $type, $user_product_id )
    {
        echo 'OK';

        switch ($type)
        {
            case 'user_product':

                $config = $this->api->load->model( 'logic/blog_config' );
                $turned_off = $config->get('disable_login_data_in_ds24');

                if (!$turned_off)
                {
                    $config = $this->api->load->model( 'logic/blog_config' );
                    $loginurl = $config->loginUrl();

                    list( $username, $password ) = $this->getUserNameAndPassword( $user_product_id );

                    if ($username && $password)
                    {
                        echo "\nusername: $username\npassword: $password\nloginurl: $loginurl";
                    }
                    else
                    {
                        $domain = str_replace( array( 'http://', 'https://' ), '', ncore_siteUrl() );

                        $find = array();
                        $repl = array();

                        $find[] = '[USERNAME]';
                        $repl[] = $username;

                        $find[] = '[DOMAIN]';
                        $repl[] = $domain;

                        $template = $username
                                  ? _digi( 'You already had the account [USERNAME] for [DOMAIN].')
                                  : _digi( 'You already had an account for [DOMAIN].' );

                        $note = str_replace( $find, $repl, $template );

                        echo "\nnote: $note\nloginurl: $loginurl";
                    }
                }

                $model = $this->api->load->model( 'data/user_product' );
                $obj = $model->get( $user_product_id );

                $model = $this->api->load->model( 'data/product' );
                $product = $model->get( $obj->product_id );

                $mode = $config->get( 'thankyou_data_policy_in_ds24', 'plain' );

                $url = $product->is_ds24_sync_enabled == 'Y' && $mode != 'hidden'
                     ? ncore_resolveUrl( $product->ds24_thankyoupage )
                     : false;

                if ($url)
                {
                    $url = ncore_addArgs( $url, array( DIGIMEMBER_THANKYOUKEY_GET_PARAM => $obj->access_key ), '&', false );
                    echo "\nthankyou_url: $url";
                    echo "\nthankyou_data_policy: $mode";
                }
                break;

            case 'download':
                $model = $this->api->load->model( 'data/download' );
                $url = $model->downloadPageUrl( $user_product_id );
                if ($url)
                {
                    echo "\nthankyou_url: $url";
                }
                break;
        }
    }

    public function orderIdsAreOfSameOrder( $order_id_a, $order_id_b )
    {
        list( $a ) = explode( '-', str_replace( 'o', '-', $order_id_a ) );
        list( $b ) = explode( '-', str_replace( 'o', '-', $order_id_b ) );

        return $a == $b;
    }

    public function getProductIds()
    {
        $result     = $this->getParam('product_id', $required = true, $array_allowed=true );
        $quantities = $this->getParam('quantity',   $required = false, $array_allowed=true );

        if (is_array($result))
        {
            $product_id_list = $result;
        }
        elseif ($result)
        {
            $product_id      = $result;
            $product_id_list = array( $product_id );

            $quantity = is_array($quantities)
                        ? 1
                        : $quantities;

            $quantities = array( $quantity );

        }
        else
        {
            $product_id_list = array();
            $quantities = array();
        }

        $model = $this->api->load->model( 'data/product' );
        $products = $model->getAll();

        $sanitized_product_id_list = array();
        foreach ($products as $product)
        {
            $is_published = $product->published;
            if (!$is_published) {
                continue;
            }

            $have_ds24_products = (bool) $product->ds24_product_ids;
            if (!$have_ds24_products) {
                continue;
            }

            $ds24_product_ids = explode( ',', $product->ds24_product_ids );

            $have_all = in_array( 'all', $ds24_product_ids );

            foreach ($product_id_list as $index => $ds24_product_id) {

                $matches = $have_all
                        || in_array( $ds24_product_id, $ds24_product_ids );

                if (!$matches) {
                    continue;
                }

                $product_id = intval( $product->id );

                $present_quantity = intval( ncore_retrieve( $sanitized_product_id_list, $product_id, 0 ) );

                $quantity = intval( ncore_retrieve(  $quantities, $index, 1 ) );

                $sanitized_product_id_list[ $product_id ] = $quantity + $present_quantity;
            }
        }

        return $sanitized_product_id_list;
    }

    protected function methods()
    {
        return array( METHOD_POST );
    }

    protected function parameterNameMap()
    {
        return array(
            'event'             => 'event_type',
            'address_email'     => 'email',             // NEU
            'buyer_email'       => 'email',
            'product_id'        => 'product_code',
            'order_id'          => 'order_id',
            'order_date_time'   => 'order_date',
            'delivery_date'     => 'delivery_date',
            'upgraded_order_id' => 'upgraded_order_id',
            'upgrade_type'      => 'upgrade_type',
            'custom_key'        => 'loginkey',

            'address_first_name' => 'first_name',
            'address_last_name'  => 'last_name',
            'buyer_first_name'   => 'first_name',             // NEU
            'buyer_last_name'    => 'last_name',              // NEU

            'address_street'     => 'street',
            'address_zip_code'   => 'zipcode',
            'address_state'      => 'state',
            'address_city'       => 'city',
            'address_country'    => 'country',

            'billing_type'       => 'billing_type',

            'upgrade_key'               => 'ds24_upgrade_key',
            'customer_affiliate_name'   => 'ds24_affiliate_name',
            'purchase_key'              => 'ds24_purchase_key',
            'refund_days'               => 'ds24_refund_days',
            'receipt_url'              => 'ds24_receipt_url',
            'renew_url'                => 'ds24_renew_url',
            'add_url'                  => 'ds24_add_url',
            'support_url'              => 'ds24_support_url',
            'rebilling_stop_url'       => 'ds24_rebilling_stop_url',
            'request_refund_url'       => 'ds24_request_refund_url',
            'become_affiliate_url'     => 'ds24_become_affiliate_url',
            'newsletter_choice'        => 'ds24_newsletter_choice',
        );
    }

    protected function eventMap()
    {
        return array(
                'connection_test'   => EVENT_CONNECTION_TEST,

                'on_payment'        => EVENT_SALE,
                'on_refund'         => EVENT_REFUND,
                'on_chargeback'     => EVENT_REFUND,
                'on_missed_payment' => EVENT_MISSED_PAYMENT,
                'on_payment_missed' => EVENT_MISSED_PAYMENT,

                'payment'           => EVENT_SALE,
                'refund'            => EVENT_REFUND,
                'chargeback'        => EVENT_REFUND,
                'missed_payment'    => EVENT_MISSED_PAYMENT,
                'payment_missed'    => EVENT_MISSED_PAYMENT,
                'payment_denial'    => EVENT_CONNECTION_TEST,
                'last_paid_day'     => EVENT_MISSED_PAYMENT,

                'on_affiliation'    => EVENT_SALE,
                'on_signup'         => EVENT_SALE,
                'new_affiliation'   => EVENT_SALE,
                'new_signup'        => EVENT_SALE,
                'eticket'           => EVENT_SALE,
                'customform'        => EVENT_SALE,
                'of_filled_out'     => EVENT_CONNECTION_TEST,

                'rebill_cancelled'  => EVENT_CONNECTION_TEST,
                'rebill_resumed'    => EVENT_CONNECTION_TEST,
            );

    }


   protected function renderMessageForInvalidIpnPassword()
    {
        $find = array();
        $repl = array();

        $find[] = '[PLUGIN]';
        $repl[] = $this->api->pluginDisplayName();

        $find[] = '[DS24]';
        $repl[] = 'Digistore24';

        $template = _digi( 'The connection between [PLUGIN] and [DS24] is outdated. In your wordpress site go to [PLUGIN] - Settings, disconnect from [DS24] and connect again.' );

        return str_replace( $find, $repl, $template );
    }


}