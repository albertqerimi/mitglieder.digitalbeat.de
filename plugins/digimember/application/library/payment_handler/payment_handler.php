<?php

define('EVENT_SALE',            'sale');
define('EVENT_REFUND',          'refund');
define('EVENT_MISSED_PAYMENT',  'missed_payment');
define('EVENT_CONNECTION_TEST', 'test');

define('METHOD_POST',  'post');
define('METHOD_GET',   'get');
define('METHOD_INPUT', 'input');
define('METHOD_INPUT_JSON', 'input_json');

class digimember_PaymentHandlerLib extends ncore_Library
{
    /** @var string[] */
    private $upgrade_types_with_cancel_previous_order = array( 'upgrade', 'downgrade', 'switch_plan', 'package_change' );

    public function setPlugin( $plugin_type_or_id )
    {
        /** @var digimember_PaymentData $model */
        $model = $this->api->load->model( 'data/payment' );

        $is_id = $model->isId( $plugin_type_or_id );

        if ($is_id)
        {
            $id = $plugin_type_or_id;
            $this->setPlugniById( $id );
        }
        else
        {
            $type = $plugin_type_or_id;
            $this->setPlugniByType( $type );
        }
    }

    /**
     * @param $user_product_id
     *
     * @return bool
     * @throws Exception
     */
    public function resendWelcomeMail( $user_product_id )
    {
        /** @var digimember_UserProductData $up_model */
        $up_model = $this->api->load->model( 'data/user_product' );
        /** @var digimember_UserData $ps_model */
        $ps_model = $this->api->load->model( 'data/user' );
        /** @var digimember_ProductData $pr_model */
        $pr_model = $this->api->load->model( 'data/product' );

        $user_product = $up_model->get( $user_product_id );
        if (!$user_product)
        {
            throw new Exception( _digi( 'Invalid order id: %s', $user_product_id ) );
        }

        $user_id  = $user_product->user_id;

        $user = ncore_getUserById( $user_id );
        if (!$user)
        {
            throw new Exception( _digi( 'The user of order %s was previously deleted. No e-mail could be send.', $user_product_id ) );
        }


        $password = $ps_model->getPassword( $user_id );
        $product  = $pr_model->get( $user_product->product_id );
        if (!$product)
        {
            throw new Exception( _digi( 'The product of order %s has been deleted.', $user_product_id ) );
        }

        if (!$password)
        {
            throw new Exception( _digi( 'The user %s of order %s has changed his password, so it is encrypted and cannot be sent anymore.', $user->user_email, $user_product_id ) );
        }


        $order_id = $user_product->order_id;

        $this->sendWelcomeMail($user_id , $password, $product, $order_id, $force_sending=true );

        return true;

    }

    /**
     * @param $email
     * @param $first_name
     * @param $last_name
     * @param $order_id
     * @param $product_id
     *
     * @return array
     * @throws Exception
     */
    public function manuallyCreateSale( $email, $first_name, $last_name, $order_id, $product_id )
    {
        $payment_provider_id = 0;

        $address = array();
        $address['first_name'] = $first_name;
        $address['last_name'] = $last_name;


        list( $type, $user_product_id ) = $this->onSale( 'manual', $payment_provider_id, $order_id, $email, array($product_id=>1), $address, $force_dbl_optin=true, $order_date='' );


        $password = ncore_retrieve( $this->passwords, $this->sanitizeEmail($email), false );

        return array( $email, $password, $type, $user_product_id );
    }

    public function handleIpnRequest()
    {
        try
        {
            $plugin = $this->current_plugin;

            if (!$plugin)
            {
                $this->error( 'must use PaymentHandler::setType to load a plugin' );
            }

            $valid_plugin_types = $this->getPluginTypes();
            $is_valid = in_array( $plugin->type(), $valid_plugin_types );
            if (!$is_valid) {
                $this->error( 'is disabled - please check your %s license package.', $this->api->pluginDisplayName() );
            }


            $payment_provider_id = $plugin->id();

            $plugin->initRequest();

            $plugin->validateRequestParams();

            $event = $plugin->getEventType();

            $type = '';
            $user_product_id = false;

            switch ($event)
            {
                case EVENT_SALE:
                    $order_id         = $plugin->getParam('order_id',         $required = true);
                    $email            = $plugin->getParam('email',            $required = true);
                    $order_date       = $plugin->getParam('order_date',       $required = false);

                    $extra_data = array();

                    $extra_data['billing_type']              = $plugin->getParam('billing_type',                $required = false );

                    $extra_data['ds24_upgrade_key']          = $plugin->getParam('ds24_upgrade_key',          $required = false );
                    $extra_data['ds24_affiliate_name']       = $plugin->getParam('ds24_affiliate_name',       $required = false );
                    $extra_data['ds24_purchase_key']         = $plugin->getParam('ds24_purchase_key',         $required = false  );
                    $extra_data['ds24_refund_days']          = (int) $plugin->getParam('ds24_refund_days',    $required = false  );
                    $extra_data['ds24_receipt_url']          = $plugin->getParam('ds24_receipt_url',          $required = false  );
                    $extra_data['ds24_renew_url']            = $plugin->getParam('ds24_renew_url',            $required = false  );
                    $extra_data['ds24_add_url']              = $plugin->getParam('ds24_add_url',              $required = false  );
                    $extra_data['ds24_support_url']          = $plugin->getParam('ds24_support_url',          $required = false  );
                    $extra_data['ds24_rebilling_stop_url']   = $plugin->getParam('ds24_rebilling_stop_url',   $required = false  );
                    $extra_data['ds24_request_refund_url']   = $plugin->getParam('ds24_request_refund_url',   $required = false  );
                    $extra_data['ds24_become_affiliate_url'] = $plugin->getParam('ds24_become_affiliate_url', $required = false  );
                    $extra_data['ds24_newsletter_choice']    = $plugin->getParam('ds24_newsletter_choice',    $required = false  );

                    $extra_data = array_filter( $extra_data );

                    $delivery_date             = $plugin->getParam('delivery_date',     $required = false);
                    $upgraded_order_paid_until = $plugin->getParam('upgraded_order_paid_until', $required = false);

                    $upgraded_order_id = $plugin->getParam('upgraded_order_id', $required = false);
                    $upgrade_type      = $plugin->getParam('upgrade_type',      $required = false);
                    $loginkey          = $plugin->getParam('loginkey',          $required = false);

                    $address = $plugin->getAddress();

                    $product_ids = $plugin->getProductIds();

                    if ($product_ids)
                    {
                        list( $type, $user_product_id, $welcome_msg_sent )
                            = $this->onSale( 'order', $payment_provider_id, $order_id, $email, $product_ids, $address, $force_dbl_optin=false, $order_date, $delivery_date, $upgraded_order_id, $upgrade_type, $upgraded_order_paid_until, $loginkey, $extra_data );
                    }

                    break;

                case EVENT_REFUND:

                    $order_id          = $plugin->getParam('order_id',          $required = true);
                    $upgraded_order_id = $plugin->getParam('upgraded_order_id', $required = false);
                    $upgrade_type      = $plugin->getParam('upgrade_type',      $required = false);
                    $access_stops_on   = $plugin->getParam('access_stops_on',   $required = false);

                    $this->onRefund( $payment_provider_id, $order_id, $upgraded_order_id, $upgrade_type, $access_stops_on );
                    break;

                case EVENT_MISSED_PAYMENT:

                    $order_id          = $plugin->getParam('order_id',          $required = true);
                    $upgraded_order_id = $plugin->getParam('upgraded_order_id', $required = false);
                    $upgrade_type      = $plugin->getParam('upgrade_type',      $required = false);
                    $access_stops_on   = $plugin->getParam('access_stops_on',   $required = false);

                    $this->onMissedPayment( $payment_provider_id, $order_id, $upgraded_order_id, $upgrade_type, $access_stops_on );
                    break;

                case EVENT_CONNECTION_TEST:
                    break;

                default:
                    if ($event)
                    {
                        $this->error( _digi('has send invalid event_type sent: "%s"', $event));
                    }
            }


            $plugin->reportSuccess( $type, $user_product_id );
            die();
        }

        catch (Exception $e)
        {
           $msg = _digi3( 'IPN error with %s (#%s): %s',  $plugin->label(),  $plugin->id(), $e->getMessage() );
           $this->api->logError( 'payment', $msg );
           throw $e;
        }
    }

    public function getProviders($mode='auto')
    {
        $all_plugins = array(
                'digibank'     => 'Digistore24',
                'generic'      => _digi('generic'),
                'cleverbrigde' => 'CleverBrigde',
                'clickbank'    => 'Clickbank',
                'paypal'       => 'Paypal',
                '2checkout'    => '2CheckOut',
                //'stripe'       => 'Stripe',
                'stripe_pricing_api' => 'Stripe Pricing API',
        );
        if (dm_api()->edition() == 'DE')
        {
            $all_plugins = array_merge($all_plugins, array(
                // shareit removed as is doesn't exist since 2015
                // just removed it from this list for now, may be removed completely in a couple of versions
                // - jsiebern, 10/2020
                // 'shareit'      => 'ShareIt',
                'affilibank'   => 'Affilicon',
            ));
        }

        $basic_plugins = array();

        switch ($mode)
        {
            case 'pro':
                return $all_plugins;

            case 'basic':
                $plugins = array();
                foreach ($basic_plugins as $one)
                {
                    if (!empty($all_plugins[ $one ]))
                    {
                        $plugins[ $one ] = $all_plugins[ $one ];
                    }
                }
                return $plugins;

            case 'pro_only':
//                $plugins_premium_only = $all_plugins;
//                foreach ($basic_plugins as $plugin)
//                {
//                    unset( $plugins_premium_only[$plugin] );
//                }
//                return $plugins_premium_only;
                return $all_plugins;

            case 'auto':
            default:
                $model = $this->api->load->model( 'logic/features' );

                $have_premium = $model->canUseOtherPaymentProviders();

                return $have_premium
                       ? $this->getProviders( 'pro' )
                       : $this->getProviders( 'basic' );
        }
    }

    public function getPluginTypes()
    {
        $types = array_keys( $this->getProviders() );

        $types[] = 'digistore_api';

        return $types;
    }

    public function defaultProvider()
    {
        return 'digibank';
    }


    public function parameterNames()
    {
        return array(
            'event_type'   => _digi('Event'),
            'email'        => _digi('Email'),
            'product_code' => _digi('Product id'),
            'order_id'     => _digi('Order id'),

            'first_name'   => _digi('First name'),
            'last_name'    => _digi('Last name'),

        );
    }

    public function eventNames()
    {
        return array(
            EVENT_CONNECTION_TEST => _digi('Connection test'),
            EVENT_SALE            => _digi('Sale'),
            EVENT_REFUND          => _digi('Refund'),
            EVENT_MISSED_PAYMENT  => _digi('Missed payment')
        );
    }

    public function engineInputMetas( $engine , $id=false )
    {
        /** @var digimember_PaymentData $model */
        $model = $this->api->load->model('data/payment');

        $meta = $id > 0
            ? $model->getCached( $id )
            : false;

        if (!$meta)
        {
            $meta = new StdClass();
            $meta->id = 0;
        }

        $meta->engine = $engine;

        $plugin = $this->loadPlugin( $meta );

        $metas = $plugin->formMetas();

        foreach ($metas as $index => $one)
        {
            $metas[$index]['name'] = $engine . '_' . $one['name'];
        }

        return $metas;
    }

    public function instructionMeta( $engine )
    {
        $meta = new StdClass();
        $meta->engine = $engine;
        $meta->id = 0;

        $plugin = $this->loadPlugin( $meta );

        $instructions = $plugin->instructions();
        if (!$instructions)
        {
            return array();
        }

        $html = count($instructions) >= 3
                ? '<ol class="ncore_instructions"><li>' . implode( '</li><li>', $instructions ) . '</li></ol>'
                : '<p class="ncore_instructions">' . implode( '</p><p>', $instructions ) . '</p>';

        return array(
            'name' => $engine.'_instructions',
            'type' => 'html',
            'label' => _digi('Instructions'),
            'html' => $html,
        );
    }



    public function ipnPerProductEngines()
    {
        $this->_mayLoadIpnTypes();
        return $this->ipn_per_product_engines;
    }

    public function ipnPerProviderEngines()
    {
        $this->_mayLoadIpnTypes();
        return $this->ipn_per_provider_engines;
    }

    public function signUp( $email, $product_id_or_ids, $address, $perform_login=false, $order_id=false, $password=false, $set_payment_provider = false )
    {
        $this->api->load->helper( 'string' );

        $order_id = ncore_washText($order_id);
        if (!$order_id)
        {
            $order_id = _ncore( 'signup' );
        }

        if (!$email) {
            throw new Exception( _digi( 'Email is missing for the new order with id \'%s\'.', $order_id ) );
        }

        $payment_provider_id = 0;

        if ($perform_login)
        {
            $user_exists = (bool) ncore_getUserIdByEmail( $email )
                        || (bool) ncore_getUserIdByName( $email );

            $can_login = !$user_exists;
        }

        $seperators = array( ',',';','|','-','/','.','_' );
        $raw_product_ids = ncore_explodeAndTrim( $product_id_or_ids, $seperators );

        $first_product_id = false;

        foreach ($raw_product_ids as $product_id)
        {
            $product_id = (int) $product_id;
            if (!$product_id) {
                continue;
            }

            $product_ids[ $product_id ] = 1;
            if (!$first_product_id) {
                $first_product_id = $product_id;
            }
        }

        list( $result_type, $product_user_id, $welcome_msg_sent ) = $this->onSale( 'signup', $payment_provider_id, $order_id, $email, $product_ids, $address, $force_dbl_optin=true, $order_date='', $delivery_date='', $upgraded_order_id='', $upgrade_type='none', $upgraded_order_paid_until='', $loginkey='', $extra_data = array( 'password'=> $password, 'set_payment_provider' => $set_payment_provider ) );

        if (!$perform_login)
        {
            return $welcome_msg_sent;
        }

        switch ($result_type)
        {
            case 'user_product':

                $product = $this->getProduct( $first_product_id );

                $redirect_url = ncore_resolveUrl( ncore_retrieve( $product, 'first_login_url' ) );
                if (!$redirect_url)
                {
                    $redirect_url = ncore_resolveUrl( ncore_retrieve( $product, 'login_url' ) );
                }


                if (!$redirect_url)
                {
                    $redirect_url = ncore_siteUrl();
                }

                if ($can_login && $product_user_id)
                {
                    $model = $this->api->load->model ('data/user_product');
                    $user_product = $model->get( $product_user_id );
                    $user_id = $user_product->user_id;

                    $model = $this->api->load->model( 'data/one_time_login' );
                    $next_url = $model->setOneTimeLogin( $user_id, $redirect_url );
                }
                else
                {
                    $next_url = $redirect_url;
                }
                break;

            case 'download':
                // signup for download products not implemented
                $next_url = ncore_siteUrl();
                break;

            default:
                $next_url = ncore_siteUrl();
        }

        ncore_redirect( $next_url );
    }

    private function onSale( $reason, $payment_provider_id, $order_id, $email, $product_id_or_ids, $address, $force_dbl_optin=false, $order_date='', $delivery_date='', $upgraded_order_id='', $upgrade_type='none', $upgraded_order_paid_until='', $loginkey='', $extra_data=array() )
    {
        // valis values for $reason: 'manual', 'order', 'signup'
        $model = $this->api->load->model( 'logic/features' );
        $msg = $model->signUpObstacles();
        if ($msg) {
            throw new Exception( $msg );
        }

        $product_ids = is_array($product_id_or_ids)
                     ? $product_id_or_ids
                     : array( $product_id_or_ids => 1 );

        $result_type = '';
        $result_id   = false;

        $newsletter_choice = ncore_retrieve( $extra_data, 'ds24_newsletter_choice', 'none' );

        foreach ($product_ids as $product_id => $quantity)
        {
            $product = $this->getProduct($product_id);

            if (!$product || !$product->published)
            {
                $is_signup = $payment_provider_id == 0;
                if ($is_signup)
                {
                    continue;
                }

                $find = array(
                    '[PLUGIN]',
                    '[PRODUCT_ID]',
                    '[ORDER_ID]',
                    '[EMAIL]',
                    '[PAY_PROVIDER]'
                );

                $repl = array(
                    $this->api()->pluginDisplayName(),
                    $product_id,
                    $order_id,
                    $email,
                    $this->current_plugin->label()
                );

                $is_non_published_product_error = $product && !$product->published;

                $msg = $is_non_published_product_error
                     ? _digi('has send a purchase notification with a NON PUBLISHED product id ([PRODUCT_ID]) to [PLUGIN]. Order id: [ORDER_ID]. Buyer: [EMAIL]. In the product list please make sure, that the product is published.' )
                     : _digi('has send a purchase notification with invalid product id ([PRODUCT_ID]) to [PLUGIN]. Order id: [ORDER_ID]. Buyer: [EMAIL]. Did you enter [PAY_PROVIDER]\'s product ids under [PLUGIN] - payment provider?' );



                $this->error( str_replace( $find, $repl, $msg ) );

                continue;
            }

            $must_create_account      = $product->type == 'membership';
            $must_create_download_url = $product->type == 'download';

            if ($must_create_account) {
                list( $user_product_id, $welcome_msg_sent ) = $this->_handleMembershipProduct( $reason, $payment_provider_id, $order_id, $email, $product, $quantity, $address, $force_dbl_optin, $order_date, $delivery_date, $upgraded_order_id, $upgrade_type, $upgraded_order_paid_until, $loginkey, $extra_data, $newsletter_choice );
                if (!$result_id) {
                    $result_id   = $user_product_id;
                    $result_type = 'user_product';
                }
            }
            if ($must_create_download_url)
            {
                list( $download_id, $welcome_msg_sent ) = $this->_handleDownloadProduct( $reason, $payment_provider_id, $order_id, $email, $product, $address, $force_dbl_optin, $newsletter_choice );
                if (!$result_id) {
                    $result_id = $download_id;
                    $result_type = 'download';
                }
            }
        }

        return array( $result_type, $result_id, $welcome_msg_sent );
    }






    protected function onRefund( $payment_provider_id, $order_id, $upgraded_order_id='', $upgrade_type='none', $access_stops_on = false )
    {
        $this->revokeProductAccess( $payment_provider_id, $order_id, 'order_cancelled', $upgraded_order_id, $upgrade_type, $access_stops_on );

        $this->api->log('payment', _digi('Payment cancelled for order %s - product access revoked.'), $order_id );
    }

    protected function onMissedPayment( $payment_provider_id, $order_id, $upgraded_order_id='', $upgrade_type='none', $access_stops_on=false )
    {
        $this->revokeProductAccess( $payment_provider_id, $order_id, 'payment_missing', $upgraded_order_id, $upgrade_type, $access_stops_on );

        $this->api->log('payment', _digi('Subscription cancelled for order %s - product access revoked.'), $order_id );
    }

    public function onRefundZapier( $payment_provider_id, $order_id, $user_id)
    {
        $this->cancelProductAccess( $payment_provider_id, $order_id, 'zapier_refund', $user_id );
        $this->api->log('zapier', _digi('Payment cancelled via zapier for order %s - product access revoked.'), $order_id );
    }

    public function onCancelZapier( $payment_provider_id, $order_id, $user_id, $access_stops_on=false )
    {
        $reason = $access_stops_on ? 'zapier_cancel_to_date' : 'zapier_cancel';
        $this->cancelProductAccess( $payment_provider_id, $order_id, $reason, $user_id, $access_stops_on );

        if ($access_stops_on) {
            $this->api->load->helper('date');
            list($access_stops_on, ) = explode("T", $access_stops_on);
            $readableDate = ncore_formatDate($access_stops_on);
            $this->api->log('zapier', _digi('Subscription cancelled via zapier for order %s - product access will revoked on %s.'), $order_id,  $readableDate);
        }
        else {
            $this->api->log('zapier', _digi('Subscription cancelled via zapier for order %s - product access revoked.'), $order_id );
        }
    }

    public function onCancelWebhook( $payment_provider_id, $order_id, $user_id, $access_stops_on=false )
    {
        $reason = $access_stops_on ? 'webhook_cancel_to_date' : 'webhook_cancel';
        $this->cancelProductAccess( $payment_provider_id, $order_id, $reason, $user_id, $access_stops_on );

        if ($access_stops_on) {
            $this->api->load->helper('date');
            list($access_stops_on, ) = explode("T", $access_stops_on);
            $readableDate = ncore_formatDate($access_stops_on);
            $this->api->log('webhook', _digi('Subscription cancelled via webhook for order %s - product access will revoked on %s.'), $order_id,  $readableDate);
        }
        else {
            $this->api->log('webhook', _digi('Subscription cancelled via webhook for order %s - product access revoked.'), $order_id );
        }
    }

    /** @var bool|digimember_PaymentHandler_PluginBase */
    private $current_plugin = false;
    /** @var digimember_PaymentHandler_PluginBase */
    private $loaded_plugins = array();

    private function loadPlugin( $row )
    {
        $type = $row->engine;

        if ($row->id == 0)
        {
            $row->id = $this->dummyId( $type );
        }

        $key = $type . '-' . $row->id;
        $plugin =& $this->loaded_plugins[ $key ];

        if (!isset($plugin))
        {
            $all_types = $this->getPluginTypes();
            $is_valid = in_array( $type, $all_types );
            if (!$is_valid) {
                throw new Exception( "Payment provider type not available: '$type'" );
            }


            $class_name = $this->loadPluginClass( $type );

            if (empty($class_name)) {
                throw new Exception( "Invalid payment plugin type: '$type'" );
            }

            $plugin = new $class_name($this, (array) $row);
        }

        return $plugin;
    }


    private $passwords = array();
    private function cachePassword( $email, $password )
    {
        $this->passwords[ $email ] = $password;
    }

    private function getUserId( $email, $address, $order_id=false, $password_for_new_account=false )
    {
        if (!$email)
        {
            return array( 0, false, false );
        }

        $email = $this->sanitizeEmail($email);

        $user_id = ncore_getUserIdByEmail( $email );

        $user_login = $email;
        $user_login = sanitize_user($user_login, true);

        if (!$user_id)
        {
            $user_id = ncore_getUserIdByName( $user_login );
        }

        if (!$user_id && $order_id)
        {
            $api   = $this->api;
            $model = $api->load->model('data/user_product');

            $where    = array( 'order_id' => $order_id );
            $order_by = 'created DESC';

            $entry = $model->getWhere( $where, $order_by );
            $user_id = ncore_retrieve( $entry, 'user_id' );
        }

        $have_created_new_user = ! $user_id;

        $password = ncore_retrieve( $this->passwords, $email, false );

        if ($user_id)
        {
            if (!$password)
            {
                $model = $this->api->load->model ('data/user');
                $password = $model->getPassword( $user_id );
                $this->cachePassword( $email, $password );
            }

            $new_first_name = ncore_retrieve( $address, 'first_name' );
            $new_last_name  = ncore_retrieve( $address, 'last_name' );

            $user = get_userdata( $user_id );
            $old_firstname = ncore_retrieve( $user, 'first_name' );
            $old_lastname  = ncore_retrieve( $user, 'last_name' );

            $must_update_name = !$old_firstname || !$old_lastname && ($new_first_name || $new_last_name);

            if (!$must_update_name) {
                $must_update_name = $old_firstname != $new_first_name || $old_lastname != $new_last_name;
            }
             if ($must_update_name)
            {
                $user_model = $this->api->load->model ('data/user');
                $user_model->setName( $user_id, $new_first_name, $new_last_name );
            }

            $customFieldsUpdated = apply_filters('digimember_cf_update_data', $user_id, $address);
            if ($customFieldsUpdated) {
                apply_filters('digimember_ipn_push_arcf_links', $user_id);
            }


            return array(
                $user_id,
                $password,
                $have_created_new_user
            );
        }

        if (!$password)
        {
            if ($password_for_new_account) {
                $password = $password_for_new_account;
            }
            else
            {
                $this->api->load->helper( 'string' );
                $password = ncore_randomString( 'password', NCORE_PASSWORD_LENGTH );
            }
            $this->cachePassword( $email, $password );
        }
        $user_id = wp_create_user( $user_login, $password, $email );

        $is_error = !is_numeric( $user_id ) || !$user_id;
        if ($is_error)
        {
            if ($user_id instanceof WP_Error) {
                $this->api->logError('orders', $user_id->get_error_message().' ('.$user_login.')');
                throw new Exception( $user_id->get_error_message().' ('.$user_login.')' );
            }
            else {
                $this->error("could not create new user for email '%s'", $email);
            }

        }

        $user_model = $this->api->load->model ('data/user');

        if ($password && !$is_error)
        {
            $user_model->setPassword( $user_id, $password, $is_generated_password=true );
        }

        $first_name = ncore_retrieve( $address, 'first_name' );
        $last_name  = ncore_retrieve( $address, 'last_name' );

        $display_name = trim( "$first_name $last_name" );

        $user_nicename = sanitize_title( $display_name );

        wp_update_user( array( 'ID' => $user_id, 'display_name'=> $display_name ) );
        wp_update_user( array( 'ID' => $user_id, 'user_nicename'=> $user_nicename ) );

        $this->api->load->helper('features');
        $nickname = ncore_getDmNickname(array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
        ));
        update_user_meta( $user_id, 'nickname', $nickname);

        apply_filters('digimember_cf_update_data', $user_id, $address);

        $this->api->log('payment', _digi('Register new user %s.'), $user_login . ' (#' . $user_id . ')' );


        if ($first_name||$last_name)
        {
            $user_model->setName( $user_id, $first_name, $last_name );
        }

        return array(
            $user_id,
            $password,
            $have_created_new_user
        );
    }

    private function getProduct($product_id)
    {
        $api   = $this->api;
        $model = $api->load->model('data/product');

        $product = $model->get($product_id);

        return $product;

    }

    private function sendWelcomeMail($user_id, $password, $product, $order_id, $force_sending=false)
    {
        if (!$product)
        {
            if ($password)
            {
                wp_new_user_notification(  $user_id, $password );

                $this->api->log('payment', _digi('Create user %s without product.'), '#' . $user_id);
            }
            return;
        }

        $api   = $this->api;
        $model = $api->load->model('logic/mail_hook');

        $user_info = get_userdata( $user_id );

        $password_text = $password ? $password : $model->existingPasswordLabel();

        $params = array();

        $user = ncore_getUserById( $user_id );

        if(is_numeric($product->shortcode_url)) {
            $isValidPageId = false;
            $pages = ncore_getPages();
            foreach ($pages as $page) {
                if ($page->ID == $product->shortcode_url) {
                    $isValidPageId = true;
                }
            }
            if (!$isValidPageId) {
                $product->shortcode_url = '';
            }
        }

        $params[ 'username' ]     = ncore_retrieve( $user, 'user_login' );
        $params[ 'firstname' ]    = ncore_retrieve( $user_info, 'first_name' );
        $params[ 'lastname' ]     = ncore_retrieve( $user_info, 'last_name' );
        $params[ 'password' ]     = $password_text;
        $params[ 'product_name' ] = $product->name;
        $params[ 'product_id' ]   = $product->id;
        $params[ 'product_url' ] = is_numeric($product->shortcode_url) && $isValidPageId ? '<a href="'.get_permalink($product->shortcode_url).'">'.get_permalink($product->shortcode_url).'</a>' : '<a href="'.$product->shortcode_url.'">'.$product->shortcode_url.'</a>';
        $params[ 'order_id' ]     = $order_id;


        $params = apply_filters( 'digimember_welcome_mail_placeholder_values', $params, $user, $product, $order_id );

        $email = $user->user_email;

        $model->sendMail($email, DIGIMEMBER_MAIL_HOOK_WELCOME, $product->id, $params, $force_sending );
    }

    protected function pluginDir()
    {
        return 'plugin';
    }

    private function revokeProductAccess( $payment_provider_id, $order_id, $reason, $upgraded_order_id='', $upgrade_type='none', $access_stops_on=false )
    {
        $model = $this->api->load->model( 'data/user_product' );

        $where = array(
            'order_id' => $order_id,
        );

        if ($payment_provider_id>=1)
        {
            $where[ 'payment_provider_id' ] = $payment_provider_id;
        }

        $data = $access_stops_on
              ? array( 'access_stops_on' => ncore_dbDate( $access_stops_on ) )
              : array( 'is_active'       => 'N' );

        $user_ids = array();

        $all = $model->getAll( $where );
        foreach ($all as $one)
        {
            $user_ids[] = $one->user_id;

            $model->update($one->id, $data);
            do_action( 'digimember_purchase', $one->user_id, $one->product_id, $one->order_id, $reason );
        }

        $model = $this->api->load->model('data/download');
        $model->revoke( $payment_provider_id, $order_id, $reason );

        $must_reactive_previous_order = $user_ids
                                     && $upgraded_order_id
                                     && $order_id != $upgraded_order_id
                                     && in_array( $upgrade_type, $this->upgrade_types_with_cancel_previous_order );

        if ($must_reactive_previous_order)
        {
            $model = $this->api->load->model( 'data/user_product' );

            $data = array( 'access_stops_on' => null );

            foreach ($user_ids as $user_id)
            {
                $where = array(
                    'order_id'   => $upgraded_order_id,
                    'user_id'    => $user_id,
                );

                $all = $model->getAll($where);
                foreach ($all as $one)
                {

                    $model->update( $one->id, $data );
                }
            }
        }
    }

    private function cancelProductAccess( $payment_provider_id, $order_id, $reason, $user_id, $access_stops_on=false )
    {
        $model = $this->api->load->model( 'data/user_product' );

        $where = array(
            'order_id' => $order_id,
            'user_id' => $user_id
        );

        if ($payment_provider_id>=1)
        {
            $where[ 'payment_provider_id' ] = $payment_provider_id;
        }

        $data = $access_stops_on
            ? array( 'access_stops_on' => ncore_dbDate( $access_stops_on ) )
            : array( 'is_active'       => 'N' );

        $all = $model->getAll( $where );
        if (is_array($all) && count($all) < 1) {
            throw new Exception('No order found for orderId and user.');
        }
        foreach ($all as $one)
        {
            $model->update($one->id, $data);
            do_action( 'digimember_purchase', $one->user_id, $one->product_id, $one->order_id, $reason );
        }
    }

    private $ipn_per_product_engines;
    private $ipn_per_provider_engines;

    private function _mayLoadIpnTypes()
    {
        if (isset($this->ipn_per_product_engines))
        {
            return;
        }

        $this->ipn_per_product_engines = array();
        $this->ipn_per_provider_engines = array();

        foreach ($this->getProviders() as $engine => $label)
        {
            $meta = new StdClass();
            $meta->engine = $engine;
            $meta->id = -count( $this->loaded_plugins );

            $plugin = $this->loadPlugin( $meta );

            $ipn_type = $plugin->ipnType();
            switch ($ipn_type)
            {
                case DIGIMEMBER_IPN_PER_PRODUCT:
                    $this->ipn_per_product_engines[] = $engine;
                    break;

                case DIGIMEMBER_IPN_PER_PROVIDER:
                    $this->ipn_per_provider_engines[] = $engine;
                    break;

                default:
                    trigger_error( 'Invalid ipn type' );
            }
        }
    }

    private function error( $error_msg, $arg1='', $arg2='', $arg3='' )
    {
        $text = _digi( 'Payment provider' ).': ';
        if ($this->current_plugin)
        {
            $id = $this->current_plugin->id();
            $text .= ' ' . $this->current_plugin->label() . ' (' . _ncore( '#%s', $id) . ')';
        }

        $text .=  ' ';

        $text .= sprintf( $error_msg, $arg1, $arg2, $arg3 );

        throw new Exception( $text );
    }

    private function dummyId( $engine )
    {
        $engines = array_keys( $this->getProviders() );

        $pos = array_search( $engine, $engines );

        $id = -$pos-1;

        return $id;
    }

    private function _order_id_matches( $payment_provider_id, $prev_order_id, $order_id )
    {
        if (!$payment_provider_id) {
            return $prev_order_id == $order_id;
        }

        if (!$this->current_plugin)
        {
            return false;
        }

        if ($this->current_plugin->id() != $payment_provider_id)
        {
            trigger_error( 'Expected other pay provider!' );
        }

        return $this->current_plugin->orderIdsAreOfSameOrder( $prev_order_id, $order_id );
    }

    private function setPlugniByType( $engine )
    {
        $row = new stdClass();
        $row->engine = $engine;
        $row->id   = 0;

        $this->current_plugin = $this->loadPlugin( $row );
    }


    private function setPlugniById( $id )
    {
        $api   = $this->api;
        $model = $api->load->model('data/payment');

        $row = $model->get( $id );

        if (!$row)
        {
            $this->error( ' - ' . _digi( 'Invalid payment provider id: "%s"', $id ));
        }

        $this->current_plugin = $this->loadPlugin( $row);

        if (!$this->current_plugin->isActive())
        {
            $this->error( ' - ' . _digi( 'Payment provider not active' ) );
        }
    }

    private function _handleMembershipProduct( $reason, $payment_provider_id, $order_id, $email, $product, $quantity, $address, $force_dbl_optin=false, $order_date='', $delivery_date='', $upgraded_order_id='', $upgrade_type='none', $upgraded_order_paid_until='', $loginkey='', $extra_data=array(), $newsletter_choice='none' )
    {
        // valis values for $reason: 'manual', 'order', 'signup'

        $search_order_id = $payment_provider_id > 0
                         ? $order_id
                         : false;

        $password = ncore_retrieveAndUnset( $extra_data, 'password' );
        $set_payment_provider = ncore_retrieve( $extra_data, 'set_payment_provider', false );

        list( $user_id, $password, $have_created_new_user ) = $this->getUserId($email, $address, $search_order_id, $password );



        $api   = $this->api;
        $model = $api->load->model('data/user_product');

        $product_id = $product->id;

        if (!$user_id && !$order_id)
        {
            $this->error( _digi('has sent order notification (product %s) with no valid email address and no valid order id.', "#$product_id" ));
        }


        $upgraded_entry        = false;
        $is_quantity_downgrade = false;
        $is_quantity_upgrade   = false;

        if ($upgraded_order_id && $user_id)
        {
            $model = $api->load->model('data/user_product');

            $where = array(
                'order_id'   => $upgraded_order_id,
                'user_id'    => $user_id,
                'product_id' => $product_id,
            );

            $upgraded_entry = $model->getWhere($where);

            if ($upgrade_type == 'package_change' && $quantity >= 1)
            {
                $is_quantity_upgrade = !$upgraded_entry
                                       || $quantity >= $upgraded_entry->quantity;
            }
        }

        if ($upgraded_order_id && $user_id && !$upgraded_entry)
        {
            $model = $api->load->model('data/user_product');

            $where = array(
                'order_id'   => $upgraded_order_id,
                'user_id'    => $user_id,
            );

            $upgraded_entry = $model->getWhere($where);

            $must_deactive_previous_order = $upgraded_entry
                                         && !$is_quantity_upgrade
                                         && in_array( $upgrade_type, $this->upgrade_types_with_cancel_previous_order );
            if ($must_deactive_previous_order)
            {
                $access_stop_date = $delivery_date
                                  ? $delivery_date
                                  : $upgraded_order_paid_until;

                if ($access_stop_date)
                {
                    $extra_data[ 'access_starts_on' ] = $access_stop_date;

                    $data = array( 'access_stops_on' => $access_stop_date );
                }
                else
                {
                    $now = ncore_dbDate();
                    $data = array(  'is_active' => 'N', 'access_stops_on' => $now );
                    $extra_data[ 'access_starts_on' ] = $now;
                }

                $model->update( $upgraded_entry, $data );
            }
        }


        $existing_order = false;

        if ($user_id)
        {
            $where = array();
            $where[ 'product_id' ] = $product_id;
            $where[ 'user_id'] = $user_id;

            $prev_order_id = false;

            $prev_entrys = $model->getAll($where);
            foreach ($prev_entrys as $one)
            {
                $one_order_id = ncore_retrieve( $one, 'order_id' );

                $is_match = $this->_order_id_matches( $payment_provider_id, $one_order_id, $order_id );

                if ($is_match)
                {
                    $existing_order = $one;
                    break;
                }
            }
        }

        if ($order_id && !$existing_order && $payment_provider_id>0)
        {
            $where = array();
            $where[ 'product_id' ] = $product_id;
            $where[ 'order_id']    = $order_id;

            $existing_order = $model->getWhere($where);
        }

// THIS causes ADDON orders to be ignored:
//       if ($order_id && !$existing_order && $payment_provider_id>0)
//        {
//            $where = array();
//            $where[ 'order_id']    = $order_id;
//
//            $existing_order = $model->getWhere($where);
//        }

        $send_welcome_mail = (bool) $email && !$existing_order && $upgrade_type != 'switch_plan';

        $extra_data[ 'is_active' ]           = 'Y';
        $extra_data[ 'order_id' ]            = $order_id;
        $extra_data[ 'payment_provider_id' ] = $set_payment_provider ? $set_payment_provider : $payment_provider_id;
        $extra_data[ 'quantity' ]            = $quantity;


        if ($existing_order)
        {
            $id = $existing_order->id;

            $data = $extra_data;

            $data[ 'last_pay_date' ] = ncore_dbDate();

            $model->update($id, $data);
        }
        elseif ($user_id)
        {
            $order_date_valid = $order_date
                             && strtotime( $order_date ) > 0
                             && preg_match( '/^[0-9]{4,4}-[0-9]{1,2}-[0-9]{1,2}/', $order_date );
            if (!$order_date_valid)
            {
                 $order_date = ncore_dbDate();
            }

            $data = $extra_data;

            $data[ 'user_id' ]             = $user_id;
            $data[ 'product_id' ]          = $product_id;
            $data[ 'order_date' ]          = $order_date;
            $data[ 'last_pay_date' ]       = $order_date;

            if ($delivery_date)
            {
                $data[ 'access_starts_on' ] = $delivery_date;
            }

            $id = $model->create($data);
        }
        else
        {
            $this->error( _digi('has send purchase notification (product %s) with an invalid order id: \'%s\'', "#$product_id", $order_id ));
        }

        switch ($reason)
        {
            case 'manual':
                $log_msg = $send_welcome_mail
                      ? _digi('%s gets access to %s by a manual order.')
                      : _digi('%s gets access to %s by a manual order.');
                break;

            case 'signup':
                $log_msg = $send_welcome_mail
                      ? ($have_created_new_user
                         ? _digi('%s signs up and gets product %s.')
                         : _digi('%s signs up and gets product %s.')
                        )
                      : ($have_created_new_user
                         ? _digi('%s signs up, but already has access to product %s.')
                         : _digi('%s signs up, but already has access to product %s.')
                        );
                break;


            case 'order':
            default:
                $log_msg = $send_welcome_mail
                      ? _digi('%s: purchases %s')
                      : _digi('%s: subsequent payment for %s');

        }

        $product_label = $product->name;
        $product_label .= $payment_provider_id
                        ? ' ('._digi( 'Product id %s, order id %s, payment provider id %s', $product->id, $order_id, $payment_provider_id ) . ')'
                        : ' ('._digi( 'Product id %s, order id %s, no payment provider', $product->id, $order_id ) . ')';

        $user_label = $email||$user_id
                    ? $email . ' (' . _digi( '#%s', $user_id) . ')'
                    : _digi('Unknown user');

        $this->api->log('payment', $log_msg, $user_label, $product_label );

        do_action( 'digimember_purchase', $user_id, $product_id, $order_id, 'order_paid' );



        if ($upgraded_entry)
        {
            $must_replace_order = $upgrade_type == 'upgrade' || $upgrade_type == 'downgrade' || $upgrade_type == 'switch_plan' || $upgrade_type == 'package_change';

            if ($must_replace_order)
            {
                $now = ncore_dbDate();

                $access_stops_on = $delivery_date
                                 ? $delivery_date
                                 : ($upgraded_order_paid_until && !$is_quantity_upgrade
                                    ? $upgraded_order_paid_until
                                    : ncore_dbDate());







                $data = array();
                $data['access_stops_on'] = $access_stops_on;
                $model->update( $upgraded_entry->id, $data );

                $data = array();
                $data[ 'order_date' ] = $upgraded_entry->order_date;
                $model->update( $id, $data );
            }
        }

        if ($user_id)
        {
            $model = $api->load->model('data/loginkey');
            $model->setForUser( $user_id, $loginkey );
        }

        if ($send_welcome_mail)
        {
            $this->sendWelcomeMail($user_id, $password, $product, $order_id);

            $api   = $this->api;
            $model = $api->load->model('queue/ipn_out');
            $model->addJob( $user_id, $product_id, $email, $address, $order_id, $force_dbl_optin, $newsletter_choice );
        }

        return array( $id, $send_welcome_mail );
    }

    private function _handleDownloadProduct( $reason, $payment_provider_id, $order_id, $email, $product, $address, $force_dbl_optin=false, $newsletter_choice='none' )
    {
        // valis values for $reason: 'manual', 'order', 'signup'

        $api   = $this->api;

        $model = $api->load->model('queue/ipn_out');
        $model->addJob( $user_id=0, $product->id, $email, $address, $order_id, $force_dbl_optin, $newsletter_choice );

        $model = $api->load->model('data/download');
        $download_id = $model->grant( $payment_provider_id, $order_id, $email, $product );

        $url = $model->downloadPageUrl( $download_id );

        $params = array();

        $params[ 'url' ] = $url;
        $params[ 'product_name' ] = $product->name;
        $params[ 'product_id' ]   = $product->id;
        $params[ 'order_id' ]     = $order_id;
        $params[ 'firstname' ]    = ncore_retrieve( $address, 'first_name' );
        $params[ 'lastname' ]     = ncore_retrieve( $address, 'last_name' );

        $model = $api->load->model('logic/mail_hook');
        $model->sendMail($email, DIGIMEMBER_MAIL_HOOK_DOWNLOAD, $product->id, $params );

        return array( $download_id, $welcome_msg_sent=true );
    }

    /**
     * Formats an Email to lowercase and deletes whitespaces
     * @param $email
     * @return string
     */
    private function sanitizeEmail( $email)
    {
        return strtolower( trim($email));
    }



}

