<?php

class digimember_DigistoreConnectorLogic extends ncore_BaseLogic
{
    const all_cache_keys = 'products,settings,currencies';

    const cache_lifetime_minutes   = 120;
    const error_retry_time_minutes = 60;

    const ds24_api_devel_key  = '687626-16DTbqY2O2z4XPfYQoyThJconli3yD4g3GKuwPN9';
    const ds24_api_debug_host = 'http://ds24.de';

    public function ds24( $force_reload=false )
    {
        if ($this->lib === false || $force_reload)
        {
            /** @var digimember_BlogConfigLogic $config */
            $config  = $this->api->load->model( 'logic/blog_config' );
            $api_key = $config->get( 'ds24_apikey', false );
            if (!$api_key) {
                throw new Exception( _digi( 'Please establish a connection to %s first.', $this->api->Digistore24DisplayName() ) );
            }

            $this->api->load->library( 'ds24_api', array( 'dont_instance' => true ) );
            $this->lib = DigistoreApi::connect( $api_key );

            $lang = get_locale();
            $this->lib->setLanguage( $lang  );

            if (NCORE_DEBUG)
            {
                $this->lib->setBaseUrl( self::ds24_api_debug_host );
            }

        }

        return $this->lib;

    }

    public function sanitizeException( Exception $e, $context1='', $context2='' )
    {
        $msg  = $e->getMessage();
        $code = $e->getCode();

        $is_ds24_api_loaded = defined('DS_ERR_UNKNOWN');
        if (!$is_ds24_api_loaded) {
            return $e;
        }

        switch ($e->getCode())
        {
            case DS_ERR_NOT_FOUND:
                switch ($context1)
                {
                    case 'product':
                        $digistore24 = $this->api->Digistore24DisplayName( $as_link=false );
                        $msg = _digi( 'The product has been deleted in %s.', $digistore24 );

                        if ($context2=='sync')
                        {
                            $msg .= ' ' . _digi( 'This %s product cannot be synchronized with %s any more.', $this->api->pluginDisplayName(), $digistore24 );
                        }
                        break;
                }
        }

        return new Exception( $msg, $code );
    }


    public function createImage( $image_url, $image_name, $usage_type='product' )
    {
        try
        {
            if (empty($image_url)) {
                return null;
            }

            $ds24 = $this->ds24();

            $data = $ds24->createImage( $image_url, $usage_type, $image_name, $alt_tag='' );

            return $data['image_id'];
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    public function getPurchaseDownloads( $order_id_or_ids )
    {
        $purchase_ids = is_string($order_id_or_ids)
                      ? $order_id_or_ids
                      : implode( ',', $order_id_or_ids );

        $data = ncore_cacheRetrieve( "ds24dl_$purchase_ids" );

        if ($data === false)
        {
            $data = $this->ds24()->getPurchaseDownloads( $purchase_ids );

            ncore_cacheStore( "ds24dl_$purchase_ids", $data, 3600 );
        }

        return $data;
    }

    public function getPurchase( $purchase_id )
    {
        $data = ncore_cacheRetrieve( "ds24p_$purchase_id" );

        if ($data === false)
        {
            $data = $this->ds24()->getPurchase( $purchase_id );
            ncore_cacheStore( "ds24p_$purchase_id", $data, 3600 );
        }

        return $data;
    }

    public function getGlobalSetting( $key, $subkey=false )
    {
        $global_settings = $this->cacheGet( 'settings' );

        $must_reload = !empty( $_GET['reload'] );

        if (!$global_settings || $must_reload)
        {
            $expire_minutes = self::cache_lifetime_minutes;

            try
            {
                $ds24 = $this->ds24();
                if (!$ds24)
                {
                    return false;
                }

                $global_settings = $this->ds24()->getGlobalSettings();
            }
            catch (Exception $e)
            {
                $expire_minutes = self::error_retry_time_minutes;
            }

            $this->cacheSet( 'settings', $global_settings, $expire_minutes );
        }

        $settings = ncore_retrieve( $global_settings, $key, false );
        if ($subkey)
        {
            $settings = ncore_retrieve( $settings, $subkey, false );
        }

        return is_object($settings)
               ? (array) $settings
               : $settings;
    }

    public function url( $what, $id=false )
    {
        $url = $this->getGlobalSetting( 'urls', $what );
        if (!$url) {
            return '';
        }

        $have_id   = strpos( $url, '_ID' ) !== false;
        $have_name = strpos( $url, '_NAME' ) !== false;

        if ($have_id)   $url = preg_replace( '/[A-Z0-9_]*\_ID/',   $id, $url );

        if ($have_name) $url = preg_replace( '/[A-Z0-9_]*\_NAME/', $id, $url );

        return $url;
    }

    public function getCurrencyOptions( $allow_null=true )
    {
        $must_reload = !empty( $_GET['reload'] );

        $cache_key = 'cur_'.intval($allow_null);

        $options = $this->cacheGet( $cache_key );
        if ($options && !$must_reload) {
            return $options;
        }

        $fallback_options = array( 'EUR' => 'Euro' );
        $expire_minutes = self::cache_lifetime_minutes;

        try
        {
            $ds24 = $this->ds24();
            if (!$ds24)
            {
                return $fallback_options;
            }

            $currencies = $this->ds24()->listCurrencies();
            $options = array();
            foreach ($currencies as $one)
            {
                $options[ $one->code ] = $one->name;
            }
            $have_used_fallback_options = false;
        }
        catch (Exception $e)
        {
            $options  = $fallback_options;
            $expire_minutes = self::error_retry_time_minutes;
        }

        $this->cacheSet( $cache_key, $options, $expire_minutes );

        return $options;
    }

    public function cronDaily()
    {
        try
        {
            if ($this->checkConnection()) {
                $this->updateIpnConnection();
            }
        }
        catch (Exception $e)
        {
        }
    }

    public function validateSetup()
    {
        if ($this->isConnectionCheckEnabled())
        {
            list( $type, $message ) = $this->renderStatusNotice();
            if ($type != NCORE_NOTIFY_SUCCESS) {
                ncore_flashMessage( $type, $message, 'ds24_not_connected', 'ds24_not_connected' );
            }
        }
    }


    public function renderStatusNotice( $layout='default', $buttton_style='default', $force_reload=false )
    {
        $modified = $this->handleEvents();

        if ($modified) $force_reload = true;

        $is_ds24_name_as_link_allowed = $layout=='default' && $buttton_style=='default';

        list( $is_connected, $type, $message ) = $this->connectionStatus( $force_reload, $is_ds24_name_as_link_allowed );

        if ($buttton_style==='auto')
        {
            $buttton_style = $is_connected
                           ? 'link'
                           : 'button';
        }

        $button = $is_connected
                ? $this->renderDisconnectButton($buttton_style)
                : $this->renderConnectButton($buttton_style);

        $seperated_button = '';

        switch ($layout)
        {
            case 'button':
                $message = $button;
                break;

            case 'mixed':
                $message = $type==NCORE_NOTIFY_SUCCESS
                         ? $message
                         : $button;
                break;

            case 'seperate':
                $seperated_button = $button;
                break;

            case 'default':
            default:
                $message .= '&nbsp;' . $button;
        }

        return array( $type, $message, $seperated_button );
    }

    public function getProductOptions( $force_reload=false )
    {
        $this->api->load->helper( 'string' );

        $max_name_length = 50;

        $products = $this->getProducts($force_reload);
        if (!$products) {
            return $products;
        }

        $last_product_group_id=false;
        $options = array();
        foreach ($products as $one)
        {
            if ($one->product_group_id != $last_product_group_id)
            {
                $options[ 'optgroup_'.$one->product_group_id] = $one->product_group_name;
                $last_product_group_id = $one->product_group_id;
            }

            $len = $max_name_length;
            if ($one->note)
            {
                $len = max( 10, $max_name_length-15, $max_name_length-4-strlen($one->note) );
            }

            $label = $one->id . ' - ';

            $label .= ncore_shortenText( $one->name, $len, $grace = 10, $end_msg = '&#8230;' );

            if ($one->note)
            {
                $label .= ' ['.$one->note.']';
            }

            $options[ $one->id ] = $label;
        }

        return $options;
    }

    public function getProducts( $force_reload=false )
    {
        if ($force_reload)
        {
            $found = false;
        }
        else
        {
            $products = $this->cacheGet( 'products', 'not_found' );
            $found = $products !== 'not_found';
        }

        if (!$found)
        {
            $products = false;

            try
            {
                $ds24 = $this->ds24();

                add_filter('http_request_timeout', function($current) {
                    return 30;
                });
                $data = $ds24
                          ? $this->ds24()->listProducts( $sort_by='group' )
                          : false;

                $products = ncore_retrieve( $data, 'products', false );
            }
            catch (Exception $e)
            {
                $this->api->log('payment', $e->getMessage(), '', '', '', 'error');
            }

            if ($products === false)
            {
                $products = $this->cacheGet( 'products', 'not_found' );
                $found = $products !== 'not_found';

                if (!$found)
                {
                    $this->cacheSet( 'products', false );
                    $products = array();
                }
            }
            else
            {
                $this->cacheSet( 'products', $products );
            }
        }

        return $products;
    }


    public function setupChecklistDone()
    {
        return $this->isConnected();
    }

    public function isConnected( $force_reload=false ) {

        if (!$force_reload)
        {
            try
            {
                $have_connection = (bool) $this->ds24();
                return $have_connection;
            }
            catch (Exception $e) {
                return false;
            }
        }

        list( $is_connected, $style, $message ) = $this->connectionStatus( $force_reload );
        return  $is_connected;
    }

    public function connectionStatus( $force_reload=false, $is_ds24_name_as_link_allowed=true ) {

        static $is_connected;

        if ($force_reload || !isset($is_connected))
        {
            $is_connected = $this->checkConnection();
        }

        if ($is_connected) $is_ds24_name_as_link_allowed=true;


        // define( 'NCORE_NOTIFY_SUCCESS', 'success' );
        // define( 'NCORE_NOTIFY_WARNING', 'warning' );
        // define( 'NCORE_NOTIFY_ERROR',   'error' );

        $plugin   = $this->api->pluginDisplayName();
        $ds24name = $this->api->Digistore24DisplayName($is_ds24_name_as_link_allowed);

        $config  = $this->api->load->model( 'logic/blog_config' );
        $user    = $config->get( 'ds24_username' );

        $find = array( '[PLUGIN]','[DS24]',  '[USER]' );
        $repl = array( $plugin,   $ds24name, "<strong>$user</strong>"  );

        if ($is_connected)
        {
            $style = NCORE_NOTIFY_SUCCESS;
            $message = _digi( '[PLUGIN] is connected to [DS24] account [USER].' );
        }
        else
        {
            $style = NCORE_NOTIFY_ERROR;
            $message = _digi( '[PLUGIN] is NOT connected to [DS24].' );
        }

        $message = str_replace( $find, $repl, $message );

        return array( $is_connected, $style, $message );
    }

    public function connectionInfo()
    {
        $config  = $this->api->load->model( 'logic/blog_config' );

        $ds24_username = $config->get( 'ds24_username' );
        $ds24_userid   = $config->get( 'ds24_userid' );

        return array(
            'username' => $ds24_username,
            'userid'   => $ds24_userid,
        );
    }


    public function testDs24ServerConnection()
    {
        global $DM_SUPPORT_URL_TEST;

        $site_url = ncore_siteUrl();

        $api = $this->developerApi();
        $permissions = defined( 'DIGIMEMBER_DS24_API_KEY_PERMISSIONS' )
                       ? DIGIMEMBER_DS24_API_KEY_PERMISSIONS
                       : 'digimember';

        $DM_SUPPORT_URL_TEST = true;
        $old_level = error_reporting( E_ALL );

        $data = $api->requestApiKey( $permissions, $site_url, $site_url, $site_url, $referrer_id=false );

        error_reporting( $old_level );
        $DM_SUPPORT_URL_TEST = false;

        $success = is_object($data) && !empty( $data->request_url );

        return $success;
    }


    private $events_handled = false;
    public function handleEvents()
    {
        if ($this->events_handled) {
            return false;
        }

        $this->events_handled = true;
        //
        // Handle disconnect
        //
        $is_disconnect = isset( $_GET['ds24_disconnect' ] );
        if ($is_disconnect) {
            $this->api->load->helper( 'xss_prevention' );
            if (ncore_XssPasswordVerified())
            {
                $this->deleteConnection();
                ncore_flashMessage( NCORE_NOTIFY_WARNING, _digi( 'The connection to %s has been cut.', $this->api->Digistore24DisplayName()  ) );
                $this->cachePurge();
            }
            $url = $this->currentUrl();
            ncore_redirect($url);
        }

        //
        // Handle connect initialisation
        //
        try
        {
            $is_connect = isset( $_GET['ds24_connect' ] );
            if ($is_connect) {

                $url = $this->currentUrl();

                $this->api->load->helper( 'xss_prevention' );
                if (ncore_XssPasswordVerified())
                {

                    $this->api->load->helper( 'xss_prevention' );

                    $args_Y = array();
                    $args_Y[ ncore_XssVariableName() ] = ncore_XssPassword();
                    $args_Y[ 'ds24_connected' ] = 'Y';

                    $args_N = $args_Y;
                    $args_N[ 'ds24_connected' ] = 'N';


                    $return_url = ncore_addArgs( $url, $args_Y, '&', false );
                    $cancel_url = ncore_addArgs( $url, $args_N, '&', false );

                    $site_url = ncore_siteUrl();

                    $api = $this->developerApi();
                    $permissions = defined( 'DIGIMEMBER_DS24_API_KEY_PERMISSIONS' )
                                 ? DIGIMEMBER_DS24_API_KEY_PERMISSIONS
                                 : 'digimember';

                    /** @var digimember_LinkLogic $link_model */
                    $link_model = $this->api->load->model('logic/link');
                    $referrer_id = $link_model->digistoreReference();

                    $data = $api->requestApiKey( $permissions, $return_url, $cancel_url, $site_url, $referrer_id );

                    $request_token = $data->request_token;
                    $request_url   = $data->request_url;

                    /** @var digimember_BlogConfigLogic $config */
                    $config  = $this->api->load->model( 'logic/blog_config' );
                    $config->set( 'ds24_token', $request_token, 3 );

                    ncore_redirect( $request_url );

                }

                ncore_redirect($url);
            }
        }
        catch (Exception $e)
        {
            ncore_flashMessage( NCORE_NOTIFY_ERROR, $e->getMessage()  );
            return true;
        }


        //
        // Handle connect completion
        //
        try
        {
            $result = ncore_retrieve( $_GET, 'ds24_connected' );
            if (!$result)
            {
                return false;
            }

            $this->api->load->helper( 'xss_prevention' );
            if (!ncore_XssPasswordVerified())
            {
                $msg = _digi( 'The connection attempt took too much time. Please try again.' );
                throw new Exception( $msg );
            }

            if ($result!=='Y')
            {
                $ds24name = $this->api->Digistore24DisplayName();
                $msg = _digi( 'The connection attempt to %s was cancelled. You may try again.', $ds24name );
                throw new Exception( $msg );
            }

            $config  = $this->api->load->model( 'logic/blog_config' );
            $request_token = $config->get( 'ds24_token' );
            $config->delete( 'ds24_token' );
            if (!$request_token) {
                return false;
            }

            $api = $this->developerApi();

            $data = $api->retrieveApiKey( $request_token );
            $apikey = ncore_retrieve( $data, 'api_key' );
            $note   = ncore_retrieve( $data, 'note' );

            $ds24name = $this->api->Digistore24DisplayName();

            if (!$apikey) {
                $msg = _digi( 'The connection to %s failed.', $ds24name );
                if ($note)
                {
                    $msg .= ' ' . _digi( '%s reported this error: %s', $ds24name, "<em>$note</em>" );
                }
                $msg .= ' ' . _digi( 'Please try again.' );
                throw new Exception( $msg );
            }

            $digistore_id = $this->setupConnection( $apikey );

            ncore_flashMessage( NCORE_NOTIFY_SUCCESS, _digi( 'The connection to %s account %s has been established.', $this->api->Digistore24DisplayName(), "<strong>$digistore_id</strong>" ) );

            $url = $this->currentUrl();
            ncore_redirect($url);
        }
        catch (Exception $e)
        {
            ncore_flashMessage( NCORE_NOTIFY_ERROR, $e->getMessage()  );
            return true;
        }

        return false;
    }


    public function developerApi()
    {
        $host = (NCORE_DEBUG
                 ? self::ds24_api_debug_host
                 : false);

        $apikey = (defined( 'DIGIMEMBER_DS24_DEVELOPER_API_KEY' )
                   ? DIGIMEMBER_DS24_DEVELOPER_API_KEY
                   : self::ds24_api_devel_key );

        $this->api->load->library( 'ds24_api', array( 'dont_instance' => true ) );

        $api = DigistoreApi::connect( $apikey );
        $api->setLanguage( get_locale() );

        if ($host) {
            $api->setBaseUrl( $host );
        }

        return $api;

    }

    private function connectorPassword( $existing_only=false)
    {
        $key = 'dm24_con_password';

        $config  = $this->api->load->model( 'logic/blog_config' );

        $password = $config->get( $key );

        if (!$password && !$existing_only) {
            $this->api->load->helper( 'string' );
            $password = ncore_randomString( 'alnum', 20 );
        }

        $config->set( $key, $password, 2 );

        return $password;
    }

    private $lib = false;

    private function deleteConnection()
    {
        try
        {
            $data = $this->ds24()->unregister();
        }
        catch (Exception $e)
        {
        }

        $config  = $this->api->load->model( 'logic/blog_config' );
        $config->set( 'ds24_apikey',   false, -1 );
        $config->set( 'ds24_username', '', -1 );
        $config->set( 'ds24_userid',   0, -1 );
    }

    private function setupConnection( $api_key )
    {
        $config  = $this->api->load->model( 'logic/blog_config' );
        $config->set( 'ds24_apikey', $api_key );

        $this->ds24($force_reload=true);

        $user_info = $this->ds24()->getUserInfo();

        $ds24_name     = ncore_retrieve( $user_info, 'user_name' );
        $ds24_userid   = ncore_retrieve( $user_info, 'user_id' );
        $is_connected  = (bool) $ds24_name;
        if (!$is_connected) {
            $ds24name = $this->api->Digistore24DisplayName(false);
            throw new Exception( _digi( 'The connection request to %s failed (due to an internal error). Please try again.', $ds24name ) );
        }

        $config->set( 'ds24_username', $ds24_name );
        $config->set( 'ds24_userid',   $ds24_userid );

        $this->cachePurge();

        $this->updateIpnConnection( $reset_pw=true );


        $model = $this->api->load->model( 'logic/event_subscriber' );
        $model->call( 'dm_ds24_connection_established', $ds24_name );

        return $ds24_name;

    }

    public function updateIpnConnection( $reset_pw=false )
    {
        $model = $this->api->load->model( 'data/product' );
        $ds24_product_ids = $model->getAllDS24ProductIdsCommaSeperated();

        $model = $this->api->load->model( 'data/payment' );
        $cfg = $model->getDs24config( $reset_pw );

        $model = $this->api->load->model( 'logic/link' );
        $url = $model->ipnCall( $cfg, $product_id=false, $arg_sep='&' );

        $find = array( '[SITE]', '[PLUGIN]' );
        $repl = array( ncore_siteUrl(), $this->api->pluginDisplayName() );

        $name = str_replace( $find, $repl, _digi( '[PLUGIN] on [SITE]' ) );

        try
        {
            $data = $this->ds24()->ipnSetup( $url, $name, $ds24_product_ids );
        }
        catch (Exception $e)
        {
            $ds24name = $this->api->Digistore24DisplayName($is_ds24_name_as_link_allowed=false);

            $msg = _digi( 'Error when updating the %s IPN connection: %s', $ds24name, $e->getMessage() );
            $this->api->logError( 'api', $msg );
            ncore_flashMessage( NCORE_NOTIFY_ERROR, $msg );
            return false;
        }
    }



    private function renderConnectButton( $button_style='default' )
    {
        $label = _digi( 'Connect [DS24] now' );

        $this->api->load->helper( 'xss_prevention' );
        $params = array(
            ncore_XssVariableName() => ncore_XssPassword(),
            'ds24_connect' => true,
        );
        $url = $this->currentUrl();
        $url = ncore_addArgs( $url, $params, '&', false );

        $confirm = _digi( 'You will now be redirected to [DS24].|If you do not have a [DS24] account yet, you will have the option to create one.|Continue?' );

        return $this->_renderButton( $button_style, 'button', $label, $url, $confirm );
    }

    private  function renderDisconnectButton( $button_style='default' )
    {
        $label = _digi( 'Disconnect' );

        $this->api->load->helper( 'xss_prevention' );
        $params = array(
            ncore_XssVariableName() => ncore_XssPassword(),
            'ds24_disconnect' => true,
        );
        $url = $this->currentUrl();
        $url = ncore_addArgs( $url, $params, '&', false );
        $confirm = _digi( 'The connection to [DS24] will now be cut.|Do not continue unless you want to establish a connection to another [DS24] account.|Continue?' );

        return $this->_renderButton( $button_style, 'link', $label, $url, $confirm );

    }


    private function cacheGet( $key, $default=false ) {
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $value = $model->get( "ds24$key", 'not_found' );
        if ($value === 'not_found') {
            return $default;
        }
        else {
            return @unserialize($value);
        }
    }

    private function cacheSet( $key, $value, $lifetime_minutes=60 ) {
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $lifetime_hours = $lifetime_minutes/60;
        $model->set( "ds24$key", serialize($value), $lifetime_hours );
    }

    private function cachePurge( $key_or_keys='all' )
    {
        $all_cache_keys = explode( ',', str_replace( ' ','', self::all_cache_keys ) );

        if ($key_or_keys === 'all') {
            $keys_to_purge = $all_cache_keys;
        }
        elseif (is_string($key_or_keys)) {
            $keys_to_purge = array( $key_or_keys );
        }
        else {
            $keys_to_purge = $key_or_keys;
        }


        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );

        foreach ($keys_to_purge as $key)
        {
            $model->delete( "ds24$key" );
        }
    }


    private function isConnectionCheckEnabled()
    {
        $config  = $this->api->load->model( 'logic/blog_config' );
        $is_connected = $config->get( 'ds24_connected', 'N' ) == 'Y';
        $is_options_page = ncore_retrieve( $_GET, 'page' ) == $this->api->pluginName();
        if ($is_connected && !$is_options_page) {
            return false;
        }

        $model = $this->api->load->model( 'logic/features' );
        $must_check = !$model->canUseOtherPaymentProviders();
        if ($must_check) {
            return true;
        }

        $config = $this->api->load->model( 'logic/blog_config' );
        $have_providers = $config->get( 'ds24_pay_prv_are_setup' );

        if ($have_providers) {
            return $have_providers == 'N';
        }

        $model = $this->api->load->model( 'data/payment' );
        $have_providers = $model->getAll() ? 'Y' : 'N';

        $config->set( 'ds24_pay_prv_are_setup', $have_providers, 60 );
        return $have_providers == 'N';
    }

    private function _renderButton( $button_style, $default_style, $label, $url, $confirm )
    {
        if ($button_style === 'default') {
            $button_style  = $default_style;
        }

        $ds24name = $this->api->Digistore24DisplayName( $asLink=false );

        $find = array( '[DS24]' );
        $repl = array( $ds24name );

        $label   = str_replace( $find, $repl, $label );
        $confirm = str_replace( $find, $repl, $confirm );

        switch ($button_style)
        {
            case 'button': $css = 'dm-btn dm-btn-primary' ;break;
            case 'link':   $css = 'dm-btn dm-btn-success dm-btn-outlined'; break;
            default:
                trigger_error( "Invalid button style: '$button_style'" );
                $css = '';
        }

        $attr = array();
        $attr['class'] = $css;
        $attr['confirm']= $confirm;

        $this->api->load->helper( 'html_input' );
        switch ($button_style)
        {
            case 'button':
                return ncore_htmlButtonUrl( $label, $url, $attr );
            case 'link':
            default:
                return ncore_htmlLink( $url, $label, $attr );
        }
    }

    private function checkConnection()
    {
        try {
            $user_info = $this->ds24()->getUserInfo();

            $digistore_id = ncore_retrieve( $user_info, 'user_name' );
            $site_url     = ncore_retrieve( $user_info, 'api_key_site_url' );
            $is_taunted   = ncore_retrieve( $user_info, 'api_key_is_taunted', 'N' ) == 'Y';

            $is_connected = (bool) $digistore_id;

            $config = $this->api->load->model( 'logic/blog_config' );
            $config->set( 'ds24_connected', $is_connected? 'Y': 'N', 1 );

            if ($is_connected) {

                $must_get_new_key = str_replace( 'https://', 'http://', $site_url )
                                 != str_replace( 'https://', 'http://', ncore_siteUrl() ) || $is_taunted;

                if ($must_get_new_key)
                {
                    $ds24name = $this->api->Digistore24DisplayName(false);
                    $find = array( '[PLUGIN]', '[DS24]' );
                    $repl = array( $this->api->pluginDisplayName(), $ds24name );
                    $msg = _digi( 'The connection [PLUGIN] and [DS24] was outdated and therefore disconnected. In your wordpress site go [PLUGIN] - Settings and connect to [DS24] again.' );
                    do_action('render_admin_notice', str_replace( $find, $repl, $msg ));
                    $this->api->log( 'api', str_replace( $find, $repl, $msg ) );
                    $this->deleteConnection();
                }

                return true;
            }

            return false;
        }

        catch (Exception $e)
        {
            return false;
        }
    }

    private function currentUrl()
    {
        $this->api->load->helper( 'xss_prevention' );
        $url = ncore_removeArgs( ncore_currentUrl(), array( 'ds24_disconnect', 'ds24_connect', 'ds24_connected', ncore_XssVariableName() ), '&', false );
        return $url;
    }


}

