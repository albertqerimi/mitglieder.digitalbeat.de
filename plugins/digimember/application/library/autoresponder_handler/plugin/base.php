<?php

abstract class digimember_AutoresponderHandler_PluginBase extends ncore_Plugin
{
    public function __construct( digimember_AutoresponderHandlerLib $parent, $meta )
    {
       $type = ncore_retrieve( $meta, 'engine' );

       parent::__construct( $parent, $type, $meta );
    }

    public function hasUnsubscribe() {
        return false;
    }

    abstract public function unsubscribe( $email );

    abstract public function getPersonalData( $email );

    abstract public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() );

    final public function getFormMetas()
    {
        $default_metas = $this->formMetas();

        $custom_field_metas = $this->hasCustomFields()
                            ? $this->customFieldMetas()
                            : array();

        $dynamic_custom_field_metas = $this->hasDynamicCustomFields()
                            ? $this->dynamicCustomFieldMetas()
                            : array();

        $auto_join_metas = $this->hasAutojoin()
                         ? $this->autoJoinMetas()
                         : array();

        return array_merge( $default_metas, $custom_field_metas, $dynamic_custom_field_metas, $auto_join_metas );
    }

    abstract protected function formMetas();

    final public function isAutoJoinAvailable()
    {
        return $this->hasAutojoin();
    }

    public function isActionSupportAvailable() {
        return $this->hasActionSupport();
    }

    public function retrieveAutojoinContactData(){
        return array( $extern_contact_id=false, $email='', $firstname='', $lastname='', $password='', $loginkey='' );
    }
    public function setAutojoinLoginData( $extern_contact_id, $username, $password, $login_url, $loginkey  ) {
    }

    public function renderOptionLabel()
    {
        return $this->label() . ' (' . _ncore('id %s', $this->id()) . ')';
    }

    public function instructions()
    {
        return array();
    }

    public function haveInstructionNumbers()
    {
        $have_numbers = count($this->instructions()) >= 2;
        return $have_numbers;
    }

    public function isEnabled()
    {
        if (!$this->isActive())
        {
            return false;
        }

        $is_active = $this->meta( 'is_active' );

        return ncore_isTrue( $is_active );
    }

    public function getEngine() {
        $engine = $this->meta('engine');
        return $engine != '' ? $engine : false;
    }

    public function isActive()
    {
        return true;
    }


    public function inactiveMsg()
    {
        return _digi3('This autoresponder type is inactive, because the web server does not meet the technical requirements.' );
    }

    public function optinFormNameInputMode()
    {
        // fullname
        // firstname
        // none
        return $this->data( 'optin_form_name_input', 'fullname' );
    }

    protected function forbiddenCharactersInCustomFieldNames()
    {
        $forbidden_chars = array();

        return $forbidden_chars;
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
            'field_date'       => date( 'Y-m-d' ),
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

    public function dynamicCustomFieldMetas() {
        $metas = array();
        $customFieldModel = $this->api->load->model('data/custom_fields');
        $customFields = $customFieldModel->getAllActive();
        if (is_array($customFields) && count($customFields) > 0) {
            $autoresponderAttributes = $this->getAttributes();
            if (count($autoresponderAttributes) > 0) {
                $metas[] = array(
                    'type' => 'html',
                    'label' => 'none',
                    'html' => '<div class="dm-formbox-headline">'._ncore('Connection with custom fields in DigiMember').'</div>',
                );
                foreach ($customFields as $customField) {
                    $metas[] = $customFieldModel->getArLinkSelectMeta($this->meta('id'), $customField, $autoresponderAttributes);
                }
            }
            else {
                $metas[] = array(
                    'type' => 'html',
                    'label' => 'none',
                    'html' => '<div class="dm-formbox-headline">'._ncore('Connection with custom fields in DigiMember').'</div>',
                );
            }
        }
        return $metas;
    }

    protected function hasCustomFields()
    {
        return false;
    }

    protected function hasDynamicCustomFields() {
        return false;
    }



    protected function hasAutojoin()
    {
        return false;
    }

    protected function hasActionSupport() {
        return false;
    }

    protected function data( $key, $default = '' )
    {
        $postname = $this->postname( $key );

        $posted_value = ncore_retrieve( $_POST, $postname, false );

        if ($posted_value !== false)
        {
            return $posted_value;
        }

        $data = $this->meta( 'data', array() );
        $key = $this->type() . '_' . $key;
        return ncore_retrieve( $data, $key, $default );
    }

    protected function setData( $key, $value )
    {
        $model = $this->dataModel();

        $row = $model->get( $this->id() );

        $typed_key = $this->type() . '_' . $key;

        $meta = $row->data;
        $meta[ $typed_key ] = $value;

        $data = array();
        $data['data_serialized'] = serialize( $meta ); //serialisieren der Daten !!!

        $model->update( $this->id(), $data );

        $this->meta['data'][ $typed_key ] = $value;
    }

    protected function config( $key, $default = '')
    {
        $config = $this->configModel();

        $key = $this->type() . '_' . $this->id() . '_' . $key;

        $value = $config->get( $key );

        return $value
               ? unserialize( $value )
               : $default;
    }

    protected function setConfig( $key, $value )
    {
        $key = $this->type() . '_' . $this->id() . '_' . $key;

        $this->configModel()->set( $key, serialize($value) );
    }

    protected function id()
    {
        return $this->meta( 'id' );
    }

    final public function label()
    {
        if (!$this->label)
        {
            $type = $this->type();

            $providers = $this->parent()->getProviders();

            $this->label = ncore_retrieve( $providers, $type, ucfirst( $type ) );
        }

        return $this->label;
    }

    protected function textLabel()
    {
        return $this->label();
    }

    protected function autoJoinMetas()
    {
        $metas = [];

        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller('shortcode');
        $shortcode = '[' . $controller->shortcode('autojoin') . ']';

        $find = ['[ARNAME]', '[PLUGIN]', '[SHORTCODE]'];
        $repl = [$this->textLabel(), $this->api->pluginDisplayName(), $shortcode];

        $headline = _digi3('Member auto join');
        $text = _digi3('After signing up in [ARNAME], new contacts may automatically get an account in [PLUGIN]. To do so, use the shortcode [SHORTCODE] on the Double opt-in processes thank you page.');

        $headline = str_replace($find, $repl, $headline);
        $text = str_replace($find, $repl, $text);

        $headline = '<div class="dm-formbox-headline">' . $headline . '</div>';
        $text = '<div class="dm-form-instructions">' . $text . '</div>';

        $metas[] = [
            'type' => 'html',
            'label' => 'none',
            'html' => $headline,
        ];

        $metas[] = [
            'type' => 'html',
            'label' => 'none',
            'html' => $text,
        ];

        return $metas;
    }

    protected function customFieldMetaHeadline()
    {
        return  _digi3( '[ARNAME] custom field names' );
    }

    protected function customFieldInstructions()
    {
        return _digi3( 'In [ARNAME], you may extend your contacts by custom fields. Check the documention of [ARNAME] on how to do this. Below you find a list of fields [PLUGIN] can send to [ARNAME]. In [ARNAME] you may add these fields as a placeholder to the emails [ARNAME] sends.' );
    }

    protected function customFieldFormat( $placeholder_name )
    {
        return false;
    }

    protected function customFieldMetas()
    {
        $find = ['[ARNAME]', '[PLUGIN]'];
        $repl = [$this->textLabel(), $this->api->pluginDisplayName()];

        $headline = str_replace($find, $repl, $this->customFieldMetaHeadline());
        $text = str_replace($find, $repl, $this->customFieldInstructions());

        $headline = '<div class="dm-formbox-headline">' . $headline . '</div>';
        $text = '<div class="dm-form-instructions">' . $text . '</div>';

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
                'type' => 'text',
                'label' => _digi3('First name'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_first_name'),
            ],

            [
                'name' => 'field_last_name',
                'type' => 'text',
                'label' => _digi3('Last name'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_last_name'),
            ],

            [
                'name' => 'field_date',
                'type' => 'text',
                'label' => _digi3('Order date'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_date'),
            ],

            [
                'name' => 'field_order_id',
                'type' => 'text',
                'label' => _digi3('Order id'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_order_id'),
            ],

            [
                'name' => 'field_login',
                'type' => 'text',
                'label' => _digi3('Username'),
                'rules' => 'defaults',
                'tooltip' => _digi3('The user\'s login name for your site. Default is his email address.'),
                'hint' => $this->renderCustomFieldHint('field_login'),
            ],
            [
                'name' => 'field_password',
                'type' => 'text',
                'label' => _digi3('Password'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_password'),
            ],
            [
                'name' => 'field_loginurl',
                'type' => 'text',
                'label' => _digi3('Login URL'),
                'rules' => 'defaults',
                'tooltip' => _digi3('The URL to the web page the user visits to log into your site. This is the page containing the login form.'),
                'hint' => $this->renderCustomFieldHint('field_loginurl'),

            ],
            [
                'name' => 'field_loginkey',
                'type' => 'text',
                'label' => _digi3('Login key'),
                'tooltip' => _digi3('You may use the login key for auto login links in your email.|Add a GET parameter %s to your blogs URL int the email and set it to the value of the login key custom field.', DIGIMEMBER_LOGINKEY_GET_PARAM),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldLoginkeyHint('field_loginkey', 'ncore_form_hint'),

            ],
        ];

        return $metas;
    }

    protected function customFieldDefaultNames()
    {
        return array(
            'first_name' => _digi3( 'FirstName' ),
            'last_name'  => _digi3( 'LastName' ),
            'date'       => _digi3( 'OrderDate' ),
            'login'      => _digi3( 'Login' ),
            'password'   => _digi3( 'Password' ),
            'loginurl'   => _digi3( 'LoginUrl' ),
            'loginkey'   => _digi3( 'LoginKey' ),
        );
    }

    private $meta;
    private $config = false;
    private $autoresponder = false;
    private $label = false;

    private function configModel()
    {
        if (!$this->config)
        {
            $this->config = $this->api->load->model( 'logic/blog_config' );
        }

        return $this->config;
    }

    private function dataModel()
    {
        if (!$this->autoresponder)
        {
            $this->autoresponder = $this->api->load->model( 'data/autoresponder' );
        }

        return $this->autoresponder;
    }

    private function postname( $key )
    {
        $postname = 'ncore_sub_data_' . $this->type() . '_' . $key . $this->id();

        return $postname;
    }

    protected function renderCustomFieldHint( $fieldname )
    {
        $placeholder = $this->data( $fieldname );

        $have_placeholder = $placeholder && $placeholder !== 'NULL';

        if ($have_placeholder) {
            $label       = $this->textLabel();
            $placeholder = $this->customFieldFormat( $placeholder );

            $msg = _digi3( 'Placeholder in %s mails: %s', $label, "<tt>$placeholder</tt>" );

            return $msg;
        }
        else
        {
            $default_names = $this->customFieldDefaultNames();

            $key = substr( $fieldname, 6 ); // remove 'field_' from the beginning

            $default = ncore_retrieve(  $default_names, $key );
            if (!$default) {
                return '';
            }
            return _digi3( 'Default is: %s', $default );
        }
    }

    protected function renderCustomFieldLoginkeyHint( $fieldname, $select_css='' )
    {
        $placeholder = $this->data( $fieldname );

        $have_placeholder = $placeholder && $placeholder !== 'NULL';

        if (!$have_placeholder) {
            return $this->renderCustomFieldHint( $fieldname );
        }

        $this->api->load->helper( 'html_input' );

        $placeholder = $this->customFieldFormat( $placeholder );

        $attr = array();
        $attr['class'] = $select_css;
        $attr['onchange'] = "var url=ncoreJQ(this).val(); ncoreJQ(this).parent().parent().find('span.digi_custom_meta_show_url').html(url);";

        $options = array();
        $pages   = ncore_resolveOptions( 'page' );
        $first_url = false;

        $url = ncore_siteUrl();
        $home_url = ncore_addArgs( $url, array( DIGIMEMBER_LOGINKEY_GET_PARAM => $placeholder ), '&', false );
        $options[ $home_url ] = _digi3( 'Home page' );;

        if ($pages)
        {
            foreach ($pages as $page_id => $label)
            {
                $url = get_permalink( $page_id );
                $url = ncore_addArgs( $url, array( DIGIMEMBER_LOGINKEY_GET_PARAM => $placeholder ), '&', false );
                $options[ $url ] = $label;
            }
        }

        $selector = ncore_htmlSelect( 'dummy', $options, 'NULL', $attr );

        $label       = $this->textLabel();
        $placeholder = $this->customFieldFormat( $placeholder );


        $msg = _digi3( 'Placeholder for login URL to page %s: %s', $selector, "<br /><tt><span class='digi_custom_meta_show_url'>$home_url</span></tt>" );

        return $msg;

    }

    public function getAttributes() {
        return array();
    }

    public function updateUserName($user_id) {
        return false;
    }


}