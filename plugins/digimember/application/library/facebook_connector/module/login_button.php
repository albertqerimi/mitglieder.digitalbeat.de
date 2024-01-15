<?php

class digimember_FacebookLoginButton extends digimember_FacebookBaseModule
{
    const login_window_width  = 410;
    const login_window_height = 250;

    public function __construct( $api, $parent, $app_id, $app_secret ) // , $use_extended_permissions=false )
    {
        parent::__construct( $api, $parent, $app_id, $app_secret ); // , $use_extended_permissions );
    }

    function setRedirectUrl( $url )
    {
        $this->redirect_url = str_replace( '&amp;', '&', $url );
    }

    function enableReauth()
    {
        $this->is_reauth_eabled = true;
    }

    function setButtonLabel( $label )
    {
        $this->button_label = $label;
    }

    function setButtonStyle( $style )
    {
        $is_valid = $style == 'button' || $style=='link';

        if (!$is_valid) {
            trigger_error( "Invalid style: '$style'" );
            return;
        }

        $this->button_style = $style;
    }

    function checkLogin()
    {
        if ($this->is_login_checked) {
            return;
        }
        $this->is_login_checked = true;

        $code           = ncore_retrieveGET( 'code' );
        $granted_scopes = ncore_retrieveGET( 'granted_scopes' );

        // $error_reason       = ncore_retrieveGET( 'error_reason' );  // user_denied
        // $error              = ncore_retrieveGET( 'error' );
        // $error_description  = ncore_retrieveGET( 'error_description' );

        if (!$code) {
            return;
        }

        $return_url = ncore_retrieveGET( 'state' );

        $params = array(
            'client_id'     => $this->app_id,
            'client_secret' => $this->app_secret,
            'redirect_uri'  => $return_url,
            'code'          => $code,
        );

        try
        {
            $response = digimember_facebook_get( '/oauth/access_token', $params );

            $access_token    = ncore_retrieve( $response, 'access_token' );
            $access_lifetime = ncore_retrieve( $response, 'expires' );
        }
        catch (Exception $e)
        {
            $access_token    = false;
            $access_lifetime = 0;

            ncore_flashMessage( NCORE_NOTIFY_ERROR, _digi( 'Failed to login via Facebook: %s', $e->getMessage() ) );
        }

        if ($access_token) {

            $this->api->load->helper( 'xss_prevention' );
            $is_valid = ncore_XssPasswordVerified();
            if (!$is_valid)
            {
                $access_token    = false;
                $access_lifetime = 0;

                ncore_flashMessage( NCORE_NOTIFY_ERROR, _digi( 'There was an error with our cross site request forgery protection. Please reload this page and try again.' ) );
            }
        }

        try
        {
            $user_id = $access_token
                     ? $this->parent->login( $access_token, $access_lifetime, $granted_scopes )
                     : false;
        }
        catch (Exception $e)
        {
            $user_id = 0;
            ncore_flashMessage( NCORE_NOTIFY_ERROR, $e->getMessage() );
        }




        $js = '';

        if ($user_id)
        {
            $redirect_url = ncore_retrieveGET( 'redirect_url', $this->redirect_url );
            if ($redirect_url)
            {
                $redirect_url = $this->_urldecode_value( $redirect_url );
                $redirect_url = ncore_sanitizeAbsoluteUrl( $redirect_url );
            }
            else
            {
                $model        = $this->api->load->model( 'logic/access' );
                $redirect_url = $model->loginUrl( $user_id );
            }

            if (!$redirect_url) {

                $get_param_names_to_remove = array( 'code', 'granted_scopes', 'denied_scopes', 'state', ncore_XssVariableName() );

                $redirect_url = ncore_removeArgs( ncore_currentUrl(), $get_param_names_to_remove, '&', false );
            }

            $model = $this->api->load->model( 'data/one_time_login' );
            $next_url = $model->setOneTimeLogin( $user_id, $redirect_url );

            $js = "window.opener.location.href='$next_url';";
        }
        else
        {
            $js = "window.opener.location.reload(true);";
        }

        $js .= "window.close();";

        die( "<script>$js</script>" );
    }

    function renderHtml() {

        $this->checkLogin();

        $permissions = $this->requestedPremissions();

        $height      = self::login_window_height;
        $width       = self::login_window_width;

        $this->api->load->helper( 'xss_prevention' );

        $redirect_url_esc = $this->_urlencode_value( $this->redirect_url );

        $params = array(
            'redirect_url'          => $redirect_url_esc,
            ncore_XssVariableName() => ncore_XssPassword(),
        );
        $return_url = $this->_build_sanitized_return_url( $params );

        $params = array(
            'client_id'     => $this->app_id,
            'state'         => $return_url,
            'response_type' => 'code granted_scopes',
            'scope'         => $permissions,
            'display'       => 'popup',
            'redirect_uri'  => $return_url,
        );

        if ($this->is_reauth_eabled)
        {
            $params[ 'auth_type' ]= 'rerequest';
        }

        $url = 'https://www.facebook.com/dialog/oauth?' . http_build_query( $params, null, '&' );


        $label = $this->button_label
               ? $this->button_label
               : _digi('Sign in with Facebook');

        $icon = ncore_icon( 'facebook' );

        $js = "window.open('$url','_blank',ncore_windowOpenPosition($width,$height)+',location=no,menubar=no,status=no,toolbar=no') ;return false;";

        switch ($this->button_style)
        {
            case 'link':
                return "<a onclick=\"$js\" href='#' class='ncore_facebook_login_link'>$label</a>";

            case 'button':
            default:
                return "<button onclick=\"$js\" class='ncore_facebook_login_button'>$icon$label</button>";
        }
    }

    private $redirect_url = '';
    private $is_reauth_eabled = false;
    private $is_login_checked = false;
    private $button_label = '';
    private $button_style = 'button';


    private function _urlencode_value( $raw_value )
    {
        $encoded_value = str_replace( array( '+', '=' ), array( '-', '_' ), base64_encode( $raw_value ) );

        return $encoded_value;
    }

    private function _urldecode_value( $encoded_value )
    {
        $raw_value = base64_decode( str_replace( array( '-', '_' ), array( '+', '=' ), $encoded_value ) );

        return $raw_value;
    }

    private function _build_sanitized_return_url( $params )
    {
        $invalid_chars = array( ';', '"', "'", ';', '%', ',' );

        $url = ncore_currentUrl();

        $parts = parse_url( $url );

        $scheme = ncore_retrieve( $parts, 'scheme', 'http' );
        $host   = ncore_retrieve( $parts, 'host' );
        $path   = ncore_retrieve( $parts, 'path' );
        $query  = ncore_retrieve( $parts, 'query' );

        $args = array();
        if ($query) {
            parse_str( $query, $args );
        }

        unset( $args['DBGSESSID'] );
        foreach ($args as $key => $value)
        {
            $is_valid = !empty($value)
                     && empty( $params[$key] )
                     && strlen( str_replace( $invalid_chars, '', $value ) ) === strlen( $value );
            if ($is_valid) {
                $params[ $key ] = $value;
            }
        }

        foreach ($params as $key => $value)
        {
            if (empty($value)) {
                unset( $params[ $key ] );
            }
        }

        $path = trim( $path, '/' );
        if ($path) {
            $path .= '/';
        }

        $url = "$scheme://$host/$path";
        if ($params)
        {
            $url .= '?' . http_build_query( $params, null, '&' );
        }

        return $url;

    }
}
