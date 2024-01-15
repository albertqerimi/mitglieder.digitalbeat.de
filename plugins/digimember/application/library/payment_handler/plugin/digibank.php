<?php

class digimember_PaymentHandler_PluginDigibank extends digimember_PaymentHandler_PluginBase
{
    public function instructions()
    {
        $instructions = parent::instructions();

        $instructions[] = _digi3('<strong>Here in DigiMember</strong> click on <em>Save changes</em> below to create a notification URL.');
        $instructions[] = _digi3('Copy the notification URL below to the clipboard.');

        $find = ['<a1>', '<a2>'];
        $repl = ["<a href='https://www.digistore24.com' target='_blank'>", "<a href='https://www.digistore24.com/account/products' target='_blank'>"];
        $msg = _digi3('<strong><a1>In Digistore</a></strong> make sure <a2>you have setup a Digistore product</a> and know the Digistore product id.');
        $instructions[] = str_replace($find, $repl, $msg);

        $find = ['<a>'];
        $repl = ["<a href='https://www.digistore24.com/settings/ipn' target='_blank'>"];
        $msg = _digi3('Go to <a>settings - IPN</a> and edit or add an ipn setting. Select as type DigiMember.');
        $instructions[] = str_replace($find, $repl, $msg);

        $instructions[] = _digi3('Paste the notification URL in the appropriate text field.');
        $instructions[] = _digi3('Locate the Digistore24 product names below the notification URL. Check all products you want DigiMember to handle. Or just check <em>all</em>.');
        $instructions[] = _digi3('Note the Digistore24 product ids right of the product names.');
        $instructions[] = _digi3('<strong>In DigiMember</strong> enter the Digistore24 product ids so that they match your DigiMember products.');

        return $instructions;
    }

    public function formMetas()
    {
        return array(
          array(
                'name' => 'product_code_map',
                'type' => 'map',
                'label' => _digi3('Digistore24 product ids' ),
                'array' => $this->productOptions(),
                'hint' => _digi('Seperate multiple product ids by commas.'),
          ),
        );
    }

    public function label()
    {
        return 'Digistore24';
    }

    public function reportSuccess( $type, $user_product_id )
    {
        echo 'OK';

        switch ($type)
        {
            case 'user_product':

                /** @var digimember_BlogConfigLogic $config */
                $config = $this->api->load->model( 'logic/blog_config' );
                $turned_off = $config->get('disable_login_data_in_ds24');

                if (!$turned_off)
                {
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

                /** @var digimember_UserProductData $userProductData */
                $userProductData = $this->api->load->model( 'data/user_product' );
                $obj = $userProductData->get( $user_product_id );

                /** @var digimember_ProductData $productData */
                $productData = $this->api->load->model( 'data/product' );
                $product = $productData->get( $obj->product_id );

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
                /** @var digimember_DownloadData $downloadData */
                $downloadData = $this->api->load->model( 'data/download' );
                $url = $downloadData->downloadPageUrl( $user_product_id );
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

    protected function methods()
    {
        return array( METHOD_POST );
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

            'billing_type'       => 'billing_type',

            'address_first_name' => 'first_name',
            'address_last_name'  => 'last_name',
            'buyer_first_name'   => 'first_name',             // NEU
            'buyer_last_name'    => 'last_name',              // NEU

            'address_street'     => 'street',
            'address_zip_code'   => 'zipcode',
            'address_state'      => 'state',
            'address_city'       => 'city',
            'address_country'    => 'country',

            'upgrade_key'               => 'ds24_upgrade_key',
            'customer_affiliate_name'   => 'ds24_affiliate_name',
            'purchase_key'              => 'ds24_purchase_key',

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




}