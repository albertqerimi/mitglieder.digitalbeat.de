<?php

class ncore_RpcApiLib extends ncore_Library
{
    public function exec( $controller, $action, $args=array() )
    {
        return $this->call( "api_$controller", $action, $args );
    }

    public function pluginApi( $action, $args )
    {
        return $this->call( 'api_plugin', $action, $args );
    }

    public function licenseApi( $action, $args )
    {
        return $this->call( 'api_license', $action, $args );
    }

    public function infoboxApi( $action, $args )
    {
        return $this->call( 'api_infobox', $action, $args );
    }

    public function mailtemplateApi( $action, $args )
    {
        return $this->call( 'api_mailtemplate', $action, $args );
    }

    public function certificateApi( $action, $args )
    {
        return $this->call( 'api_certificate', $action, $args );
    }

    public function webpushApi( $action, $args=array() )
    {
        return $this->call( 'api_webpush', $action, $args );
    }

    protected function call( $domain, $action, $args )
    {
        $api_url = $this->apiUrl();

        $wp_version = get_bloginfo('version');

        $plugin_version = $this->api->pluginVersion();
        $plugin_name    = $this->api->pluginName();

        $home_url = ncore_siteUrl();
        $locale   = get_locale();

        $request_args = base64_encode(serialize($args));

        $lib = $this->api->loadLicenseLib();
        $license_code = $lib->getLicenseCode();

        $request = array(
            'domain'         => $domain,
            'action'         => $action,
            'request'        => $request_args,
            'site'           => $home_url,
            'license_code'   => $license_code,
            'wp_version'     => $wp_version,
            'plugin'         => $plugin_name,
            'plugin_version' => $plugin_version,
            'php_version'    => phpversion(),
            'locale'         => $locale,
            'signature'      => md5( 'gfc7uIGCyCDSMgv' . $request_args ),
        );

        $handler = $this->api->load->library( 'http_request' );

        $settings = array();
        $settings['dont_validate_ssl'] = ! (bool) NCORE_API_VALIDATE_SSL;
        $settings['timeout' ] = 30;

        $response = $handler->postRequest( $api_url, $request, $settings );

        if ($response->isError()) {

            $error_msg = $response->errorMsg() . '(' . $response->errorNo() . ')';

            $message = "Ncore api error: $error_msg (action: $action)";
            throw new Exception( $message );
        }

        $body_serialized = $response->contents();

        $old_level = error_reporting( 0 );
        $body = unserialize( base64_decode(str_replace( ' ', '+', $body_serialized) ) );
        error_reporting( $old_level );

        if ($body === false) {
            if (NCORE_DEBUG)
            {
                echo $body_serialized;
            }
            throw new Exception( 'The upgrade server delivered an unexpected response. Please try again later.' );
        }

        $status = ncore_retrieve( $body, 'status' );
        if ($status === 'ERROR')
        {
            $error = ncore_retrieve( $body, 'message' );

            $msg = _ncore( 'Error connecting to %s server: %s', $this->api->pluginDisplayName(), $error );
            throw new Exception( $msg );
        }

        return $body;
    }


    public function setTestApiUrl( $api_url=false )
    {
        $this->test_api_url = $api_url;
    }

    private $test_api_url = false;
    private function apiUrl()
    {
        if ($this->test_api_url) {
            return $this->test_api_url;
        }

        $api_url = $this->api->licenseServerBaseUrl() . NCORE_API_ROOT . 'api.php';

        return $api_url;

    }



}
