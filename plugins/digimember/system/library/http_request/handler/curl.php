<?php

class ncore_HttpRequest_HandlerCurl extends ncore_HttpRequest_MethodBase
{
    const http_user_agent = 'DigiMember-API/1.0 (Linux; en-US; rv:1.0.0.0) php/20130430 curl/20130430';

    public function isAvailable()
    {
        // if (NCORE_DEBUG) return false;

        return function_exists( 'curl_init' );
    }

    public function getRequest( $url, $params, $settings = array() )
    {
        $this->api->load->helper( 'url' );

        $is_ssl = substr( $url, 0, 5 ) === 'https';

        $headers = array (
                        'Content-type: text/html; charset=utf-8',
                        'Accept-Charset: utf-8',
                   );

        $url = ncore_addArgs( $url, $params, '&' );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt( $ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );

        $this->_setupCurl( $ch, $settings );

        $contents = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $response = new ncore_HttpResponse( $contents, $http_code );

        $error_no = curl_errno($ch);
        if ($error_no)
        {
            $error_msg = curl_error($ch);

            $response->setError( $error_no, $error_msg );
        }

        curl_close($ch);

        return $response;

    }

    public function postRequest( $url, $params, $settings = array() )
    {
        $is_ssl = substr( $url, 0, 5 ) === 'https';

        $querystring = http_build_query($params, '', '&' );

        $headers = array (
                        'Content-type: application/x-www-form-urlencoded; charset=utf-8',
                        'Accept-Charset: utf-8',
                   );


        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_POST, count($params));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $querystring);
        curl_setopt( $ch, CURLOPT_ENCODING, "" );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );

        $this->_setupCurl( $ch, $settings );

        $contents = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $response = new ncore_HttpResponse( $contents, $http_code );

        $error = !$contents;
        if ($error)
        {
            $error_no = curl_errno($ch);
            $error_msg = curl_error($ch);

            $response->setError( $error_no, $error_msg );
        }

        curl_close($ch);

        return $response;
    }

    private function _setupCurl( &$ch, $settings )
    {
        $dont_validate_ssl = ncore_retrieve( $settings, 'dont_validate_ssl', false );
        $timeout           = ncore_retrieve( $settings, 'timeout',           false );


        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $ch, CURLOPT_USERAGENT, self::http_user_agent );

        if ($dont_validate_ssl)
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if ($timeout) {
            $timeout = intval( $timeout );
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout );
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
    }



}
