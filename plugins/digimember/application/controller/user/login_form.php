<?php

$load->controllerBaseClass( 'user/form' );

class digimember_UserLoginFormController extends ncore_UserFormController
{
    /** @var bool */
    private $password_change_request_handled = false;
    /** @var string */
    private $form_style = 'modern';

    public function init( $settings=array() )
    {
        $this->resetPasswordJsFunction();

        $css_class = ncore_retrieve($settings, 'container_css');
        $css_class = trim( "$css_class digimember_login" );
        $settings[ 'container_css' ] = $css_class;

        global $DM_IS_LOGIN_VIA_LOGINFORM;
        $DM_IS_LOGIN_VIA_LOGINFORM = true;

        $this->resetFormId();

        ncore_addCssClass( $settings, 'dm_signin_form_container', 'container_css' );
        ncore_addCssClass( $settings, 'dm_signin_form',           'form_css' );

        $this->form_style        = ncore_retrieve( $settings, 'style', 'modern' );
        $this->stay_on_same_page = $settings && is_array( $settings )
                                 ? in_array( 'stay_on_same_page', $settings )
                                 : $settings === 'stay_on_same_page';

        $facebook = ncore_retrieve( $settings, 'facebook', 'no' );

        $this->have_wordpress_login = $facebook != 'only';

        $have_facebook_login = $facebook != '' && $facebook != 'no';

        if ($have_facebook_login)
        {
            /** @var digimember_FacebookConnectorLib $lib */
            $lib = $this->api->load->library( 'facebook_connector' );
            $this->fb_login_button = $lib->getLoginButton();

            $fb_products_for_new_users = trim(ncore_retrieve( $settings, 'fb_product' ));
            if ($fb_products_for_new_users) {
                $lib->enableAutoAccountCreation( $fb_products_for_new_users );
            }


        }

        if ($this->fb_login_button)
        {
            $url = $this->stay_on_same_page
                 ? ncore_currentUrl()
                 : ncore_retrieve( $settings, array( 'redirect_url', 'url' ) );

            if (!$url) {
                $url = ncore_currentUrl();
            }

            $this->fb_login_button->setRedirectUrl( $url );
            $this->fb_login_button->checkLogin();
        }

        parent::init( $settings );

        if (!$this->password_change_request_handled)
        {
            /** @var ncore_BusinessLogic $business */
            $business = $this->api->load->model( 'logic/business' );
            $modified = $business->validateNewPasswordConfirmation();


            if ($modified===true)
            {
                $this->formSuccess( _dgyou( 'We have sent you an email with your new password.' ) );
            }
            elseif ($modified===false)
            {
                $tooltip = _dgyou( 'Maybe the link is too old or you started two requests at the same time. Please try again.' );
                $message = _dgyou( 'Your password request could not be processed.');
                $this->formError( ncore_tooltip( $tooltip, $message ) );
            }

            $this->password_change_request_handled = true;
        }

        $this->have_popup_dialog = $this->setting( 'type' ) === 'button' && $this->have_wordpress_login;
    }

    protected function view()
    {
        $css = $this->renderCss();
        echo "<style type=\"text/css\">$css</style>";

        $have_dialog = $this->have_popup_dialog;
        if ($have_dialog)
        {
            $container_id = ncore_id( 'dlg' );

            $title_close = _ncore( 'Close' );

            $style_attr = $this->isPosted()
                        ? ''
                        : "style='display:none;'";

            echo "<div id='$container_id' class='ncore_custom_user_dialog' $style_attr>";

            echo "<div class='ncore_custom_user_dialog_close_icon' title=\"$title_close\"></div>";

            $hl = $this->setting( 'dialog_headline' );

            if ($hl) {
                echo "<h1>$hl</h1>";
            }

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

        $bg_color      = $this->setting( 'button_bg',    '#21759b' );
        $fg_color      = $this->setting( 'button_fg',    '#FFFFFF' );
        $button_radius = $this->setting( 'button_radius',false );

        $button_radius_css = $button_radius !== false
                           ? 'border-radius: '.$button_radius.'px; border-top-left-radius: '.$button_radius.'px; border-top-right-radius: '.$button_radius.'px; border-bottom-left-radius: '.$button_radius.'px; border-bottom-right-radius: '.$button_radius.'px;'
                           : '';
        return "
form#$form_id div.ncore_with_focus input[type=\"text\"],
form#$form_id div.ncore_with_focus input[type=\"password\"]
{
    border-color: $bg_color;
    $button_radius_css
}

form#$form_id div.ncore_with_focus label,
form#$form_id div.ncore_with_focus label
{
    color: $bg_color;
    $button_radius_css
}

.button.button-primary.ncore_custom_button,
form#$form_id .button.button-primary {
     background-color: $bg_color;
     color: $fg_color;
     $button_radius_css
 }
";
    }

    protected function pageHeadline()
    {
        return _dgyou( 'Login' );
    }

    protected function inputMetas()
    {
        $user_id = 0;

        $metas = array();


        if ($this->have_wordpress_login)
        {
            $html = $this->_renderSignupMsg();

            $metas[] = array(
                'section' => 'login',
                'type' => 'html',
                'label' => '',
                'html' => $html,
                'element_id' => $user_id,
                'hide' => !$html,
            );
        }

        if ($this->fb_login_button)
        {
            $html = $this->fb_login_button->renderHtml();

            $metas[] = array(
                'section' => 'login',
                'type' => 'html',
                'label' => '',
                'html' => $html,
                'element_id' => $user_id,
                'css' => 'ncore_fb_login',
            );

            if ($this->have_wordpress_login) {

                $or = _dgyou('or');
                $html = "<div class='ncore_fb_login_seperator_container'><div class='ncore_fb_login_seperator_label'><span>$or</span></div></div>";
                $metas[] = array(
                    'section' => 'login',
                    'type' => 'html',
                    'label' => '',
                    'html' => $html,
                    'element_id' => $user_id,
                    'css' => 'ncore_fb_login_seperator',
                );
            }
        }

        if ($this->have_wordpress_login)
        {
            $metas[] = array(
                 'name' => 'username',
                'section' => 'login',
                'type' => 'text',
                'label' => _dgyou( 'Username' ),
                'rules' => "defaults",
                'element_id' => $user_id
            );

            $metas[] = array(
                 'name' => 'password',
                'section' => 'login',
                'type' => 'password',
                'label' => _dgyou( 'Password' ),
                'rules' => "defaults",
                'element_id' => $user_id
            );

            $metas[] = array(
                'name'       => 'remember',
                'section'    => 'login',
                'type'       => 'checkbox',
                'checkbox_label'     => _dgyou( 'Remember me' ),
                'label'      => 'none',
                'rules'      => "defaults",
                'element_id' => $user_id,
                'suffix'     => $this->renderResetPasswordLlink(),
            );
        }

        return $metas;
    }

    protected function sectionMetas()
    {
        return array(
             'login' => array(
                 'headline'     => '',
                 'instructions' => ''
            )
        );
    }

    protected function buttonMetas()
    {
        $javascript = $this->renderAjaxLoginJavascript();

        $metas = array();

        if ($this->have_wordpress_login)
        {
            $metas[] = array(
                    'type' => 'onclick',
                    'name' => 'save',
                    'label' => $this->saveButtonLabel(),
                    'image_url' => $this->saveButtonUrl(),
                    'primary' => true,
                    'javascript' => $javascript
            );
        }

        return $metas;
    }

    protected function saveButtonLabel()
    {
        return $this->setting( 'button_text', _dgyou( 'Log In' ) );
    }

    protected function saveButtonUrl()
    {
        return $this->setting( array( 'img', 'image_url'), false );
    }

    protected function editedElementIds()
    {
        return array(
             0
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

    protected function formSettings()
    {
        $is_legacy = $this->form_style != 'modern';
        return $is_legacy
            ? array(
                 'form_css'           => 'ncore_form_narrow',
                 'layout'             => 'narrow_legacy',
                 'hide_required_hint' => true,
              )
            :  array(
                 'layout'             => 'narrow',
                 'hide_required_hint' => true,
              );
    }

    protected function handleRequest()
    {
        parent::handleRequest();
    }


    protected function getData( $user_id )
    {
        return array();
    }

    protected function setData( $user_id, $data )
    {
        try
        {
            $username = ncore_retrieve( $data, 'username' );
            $password = ncore_retrieve( $data, 'password' );
//            $remember = (bool) ncore_retrieve( $data, 'remember', false );

            $url      = ncore_retrieve( $data, 'redirect_url' );
            if (!$url)
            {
                $url = $this->setting( 'redirect_url' );
            }

            if ( !$username && !$password )
            {
                throw new Exception( _dgyou( 'Please enter a username and a password.' ) );
            }

            // NOTE: using ncore_wp_login fails, since the html header is rendered at this point of time and wp_login sets a cookie
            $user = ncore_wp_authenticate( $username, $password/*, $remember */);

            if (!$url)
            {
                /** @var digimember_AccessLogic $model */
                $model = $this->api->load->model( 'logic/access' );
                $url   = $model->loginUrl( $user );
            }

            /** @var ncore_OneTimeLoginData $model */
            $model = $this->api->load->model( 'data/one_time_login' );
            $redirect_url = $model->setOneTimeLogin( $user->ID, ncore_resolveUrl( $url ) );

            ncore_redirect( $redirect_url );

            return true;
        }
        catch ( Exception $e )
        {
            $this->formError( $e->getMessage() );
            return false;
        }


    }

    private function resetPasswordJsFunction()
    {
        static $function;

        if (!empty($function)) {
            return $function;
        }

        $function = ncore_id( 'ncore_new_password_link' );

        $dialog       = $this->createForgottonPasswordDialog();
        $js           = $dialog->showDialogJs();
        $js_function  = "function $function() { $js; return false; } ";

        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model( 'logic/html' );
        $htmlLogic->jsFunction( $js_function );

        return $function;
    }


    private $reset_password_link = false;
    private function renderResetPasswordLlink()
    {
        if (!$this->have_wordpress_login)
        {
            return '';
        }

        if ($this->reset_password_link)
        {
            return $this->reset_password_link;
        }

        $function = $this->resetPasswordJsFunction();


        $new_password_label = _dgyou( 'Lost your password?' );
        /** @noinspection UnreachableCodeJS */
        $new_password_link  = "<a onclick=\"return $function();\" class='ncore_forgotton_password_link' href=\"/\">$new_password_label</a>";

        $this->reset_password_link = "<div class='ncore_user_form footnote ncore_new_password_link'>$new_password_link</div>";

        return $this->reset_password_link;
    }

    protected function formSuccessMessage()
    {
        return _dgyou( 'You are logged in now.' );
    }

    protected function ajaxEventHandlers()
    {
        $handlers = parent::ajaxEventHandlers();

        $handlers['login'] = 'handleAjaxLoginEvent';
        $handlers['ok']    = 'handleAjaxRequestPasswordEvent';

        return $handlers;
    }

    protected function saveButtonClass()
    {
        return 'ncore_custom_user_button';
    }


    protected function secureAjaxEvents()
    {
        $events = parent::secureAjaxEvents();

        $events[] = 'login';
        $events[] = 'ok';

        return $events;
    }

    private $forgotten_password_dialog = false;
    private $stay_on_same_page = false;
    private $have_wordpress_login   = true;
    private $have_popup_dialog = false;
    /** @var digimember_FacebookLoginButton | bool */
    private $fb_login_button = false;

    private $form_id;

    private function resetFormId()
    {
        static $i;
        $i++;

        $this->form_id = 'login_' . $i;
    }

    protected function formId()
    {
        return $this->form_id . '_form';
    }

    protected function ajaxErrorMsgDivId()
    {
        return $this->form_id . '_form_error_message';
    }

    private function renderAjaxLoginJavascript()
    {
        $error_div_class = $this->ajaxErrorMsgDivClass();

        $args = array();

        $redirect_url = $this->redirectUrl();
        if ($redirect_url) {
            $args['redirect_url'] = $redirect_url;
        }
        $args['current_url'] = ncore_currentUrl();

            $js_login = $this->renderAjaxJs( 'login', $args, 'data' );

        return "
var form = ncoreJQ( this ).closest( 'form' );
var container = form.parent();

var error_div =  container.find( '.$error_div_class' );
var error_div_id = error_div.attr( 'id' );

var username = encodeURIComponent( form.find( 'input[name=ncore_username0]').val() );
var password = encodeURIComponent( form.find( 'input[name=ncore_password0]').val() );
var remember = form.find( 'input[name=ncore_remember0]').val() ? '1' : '0';

var data = {
    'username': username,
    'password': password,
    'remember': remember,
    'errordiv': error_div_id
};

$js_login

";
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    protected function handleAjaxRequestPasswordEvent( $response )
    {
        $email     = $this->ajaxArg( 'ncore_email' );
        $redir_url = $this->ajaxArg( 'ncore_redir_url' );

        /** @var ncore_BusinessLogic $business */
        $business      = $this->api->load->model( 'logic/business' );

        /** @var ncore_RuleValidatorLib $rules */
        $rules = $this->api->load->library( 'rule_validator' );

        $error_msg = $rules->validate( _dgyou( 'Email' ), $email, 'email|required' );

        if ( is_string( $error_msg ) )
        {
            $response->error( $error_msg );
            return;
        }

        $user_id = ncore_getUserIdByEmail($email);

        /** @var digimember_IpCounterData $model */
        $model = $this->api->load->model( 'data/ip_counter' );

        $count = $model->getForUser( $user_id );

        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $limit = $model->getIpAccessLimit();

        $limit_violated = $limit > 0 && $count > $limit;

        if ($limit_violated) {
            $msg = _dgyou( 'Your account has been blocked, because there have been logins from too many devices (IPs). Please try again tomorrow.' );
            $response->error( $msg );
        }
        else {
            $success = $business->sendRequestNewPasswordMail( $email, $redir_url );
        }



        if ($success)
        {
            $msg = _dgyou( 'We have sent you the password confirmation link via email to %s.', $email )
                  . "\n\n"
                  . _dgyou('After you clicked on the link, we will send your password to you via email.'  );

            $response->success( $msg );
        }
        else if (!$limit_violated)
        {
            $msg = _dgyou( 'We have no user account for email %s.', $email );
            $response->error( $msg );
        }

    }

    /**
     * @param ncore_AjaxResponse $response
     */
    protected function handleAjaxLoginEvent( $response )
    {
        $errordiv = $this->ajaxArg( 'errordiv' );
        $username = $this->ajaxArg( 'username' );
        $password = $this->ajaxArg( 'password' );
        $remember = $this->ajaxArg( 'remember', 0 );
        $url      = $this->ajaxArg( 'redirect_url' );

        try
        {
            $must_fix_limit_login_atempt_captcha_bug = function_exists( 'wp_limit_login_auth_signon' )
                                                && !empty($_SESSION['wp_limit_captcha']);

            if ($must_fix_limit_login_atempt_captcha_bug) {
                $_GET['captcha'] = $_SESSION["wp_limit_captcha"];
            }

            if (!$url)
            {
                $url = $this->setting( 'redirect_url' );
            }

            if ( !$username && !$password )
            {
                throw new Exception( _dgyou( 'Please enter a username and a password.' ) );
            }

            $user = ncore_wp_login( $username, $password, $remember );

            $user_id = ncore_retrieve( $user, array( 'ID', 'id' ) );
            /** @var digimember_IpLockLogic $model */
            $model = $this->api->load->model( 'logic/ip_lock' );
            $model->checkLogin( $user_id );

            /** @var digimember_AccessLogic $model */
            $model = $this->api->load->model( 'logic/access' );
            $block_reason = $model->blockAccessReason( $user_id );
            if ($block_reason)
            {
                throw new Exception( _dgyou( 'Your account has been blocked, because there have been logins from too many devices (IPs). Please try again tomorrow.' )
                                     . ' ('._dgyou('Error code: %s', $block_reason ) . ')' );
            }


            if (!$url)
            {
                $model = $this->api->load->model( 'logic/access' );
                $url   = $model->loginUrl( $user );
            }

            if ( $url )
            {
                $url = ncore_resolveUrl( $url );
            }
            else
            {
                // NOT !! ncore_currentUrl() - because this is an ajax url!
                $url = ncore_siteUrl();
            }

            $response->redirect( $url );
        }

        catch ( Exception $e )
        {
            $msg = $e->getMessage();

            $dialog       = $this->createForgottonPasswordDialog();
            $js           = $dialog->showDialogJs();

            $a = "<a href=\"/\" onclick=\"$js; return false;\">";

            $msg = preg_replace( '|<a.*?>|', $a, $msg );

            $html = $this->renderFormMessage( 'error', $msg );
            $response->html( $errordiv, $html );
        }
    }


    private function createForgottonPasswordDialog()
    {
        if ( !$this->forgotten_password_dialog )
        {
            $meta = array(
                'type' => 'form',
                'ajax_dlg_id' => 'ajax_forgotton_pw_dlg',
                'cb_controller' => 'user/login_form',
                'message' => _dgyou( 'Enter your email address. Only after you clicked the link in the confirmation email, we will create a new password for you.' ),
                'title' => _dgyou( 'Set new password' ),
                'width' => '500px',
                'form_sections' => array(),
                'form_inputs' => array(
                     array(
                         'name' => 'email',
                        'type' => 'text',
                        'label' => _dgyou( 'Email' ),
                        'label_css' => 'ncore_texttoken',
                        'rules' => 'defaults|email',
                        'full_width' => true,
                    ),
                    array(
                        'name' => 'redir_url',
                        'type' => 'hidden',
                        'default' => ncore_currentUrl(),
                    ),
                )
            );

            /** @var ncore_AjaxLib $lib */
            $lib                             = $this->api->load->library( 'ajax' );
            $this->forgotten_password_dialog = $lib->dialog( $meta );
        }

        return $this->forgotten_password_dialog;
    }

    private function redirectUrl()
    {
        $url = $this->setting( 'redirect_url' );
        if (!$url)
        {
            $url = $this->setting( 'url' );
        }

        $url = str_replace( '&amp;', '&', $url );

        if ($this->stay_on_same_page)
        {
            $url = ncore_currentUrl();
        }

        return $url;
    }

    private function _renderSignupMsg()
    {
        $signup_msg = trim( $this->setting( 'signup_msg' ) );
        $signup_url = trim( $this->setting( 'signup_url' ) );

        if (!$signup_msg)
        {
            return '';
        }

        $have_link            = false;
        $have_must_close_link = false;

        $a_open = "<a href='$signup_url'>";
        $a_close = "</a>";

        while (true)
        {
            $pos = strpos($signup_msg, '__' );
            if ($pos === false) {
                break;
            }

            $have_link            = true;
            $have_must_close_link = true;

            $signup_msg = substr_replace( $signup_msg, $a_open, $pos, 2 );

            $pos = strpos($signup_msg, '__' );
            if ($pos === false) {
                break;
            }
            $signup_msg = substr_replace( $signup_msg, $a_close, $pos, 2 );
            $have_must_close_link = false;
        }

        if (!$have_link)
        {
            $signup_msg = $a_open . $signup_msg . $a_close;
        }
        elseif ($have_must_close_link)
        {
            $signup_msg = $signup_msg . $a_close;
        }

        return "<div class='ncore_form_instructions'>$signup_msg</div>";
    }

}
