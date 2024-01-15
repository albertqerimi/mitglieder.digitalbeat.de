<?php

// https://labs.aweber.com/docs/code_samples/auth/dist_authorization

class digimember_AutoresponderHandler_PluginMailchimp extends digimember_AutoresponderHandler_PluginBase
{

    public function unsubscribe( $email )
    {
    }

    public function getPersonalData( $email )
    {
        return array();
    }

    /**
     * @param       $email
     * @param       $first_name
     * @param       $last_name
     * @param       $product_id
     * @param       $order_id
     * @param bool  $force_double_optin
     * @param array $custom_fields
     * @throws Exception
     */
    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        if (!$this->isConntected()) {
            return;
        }
        $mailchimp = $this->mailchimp();

        $use_dbl_optin = $this->data( 'use_dbl_optin', 'no' ) == 'yes' || $force_double_optin;

        $list_id = $this->listId();
        if (!$list_id)
        {
            return;
        }

        $mailchimp->listSubscribe( $list_id, $email, $first_name, $last_name, $use_dbl_optin, $custom_fields );
        if ($mailchimp->errorCode){
            throw new Exception( $mailchimp->errorMessage );
        }
    }

    public function formMetas()
    {
        $metas = array();

        $metas[] = array(
                'name' => 'apikey',
                'type' => 'text',
                'size' => 36,
                'label' => _digi3( 'MailChimp API key' ),
                'rules' => 'required|trim|remove_whitespace',
                'class' => 'ncore_code',
        );

        $shortcode = 'ncore_signup';

        $metas[] = array(
                'name' => 'use_dbl_optin',
                'type' => 'select',
                'options' => array( 'no' => _ncore('No' ), 'yes' => _ncore('Yes' ) ),
                'label' => _digi3('Use double-opt-in' ),
                'default' => 'no',
                'tooltip' => _digi3('This option applies only to sales (where the customer pays money).|For free sign ups (using the shortcode %s), a double-opt-in is always used.', $shortcode ),
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
                'label' => _digi3( 'MailChimp list' ),
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
                 ? _digi3( 'Enter a valid API key above, save and then pick a MailChimp list here.' )
                 : _digi3( 'Please log into your MailChimp account and create an email list.' );


            $css = '';

            $show_error = $list_error && (bool) $this->apiKey();

            if ($show_error)
            {
                $css = 'ncore_form_cell_error_message';
                $msg = _digi3( 'The API key is invalid.' ) . ' ' . $msg;
            }

            $metas[] = array(
                'label' => _digi3( 'MailChimp list' ),
                'type' => 'html',
                'html' => $msg,
                'css'  => $css,
            );
        }


        return $metas;
    }


    //TODO change to mailchimp
    protected function customFieldMetas()
    {
        $invalid_access_data_msg = _digi3('Enter your %s credentials and save.', 'Mailchimp');
        $select_list_msg = _digi3('To use field connections, please select the Mailchimp list first.');
        $must_create_fields_msg  = _digi3( 'In Mailchimp, select <strong>Audience - Settings - Audience fields and MERGE tags</strong> and create additional fields.' );

        $original_metas = parent::customFieldMetas();

        $attributes = $this->getAttributes();

        $options = array();
        $options[] = _ncore('No linking');
        foreach ($attributes as $attribute) {
            $options[$attribute['tag']] = $attribute['label'] != '' ? $attribute['label'].' - '.$attribute['tag'] : $attribute['tag'];
        }
        $defaults = array(
            'field_first_name' => 'FNAME',
            'field_last_name'  => 'LNAME',
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
                $meta['must_save_css'] = 'mailchimp_hint';
            }
            elseif ($options)
            {
                $meta[ 'type' ] = 'select';
                $meta[ 'options' ] = $options;
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
                $meta['hint'] = $options === false
                    ? $invalid_access_data_msg
                    : $must_create_fields_msg;
            }

            $my_metas[] = $meta;
        }

        if (!$this->isConntected()) {
            foreach ($my_metas as &$meta) {
                if ($meta['type'] != 'html') {
                    $meta['type'] = 'hidden';
                    $meta['hint'] = $invalid_access_data_msg;
                }
            }
        }
        if (!$this->listId()) {
            foreach ($my_metas as &$meta) {
                if ($meta['type'] != 'html') {
                    $meta['type'] = 'hidden';
                    $meta['hint'] = $select_list_msg;
                }
            }
        }

        return $my_metas;
    }

    protected function customFieldFormat( $placeholder_name )
    {
        return $placeholder_name;
    }

    //TODO change to mailchimp
    public function dynamicCustomFieldMetas() {
        $linkModel = $this->api->load->model('logic/link');
        $customfields_link = $linkModel->adminMenuLink('customfields');
        $metas = parent::dynamicCustomFieldMetas();
        array_splice( $metas, 1, 0, array(array(
            'type' => 'html',
            'label' => 'none',
            'html' => '<div class="dm-form-instructions">'._ncore('In addition to the fixed fields above, the %s can also be synchronized with %s.', $customfields_link, 'Mailchimp').'<br>'._ncore('In %s you can add these fields as placeholders to the e-mail texts. IMPORTANT: In order to use your own fields in DigiMember, these must be created as “cross-list fields” in %s.', $this->textLabel(), 'Mailchimp').'</div>',
        )));
        if (count($metas) < 3) {

            if (!$this->isConntected()) {
                $hint = _ncore('To use the %s with the fields in %s, please first enter your access data and save it.', $customfields_link, $this->textLabel());
            }
            elseif (!$this->listId()) {
                $hint = _digi3( 'To use field connections, please select the Mailchimp list first.' );
            }
            else {
                $hint = _digi3( 'In Mailchimp, select <strong>Audience - Settings - Audience fields and MERGE tags</strong> and create additional fields.' );
            }
            $meta['type'] = 'html';
            $meta['label'] = 'none';
            $meta['html'] = $hint;
            $metas[] = $meta;
        }
        return $metas;
    }

    public function getAttributes() {

        if ($this->isConntected()) {
            $mailchimp = $this->mailchimp();
            return $mailchimp->getAttributes($this->listId());
        }
        return array();
    }

    public function instructions()
    {
        $instructions = parent::instructions();

        $instructions[] = _digi3( '<strong>In MailChimp</strong> click on your name on the top right of the screen.' ) . ' ' . _digi3( 'Select %s.', '<em>Account</em>' );

        $instructions[] = _digi3( 'Select %s.', '<em>Extras - API Keys</em>' );

        $instructions[] = _digi3( 'Click on %s.', '<em>Create A Key</em>' );

        $instructions[] = _digi3( 'In the column %s click on the API key and copy it to the clipboard.', '<em>API Key</em>' );

        $instructions[] = _digi3( 'Optional: In the column %s click on %s and enter %s.', '<em>Label</em>', '<em>none set</em>', '"DigiMember"' );

        $instructions[] = _digi3( '<strong>In DigiMember</strong> paste the API key into the text input %s.', '<em>MailChimp API Key</em>' );

        $instructions[] = _digi3( 'Save your changes.' );

        return $instructions;
    }


    protected function hasCustomFields()
    {
        return true;
    }

    protected function hasDynamicCustomFields() {
        return true;
    }

    /**
     * @var Digimember_Mailchimp_Helper
     */
    private $mailchimp;

    /**
     * @return Digimember_Mailchimp_Helper
     * @throws Exception
     */
    private function mailchimp()
    {
        if (isset($this->mailchimp))
        {
            if (!$this->mailchimp)
            {
                throw new Exception( $this->unauthMsg() );
            }

            return $this->mailchimp;
        }

        require_once 'helper/mailchimp_api.php';

        $apikey = $this->apiKey();

        if (!$apikey)
        {
            return false;
        }
        else {
            $this->mailchimp = new Digimember_Mailchimp_Helper($apikey);
            if ($this->mailchimp->errorCode)
            {
                throw new Exception( $this->mailchimp->errorMessage );
            }
            return $this->mailchimp;
        }



    }

    private function getLists()
    {
        if (!$this->isConntected())
        {
            return 'error';
        }

        try
        {
            $mailchimp = $this->mailchimp();

            $retval = $mailchimp->lists();
            if ($mailchimp->errorCode)
            {
                return 'error';
            }

            if (empty($retval['lists'])) {
                return 'error';
            }

            $lists = $retval['lists'];

            $options = array();
            $options[ "" ] = _ncore( '(Please select ...)' );

            foreach ($lists as $one)
            {
                $options[ $one['id'] ] = $one['name'];
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
            if (!$this->apiKey())
            {
                return false;
            }

            $this->mailchimp();

            return true;
        }

        catch (Exception $e)
        {
            return false;
        }
    }

    private function unauthMsg()
    {
        return _digi3( 'The MailChimp API key is invalid. Please enter a valid API key.' );
    }

    private function apiKey()
    {
        return trim( $this->data( 'apikey' ) );
    }

    private function listId() {
        $list_id = trim( $this->data( 'list_id' ) );
        if ($this->mailchimp()) {
            if ($this->mailchimp()->listIsValid($list_id)) {
                return $list_id;
            }
            return false;
        }
        return false;
    }

    /**
     * updateSubscriber
     * sets attributes given as data array for a given user_id
     * only works if there is a related subscriber and attribute fields that match at the provider
     * @param $user_id
     * @param $data
     */
    public function updateSubscriber ($user_id, $data) {
        if (!$this->isConntected()) {
            return false;
        }
        $userData = ncore_getUserById($user_id);
        $receiver = $this->mailchimp()->getListMember($this->listId(), $userData->user_email);
        if ($receiver && $receiver->list_id != '') {
            $attributes = $this->getAttributes();
            foreach ($attributes as $attribute) {
                if (array_key_exists($attribute['tag'],$data)) {
                    $this->mailchimp()->updateSubscriberAttributes($this->listId(), $receiver->id, $attribute['tag'], $data[$attribute['tag']]);
                }
            }
            return true;
        }
        return false;
    }

    public function updateUserName ($user_id) {
        if (!$this->isConntected()) {
            return false;
        }
        $userData = ncore_getUserById($user_id);
        $userFirstName = get_user_meta($userData->ID, 'first_name', true);
        $userLastName = get_user_meta($userData->ID, 'last_name', true);
        $receiver = $this->mailchimp()->getListMember($this->listId(), $userData->user_email);
        if ($receiver && $receiver->list_id != '') {
            $this->mailchimp()->updateSubscriberAttributes($this->listId(), $receiver->id, 'FNAME', $userFirstName);
            $this->mailchimp()->updateSubscriberAttributes($this->listId(), $receiver->id, 'LNAME', $userLastName);
            return true;
        }
    }
}
