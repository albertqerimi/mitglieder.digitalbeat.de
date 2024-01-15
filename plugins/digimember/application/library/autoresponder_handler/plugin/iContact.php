<?php


class digimember_AutoresponderHandler_PluginIContact extends digimember_AutoresponderHandler_PluginBase
{
    const aweber_app_id = 'xxxxx';

    public function unsubscribe( $email )
    {
    }    
    
    public function getPersonalData( $email )
    {
        return array();
    }    
    
    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        $account = $this->account();

        $list_id = $this->data( 'list_id' );

        if (!$list_id)
        {
            return;
        }

        $lists = $account->lists->find(array('id' => $list_id));

        if (!$lists)
        {
            throw new Exception( _ncore( 'Invalid email list.' ) );
        }

        $list = false;
        foreach ($lists as $one)
        {
            if ($one->id == $list_id)
            {
                $list = $one;
                break;
            }
        }
        if (!$list)
        {
            throw new Exception( _ncore( 'Invalid email list.' ) );
        }

        $params = array(
            'email' => $email,
            'ip_address' => '127.0.0.1',
            // 'ad_tracking' => 'client_lib_example',
            // 'last_followup_message_number_sent' => 1,
            'misc_notes' => $this->api->pluginDisplayName(),
            'name' => "$first_name $last_name",
            //'custom_fields' => array(
            //    'Car' => 'Ferrari 599 GTB Fiorano',
            //    'Color' => 'Red',
            //),
         );

        $subscribers = $list->subscribers;

        try
        {
            $new_subscriber = $subscribers->create($params);
        }
        catch (AWeberAPIException $e)
        {
            // empty - allready subscribed
        }


    }

    public function isActive()
    {
        $curl_installed = extension_loaded('curl');
        return $curl_installed;
    }


    public function inactiveMsg()
    {
        return _digi3('The AWeber API needs the php extension "curl" activated for your webaccount. Without "curl", a connection to AWeber is not possible. Please ask your webhoster to activate "curl".' );
    }


    public function formMetas()
    {
        $auth_token_url = $this->aweberAuthTokenUrl();

        $auth_hint_templ = _digi3( 'To get an authorization code from AWeber, <a>click here</a>.' );

        $tooltip_templ = _digi3( 'To authenticate with AWeber, you need an authoriation code. Click on the link below the input area to visit the AWeber web site and get one.<p><strong>Important:</strong> If you have more than one AWeber autoresponder setting, you need to enter a new authorization code for each of these settings - even if you use the same AWeber account.' );

        $find = array( '<a>', '[PLUGIN]');
        $repl = array( "<a href='$auth_token_url' target='_blank'>", $this->api->pluginDisplayName() );

        $auth_hint = str_replace( $find, $repl, $auth_hint_templ );
        $tooltip = str_replace( $find, $repl, $tooltip_templ );


        $metas = array();

        $metas[] = array(
                'name' => 'authtoken',
                'type' => 'textarea',
                'label' => _digi3( 'AWeber Authorization Code' ),
                'rules' => 'required|trim|remove_whitespace',
                'hint' => $auth_hint,
                'tooltip' => $tooltip,
                'rows' => 4,
                'cols' => 75,
                'css'  => 'ncore_code',
        );

        $list_options = $this->getLists();
        $list_error = $list_options === 'error';;
        $have_lists = $list_options && !$list_error;
        if ($have_lists)
        {
            $metas[] = array(
                'name' => 'list_id',
                'type' => 'select',
                'options' => $list_options,
                'label' => _digi3( 'AWeber list' ),
            );
        }
        else
        {
            $metas[] = array(
                'name' => 'list_id',
                'type' => 'hidden',
            );

            $msg = $list_error
                   ? _digi3( 'Enter a valid authorization code above, save and then pick a AWeber list here.' )
                   : _digi3( 'Please log into your AWeber account and create an email list.' );

            $css = '';

            $show_error = $list_error && (bool) $this->authtoken();

            if ($show_error)
            {
                $css = 'ncore_form_cell_error_message';
                $msg = _digi3( 'The authorization code is invalid.' ) . ' ' . $msg;
            }

            $metas[] = array(
                'label' => _digi3( 'AWeber list' ),
                'type' => 'html',
                'html' => $msg,
                'css'  => $css,
            );
        }

        return $metas;

    }

    public function instructions()
    {
        $instructions = parent::instructions();

        $msg = _digi3( '<a>Click here</a> to log into AWeber and retrieve an authorization code. Copy the code to the clipboard.' );
        $url = $this->aweberAuthTokenUrl();
        $find = '<a>';
        $repl = "<a href='$url' target='_blank'>";

        $instructions[] = str_replace( $find, $repl, $msg );

        $instructions[] = _digi3( '<strong>Here in DigiMember</strong> paste the code into the <em>AWeber Authorization Code</em> text input.' );

        $instructions[] = _digi3( 'Save your changes.' );

        $instructions[] = _digi3( 'Select the correct list from the <em>AWeber list</em> dropdown list above.' );

        $instructions[] = _digi3( 'Save your changes again and you are done.' );

        return $instructions;
    }


    private $account;
    private $consumerKey = '';
    private $consumerSecret = '';
    private $accessKey = '';
    private $accessSecret = '';

    private function getLists()
    {
        if (!$this->isConntected())
        {
            return 'error';
        }

        try
        {
            $account = $this->account();

            $lists = $account->lists;

            $options = array();

            $options[ "" ] = _ncore( '(Please select ...)' );

            foreach ($lists as $one)
            {
                $options[ $one->id ] = $one->name;
            }

            $this->api->load->helper( 'array' );
            return ncore_sortOptions( $options );
        }

        catch (Exception $e)
        {
            return 'error';
        }
    }

    private function isConntected()
    {
        try
        {
            if (!$this->authtoken())
            {
                return false;
            }

            $this->account();

            return true;
        }

        catch (Exception $e)
        {
            return false;
        }
    }


    private function account()
    {
        if (isset($this->account))
        {
            if (!$this->account)
            {
                throw new Exception( $this->unauthMsg() );
            }

            return $this->account;
        }

        try
        {
            $this->account = false;

            require_once 'helper/aweber_api/aweber_api.php';

            $this->consumerKey = $this->config( 'consumerKey' );
            $this->consumerSecret = $this->config( 'consumerSecret' );
            $this->accessKey = $this->config( 'accessKey' );
            $this->accessSecret = $this->config( 'accessSecret' );

            $authorization_code = $this->authtoken();

            $used_authorization_code = $this->config( 'usedAuthCode' );

            $authentiated = $this->consumerKey != ''
                         && $authorization_code ==  $used_authorization_code;

            if (!$authentiated)
            {
                if (!$authorization_code)
                {
                    throw new Exception( $this->unauthMsg() );
                }

                $auth = AWeberAPI::getDataFromAweberID($authorization_code);
                if (!$auth)
                {
                    throw new Exception( $this->unauthMsg() );
                }

                list($this->consumerKey, $this->consumerSecret, $this->accessKey, $this->accessSecret) = $auth;

                $this->setConfig( 'consumerKey', $this->consumerKey );
                $this->setConfig( 'consumerSecret', $this->consumerSecret );
                $this->setConfig( 'accessKey', $this->accessKey );
                $this->setConfig( 'accessSecret', $this->accessSecret );
                $this->setConfig( 'usedAuthCode', $authorization_code );
            }

            $aweber_api = new AWeberAPI( $this->consumerKey, $this->consumerSecret );

            $this->account = $aweber_api->getAccount( $this->accessKey, $this->accessSecret );

            if (!$this->account)
            {
                throw new Exception( $this->unauthMsg() );
            }

            return $this->account;
        }

        catch (AWeberAPIException $e)
        {
            $is_unauth_error = $e->type == 'UnauthorizedError';

            $msg = $is_unauth_error
                 ? $this->unauthMsg()
                 : $e->message;

            throw new Exception( $msg );
        }
    }


    private function unauthMsg()
    {
        return _digi3( 'The AWeber Authoration is invalid. Please get an new AWeber authorization code.' );
    }

    private function aweberAuthTokenUrl()
    {
        return 'https://auth.aweber.com/1.0/oauth/authorize_app/'.self::aweber_app_id;
    }

    private function authtoken()
    {
        return trim( $this->data( 'authtoken' ) );
    }

}