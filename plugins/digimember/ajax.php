<?php

define( 'NCORE_IS_AJAX',  true );
define( 'DONOTCACHEPAGE', 1 );

add_filter('allowed_http_origins', function($origins) {
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
    $origins[] = 'http://localhost:1234';
    return $origins;
});
send_origin_headers();

//$is_legacy_ajax_php_request = empty($_GET['dm_is_ajax_request']);
//if ($is_legacy_ajax_php_request)
//{
//    if (!defined('DIGIMEMBER_DIR')) {
//        require_once dirname(__FILE__).'/digimember.php';
//    }
//    // Overwrite potential 404 on plugin php files
//    define( 'WP_ADMIN',       true  ); // prevent 404 not found errors and redirects
//    $_SERVER['PHP_SELF'] = '/wp-admin/digimember/ajax.php';
//    define( 'WP_USE_THEMES',  false );
//    header("HTTP/1.1 200 OK");
//    header("Status: 200 OK") ;
//    header('Content-type: application/json' );
//}

$api = ncore_api();

$ajax = $api->load->library( 'ajax' );
$api->load->helper( 'xss_prevention' );

try
{
    $is_active = $api->pluginIsActive();
    if (!$is_active)
    {
        throw new Exception('Plugin is not active!');
    }


    $controller_name = ncore_retrieve( $_GET, 'controller' );
    if (!$controller_name)
    {
        throw new Exception( 'Ajax controller missing.' );
    }

    $controller = $api->load->controller( $controller_name );

    $event = ncore_retrieve( $_GET, 'event' );
    if (!$event)
    {
        throw new Exception ( 'Ajax event missing.' );
    }

    $must_verify_xss_password = $controller->mustVerifyXssPassword( $event );
    if ($must_verify_xss_password && !ncore_XssPasswordVerified())
    {
        throw new Exception( 'Ajax request not authorized. Do you have cookies enabled? If so, please scan your computer for viruses.' );
    }

    unset( $_GET['controller'] );
    unset( $_GET['event'] );
    unset( $_GET[ncore_XssVariableName()] );

    $response = $controller->dispatchAjax( $event, $_GET );

    $response->output();
}

catch (Exception $e)
{
    $response = new ncore_AjaxResponse( ncore_api() );
    $response->error( 'An ajax error occurred: ' . $e->getMessage() );
    $response->output();
}

