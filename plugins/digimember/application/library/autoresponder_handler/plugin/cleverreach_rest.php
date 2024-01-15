<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use rdoepner\CleverReach\ApiManager;
use rdoepner\CleverReach\Http\Guzzle as HttpAdapter;

class digimember_AutoresponderHandler_PluginCleverreachRest extends digimember_AutoresponderHandler_PluginBase
{
    private $accessToken = false;
    private $couldConnect = false;

    private function getHttpAdapter() {
        if ($this->accessToken) {
            return new HttpAdapter(['access_token' => $this->accessToken]);
        }
        else{
            return new HttpAdapter();
        }
    }

    private function getApiManager() {
        if ($this->getAccessToken()) {
            return new ApiManager($this->getHttpAdapter());
        }
        return false;
    }

    private function getAccessToken() {
        if(!$this->accessToken) {
            $clientId = $this->clientId();
            $clientSecret = $this->clientSecret();
            if ($clientId && $clientSecret) {
                $response = $this->getHttpAdapter()->authorize($clientId, $clientSecret);
                if (array_key_exists('access_token',$response)) {
                    $this->accessToken = $response['access_token'];
                    return true;
                }
                return false;
            }
            return false;
        }
        return true;
    }

    private function clientId()
    {
        return trim( $this->data( 'client_id' ) );
    }

    private function clientSecret()
    {
        return trim( $this->data( 'client_secret' ) );
    }

    private function getCampaigns()
    {
        $apiManager = $this->getApiManager();

        if (!$apiManager || !$this->accessToken)
        {
            return 'error';
        }

        try {
            $options = array();

            $options[ "" ] = _ncore( '(Please select ...)' );

            $groupList = $apiManager->getGroups();

            foreach ($groupList as $one)
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
        $apiManager = $this->getApiManager();

        if (!$apiManager || !$this->accessToken)
        {
            return 'error';
        }

        try {
            $options = array();

            $options[ "" ] = _ncore( '(Please select ...)' );

            $result = $apiManager->getForms($campaign_id);

            foreach ($result as $one)
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

    public function getAttributes($groupId = 0) {
        $cleanAttributes = array();
        if($apiManager = $this->getApiManager()) {
            $this->couldConnect = true;
            $attributesList = $apiManager->getAttributes($groupId);
            if (is_array($attributesList)) {
                foreach ($attributesList as $key => $attribute) {
                    $cleanAttributes[$key]['id'] = $attribute['id'];
                    $cleanAttributes[$key]['group'] = $attribute['group_id'];
                    $cleanAttributes[$key]['name'] = $attribute['name'];
                    $cleanAttributes[$key]['label'] = $attribute['preview_value'];
                    $cleanAttributes[$key]['tag'] = $attribute['tag'];
                }
                return $cleanAttributes;
            }
        }
        return array();
    }

    public function unsubscribe( $email )
    {
    }

    public function getPersonalData( $email )
    {
        return array();
    }

    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
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

        $apiManager = $this->getApiManager();

        $global_attributes = array(
            "firstname" => $first_name,
            "lastname"  => $last_name,
        );

        foreach ($custom_fields as $key => $value)
        {
            $keyWithoutBrackets = str_replace('}','',str_replace('{','',$key));
            $lower_key = strtolower( $keyWithoutBrackets );
            $global_attributes[ $lower_key ] = $value;
        }

        $find = array( '[ARNAME]',      '[PLUGIN]' );
        $repl = array( $this->arName(), $this->api->pluginDisplayName() );

        $metas[] = array(
            'type' => 'html',
            'label' => 'none',
            'html' =>   '<h3>' . _digi3( '%s Custom field names', $this->arName() ) . '</h3>'
                        . str_replace( $find, $repl, _digi3( 'In [ARNAME] you may add custom fields (select <em>Contacts</em>, then select a group and select the tabs <em>Settings</em> and then <em>fields</em>. Below you find a list of fields [PLUGIN] can send to [ARNAME]. Add the fields in [ARNAME] and make sure the names in [PLUGIN] and in [ARNAME] match.' )),
         );

        $user = array(
             "email"      => $email,
             "registered" => time(),
             "source"     => $this->api->pluginDisplayName(),
             "global_attributes" => $global_attributes,
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
              'order_id'      => $order_id." ".date("d.m.Y H:i:s", time()), //unique order ID (order_id & product are the unqiue key)
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
        else {
            $user['activated'] = 0;
        }

        $result = $apiManager->createSubscriber($list_id, $user);

        if ($resultError = $this->resultHasError($result)) {
            throw new Exception(_ncore('Error adding %s to group %s: %s', $email, $list_id, $resultError));
        }

        if ($use_dbl_optin) {
            $apiManager->triggerDoubleOptInEmail($email,$form_id);
        }
    }

    public function resultHasError($result) {
        foreach ($result as $row) {
            if (array_key_exists("status", $row) && strpos($row['status'], "error")) {
                return $row['log'][0];
            }
        }
        return false;
    }

    public function formMetas()
    {
        $metas = array();

        $metas[] = array(
            'name' => 'client_id',
            'type' => 'text',
            'label' => 'CleverReach Client ID',
            'rules' => 'defaults',
            'hint'  => 'E.g. 2h6gt8zs9h',
            'class' => 'ncore_code',
            'size'  => 10,
        );

        $metas[] = array(
            'name' => 'client_secret',
            'type' => 'text',
            'label' => 'CleverReach Client Secret',
            'rules' => 'defaults',
            'hint'  => 'E.g. d7hnakuh9h36js8kz6hn0hhjz64fdzte87',
            'class' => 'ncore_code',
            'size'  => 32,
        );

        $list_options = $this->getCampaigns();

        $list_error = $list_options === 'error';
        $have_lists = is_array($list_options) && count($list_options) > 1 ? true : false;

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
                 ? _digi3( 'Enter your %1$s Client ID and Client Secret, save and then pick a %1$s group here.', $this->arName() )
                 : _digi3( 'Please log into your %s account and create a recpient group.', $this->arName() );

            $css = '';

            $show_error = $list_error && (bool) $this->clientId();
            $show_error = $list_error && (bool) $this->clientSecret();

            if ($show_error)
            {
                $css = 'ncore_form_cell_error_message';
                $msg = _digi3( 'The Client ID or the Client Secret is invalid.' ) . ' ' . $msg;
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

            $have_forms = is_array($forms) && count($forms) > 1 ? true : false;

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

    public function customFieldMetas()
    {
        $this->api->load->helper( 'html_input' );
        $find = ['[ARNAME]', '[PLUGIN]'];
        $repl = [$this->textLabel(), $this->api->pluginDisplayName()];

        $headline = str_replace($find, $repl, $this->customFieldMetaHeadline());
        $text = str_replace($find, $repl, $this->customFieldInstructions());

        $headline = '<div class="dm-formbox-headline">' . $headline . '</div>';
        $text = '<div class="dm-form-instructions">' . $text . '<br>'._digi3( 'IMPORTANT: If you want to use custom fields in Digimember, you need to create them as an “Intergroup field“.' ).'</div>';
        $attributes = $this->getAttributes();

        $options = array();
        $options[] = _ncore('No linking');
        foreach ($attributes as $attribute) {
            $options[$attribute['tag']] = $attribute['label'] != '' ? $attribute['label'].' - '.$attribute['tag'] : $attribute['tag'];
        }
        $metas = [

            [
                'type' => 'html',
                'label' => 'none',
                'html' => $headline,
            ],
            [
                'type' => 'html',
                'label' => 'none',
                'html' => $text,
            ],

            [
                'name' => 'field_first_name',
                'type' => 'select',
                'label' => _digi3('First name'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_first_name'),
                'options' => $options
            ],

            [
                'name' => 'field_last_name',
                'type' => 'select',
                'label' => _digi3('Last name'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_last_name'),
                'options' => $options
            ],

            [
                'name' => 'field_date',
                'type' => 'select',
                'label' => _digi3('Order date'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_date'),
                'options' => $options
            ],

            [
                'name' => 'field_order_id',
                'type' => 'select',
                'label' => _digi3('Order id'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_order_id'),
                'options' => $options
            ],

            [
                'name' => 'field_login',
                'type' => 'select',
                'label' => _digi3('Username'),
                'rules' => 'defaults',
                'tooltip' => _digi3('The user\'s login name for your site. Default is his email address.'),
                'hint' => $this->renderCustomFieldHint('field_login'),
                'options' => $options
            ],
            [
                'name' => 'field_password',
                'type' => 'select',
                'label' => _digi3('Password'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_password'),
                'options' => $options
            ],
            [
                'name' => 'field_loginurl',
                'type' => 'select',
                'label' => _digi3('Login URL'),
                'rules' => 'defaults',
                'tooltip' => _digi3('The URL to the web page the user visits to log into your site. This is the page containing the login form.'),
                'hint' => $this->renderCustomFieldHint('field_loginurl'),
                'options' => $options
            ],
            [
                'name' => 'field_loginkey',
                'type' => 'select',
                'label' => _digi3('Login key'),
                'tooltip' => _digi3('You may use the login key for auto login links in your email.|Add a GET parameter %s to your blogs URL int the email and set it to the value of the login key custom field.', DIGIMEMBER_LOGINKEY_GET_PARAM),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldLoginkeyHint('field_loginkey', 'ncore_form_hint'),
                'options' => $options
            ],
        ];
        if (!$this->couldConnect) {
            $invalid_access_data_msg = _digi3('Enter your %s credentials and save.', 'CleverReach');
            foreach ($metas as &$meta) {
                if ($meta['type'] != 'html') {
                    $meta['type'] = 'hidden';
                    $meta['hint'] = $invalid_access_data_msg;
                }
            }
        }
        return $metas;
    }

    public function dynamicCustomFieldMetas() {
        $linkModel = $this->api->load->model('logic/link');
        $customfields_link = $linkModel->adminMenuLink('customfields');
        $metas = parent::dynamicCustomFieldMetas();
        array_splice( $metas, 1, 0, array(array(
            'type' => 'html',
            'label' => 'none',
            'html' => '<div class="dm-form-instructions">'._ncore('In addition to the fixed fields above, the %s can also be synchronized with %s.', $customfields_link, 'CleverReach').'<br>'._ncore('In %s you can add these fields as placeholders to the e-mail texts. IMPORTANT: In order to use your own fields in DigiMember, these must be created as “cross-list fields” in %s.', $this->textLabel(), 'CleverReach').'</div>',
        )));
        if (count($metas) < 3) {

            if (!$this->couldConnect) {
                $hint = _ncore('To use the %s with the fields in %s, please first enter your access data and save it.', $customfields_link, $this->textLabel());
            }
            else {
                //TODO
                $hint = _digi3( 'In CleverReach, select <strong>ContactCloud - New Field</strong> and create custom fields.' );
            }
            $meta['type'] = 'html';
            $meta['label'] = 'none';
            $meta['html'] = $hint;
            $metas[] = $meta;
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
            _digi3('<strong>In %s</strong> click on My Account - Extras - REST API.', $this->arName()),
            _digi3('Click on <em>Create new OAuth App</em>.'),
            _digi3('Optional: enter "%s" (or any other name) as a name for the OAuth App.', $this->api->pluginDisplayName()),
            _digi3('Please use REST API Version 3.' ),
            _digi3('Make sure, the <em>Scopes</em> are set for at least <em>receivers</em> and <em>forms</em>.' ),
            _digi3('Save your changes.' ),
            _digi3('Copy the Client ID and the Client Secret into the related text fields below this description.' ),
            _digi3('Save your changes.' ),
            _digi3('Select the %s group from the dropdown list and save again.', $this->arName() ),
            );
    }

    public function isActive()
    {
        //return true because method is used by global handler. Soap extension is not needed here
        return true;
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

    /**
     * updateSubscriber
     * sets attributes given as data array for a given user_id
     * only works if there is a related subscriber and attribute fields that match at the provider
     * @param $user_id
     * @param $data
     */
    public function updateSubscriber ($user_id, $data) {
        $userData = ncore_getUserById($user_id);
        $apiManager = $this->getApiManager();
        $receiver = $apiManager->getSubscriber($userData->user_email);
        if (!ncore_retrieve($receiver,'error',false)) {
            $attributes = $this->getAttributes();
            foreach ($attributes as $attribute) {
                if (array_key_exists($attribute['tag'],$data)) {
                    $apiManager->updateSubscriberAttributes($receiver['id'], $attribute['id'], $data[$attribute['tag']]);
                }
            }
            return true;
        }
        return false;
    }

    public function updateUserName ($user_id) {
        $userData = ncore_getUserById($user_id);
        $apiManager = $this->getApiManager();
        if (!$apiManager) {
            return false;
        }
        $receiver = $apiManager->getSubscriber($userData->user_email);
        if (!ncore_retrieve($receiver,'error',false)) {
            $userFirstName = get_user_meta($userData->ID, 'first_name', true);
            $userLastName = get_user_meta($userData->ID, 'last_name', true);
            $userData = array(
                "first_name" => $userFirstName,
                "last_name" => $userLastName,
            );
            $sendFields = $this->fillCustomFields($userData);
            $attributes = $this->getAttributes();
            foreach ($attributes as $attribute) {
                if (array_key_exists($attribute['tag'],$sendFields)) {
                    $apiManager->updateSubscriberAttributes($receiver['id'], $attribute['id'], $sendFields[$attribute['tag']]);
                }
            }
        }
    }
}
