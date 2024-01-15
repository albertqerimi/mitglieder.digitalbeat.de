<?php

function digimember_facebook_get( $file, $params )
{
    $fb_api_url     = digimember_FacebookConnectorLib::BASE_GRAPH_URL;
    $fb_api_version = digimember_FacebookConnectorLib::GRAPH_API_VERSION;


    $url = $fb_api_url . '/' . $fb_api_version . $file . '?' . http_build_query( $params, null, '&' );

    $raw_response = wp_remote_get( $url );
    $response = ncore_retrieve( $raw_response, 'body' );

    if ( is_wp_error( $response ) ) {
        throw new Exception( $response->get_error_message() );
    }

    $is_json = $response && $response[0] == '{';

    if ($is_json)
    {
        $data = (array) @json_decode( $response );
        $error = ncore_retrieve( $data, 'error' );

        if ($error)
        {
            $msg  = _digi('Facebook error:') . ' ' . ncore_retrieve( $error, 'message', 'Unknown Facebook error' );
            $type = ncore_retrieve( $error, 'type' );
            $code = ncore_retrieve( $error, 'code' );

            throw new Exception( $msg, $code );
        }
    }
    else
    {
        @parse_str( $response, $data );
    }

    if (empty($data)) {
        $data = array();
    }

    return $data;

}

function digimember_facebook_post( $file, $params )
{
    $fb_api_url     = digimember_FacebookConnectorLib::BASE_GRAPH_URL;
    $fb_api_version = digimember_FacebookConnectorLib::GRAPH_API_VERSION;


    $url = $fb_api_url . '/' . $fb_api_version . $file;

    $args = array();

    $args['body'] = $params;

    $raw_response = wp_remote_post( $url, $args );
    $response = ncore_retrieve( $raw_response, 'body' );

    if ( is_wp_error( $response ) ) {
        throw new Exception( $response->get_error_message() );
    }

    $is_json = $response && $response[0] == '{';

    if ($is_json)
    {
        $data = (array) @json_decode( $response );
        $error = ncore_retrieve( $data, 'error' );

        if ($error)
        {
            $msg  = _digi('Facebook error:') . ' ' . ncore_retrieve( $error, 'message', 'Unknown Facebook error' );
            $type = ncore_retrieve( $error, 'type' );
            $code = ncore_retrieve( $error, 'code' );

            throw new Exception( $msg, $code );
        }
    }
    else
    {
        @parse_str( $response, $data );
    }

    if (empty($data)) {
        $data = array();
    }

    return $data;

}

?>
