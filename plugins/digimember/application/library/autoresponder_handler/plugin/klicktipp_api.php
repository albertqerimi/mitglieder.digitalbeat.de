<?php

require_once dirname(__FILE__) . '/with_tags_interface.php';

class digimember_AutoresponderHandler_PluginKlicktippApi extends digimember_AutoresponderHandler_PluginWithTags
{
    public function getTagOptions()
    {
        $this->maybeLoadKlicktippData();
        return $this->klicktipp_tags;
    }

    public function setTags( $email, $add_tag_ids_comma_seperated, $remove_tag_ids_comma_seperated )
    {
        $add_tag_ids = explode( ',', $add_tag_ids_comma_seperated );
        $rem_tag_ids = explode( ',', $remove_tag_ids_comma_seperated );

        $add_tag_ids = array_diff( $add_tag_ids, $rem_tag_ids, array( '', 0 ) );

        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            return false;
        }
        $subscriber_id = $klicktipp->subscriber_search( $email );;
        $klicktipp->get_last_error();

        if (!$subscriber_id)
        {
            return false;
        }

        foreach ($add_tag_ids as $tag_id)
        {
            $tag_id = ncore_washInt( $tag_id );
            if ($tag_id>0)
            {
                $klicktipp->tag( $email, $tag_id );
            }
        }

        foreach ($rem_tag_ids as $tag_id)
        {
            $tag_id = ncore_washInt( $tag_id );
            if ($tag_id>0)
            {
                $klicktipp->untag( $email, $tag_id );
            }
        }

        return true;
    }

    public function createTag( $new_tag_name )
    {
        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            throw $e;
        }

        $id = $klicktipp->tag_create( $new_tag_name );
        if (!$id)
        {
            throw new Exception( _ncore( 'Failed to create new tag %s', "'$new_tag_name'" ) );
        }

        if (isset($this->klicktipp_tags) && $id>0)
        {
            $this->klicktipp_tags[$id] = $new_tag_name;
        }

        $this->api->load->helper( 'array' );
        $this->klicktipp_tags = ncore_sortOptions( $this->klicktipp_tags );
        return $id;
    }

    public function hasUnsubscribe() {
        return true;
    }

    public function unsubscribe( $email )
    {
        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            return;
        }

        $subscriber_id = $klicktipp->subscriber_search( $email );

        if ($subscriber_id) {
            $klicktipp->subscriber_delete( $subscriber_id );
        }

        $this->KlicktippDisconnect();
    }

    public function getPersonalData( $email )
    {
        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            return array();
        }
        $subscriber_id = $klicktipp->subscriber_search( $email );

        if (!$subscriber_id) {
            return array();
        }

        $contact = $klicktipp->subscriber_get( $subscriber_id );

        return $contact;
    }

    public function subscribe( $recipient_email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        $list_id = (int) $this->data( 'list_id' );

        $add_tag_ids = explode( ',', $this->data( 'tag_id' ) );
        $rem_tag_ids = explode( ',', $this->data( 'remove_tag_id' ) );

        $add_tag_ids = array_diff( $add_tag_ids, $rem_tag_ids, array( '', 0 ) );

        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            throw $e;
        }

        $subscriber_id = $klicktipp->subscriber_search( $recipient_email );
        $klicktipp->get_last_error();

        if (!$subscriber_id || $list_id)
        {
            $first_tag = is_array($add_tag_ids) && isset($add_tag_ids[0]) ? (int) @$add_tag_ids[0] : 0;
            unset( $add_tag_ids[0] );

            $subscriber = $klicktipp->subscribe( $recipient_email, $list_id, $first_tag, $custom_fields );

            $error = $klicktipp->get_last_error();
            if ($error) {

                $need_klick_tipp_premium = stripos( $error, 'api access denied' ) !== false;

                if ($need_klick_tipp_premium)
                {
                    $error = _digi3( 'You need KlickTipp Premium or higher for %s (Technical info: %s).', _digi3( 'KlickTipp (full integration)' ), $error );
                }

                throw new Exception( $error );
            }

            if ($subscriber && is_object($subscriber)) {
                $subscriber_id = $subscriber->id;
                $custom_fields = array();
            }
        }

        if ($subscriber_id && $custom_fields)
        {
            if (is_array($custom_fields)) {
                foreach ($custom_fields as $k => $v)
                {
                    if (!$v) {
                        unset( $custom_fields[$k] );
                    }
                }
            }
            else
            {
                $custom_fields = false;
            }

            if ($custom_fields) {
                $success = $klicktipp->subscriber_update( $subscriber_id, $custom_fields );
            }
        }

        foreach ($add_tag_ids as $tag_id)
        {
            $tag_id = ncore_washInt( $tag_id );
            if ($tag_id>0)
            {
                $klicktipp->tag( $recipient_email, $tag_id );
            }
        }

        foreach ($rem_tag_ids as $tag_id)
        {
            $tag_id = ncore_washInt( $tag_id );
            if ($tag_id>0)
            {
                $klicktipp->untag( $recipient_email, $tag_id );
            }
        }

        if (!$this->is_handling_autojoin) {
            $this->KlicktippDisconnect();
        }
    }

    public function fillCustomFields( $order_data )
    {
        $forbidden_chars = $this->forbiddenCharactersInCustomFieldNames();

        /** @var digimember_BlogConfigLogic $config */
        $config  = $this->api->load->model( 'logic/blog_config' );

        $user_id = ncore_retrieve( $order_data, 'user_id' );
        $user    = ncore_getUserById( $user_id );

        $dynamic_custom_field_data = array();
        if ($user)
        {
            /** @var digimember_UserData $pwstore */
            $pwstore = $this->api->load->model( 'data/user' );
            /** @var digimember_LoginkeyData $lkstore */
            $lkstore = $this->api->load->model( 'data/loginkey' );

            $password   = $pwstore->getPassword( $user_id , '('._ncore( 'Your password' ).')' );
            $loginkey   = $lkstore->getForUser( $user_id );
            $user_login = ncore_retrieve( $user, 'user_login' );
        }
        else
        {
            $password   = '';
            $loginkey   = '';
            $user_login = '';
        }

        $map = array(
            'field_first_name' => ncore_retrieve( $order_data, 'first_name' ),
            'field_last_name'  => ncore_retrieve( $order_data, 'last_name' ),
            'field_date'       => ncore_unixDate(),
            'field_order_id'   => ncore_retrieve( $order_data, 'order_id' ),
            'field_login'      => $user_login,
            'field_password'   => $password,
            'field_loginurl'   => $config->loginUrl(),
            'field_loginkey'   => $loginkey,
        );

        $custom_fields = array();

        foreach ($map as $fieldkey => $value)
        {
            $fieldname = $this->data( $fieldkey );

            if ($forbidden_chars) {
                $fieldname = str_replace( $forbidden_chars, '', $fieldname );
            }

            if (!$fieldname || $fieldname==='NULL')
            {
                continue;
            }

            $custom_fields[ $fieldname ] = $value;
        }
        $custom_fields = array_merge($custom_fields, $dynamic_custom_field_data);

        return $custom_fields;
    }

    public function formMetas()
    {
        $invalid_access_data_msg = _digi3( 'Enter your %s credentials and save.', 'KlickTipp' );

        $metas = array();

        $metas[] =  array(
                'name' => 'username',
                'type' => 'text',
                'label' => _digi3( 'KlickTipp username' ),
                'rules' => 'required',
            );

        $metas[] =  array(
                'name' => 'password',
                'type' => 'password',
                'label' => _digi3( 'KlickTipp password' ),
                'rules' => 'required',
        );

        $list_options = $this->getListOptions();
        if ($list_options)
        {
            $metas[] = array(
                        'name' => 'list_id',
                        'type' => 'select',
                        'options' => $list_options,
                        'label' => _digi3( 'KlickTipp double-opt-in process' ),
            );
        }

        $metas[] = array(
                    'name' => 'tag_id',
                    'type' => 'autoresponder_tag_list_select',
                    'label' => _digi3( 'Add tags' ),
                    'msg_connect_error' => $invalid_access_data_msg,
                    'rules' => '',
                    'seperator' => '<br />',
                    'autoresponder' => $this->id(),
                    'size' => 5,

        );
        $metas[] = array(
                    'name' => 'remove_tag_id',
                    'type' => 'autoresponder_tag_list_select',
                    'label' => _digi3( 'Remove tags' ),
                    'msg_connect_error' => $invalid_access_data_msg,
                    'rules' => '',
                    'seperator' => '<br />',
                    'autoresponder' => $this->id(),
        );

        return $metas;
    }


    protected function forbiddenCharactersInCustomFieldNames()
    {
        $forbidden_chars = array( '%' );

        return $forbidden_chars;
    }

    protected function customFieldMetas()
    {
        $invalid_access_data_msg = _digi3('Enter your %s credentials and save.', 'KlickTipp');
        $must_create_fields_msg  = _digi3( 'In KlickTipp, select <strong>ContactCloud - New Field</strong> and create custom fields.' );

        $original_metas = parent::customFieldMetas();

        $field_options = $this->getFieldOptions();

        $defaults = array(
            'field_first_name' => 'fieldFirstName',
            'field_last_name'  => 'fieldLastName',
        );

        $my_metas = array();

        foreach ($original_metas as $meta)
        {
            $name = ncore_retrieve( $meta, 'name' );
            $default = ncore_retrieve( $defaults, $name );

            if (empty($name) || $meta['type'] == 'html')
            {
                // empty
            }
            elseif ($default)
            {
                $meta['type']  = 'hidden';
                $meta['value'] = $default;
                $placeholder = $this->customFieldFormat( $default );
                $meta['hint']  = _digi3( 'Placeholder in %s mails: %s', $this->textLabel(), "<tt>$placeholder</tt>" );
                $meta['must_save_css'] = 'klicktipp_hint';
            }
            elseif ($field_options)
            {
                $meta[ 'type' ] = 'select';
                $meta[ 'options' ] = $field_options;
                $meta[ 'invalid_label' ] = _digi3( 'Invalid field name: %s', '[VALUE]' );

                if (!empty($meta['name']) && empty($meta[ 'default' ]))
                {
                    $meta[ 'default' ] = ncore_retrieve( $defaults, $meta['name'] );
                    if ($meta[ 'default' ]) {
                        $meta[ 'rules' ] = 'readonly';
                    }
                }
            }
            else
            {
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
            'html' => '<div class="dm-form-instructions">'._ncore('In addition to the fixed fields above, the %s can also be synchronized with %s. This means that the custom fields can also be used in e-mails in %s via the drop-down list placeholders.', $customfields_link, 'KlickTipp', 'KlickTipp').'</div>',
        )));
        if (count($metas) < 3) {

            if (!$this->couldConnect) {
                $hint = _ncore('To use the %s with the fields in %s, please first enter your access data and save it.', $customfields_link, 'KlickTipp');
            }
            else {
                $hint = _digi3( 'In KlickTipp, select <strong>ContactCloud - New Field</strong> and create custom fields.' );
            }
            $meta['type'] = 'html';
            $meta['label'] = 'none';
            $meta['html'] = $hint;
            $metas[] = $meta;
        }
        return $metas;
    }

    protected function customFieldDefaultNames()
    {
        return array();
    }


    public function instructions()
    {
        $model = $this->api->load->model( 'logic/link' );
        $info_url  = $model->productInfoUrl( 'klicktipp', 'info' );
        $order_url = $model->productInfoUrl( 'klicktipp', 'order' );

        return array(
            ncore_linkReplace( _digi3('<a>KlickTipp</a> provides the best integration with %s.', $this->api->pluginDisplayName()), $info_url ),
            ncore_linkReplace( _digi3('To get your KlickTipp-Account <a>click here</a>.'), $order_url),
            ncore_linkReplace( _digi3( 'You need access to the KlickTipp API. This is provided with <a>KlickTipp Premium</a> and above.'),$order_url  ),
            ncore_linkReplace( _digi3('Enter your <a>KlickTipp</a> credentials below. Then save.'), $info_url ),
            _digi3('Select which tags to add or remove below.' ),
            _digi3('Optional: Select custom field names at the bottom.' ),
        );
    }

    protected function hasCustomFields()
    {
        return true;
    }

    protected function hasDynamicCustomFields() {
        return true;
    }

    protected function customFieldMetaHeadline()
    {
        return  _digi3( '%s custom field names', 'KlickTipp' );
    }

    protected function customFieldFormat( $placeholder_name )
    {
        return '%Subscriber:Custom' . ucfirst($placeholder_name) . '%';
    }

    protected function textLabel()
    {
        return 'KlickTipp';
    }

    protected function customFieldInstructions()
    {
        return _digi3( 'In KlickTipp, you may extend your address book and add additional fields (in KlickTipp select <em>ConctactCloud - New address field</em>). Below you find a list of fields [PLUGIN] can send to KlickTipp. Later, if you create an email with KlickTipp, you may select these fields from the dropdown list <em>placeholders</em>.' );
    }

    protected function hasAutojoin()
    {
        return true;
    }

    public function retrieveAutojoinContactData()
    {
        // http://dmtst.de/?SubscriberID=2422162&email=test%40digitest24.de&listid=39736&hk=189a539c74ce178333cbd8ca6cec709a

        $this->is_handling_autojoin = true;

        $subscriber_id = ncore_retrieve( $_GET, 'SubscriberID' );
        $given_email   = ncore_retrieve( $_GET, 'email' );

        if (!$subscriber_id || !$given_email){
            throw new Exception( _digi3('GET parameters on thankyou page are missing. This is not a visit of a new contact. The required GET parameters are: %s', 'SubscriberID, email' ) );
        }

        try {
        $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            throw $e;
        }
        if (!$klicktipp) {
            throw new Exception( 'Invalid %s username or password.', $this->textLabel() );
        }

        $subscriber = $klicktipp->subscriber_get($subscriber_id);
        if (!$subscriber) {
            throw new Exception( _digi3('invalid GET params on thankyou page - invalid SubscriberID. Someone has probably manipulated the GET params.') );
        }

        $email     = $subscriber->email;
        $firstname = $subscriber->fieldFirstName;
        $lastname  = $subscriber->fieldLastName;

        if ($email != $given_email) {
            throw new Exception( _digi3('invalid GET params on thankyou page - email does not match SubscriberID. Someone has probably manipulated the GET params.') );
        }

        $field_password = $this->data( 'field_password' );
        $field_loginkey = $this->data( 'field_loginkey' );

        $password = $field_password
                  ? ncore_retrieve( $subscriber, $field_password, false )
                  : false;

        $loginkey = $field_loginkey
                  ? ncore_retrieve( $subscriber, $field_loginkey, false )
                  : false;

        return array( $subscriber_id, $email, $firstname, $lastname, $password, $loginkey );
    }

    public function setAutojoinLoginData( $subscriber_id, $username, $password, $login_url, $loginkey  ) {

        $field_login    = $this->data( 'field_login' );
        $field_password = $this->data( 'field_password' );
        $field_loginurl = $this->data( 'field_loginurl' );
        $field_loginkey = $this->data( 'field_loginkey' );

        $data = array();

        if ($field_login && $username)
        {
            $data[ $field_login ] = $username;
        }

        if ($field_password && $password)
        {
            $data[ $field_password ] = $password;
        }

        if ($field_loginurl && $login_url)
        {
            $data[ $field_loginurl ] = $login_url;
        }

        if ($field_loginkey && $loginkey)
        {
            $data[ $field_loginkey ] = $loginkey;
        }

        $this->is_handling_autojoin = false;


        if (!$data) {
            $this->KlicktippDisconnect();
            return;
        }

        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            throw $e;
        }
        if (!$klicktipp) {
            throw new Exception( 'Invalid KlickTipp username or password.' );
        }

        if (!$subscriber_id) {
            throw new Exception( 'Internal error - should have subscriber_id here!' );
        }

        $result = $klicktipp->subscriber_update( $subscriber_id, $data );
        if (!$result) {
            $error_msg = $klicktipp->get_last_error();
            throw new Exception( $error_msg );
        }

        $this->KlicktippDisconnect();
    }


    private $klicktipp = false;
    private $is_handling_autojoin = false;
    private $couldConnect = false;
    private function KlicktippConnect()
    {
        if (!$this->klicktipp)
        {
            $success = false;

            $username = $this->data( 'username' );
            $password = $this->data( 'password' );

            if ($username && $password) {
                require_once 'helper/klicktipp.api.inc.php';
                $this->klicktipp = new Digimember_KlicktippConnector();
                $success = $this->klicktipp->login( $username, $password );

                if (!$success) {
                    $password = stripslashes( $password );
                    $success = $this->klicktipp->login( $username, $password );
                }

                if (!$success) {
                    $this->klicktipp = false;
                    throw new Exception( _digi3( 'Failed to connect to KlickTipp account %s. Are the KlickTipp username and password correct?', "<em>$username</em>" ) );
                }
            }

            if (!$success) {
                $this->klicktipp = false;
            }
        }

        return $this->klicktipp;
    }

    private function KlicktippDisconnect()
    {
        if ($this->klicktipp) {
            $this->klicktipp->logout();
            $this->klicktipp = false;
        }
    }

    private $klicktipp_tags;
    private $klicktipp_fields;
    private $klicktipp_lists;

    private function maybeLoadKlicktippData()
    {
        try
        {
            if (isset($this->klicktipp_tags)) {
                return;
            }

            $this->klicktipp_tags   = false;
            $this->klicktipp_fields = false;
            $this->klicktipp_fields = false;

            try {
                $klicktipp = $this->KlicktippConnect();
            }
            catch (Exception $e){
                return;
            }
                if (!$klicktipp) {
                    return;
                }

            $this->klicktipp_tags   = $klicktipp->tag_index();
            $this->klicktipp_fields = $klicktipp->field_index();
            $this->klicktipp_lists  = $klicktipp->subscription_process_index();

            $this->KlicktippDisconnect();

            if ($this->klicktipp_fields)
            {
                $this->klicktipp_fields = $this->sanitizeKlicktippFields( $this->klicktipp_fields ) ;
            }

            $this->KlicktippDisconnect();

            $this->api->load->helper( 'array' );
            $this->klicktipp_tags = ncore_sortOptions( $this->klicktipp_tags );
        }
        catch (Exception $e) {
            return;
        }
    }

    private function getListOptions()
    {
        $this->maybeLoadKlicktippData();
        return $this->klicktipp_lists;
    }

    private function getFieldOptions()
    {
        $this->maybeLoadKlicktippData();
        return $this->klicktipp_fields;
    }

    private function sanitizeKlicktippFields( $fields )
    {
        $sanitized =  array( 'NULL' => _ncore('No linking') );

        $has_user_field = false;
        foreach ($fields as $name => $label)
        {
            $basename = substr( $name, 5 );
            $is_user_field = is_numeric( $basename )
                             && strlen( $name ) >= 6
                             && $name[0] == 'f'
                             && $name[1] == 'i'
                             && $name[2] == 'e'
                             && $name[3] == 'l'
                             && $name[4] == 'd';

            if (!$is_user_field)
            {
                // No predefined field machtes the meaning any of the login data, so skip them
                continue;
            }

            $sanitized[ $name ] = $label;

        }

        $have_fields = count($sanitized)>=2;
        return $have_fields
               ? $sanitized
               : array();
    }

    public function getAttributes() {
        $cleanAttributes = array();
        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            return array();
        }
        if (!$klicktipp) {
            return array();
        }
        $this->couldConnect = true;
        $attributesList = $klicktipp->field_index();
        if ($attributesList) {
            $attributesList = $this->sanitizeKlicktippFields( $attributesList ) ;
        }
        $this->KlicktippDisconnect();
        if (is_array($attributesList)) {
            foreach ($attributesList as $key => $attribute) {
                $cleanAttributes[$key]['label'] = $attribute;
                $cleanAttributes[$key]['tag'] = $key;
            }
            return $cleanAttributes;
        }
        return array();
    }

    public function updateSubscriber ($user_id, $data) {
        $userData = ncore_getUserById($user_id);
        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            return false;
        }
        $subscriber_id = $klicktipp->subscriber_search( $userData->user_email );
        if ($subscriber_id) {
            $updated = $klicktipp->subscriber_update( $subscriber_id, $data );
            $this->KlicktippDisconnect();
            return $updated;
        }
        $this->KlicktippDisconnect();
        return false;
    }

    public function updateUserName ($user_id) {
        $userData = ncore_getUserById($user_id);
        try {
            $klicktipp = $this->KlicktippConnect();
        }
        catch (Exception $e){
            return false;
        }
        $subscriber_id = $klicktipp->subscriber_search( $userData->user_email );
        if ($subscriber_id) {
            $userFirstName = get_user_meta($userData->ID, 'first_name', true);
            $userLastName = get_user_meta($userData->ID, 'last_name', true);
            $userData = array(
                "first_name" => $userFirstName,
                "last_name" => $userLastName,
            );
            $sendFields = $this->fillCustomFields($userData);
            $updated = $klicktipp->subscriber_update( $subscriber_id, $sendFields );
            $this->KlicktippDisconnect();
            return $updated;
        }
        $this->KlicktippDisconnect();
        return false;
    }
}