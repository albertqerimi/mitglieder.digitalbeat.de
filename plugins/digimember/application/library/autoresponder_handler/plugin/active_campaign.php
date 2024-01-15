<?php

//
// For autojoin add email=%email% as GET-Paremter to the thankyou page, e.g.
// https://onlinehundetraining.com/danke-fuer-die-newsletter-anmeldung?email=%email%&id=subscriberid
// important: for email use %, but not for subscriberid

require_once dirname(__FILE__) . '/with_tags_interface.php';

class digimember_AutoresponderHandler_PluginActiveCampaign extends digimember_AutoresponderHandler_PluginWithTags
{
    const AUTOJOIN_EMAIL_GET_PARAM = 'email'; //
    const AUTOJOIN_EMAIL_GET_HASH  = 'id';
    public $couldConnect = false;

    // const UNUSED_CUSTOM_TAG_EXPIRE = 864000;

    public function getTagOptions()
    {
        try {
            $tag_list = $this->_call( "tags_list" );
        } catch (Exception $e) {
            $tag_list = [];
        }


        $options = array();

        foreach ($tag_list as $one)
        {
            // $number_of_contacts_using_this_tag = $one->count;
            $options[] = $one->name;
        }

        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $user_tags = $model->get( $this->_config_store_taglist_key() );

        if ($user_tags) {
            $user_tags = @unserialize( $user_tags );
        }

        if ($user_tags) {

            // $expire = time() - self::UNUSED_CUSTOM_TAG_EXPIRE;

            foreach ($user_tags as $one => $time)
            {
                //  $is_too_old = $time < $expire;
                $is_new     = !in_array( $one, $options);
                if ($is_new) { // && !$is_too_old) {
                    $options[] = $one;
                }
            }
        }

        sort( $options );

        $options = array_combine( $options, $options );

        return $options;
    }

    public function setTags( $email, $add_tag_ids_comma_seperated, $remove_tag_ids_comma_seperated )
    {
        $add_tag_ids = $this->_sanitize_tag_list( $add_tag_ids_comma_seperated );
        $rem_tag_ids = $this->_sanitize_tag_list( $remove_tag_ids_comma_seperated );

        $add_tag_ids = array_diff( $add_tag_ids, $rem_tag_ids, array( '', 0 ) );

        try
        {

            if ($add_tag_ids) {
                $post = array();
                $post[ 'email' ] = $email;
                $post[ 'tags' ]  = $add_tag_ids;

                $this->_call( 'contact_tag_add', $post );
            }

            if ($rem_tag_ids) {
                $post = array();
                $post[ 'email' ] = $email;
                $post[ 'tags' ]  = $rem_tag_ids;

                $this->_call( 'contact_tag_remove', $post );
            }
        }
        catch (Exception $e)
        {
            $message = _digi3( 'Error when setting tags for contact %s of %s: %s.', $email, $this->label(), $e->getMessage() );
            $this->api->logError( 'ipn', $message );
            return false;
        }


        return true;
    }

    public function createTag( $new_tag_name )
    {
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );

        $user_tags = $model->get( $this->_config_store_taglist_key() );
        if ($user_tags) {
            $user_tags = @unserialize( $user_tags );
        }

        $is_valid = (bool) $user_tags && is_array($user_tags);
        if (!$is_valid) {
            $user_tags = array();
        }

        $must_add = empty( $user_tags[ $new_tag_name ] );
        if ($must_add) {
            $user_tags[ $new_tag_name ] = time();
        }

        $model->set( $this->_config_store_taglist_key(), serialize($user_tags) );

        return $new_tag_name;
    }


    public function unsubscribe( $email )
    {
        $params = array(
            'filters[email]' => $email
        );

        try {
            $list = $this->_call("contact_list", $params );
        } catch (Exception $e) {
            $this->api->logError( 'ipn', $e->getMessage() );
            $list = false;
        }
        if ($list) {
            foreach ($list as $one)
            {
                $params = array( 'id' => $one->id );
                try {
                    $this->_call("contact_delete", $params );
                } catch (Exception $e) {
                    $this->api->logError( 'ipn', $e->getMessage() );
                    return false;
                }
            }
        }
    }

    public function getPersonalData( $email )
    {
        $params = array(
            'filters[email]' => $email
        );
        try {
            $list = $this->_call("contact_list", $params );
        } catch (Exception $e) {
            $this->api->logError( 'ipn', $e->getMessage() );
            $list = array();
        }
        return $list;
    }


    public function hasUnsubscribe() {
        return true;
    }

    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        if (!$this->_is_setup()) {
            throw new Exception( _digi( '%s API credentials missing', $this->textLabel() ) );
        }

        $list_id = $this->_get_list_id();
        if (!$list_id)
        {
            throw new Exception( _digi3( 'No %s List selected.', $this->textLabel() ) );
        }

        $contact = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
        );

        $valid_fields = $this->valid_custom_fields();

        foreach ($custom_fields as $raw_field => $value)
        {
            $field = $this->sanitize_field_name( $raw_field );

            $is_valid = in_array( $field, $valid_fields );
            if ($is_valid)
            {
                $name = 'field[%' . $field . '%,0]';
                $contact[ $name ] = $value;
            }
        }

        $contact[ "p[$list_id]" ]      = $list_id;
        $contact[ "status[$list_id]" ] = 1;

        try
        {
            $this->_call("contact_sync", $contact);
            $tags_to_add    = $this->data( 'add_tags' );
            $tags_to_remove = $this->data( 'remove_tags' );
            $jobs = array(
                'contact_tag_add'    => $tags_to_add,
                'contact_tag_remove' => $tags_to_remove,
            );
            foreach ($jobs as $method => $raw_tag_list)
            {
                $tag_list = $this->_sanitize_tag_list( $raw_tag_list );
                if (!$tag_list) {
                    continue;
                }

                $post = array();
                $post[ 'email' ] = $email;
                $post[ 'tags' ]  = $tag_list;
                $this->_call( $method, $post );
            }


            $automation_id_to_add    = $this->data( 'add_automation_id' );
            $automation_id_to_remove = $this->data( 'remove_automation_id' );

            $jobs = array(
                'automation_contact_add'    => $automation_id_to_add,
                'automation_contact_remove' => $automation_id_to_remove,
            );
            foreach ($jobs as $method => $automation_id)
            {
                $automation_id = ncore_washInt( $automation_id );
                if (!$automation_id) {
                    continue;
                }

                $post = array();
                $post[ 'contact_email' ] = $email;
                $post[ 'automation' ]    = $automation_id;

                $this->_call( $method, $post );
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }

    }


    public function formMetas()
    {
        $metas = array();

        $metas[] = array(
            'name' => 'activecampaign_api_url',
            'type' => 'text',
            'label' => _digi3('API URL', $this->textLabel() ),
            'rules' => 'required|trim',
            'class' => 'ncore_code',
            'size'  => 32,
        );

        $metas[] = array(
            'name' => 'activecampaign_api_key',
            'type' => 'text',
            'label' => _digi3('API key', $this->textLabel() ),
            'rules' => 'required|trim',
            'hint'  => _digi3('E.g. %s', 'hhtWoe208cfX2kUGTDCY3NDwNl3PECtvBhhtWoe208cfX2kUGTDCY3NDwNl3PECtvBhhtWoe208cfX2kUGTDCY3NDwNl3PECtvB' ),
            'size'  => 90,
        );


        $list_options = $this->_get_lists();
        $list_error = !is_array( $list_options );
        $have_lists = $list_options && !$list_error;

        if ($have_lists)
        {
            $metas[] = array(
                'name' => 'list_id',
                'type' => 'select',
                'options' => $list_options,
                'label' => _digi3('List', $this->textLabel() ),
                'rules' => 'required|trim',
            );
        }
        else
        {
            $metas[] = array(
                'name' => 'list_id',
                'type' => 'hidden',
            );

            if ($list_options == 'no_api_key')
            {
                $msg = _digi3( 'Enter your %1$s API key, save and then pick a %1$s list here.', $this->textLabel() );
            }
            elseif ($list_error)
            {
                $msg = $list_options;
            }
            else
            {
                $msg = _digi3( 'Please log into your %s account and create a list.', $this->textLabel() );
            }

            $css = '';

            $show_error = $list_error && $this->_is_setup();

            if ($show_error)
            {
                $css = 'ncore_form_cell_error_message';
                $msg = _digi3( 'The API key is invalid.' );
            }

            $metas[] = array(
                'label' => _digi3('%s list', $this->textLabel() ),
                'type' => 'html',
                'html' => $msg,
                'css'  => $css,
            );
        }

        $metas[] = array(
            'label' => _digi3('Add tags' ),
            'name'  => 'add_tags',
            'type'  => 'text',
            'size'  => 100,
            'hint'  => _digi3('Separate multiple tags by commas' ),
        );

        $metas[] = array(
            'label' => _digi3('Remove tags' ),
            'name'  => 'remove_tags',
            'type'  => 'text',
            'size'  => 100,
            'hint'  => _digi3('Separate multiple tags by commas' ),
        );

        $automation_options = $this->_get_automations();
        $automation_error = !is_array( $automation_options );
        $have_automations = !$automation_error && count($automation_options)>=2;
        if ($have_automations)
        {
            $metas[] = array(
                'name' => 'add_automation_id',
                'type' => 'select',
                'options' => $automation_options,
                'label' => _digi3('Add automation' ),
                'rules' => 'trim',
                'allow_null' => true,
            );
            $metas[] = array(
                'name' => 'remove_automation_id',
                'type' => 'select',
                'options' => $automation_options,
                'label' => _digi3('Remove automation' ),
                'rules' => 'trim',
                'allow_null' => true,
            );
        }
        else
        {
            $metas[] = array(
                'name' => 'add_automation_id',
                'type' => 'hidden',
            );
            $metas[] = array(
                'name' => 'remove_automation_id',
                'type' => 'hidden',
            );

            $msg = !$automation_error && $this->_is_setup()
                ? _digi3( 'You don\'t have any automations in %s.', $this->textLabel() )
                : '';


            $metas[] = array(
                'label' => _digi3('%s automation', $this->textLabel() ),
                'type'  => 'html',
                'html'  => $msg,
            );
        }

        return $metas ;

    }

    protected function autoJoinMetas()
    {
        $metas = parent::autoJoinMetas();

        $params = '<strong>?email=%email%&id=subscriberid</strong>';
        $url = ncore_addArgs( ncore_siteUrl( '/thankyou' ), array( 'email' => '%email%', 'id' => 'subscriberid' ), $arg_sep='&', $url_encode=false );

        $example_url = "<a href='$url' target='_blank' onclick='return false;'>$url</a>";

        $find = array( '[ARNAME]',        '[GETARGS]', '[EXAMPLE_URL]' );
        $repl = array( $this->textLabel(), $params,     $example_url );


        $msg = _digi3( 'In [ARNAME] you need to append the get parameter [GETARGS] to the thankyou url, e.g. [EXAMPLE_URL].' );

        $metas[] = array(
            'type' => 'html',
            'label' => 'none',
            'html' => str_replace( $find, $repl, $msg ),
        );

        return $metas;
    }

    protected function hasAutojoin()
    {
        return true;
    }

    private $is_handling_autojoin;
    public function retrieveAutojoinContactData()
    {
        // http://dmtst.de/?SubscriberID=2422162&email=test%40digitest24.de&listid=39736&hk=189a539c74ce178333cbd8ca6cec709a

        $this->is_handling_autojoin = true;

        $given_email  = trim( ncore_retrieve( $_GET, self::AUTOJOIN_EMAIL_GET_PARAM ) );
        $hash         = trim( ncore_retrieve( $_GET, self::AUTOJOIN_EMAIL_GET_HASH  ) );

        if (!$given_email || !$hash) {
            throw new Exception( _digi3( 'GET parameters on thankyou page are missing. This is not a visit of a new contact. The required GET parameters are: %s',
                self::AUTOJOIN_EMAIL_GET_PARAM  . ', ' . self::AUTOJOIN_EMAIL_GET_HASH ) );
        }

        if (!is_email( $given_email )) {
            throw new Exception( _digi3('Invalid email %s for GET parameter %s given.', $given_email, self::AUTOJOIN_EMAIL_GET_PARAM ) );
        }

        $is_setup = $this->_is_setup();
        if (!$is_setup) {
            throw new Exception( _digi( '%s API credentials missing', $this->textLabel() ) );
        }

        $search = array(
            'filters[email]' => $given_email,
        );

        try {
            $contacts = $this->_call("contact_list", $search);
        } catch (Exception $e) {
            $contacts = [];
        }
        if (empty($contacts)) {
            $contacts = array();
        }

        $found_contact = false;
        foreach ($contacts as $contact)
        {
            $is_hash_valid = $hash == $contact->hash;
            if (!$is_hash_valid) {
                continue;
            }

            $contact_lists = (array) ncore_retrieve( $contact, 'lists', array() );
            if (!$contact_lists) {
                continue;
            }

            foreach ($contact_lists as $one)
            {
                $is_subscribed = $one->status > 0;
                if (!$is_subscribed) {
                    continue;
                }

                $found_contact = $contact;
                break 2;
            }
        }

        if (!$found_contact) {
            throw new Exception( _digi3('invalid GET params on thankyou page - invalid email or contact too old.') );
        }

        $email          = $found_contact->email;
        $firstname      = $found_contact->first_name;
        $lastname       = $found_contact->last_name;
        $subscriber_id  = $found_contact->id;

        if ($email != $given_email) {
            throw new Exception( _digi3('invalid GET params on thankyou page - email does not match SubscriberID. Someone has probably manipulated the GET params.') );
        }

        $field_password = $this->data( 'field_password' );
        $field_loginkey = $this->data( 'field_loginkey' );

        $password = $field_password
            ? ncore_retrieve( $found_contact, $field_password, false )
            : false;

        $loginkey = $field_loginkey
            ? ncore_retrieve( $found_contact, $field_loginkey, false )
            : false;

        return array( $subscriber_id, $email, $firstname, $lastname, $password, $loginkey );
    }

    public function setAutojoinLoginData($subscriber_id, $username, $password, $login_url, $loginkey)
    {
        $field_login = $this->data('field_login');
        $field_password = $this->data('field_password');
        $field_loginurl = $this->data('field_loginurl');
        $field_loginkey = $this->data('field_loginkey');

        $data = [
            'email' => $username,
            'field' => []
        ];

        if ($field_login && $username) {
            $data['field'][$this->customFieldFormat($field_login)] = $username;
        }

        if ($field_password && $password) {
            $data['field'][$this->customFieldFormat($field_password)] = $password;
        }

        if ($field_loginurl && $login_url) {
            $data['field'][$this->customFieldFormat($field_loginurl)] = $login_url;
        }

        if ($field_loginkey && $loginkey) {
            $data['field'][$this->customFieldFormat($field_loginkey)] = $loginkey;
        }

        if (!$data) {
            return;
        }
        $data['id'] = $subscriber_id;

        if (!$subscriber_id) {
            throw new Exception('Internal error - should have subscriber_id here!');
        }

        try {
            $this->_call("contact_sync", $data);
        } catch (Exception $e) {
            return;
        }
    }

    protected function forbiddenCharactersInCustomFieldNames()
    {
        $forbidden_chars = array( '[', ']', '%', ';', '"', "'", '&' );

        return $forbidden_chars;
    }

    protected function customFieldInstructions()
    {
        return _digi3( 'In <strong>ActiveCampaign</strong> in the top menu click on <em>Lists</em>. Then click on the button <strong>Manage Fields</strong>. Add new fields of type <em>Hidden field</em>.' );
    }

    protected function customFieldFormat( $placeholder_name )
    {
        $placeholder_name = trim( $placeholder_name, '{}[]%"\' ');
        return '%' . $placeholder_name . '%';
    }

    public function instructions()
    {

        return array(
            _digi3('In <strong>ActiveCampaign</strong>, at the bottom left, click on <em>Settings</em>.'),
            _digi3('On the left menu click on <em>Developer</em>.'),
            _digi3('Locate the headline <em>API Access</em>.' ),
            _digi3('Copy and paste the URL and the Key here into this form.' ),
            _digi3('Save your changes.' ),
            _digi3('Select the %s list from the dropdown list and save again.', $this->textLabel() ),
        );
    }

    public function isActive()
    {
        return true;
    }

    protected function hasCustomFields()
    {
        return true;
    }

    protected function hasDynamicCustomFields() {
        return true;
    }

    private function _get_lists()
    {
        try
        {
            $lists = $this->_call( 'list_list', array( 'ids' => 'all' ) );

            $options = array();

            foreach ($lists as $one)
            {
                $count = $one->subscriber_count;

                $options[ $one->id ] = $count == 1
                    ? _digi3( '%s (%s subscriber)', $one->name, $count )
                    : _digi3( '%s (%s subscribers)',   $one->name, $count );

            }

            $this->api->load->helper( 'array' );
            return ncore_sortOptions( $options );

        }
        catch (Exception $e)
        {
            return [];
        }
    }


    private function _get_automations()
    {
        try
        {
            $lists = $this->_call( 'automation_list', array( 'ids' => 'all' ) );

            $options = array();

            $options[0] = '&nbsp;';

            foreach ($lists as $one)
            {
                $options[ $one->id ] = $one->name;
            }

            return $options;

        }
        catch (Exception $e)
        {
            return [];
        }
    }

    private function _call( $method, $params=array(), $return='default' )
    {
        $api_url = $this->data( 'activecampaign_api_url' );
        $api_key = $this->data( 'activecampaign_api_key' );

        if (!$api_url || !$api_key)
        {
            throw new Exception( _digi3( 'Missing API credentials for %s.', $this->textLabel() ) );
        }

        $api_url .= '/admin/api.php';


        $args = array();
        $args[ 'api_key' ]    = $api_key;
        $args[ 'api_action' ] = $method;
        $args[ 'api_output' ] = 'json';

        $url = ncore_addArgs( $api_url, $args, '&', true );

        $result = wp_remote_post( $url, array( 'body' => $params, 'timeout' => 60000 ) );

        if (is_wp_error( $result )) {
            $contents = '';
            $http_code = 0;

            throw new Exception( $result->get_error_message() );
        }

        $contents = '' . @$result[ 'body'] . '';
        $response = @$result['response'];
        $http_code = (int) @$response['code'];

        if ($http_code!=200)
        {
            throw new Exception( "Invalid http code $http_code from active campaign server $api_url for method $method" );
        }

        $oldlvl = error_reporting(0);
        $result = json_decode( $contents );
        error_reporting($oldlvl);

        if (!$result) {
            throw new Exception( "Invalid response from active campaign server $api_url for method $method" );
        }

        $this->api->load->helper( 'array' );
        $is_numeric_array = ncore_isNumericArray($result);
        $is_success = $is_numeric_array
            || ncore_retrieve( $result, 'result_code' ) == 1
            || ncore_retrieve( $result, 'result_code' ) == 0;

        if (!$is_success)
        {
            $msg = ncore_retrieve( $result, array( 'error', 'result_message' ) );
            throw new Exception( $msg ? $msg : "Error when calling $method on active campaign server." );
        }

        $this->couldConnect = true;
        switch ($return)
        {
            case 'default':
                if ($is_numeric_array) {
                    return $result;
                }

                $list = array();
                for ($i=0; !empty( $result->$i ); $i++)
                {
                    $list[] = $result->$i;
                }

                return $list;

            case 'all':
                return $result;

            default:
                return ncore_retrieve( $result, $return, false );
        }
    }


    private function _is_setup()
    {
        return trim( $this->data( 'activecampaign_api_url' ) )
            && trim( $this->data( 'activecampaign_api_key' ) );
    }

    private function sanitize_field_name( $raw_field )
    {
        return str_replace( array('/', ':', '|' ), '', strtoupper( trim(trim($raw_field),' %\t\n\r') ) );
    }

    private function valid_custom_fields()
    {
        try
        {
            $raw_fields = $this->_call( 'list_field_view', array( 'ids' => 'all' ));

            $valid_fields = array();
            foreach ($raw_fields as $one)
            {
                $field = $this->sanitize_field_name( $one->tag );

                $valid_fields[] = $field;
            }
            return $valid_fields;
        }
        catch (Exception $e)
        {
            $this->api->logError( 'ipn', $e->getMessage() );
            return array();
        }
    }

    private function _sanitize_tag_list( $raw_tag_comma_sepearted )
    {
        $tags_raw = explode( ',', $raw_tag_comma_sepearted );
        $tags = array();

        foreach ($tags_raw as $tag)
        {
            $tag = trim($tag);
            if ($tag) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    private function _get_list_id()
    {
        $list_id = $this->data( 'list_id' );
        if ($list_id) {
            return $list_id;
        }

        $list_options = $this->_get_lists();
        if (!$list_options) {
            return false;
        }

        foreach ($list_options as $list_id => $label)
        {
            if ($list_id>0) {
                return $list_id;
            }
        }

        return false;
    }

    private function _config_store_taglist_key()
    {
        return  'ar_tag_ac_'.$this->id();
    }

    public function getAttributes() {
        $cleanAttributes = array();
        $attributesList = array();
        try {
            $attributesList = $this->_call( 'list_field_view', array( 'ids' => 'all' ));
        } catch (Exception $e) {
            //$this->api->logError( 'ipn', $e->getMessage() );
            $attributesList = [];
        }

        if (is_array($attributesList)) {
            foreach ($attributesList as $key => $attribute) {
                $cleanAttributes[$key]['id'] = $attribute->id;
                $cleanAttributes[$key]['name'] = $attribute->title;
                $cleanAttributes[$key]['label'] = $attribute->title;
                $cleanAttributes[$key]['tag'] = $attribute->tag;
            }
            return $cleanAttributes;
        }
        return array();
    }
    public function updateSubscriber ($user_id, $data) {
        $userData = ncore_getUserById($user_id);
        $subscriber = $this->getPersonalData($userData->user_email);
        if (is_array($subscriber) && count($subscriber) == 1) {
            $subscriber = $subscriber[0];
            if (ncore_retrieve($subscriber, 'id', false)) {
                $contact = array(
                    'email' => $userData->user_email,
                );
                foreach ($data as $key => $value) {
                    $contact['field['.$key.',0]'] = $value;
                }
                try {
                    $result = $this->_call("contact_sync", $contact, 'all');
                } catch (Exception $e) {
                    $this->api->logError( 'ipn', $e->getMessage() );
                    return false;
                }
                if ($result instanceof stdClass && $result->result_code == 1) {
                    return true;
                }
                return false;
            }
        }

        return false;
    }

    protected function customFieldMetas()
    {
        $this->api->load->helper( 'html_input' );
        $invalid_access_data_msg = $this->invalidAccessDataMessage();
        $must_create_fields_msg = $this->customFieldInstructions();

        $original_metas = parent::customFieldMetas();
        $defaults = [
            'field_first_name' => 'first_name',
            'field_last_name' => 'family_name',
        ];

        try {
            $data = $this->_call( 'list_field_view', array( 'ids' => 'all' ));
        } catch (Exception $e) {
            //$this->api->logError( 'ipn', $e->getMessage() );
            $data = [];
        }
        $data = $data ? $data : [];
        /** @var CustomField[] $data */
        $field_options = [];
        foreach ($data as $customField) {
            $field_options[$customField->tag] = $customField->title;
        }

        if (count($field_options) > 0) {
            $field_options = array_merge(array( 0 => _ncore('No linking')), $field_options);
        }

        $my_metas = [];

        foreach ($original_metas as $meta) {
            $name = ncore_retrieve($meta, 'name');
            $default = ncore_retrieve($defaults, $name);

            if (empty($name) || $meta['type'] == 'html') {
                // empty
            } else if ($default) {
                $meta['type'] = 'hidden';
                $meta['value'] = $default;
                $placeholder = $this->customFieldFormat($default);
                $meta['hint'] = _digi3('Placeholder in %s mails: %s', $this->textLabel(), "<tt>$placeholder</tt>");
                $meta['must_save_css'] = 'klicktipp_hint';
            } else if ($field_options) {
                $meta['type'] = 'select';
                $meta['options'] = $field_options;
                $meta['invalid_label'] = _digi3('Invalid field name: %s', '[VALUE]');

                if (!empty($meta['name']) && empty($meta['default'])) {
                    $meta['default'] = ncore_retrieve($defaults, $meta['name']);
                    if ($meta['default']) {
                        $meta['rules'] = 'readonly';
                    }
                }
            } else {
                $meta['type'] = 'hidden';
                $meta['hint'] = $field_options === false
                    ? $invalid_access_data_msg
                    : $must_create_fields_msg;
            }

            $my_metas[] = $meta;
        }

        return $my_metas;
    }

    public function dynamicCustomFieldMetas() {
        $linkModel = $this->api->load->model('logic/link');
        $customfields_link = $linkModel->adminMenuLink('customfields');
        $metas = parent::dynamicCustomFieldMetas();
        array_splice( $metas, 1, 0, array(array(
            'type' => 'html',
            'label' => 'none',
            'html' => '<div class="dm-form-instructions">'._ncore('In addition to the fixed fields above, the %s can also be synchronized with %s. This means that the custom fields can also be used in e-mails in %s via the drop-down list placeholders.', $customfields_link, 'ActiveCampaign', 'ActiveCampaign').'</div>',
        )));
        if (count($metas) < 3) {

            if (!$this->couldConnect) {
                $hint = _ncore('To use the %s with the fields in %s, please first enter your access data and save it.', $customfields_link, 'ActiveCampaign');
            }
            else {
                $hint = $this->customFieldInstructions();
            }
            $meta['type'] = 'html';
            $meta['label'] = 'none';
            $meta['html'] = $hint;
            $metas[] = $meta;
        }
        return $metas;
    }

    private function invalidAccessDataMessage()
    {
        static $message;
        if ($message) {
            return $message;
        }
        $result = $this->_get_lists();
        if ($result !== true) {
            $message = _digi3('Login failed. Please check your %s credentials.', $this->textLabel());
        } else {
            $message = _digi3('Enter your %s API credentials and save.', $this->textLabel());
        }

        return $message;
    }

    public function updateUserName ($user_id) {
        $userData = ncore_getUserById($user_id);
        $userFirstName = get_user_meta($userData->ID, 'first_name', true);
        $userLastName = get_user_meta($userData->ID, 'last_name', true);
        $contact = array(
            'first_name' => $userFirstName,
            'last_name'  => $userLastName,
            'email'      => $userData->user_email,
        );
        try {
            $this->_call("contact_sync", $contact);
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }
}
