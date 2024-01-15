<?php

class digimember_AutoresponderHandler_PluginCleverreach extends digimember_AutoresponderHandler_PluginBase
{
    const wsdl_url = 'http://api.cleverreach.com/soap/interface_v5.1.php?wsdl';

    public function unsubscribe( $email )
    {
    }    
    
    public function getPersonalData( $email )
    {
        return array();
    }    
    
    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        $api_key = $this->apiKey();
        $list_id = $this->data( 'list_id' );
        $use_dbl_optin = $this->data( 'use_dbl_optin', 'no' ) == 'yes' || $force_double_optin;
        $form_id = false;

        if (!$list_id)
        {
            return;
        }

        if ($use_dbl_optin)
        {
            $form_id = $this->getFormId( $list_id );

            if (!$form_id)
            {
                $lists = $this->getCampaigns();
                $list_name = ncore_retrieve( $lists, $list_id, _digi3(" Group #$list_id", $this->arName()) );

                $message = _digi3( 'No valid form for %s Group \'%s\'. Please update the Autoresponder\'s (id %s) settings.', $this->arName(), $list_name, $this->id() );
                throw new Exception( _ncore( 'Error adding %s to group %s: %s', $email, $list_id, $message ) );
            }
        }


        $client = $this->getClient();

        $defaults = array(
                 "firstname" => $first_name,
                 "lastname"  => $last_name,
                 "vorname"   => $first_name,
                 "nachname"  => $last_name,
         );

        $attributes = array();

        foreach ($custom_fields as $key => $value)
        {
            $lower_key = strtolower( $key );
            unset( $defaults[ $lower_key ] );
        }

        $find = array( '[ARNAME]',      '[PLUGIN]' );
        $repl = array( $this->arName(), $this->api->pluginDisplayName() );

        $metas[] = array(
            'type' => 'html',
            'label' => 'none',
            'html' =>   '<h3>' . _digi3( '%s Custom field names', $this->arName() ) . '</h3>'
                        . str_replace( $find, $repl, _digi3( 'In [ARNAME] you may add custom fields (select <em>Contacts</em>, then select a group and select the tabs <em>Settings</em> and then <em>fields</em>. Below you find a list of fields [PLUGIN] can send to [ARNAME]. Add the fields in [ARNAME] and make sure the names in [PLUGIN] and in [ARNAME] match.' )),
         );

        $custom_fields = array_merge( $defaults, $custom_fields );

        foreach ($custom_fields as $key => $value)
        {
            $attributes[] = array(
                'key'   => $key,
                'value' => $value,
            );
        }


        $user = array(
             "email"      => $email,
             "registered" => time(),
             "source"     => $this->api->pluginDisplayName(),
             "attributes" => $attributes,
        );

        $product_name = false;
        if ($product_id)
        {
            $model = ncore_api()->load->model( 'data/product' );
            $product = $model->get( $product_id );
            $product_name = ncore_retrieve( $product, 'name' );
        }

        if ($product_name && $order_id)
        {
            $order = array(
              'purchase_date' => time(), //order date, unix timestamp
              'order_id'      => $order_id, //unique order ID (order_id & product are the unqiue key)
              'product'       => $product_name,
              'source'        => $this->api->pluginDisplayName(),
              'product_id'    => $product_id,
            );

           $user[ "orders" ] = array( $order );
        }


        if (!$use_dbl_optin)
        {
            $user['activated'] = time();
        }

        $result = $client->receiverAdd($api_key, $list_id, $user);

        $is_success = $result->status === 'SUCCESS';
        if (!$is_success) {
            $result_update = $client->recieverUpdate($api_key, $list_id, $user);
            $is_update_successful = $result_update->status === 'SUCCESS';
            if (!$is_update_successful) {
                throw new Exception(_ncore('Error adding %s to group %s: %s', $email, $list_id, $result->message));
            }
        }

        if ($use_dbl_optin) {
            $result = $client->formsActivationMail($api_key, $form_id, $email);
        }
    }


    public function formMetas()
    {
        $metas = array();

        $metas[] = array(
                'name' => 'api_key',
                'type' => 'text',
                'label' => _digi3('%s API Key', $this->arName() ),
                'rules' => 'defaults',
                'hint'  => _digi ('E.g. 1235ca3b97b2ac44b795d871825e4f8d' ),
                'class' => 'ncore_code',
                'size'  => 32,
            );

        $list_options = $this->getCampaigns();
        $list_error = $list_options === 'error';;
        $have_lists = $list_options && !$list_error;
        if ($have_lists)
        {
            $metas[] = array(
                'name' => 'list_id',
                'type' => 'select',
                'options' => $list_options,
                'label' => _digi3('%s Recipient Group', $this->arName() ),
                'rules' => 'required|trim',
            );
        }
        else
        {
            $metas[] = array(
                'name' => 'list_id',
                'type' => 'hidden',
            );

            $msg = $list_error
                 ? _digi3( 'Enter your %1$s API key, save and then pick a %1$s group here.', $this->arName() )
                 : _digi3( 'Please log into your %s account and create a recpient group.', $this->arName() );


            $css = '';

            $show_error = $list_error && (bool) $this->apiKey();

            if ($show_error)
            {
                $css = 'ncore_form_cell_error_message';
                $msg = _digi3( 'The API key is invalid.' ) . ' ' . $msg;
            }

            $metas[] = array(
                'label' => _digi3('%s Recipient Group', $this->arName() ),
                'type' => 'html',
                'html' => $msg,
                'css'  => $css,
            );

        }

        $shortcode = 'ds_signup';

        $metas[] = array(
                'name' => 'use_dbl_optin',
                'type' => 'select',
                'options' => array( 'no' => _ncore('No' ), 'yes' => _ncore('Yes' ) ),
                'label' => _digi3('Use double-opt-in' ),
                'default' => 'no',
                'tooltip' => _digi3('This option applies only to sales (where the customer pays money).|For free sign ups (using the shortcode %s), a double-opt-in is always used.', $shortcode ),
            );

        $list_id = $this->data( 'list_id' );

        if ($list_id)
        {
            $forms = $this->getForms( $list_id );

            $have_forms = count($forms) >= 2;

            if ($have_forms)
            {
                $metas[] = array(
                    'name' => 'form_id',
                    'type' => 'select',
                    'options' => $forms,
                    'label' => _digi3('Form ID' ),
                    'depends_on' => array(  'use_dbl_optin' => 'yes' ),
                    'hint' => _digi3( 'If you change the %s group above, please save and pick another form here.', $this->arName() ),
                );
            }
            else
            {
                $metas[] = array(
                    'name' => 'form_id',
                    'type' => 'hidden',
                );

                $metas[] = array(
                    'label' => _digi3('Form ID' ),
                    'type' => 'html',
                    'html' => _digi3( '<strong>In %s</strong> please create a form for the selected recipient group. Then here in %s click <em>Save changes</em> and select the form here.', $this->arName(), $this->api->pluginDisplayName() ),
                    'depends_on' => array(  'use_dbl_optin' => 'yes' ),
                );
            }
        }
        else
        {
            $metas[] = array(
                'name' => 'form_id',
                'type' => 'hidden',
            );

            $metas[] = array(
                    'label' => _digi3('Form ID' ),
                    'type' => 'html',
                    'html' => _digi3( 'Select the %s group above and save your changes. Then select the form here.', $this->arName() ),
                    'depends_on' => array(  'use_dbl_optin' => 'yes' ),
                );
        }

        return $metas;

    }


    protected function customFieldFormat( $placeholder_name )
    {
        $placeholder_name = trim( $placeholder_name, '{}[]% ');
        return '{' . strtoupper( $placeholder_name ) . '}';
    }

    public function instructions()
    {
        return array(
            _digi3('<strong>In %s</strong> click on Account - API.', $this->arName()),
            _digi3('Click on <em>Create new API key</em>.'),
            _digi3('Optional: enter "%s" (or any other name) as a name for the API key.', $this->api->pluginDisplayName()),
            _digi3('Make sure, the <em>permissions</em> are set to <em>read/write</em>.' ),
            _digi3('Save your changes.' ),
            _digi3('Copy the API key to the clipboard.' ),
            _digi3('<strong>Here in %s</strong> paste the API key into the <em>%s API key</em> text field.', $this->api->pluginDisplayName(), $this->arName() ),
            _digi3('Save your changes.' ),
            _digi3('Select the %s group from the dropdown list and save again.', $this->arName() ),
            // _digi3('<strong>For double-opt-in only:</strong> In %s you need to create a signup form for the selected recipient group.', $this->arName()),
            );
    }

    public function isActive()
    {
        $soap_installed = extension_loaded('soap');

        return $soap_installed;
    }

    protected function hasCustomFields()
    {
        return true;
    }

    protected function hasDynamicCustomFields() {
        return true;
    }


    public function inactiveMsg()
    {
        return _digi3('The %s API needs the php extension "soap" activated for your web account. Without, a connection to %s is not possible. Please ask your web hoster to install and activate "soap" for php.', $this->arName(), $this->arName() );
    }

    protected function arName() {
        return 'CleverReach';
    }

    private $client;

    private function getCampaigns()
    {
        $client = $this->getClient();

        $api_key = $this->apiKey();

        if (!$client || !$api_key)
        {
            return 'error';
        }

        try {
            $options = array();

            $options[ "" ] = _ncore( '(Please select ...)' );

            $result = $client->groupGetList( $api_key );

            $is_success = $result->status === 'SUCCESS';
            if (!$is_success)
            {
                throw new Exception( $result->message );
            }

            $lists = $result->data;
            foreach ($lists as $one)
            {
                $id = ncore_retrieve( $one, 'id', false );
                $name = ncore_retrieve( $one, 'name', "List $id" );

                if ($id)
                {
                    $options[ $id ] = $name;
                }
            }

            $this->api->load->helper( 'array' );
            return ncore_sortOptions( $options );
        }

        catch (Exception $e) {
            return 'error';
        }
    }

    private function getForms( $campaign_id )
    {
        $client = $this->getClient();

        $api_key = $this->apiKey();

        if (!$api_key)
        {
            return 'error';
        }

        try {
            $options = array();

            $options[ "" ] = _ncore( '(Please select ...)' );

            $result = $client->formsGetList( $api_key, $campaign_id );

            $is_success = $result->status === 'SUCCESS';
            if (!$is_success)
            {
                throw new Exception( $result->message );
            }

            $lists = $result->data;
            foreach ($lists as $one)
            {
                $id = ncore_retrieve( $one, 'id', false );
                $name = ncore_retrieve( $one, 'name', "Form $id" );

                if ($id)
                {
                    $options[ $id ] = $name;
                }
            }

            $this->api->load->helper( 'array' );
            return ncore_sortOptions( $options );
        }

        catch (Exception $e) {
            return 'error';
        }
    }

    private function getClient()
    {
        if (!$this->apiKey())
        {
            return false;
        }

        if (!isset($this->client)) {
            $this->client = new SoapClient( self::wsdl_url );
        }

        return $this->client;
    }

    private function getFormId( $list_id )
    {
        $forms = $this->getForms( $list_id );
        unset( $forms[''] );
        unset( $forms[0] );

        $form_id = $this->data( 'form_id' );

        $is_valid = $form_id > 0 && isset( $forms[ $list_id ] );

        if (!$is_valid)
        {
            $form_ids = array_keys( $forms );
            $form_id = ncore_retrieve( $form_ids, 0, false );
        }

        return $form_id;
    }

    private function apiKey()
    {
        return trim( $this->data( 'api_key' ) );
    }
}
