<?php

$load->controllerBaseClass( 'user/form' );

class digimember_UserAdvancedFormsController extends ncore_UserFormController
{
    private static $handled_emails = array();

    private $new_account_limit_in_24h = 10;

    private $recaptcha_key = '';
    private $recaptcha_secret = '';
    
    private static $user_id  = 0;
    private static $user_ids = array();

    public function init( $settings=array() )
    {
        self::$user_id++;
        self::$user_ids[] = self::$user_id; 
    
        $css_class = ncore_retrieve($settings, 'container_css');
        $css_class = trim( "$css_class dm_signup" );
        $settings[ 'container_css' ] = $css_class;

        $recaptcha_key    = ncore_retrieve( $settings, 'recaptcha_key' );
        $recaptcha_secret = ncore_retrieve( $settings, 'recaptcha_secret' );

        if ($recaptcha_key && $recaptcha_secret)
        {
            $this->recaptcha_key    = $recaptcha_key;
            $this->recaptcha_secret = $recaptcha_secret;
        }

        $this->resetFormId();

        $this->_setProductIds( $settings );

        $this->hide_after_signup = ncore_retrieve( $settings, 'hideform' );

        $facebook = ncore_retrieve( $settings, 'facebook', 'no' );

        $this->have_wordpress_login = $facebook != 'only';
        $have_facebook_login = $facebook != '' && $facebook != 'no';

        if ($have_facebook_login)
        {
            /** @var digimember_FacebookConnectorLib $lib */
            $lib = $this->api->load->library( 'facebook_connector' );

            $product_ids = $this->productIds();
            $lib->enableAutoAccountCreation( $product_ids );

            $this->fb_login_button = $lib->getLoginButton();
        }

        if ($this->fb_login_button)
        {
            $url = ncore_retrieve( $settings, array( 'redirect_url', 'url' ) );

            $this->fb_login_button->setRedirectUrl( $url );

            $this->fb_login_button->checkLogin();
        }

        parent::init( $settings );

        if ($this->recaptcha_key)
        {
            /** @var ncore_HtmlLogic $html */
            $html = ncore_api()->load->model( 'logic/html' );
            $html->includeJs( 'https://www.google.com/recaptcha/api.js?onload=ncoreCaptchaCallback&render=explicit' );

            $fct = "function ncoreCaptchaCallback() {
    ncoreJQ( '.ncore_repatcha' ).each(function(i,o) {
        var id=ncoreJQ(o).attr('id');
        grecaptcha.render(id, {
          'sitekey' : '$this->recaptcha_key'
        });

    });
}";
            $html->jsFunction( $fct );
        }

        $this->have_popup_dialog = $this->setting( 'type' ) === 'button' && $this->have_wordpress_login;
    }

    private function resetFormId()
    {
        static $i;
        $i++;

        $this->form_id = 'signup_' . $i;
    }

    protected function formId()
    {
        return $this->form_id . '_form';
    }

    protected function view()
    {
        $css = $this->renderCss();
        echo "<style>$css</style>";

        $have_dialog = $this->have_popup_dialog;
        if ($have_dialog)
        {
            $container_id = ncore_id( 'dlg' );

            $title_close = _ncore( 'Close' );

            $style_attr = $this->isPosted()
                        ? ''
                        : "style='display:none;'";

            echo "<div id='$container_id' $style_attr class='ncore_custom_user_dialog'>";

            echo "<div class='ncore_custom_user_dialog_close_icon' title=\"$title_close\"></div>";

            $hl = $this->setting( 'dialog_headline', _digi( 'Sign up' ) );

            echo "<h1>$hl</h1>";

            parent::view();

            echo "</div>";

            $js = "ncoreJQ( '#$container_id' ).fadeIn();";

            $button_id  = $this->formId() . '_button';
            $js  = "ncoreJQ( '#$button_id' ).click(function() { $js; return false; } );"
                 . "ncoreJQ( '#$container_id div.ncore_custom_user_dialog_close_icon' ).click(function(){ncoreJQ( '#$container_id' ).fadeOut();} );";
            /** @var ncore_HtmlLogic $htmlLogic */
            $htmlLogic = $this->api->load->model('logic/html');
            $htmlLogic->jsOnLoad( $js );

            $label = $this->saveButtonLabel();
            echo "<button id='$button_id' class='button button-primary ncore_custom_button'>$label</button>";
        }
        else
        {
            parent::view();
        }
    }

    private function renderCss()
    {
        $form_id = $this->formId();

        $bg_color = $this->setting( 'button_bg',     '#21759b' );
        $fg_color = $this->setting( 'button_fg',  '#FFFFFF' );
        $radius   = (int) $this->setting( 'button_radius', '50' );
        return "
form#$form_id div.ncore_with_focus input[type=\"text\"],
form#$form_id div.ncore_with_focus input[type=\"password\"]
{
    border-color: $bg_color;
}

form#$form_id div.ncore_with_focus label,
form#$form_id div.ncore_with_focus label
{
    color: $bg_color;
}

.button.button-primary.ncore_custom_button,
form#$form_id .button.button-primary {
     background-color: $bg_color;
     color: $fg_color;
     border-radius: ${radius}px;
 }
";
    }
    protected function pageHeadline()
    {
        return _dgyou( 'Sign up' );
    }

    protected function inputMetas()
    {
        $user_id = self::$user_id;

        $metas = array();

        $customFieldsObj = $this->api->load->model('data/custom_fields');
        $customFields = $customFieldsObj->getAllActive();

        if ($this->fb_login_button)
        {
            $html = $this->fb_login_button->renderHtml();

            $metas[] = array(
                'section' => 'account',
                'type' => 'html',
                'label' => 'none',
                'html' => $html,
                'element_id' => $user_id,
                'css' => 'ncore_fb_login',
            );

            if ($this->have_wordpress_login) {

                $or = _dgyou('or');
                $html = "<div class='ncore_fb_signup_seperator_container'><div class='ncore_fb_signup_seperator_label'><span>$or</span></div></div>";
                $metas[] = array(
                    'section' => 'account',
                    'type' => 'html',
                    'label' => 'none',
                    'html' => $html,
                    'element_id' => $user_id,
                    'css' => 'ncore_fb_signup_seperator',
                );
            }
        }

        if ($this->have_wordpress_login) {

            $metas[] = array(
                    'name' => 'email',
                    'section' => 'account',
                    'type' => 'email',
                    'label' => _dgyou('Email address' ),
                    'rules' => "defaults|required",
                    'element_id' => $user_id,
                    //'tooltip' => _dgyou( 'We send your access to this email address.' ),
           );

           $have_all_name_inputs = $this->setting( 'name', false )
                                || in_array( 'name', $this->fields() );

           $have_first_name_inputs = $this->setting( 'first_name', false )
                                  || in_array( 'first_name', $this->fields() );

           $have_last_name_inputs = $this->setting( 'last_name', false )
                                  || in_array( 'last_name', $this->fields() );

           $have_custom_field_inputs = $this->setting( 'custom_fields', false )
                                  || in_array( 'custom_fields', $this->fields() );

           if ($have_first_name_inputs || $have_all_name_inputs)
           {
                $metas[] = array(
                    'name' => 'firstname',
                    'section' => 'account',
                    'type' => 'text',
                    'label' => _dgyou('First name' ),
                    'rules' => "defaults|required",
                    'element_id' => $user_id,
                );
           }

           if ($have_last_name_inputs || $have_all_name_inputs)
           {
                $metas[] = array(
                    'name' => 'lastname',
                    'section' => 'account',
                    'type' => 'text',
                    'label' => _dgyou('Last name' ),
                    'rules' => "defaults|required",
                    'element_id' => $user_id,
                );
           }

            if ($have_custom_field_inputs && count($customFields) > 0) {
                foreach ($customFields as $customField) {
                    if ($customField->visible === 'Y') {
                        $metas[] = $customFieldsObj->getMeta($user_id, $customField);
                    }
                }
            }
        }
        
        $confirm = $this->setting( 'confirm', false );
        if ($confirm)
        {
                $metas[] = array(
                    'name' => 'is_confirmed',
                    'section' => 'account',
                    'type' => 'checkbox',
                    'label' => $confirm,
                    'rules' => "required",
                    'element_id' => $user_id,
                    'css' => 'ncore_confirm_signup',
                );
                
                static $initialized;
                if (empty($initialized))
                {
                    $initialized = true;
                    $js = "ncoreJQ( '.ncore_confirm_signup  .ncore_checkbox input[type=hidden]' ).change(function(){ 
                    
    var button = ncoreJQ(this).parentsUntil('.ncore ncore_user_form').parent().find('.ncore_signup_button');
    
    if (ncoreJQ(this).val() != '0')
    {
        button.removeClass('ncore_disabled');
    }
    else
    {
        button.addClass('ncore_disabled');
    }                
} );

    ncoreJQ( '.ncore_confirm_signup  .ncore_checkbox input[type=hidden]' ).trigger('change');
";

                    /** @var ncore_HtmlLogic $htmlLogic */
                    $htmlLogic = $this->api->load->model( 'logic/html' );
                    $htmlLogic->jsOnLoad( $js );
                }
                
        }

        if ($this->recaptcha_secret)
        {
            $id = ncore_id();
            $html = "<div class='ncore_repatcha' id='$id'></div>";
            $metas[] = array(
                    'name' => 'captcha',
                    'section' => 'account',
                    'type' => 'html',
                    'label' => 'none',
                    'html' => $html,
                    'element_id' => $user_id,
            );
        }

       return $metas;
    }

    protected function sectionMetas()
    {
        return array(
            'account' =>  array(
                            'headline' => '',
                            'instructions' => '',
                          ),
        );
    }

    protected function saveButtonClass()
    {
        return 'ncore_custom_user_button';
    }

    protected function buttonMetas()
    {
        if (!$this->have_wordpress_login)
        {
            return array();
        }
        
        $metas   = parent::buttonMetas();
        $confirm = $this->setting( 'confirm', false );
        
        if (!$confirm) {
            return $metas;
        }
 
        $save_button =& $metas[0];
        
        $save_button[ 'class' ] = 'ncore_signup_button ncore_disabled';
        
        $msg = _dgyou( 'Please accept our terms and check the checkbox.' );
        
        $save_button[ 'title' ] = $msg;
        
        $msg = str_replace( "'", "\\'", $msg );
        
        $save_button[ 'onclick' ] = "if (ncoreJQ(this).hasClass('ncore_disabled')) { alert( '$msg' ); return false; } return true;";
        
        
        return $metas;
    }

    protected function saveButtonLabel()
    {
        return $this->setting( 'button_text', _dgyou('Sign up') );
    }

    protected function saveButtonUrl()
    {
        return $this->setting( array( 'img', 'image_url') , false );
    }

    protected function editedElementIds()
    {
        return self::$user_ids;
    }


    protected function handleRequest()
    {
        parent::handleRequest();
    }

    protected function formSettings()
    {
        return array(
             'layout'             => 'narrow',
             'hide_required_hint' => true,
        );
    }

    protected function containerCss()
    {
        return parent::containerCss()
                . ' ' .
                ($this->have_wordpress_login
                ? 'ncore_with_wp_login'
                : 'ncore_without_wp_login')
                . ' ' .
                ($this->fb_login_button
                ? 'ncore_with_fp_login'
                : 'ncore_without_fp_login');
    }


    protected function getData( $user_id )
    {
        return array();
    }

    public function handleGeneratedFormSignup( $email, $firstname, $lastname, $redirect_url )
    {
        $is_handled =& self::$handled_emails[ $email ];

        if (!$is_handled)
        {
            $data = array();
            $data[ 'email' ]     = $email;
            $data[ 'firstname' ] = $firstname;
            $data[ 'lastname' ]  = $lastname;

            $do_perform_login = empty( $redirect_url );

            $this->setSettings( array( 'login' => $do_perform_login ) );

            $this->setData( $user_id=0, $data );
        }

        $is_handled = true;

        if ($redirect_url)
        {
            ncore_redirect($redirect_url);
        }
    }

    protected function setData( $user_id, $data )
    {
        $email        = ncore_retrieve( $data, 'email' );
        $first_name   = ncore_retrieve( $data, 'firstname' );
        $last_name    = ncore_retrieve( $data, 'lastname' );
        $is_confirmed = ncore_retrieve( $data, 'is_confirmed' );

        $customFieldsObj = $this->api->load->model('data/custom_fields');
        $customFields = $customFieldsObj->getAllActive();
        $customFieldData = array();
        if (count($customFields) > 0) {
            foreach ($customFields as $customField) {
                if ($customField->visible === 'Y') {
                    $customFieldData[$customField->name] = ncore_retrieve( $data, $customField->name );
                }
            }
        }

        
        if (!$email) {
            return false;
        }
        
        $confirm = $this->setting( 'confirm', false );
        if ($confirm && !$is_confirmed)
        {
            $this->formError( _dgyou( 'Please accept our terms and check the checkbox.' ) );
            return false;
        }
                
        if ($this->recaptcha_key)
        {
            $response = ncore_retrieve( $_POST, 'g-recaptcha-response' );

            $args = array();
            $args[ 'body' ] = "secret=$this->recaptcha_secret&response=$response";

            $url      = 'https://www.google.com/recaptcha/api/siteverify';

            $result = wp_remote_post( $url, $args );

            $json   = ncore_retrieve( $result, 'body' );
            $result = $json
                    ? @json_decode( $json )
                    : false;

            $is_valid = ncore_retrieve( $result, 'success', false );

            if (!$is_valid) {
                $this->formError( _dgyou( 'Please prove that you are human and not a bot.' ) );
                return false;
            }
        }
        
        if ($confirm) {
            $this->api->log('privacy', _digi('User %s with IP %s has accepted our text: %s'), $email, ncore_clientIp(), $confirm );
        }


        $is_handled =& self::$handled_emails[ $email ];
        if ($is_handled)
        {
            return false;
        }

        $is_handled = true;

        $this->success_msg_email = $email;

        /** @var digimember_PaymentHandlerLib $library */
        $library = $this->api->load->library( 'payment_handler' );

        $product_ids = $this->productIds();

        if (!$product_ids)
        {
            $product_ids = 'none';
        }

        $address = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
        );


        foreach ($customFieldData as $customFieldName => $customFieldValue) {
            $address[$customFieldName] = $customFieldValue;
        }

        $do_perform_login = $this->setting( 'login', false );

        $user_id = ncore_getUserIdByEmail( $email );
        $this->had_account = $user_id > 0;

        if ($this->had_account)
        {
            $this->had_admin_account = ncore_canAdmin($user_id);
        }
        else
        {
            /** @var ncore_IpLockData $model */
            $model = $this->api->load->model( 'data/ip_lock' );
            $is_locked = $model->isLocked( 'signup', $this->new_account_limit_in_24h, 86400 );
            if ($is_locked)
            {
                $this->formError( _dgyou( 'Sorry, you have created too many accounts already.' ) );
                return $modified = false;
            }
        }

        try
        {
            $this->welcome_msg_sent = $library->signUp( $email, $product_ids, $address, $do_perform_login );

            if ($this->hide_after_signup)
            {
                $this->is_form_visible = false;
            }
        }
        catch (Exception $e)
        {
            $this->formError( $e->getMessage() );
            return $modified = false;
        }

        return $modified = true;
    }

    protected function formSuccessMessage()
    {
        $email = $this->success_msg_email;

        if ($this->had_admin_account && ncore_canAdmin())
        {
            return _digi( 'The email address %s belongs to an admin account. For testing purposes, please use a NON admin email address, because for admins wordpress looks and behaves different than for regular users.', "<em>$email</em>" );
        }
        elseif (!$this->welcome_msg_sent)
        {
            /** @var digimember_BlogConfigLogic $config */
            $config = $this->api->load->model( 'logic/blog_config' );
            $login_url = $config->loginUrl();

            $msg = _dgyou( 'There is already an account for the email address %s.', "<em>$email</em>" );
            if ($login_url)
            {
                $msg .= ' ' . ncore_linkReplace( _dgyou( '<a>Click here to login.</a>' ), $login_url );
            }
            return $msg;
        }
        elseif ($this->had_account)
        {
            return _dgyou( 'There is already an account for the email address %s. We have re-sent the confirmation email to this email address.', "<em>$email</em>" );
        }
        else
        {
            return _dgyou('Your account has been created. We have send your password to %s. If you don\'t receive the email within 10 minutes, please check your spam folder.', "<em>$email</em>" );
        }
    }

    protected function isFormVisible()
    {
        return $this->is_form_visible;
    }


    private $fields = false;
    private $product_ids = false;
    private $success_msg_email=false;
    private $welcome_msg_sent=true;

    /**
     * @var bool | digimember_FacebookLoginButton
     */
    private $fb_login_button = false;
    private $have_wordpress_login = true;
    private $have_popup_dialog = false;

    private $had_account = false;
    private $had_admin_account = false;

    private $hide_after_signup = false;
    private $is_form_visible   = true;

    private $form_id;

    // depcreated shortcode option "fields" - removed on 02/20/2013
    private function fields()
    {
        if ($this->fields === false)
        {
            $this->fields = array();

            $fields_comma_seperated = $this->setting( 'fields', false );
            $list = $fields_comma_seperated
                    ? explode(',',$fields_comma_seperated)
                    : array();

            foreach ($list as $val)
            {
                $field = ncore_washText( $val );
                if ($field)
                {
                    $this->fields[] = $field;
                }
            }
        }

        return $this->fields;
    }

    private function _setProductIds( $settings )
    {
        $ids_comma_seperated = ncore_retrieve( $settings, array( 'product', 'products' ) );

        $this->product_ids = array();

        $list = $ids_comma_seperated
              ? explode(',',$ids_comma_seperated)
              : array();

        foreach ($list as $val)
        {
            $id = intval( $val );
            if ($id >= 1 && !in_array( $id, $this->product_ids))
            {
                $this->product_ids[] = $id;
            }
        }
    }

    private function productIds()
    {
        return $this->product_ids;
    }


}
