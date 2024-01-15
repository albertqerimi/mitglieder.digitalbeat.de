<?php

/*
Plugin Name: DigiMember 3
Plugin URI: https://digimember.de
Description: Mit DigiMember erstellst Du innerhalb von wenigen Minuten einen Mitgliederbereich auf Deiner WordPress Seite!
Author: Digital Solutions GmbH
Version: 3.9.1
Requires PHP: 7.4
Author URI: https://digimember.de
Text Domain: meta
Domain Path: /languages
License: Commercial - siehe https://digimember.de für mehr Informationen
Copyright 2019 Digital Solutions GmbH (email: support@digimember.de)
*/

/** @noinspection PhpIncludeInspection */

define( 'DIGIMEMBER_VERSION', '3.9.1' );
define( 'DIGIMEMBER_AFFILIATE', '' );
define( 'DIGIMEMBER_CAMPAIGNKEY', '' );
define( 'DIGIMEMBER_LICENSE_SERVER_URL', 'http://digimember.de' );
define( 'DIGIMEMBER_EDITION', 'DE' );

define( 'DIGIMEMBER_SERVICE_WORKER_VERSION', 1.1 );

// @obsolete in v3.000.160, used in a function where it never makes sense
define( 'DM_GET_PARAM_AFFILIATE', 'aff' );
define( 'DM_GET_PARAM_CAMPAIGNKEY', 'cam' );

/************************************************************************
 ************************************************************************
 ************************************************************************/

 define( 'DIGIMEMBER_DIR', dirname(__FILE__) );

require_once DIGIMEMBER_DIR.'/inc/api.php';
require_once DIGIMEMBER_DIR.'/inc/functions.php';

$must_init_wp = !defined( 'ABSPATH' );
if ($must_init_wp)
{
    $wp_path = dirname( dirname( dirname( DIGIMEMBER_DIR ) ) );
    if (file_exists( "$wp_path/wp-load.php" )) {
        require "$wp_path/wp-load.php";
    }
    else
    {
        $wp_path = dirname( dirname( DIGIMEMBER_DIR ) );
        if (file_exists( "$wp_path/wp-load.php" )) {
            require "$wp_path/wp-load.php";
        }
        else
        {
            $wp_path = dirname( DIGIMEMBER_DIR );
            if (file_exists( "$wp_path/wp-load.php" )) {
                require "$wp_path/wp-load.php";
            }
            else
            {
                $wp_path = dirname( dirname( dirname( dirname( DIGIMEMBER_DIR ) ) ) );
                if (file_exists( "$wp_path/wp-load.php" )) {
                    require "$wp_path/wp-load.php";
                }
                else
                {
                    $wp_path = dirname( dirname( dirname( dirname( dirname( DIGIMEMBER_DIR ) ) ) ) );
                    if (file_exists( "$wp_path/wp-load.php" )) {
                        require "$wp_path/wp-load.php";
                    }
                    else
                    {
                        die( 'ERROR: DigiMember cannot handle this unusual Wordpress installation.' );
                    }
                }
            }
        }
    }
}

require_once DIGIMEMBER_DIR.'/inc/filters.php';

$api = ncore_api();

add_action( 'after_setup_theme', 'digimember_after_setup_theme' );
function digimember_after_setup_theme() {

    $must_one_time_login = isset( $_GET[ DIGIMEMBER_ONE_TIME_LOGINKEY_GET_PARAM ] ) && $_GET[ DIGIMEMBER_ONE_TIME_LOGINKEY_GET_PARAM ];
    if ($must_one_time_login) {
        /** @var ncore_OneTimeLoginData $model */
        $model = ncore_api()->load->model( 'data/one_time_login' );
        $model->performOneTimeLogin( $_GET[ DIGIMEMBER_ONE_TIME_LOGINKEY_GET_PARAM ] );
    }

    $must_autologin = !ncore_isLoggedIn() && isset( $_GET[ DIGIMEMBER_LOGINKEY_GET_PARAM ] ) && $_GET[ DIGIMEMBER_LOGINKEY_GET_PARAM ];
    if ($must_autologin)
    {
        /** @var digimember_LoginkeyData $model */
        $model = ncore_api()->load->model( 'data/loginkey' );
        $logged_in = $model->maybeAutoLogin( $_GET[ DIGIMEMBER_LOGINKEY_GET_PARAM ] );
        if ($logged_in) {
            $current_url = ncore_removeArgs( ncore_currentUrl(), DIGIMEMBER_LOGINKEY_GET_PARAM );
            ncore_redirect( $current_url );
        }
    }

    $access_key = isset($_GET[ DIGIMEMBER_THANKYOUKEY_GET_PARAM ])
                ? $_GET[ DIGIMEMBER_THANKYOUKEY_GET_PARAM ]
                : false;

    $must_thankyou = isset( $access_key ) && $access_key;
    if ($must_thankyou) {
        /** @var digimember_DownloadData $model */
        $model = ncore_api()->load->model( 'data/download' );
        $model->authenticate( $access_key );

        /** @var digimember_UserProductData $model */
        $model = ncore_api()->load->model( 'data/user_product' );
        $model->authenticate( $access_key );
    }

    $download_key = isset( $_GET[ DIGIMEMBER_DOWNLOADKEY_GET_PARAM ] )
                  ? $_GET[ DIGIMEMBER_DOWNLOADKEY_GET_PARAM ]
                  : false;

    $must_try_download = !empty( $download_key );
    if ($must_try_download) {
        /** @var digimember_DownloadLogic $model */
        $model = ncore_api()->load->model( 'logic/download' );
        $model->exec( $download_key );
    }

    $must_handle_signup = !empty($_GET[ 'dm_signup' ]) && !empty( $_POST )  && isset( $_POST['email' ] ) && $_POST[ 'email' ];
    if ($must_handle_signup) {

        /** @var digimember_ProductData $model */
        $model = dm_api()->load->model( 'data/product' );

        $product_ids = $model->resolveAccessKeys( $_GET[ 'dm_signup' ] );

        $email        = ncore_retrieve( $_POST, 'email' );
        $first_name   = ncore_retrieve( $_POST, 'firstname' );
        $last_name    = ncore_retrieve( $_POST, 'lastname' );

        $redirect_url = ncore_retrieve( $_GET, 'dm_redirect' );

        $is_valid =$email && $product_ids;

        if ($is_valid)
        {
            $settings = array();
            $settings[ 'product' ] = $product_ids;
            /** @var digimember_UserSignupFormController $controller */
            $controller = dm_api()->load->controller( 'user/signup_form', $settings );

            $controller->handleGeneratedFormSignup( $email, $first_name, $last_name, $redirect_url );
        }
    }

    $must_manage_subscriptions = !empty($_GET[ 'dm_manage_subscriptions' ]) && !is_admin() && empty( $_POST );
    if ($must_manage_subscriptions)
    {
        /** @var digimember_AjaxUnsubscribeController $controller */
        $controller = dm_api()->load->controller( 'ajax/unsubscribe' );
        $controller->handleManageSubscriptions( $_GET[ 'dm_manage_subscriptions' ] );
    }

    $must_handle_webhook = !empty($_GET['dm_webhook'] );
    if ($must_handle_webhook) {
        /** @var digimember_WebhookLogic $model */
        $model = dm_api()->load->model( 'logic/webhook' );
        $model->handleRequest();
    }
}

add_action( 'plugins_loaded', 'digimember_plugins_loaded', PHP_INT_MAX );
function digimember_plugins_loaded()
{
    $must_disable_cache = !empty( $_GET[ DIGIMEMBER_DOWNLOADKEY_GET_PARAM ] );
    if ($must_disable_cache)
    {
        ncore_disableCaching();
    }

    $have_certificate_download = !empty($_GET['dm_certificate'])
                              && $_GET['dm_certificate'] > 0;
    if ($have_certificate_download) {
        $api = dm_api();
        $api->load->model( 'data/exam_certificate' );
        $password = empty( $_GET['dm_pw'] ) ? '' : $_GET['dm_pw'];
        $fullname = empty( $_GET['dm_recipient_name'] ) ? '' : $_GET['dm_recipient_name'];
        $api->exam_certificate_data->execDownload( $_GET['dm_certificate'], $password, $fullname );
    }


    $is_my_ajax_request = !empty($_GET['dm_is_ajax_request'])
                       && $_GET['dm_is_ajax_request'] === 'digimember';
    if ($is_my_ajax_request)
    {
        require_once dirname(__FILE__).'/ajax.php';
    }
}


add_action( 'init', 'digimember_init', PHP_INT_MAX );
function digimember_init()
{
    $ipn_id = false;
    if (isset($_GET['dm_ipn']))
    {
        $ipn_id = $_GET['dm_ipn'];
    }

    if (isset($_POST['dm_ipn']))
    {
        if (!$ipn_id) $ipn_id = $_POST['dm_ipn'];
    }

    if (isset($_POST['ipn_id']))
    {
        if (!$ipn_id) $ipn_id = $_POST['ipn_id'];
    }

    if (isset($_GET['ipn_id']))
    {
        if (!$ipn_id) $ipn_id = $_GET['ipn_id'];
    }

    if (!$ipn_id) {
        return;
    }

    if (!defined('NCORE_IS_IPN')) {
        define( 'NCORE_IS_IPN',   true );
    }

    $api = ncore_api();
    /** @var digimember_PaymentHandlerLib $handler */
    $handler = $api->load->library( 'payment_handler' );

    try
    {
        $handler->setPlugin( $ipn_id );
        $handler->handleIpnRequest();
    }

    catch (Exception $e)
    {
        die( 'ERROR: ' . $e->getMessage() );
    }
}

add_action('init', 'digimember_init_public_api', PHP_INT_MAX);
function digimember_init_public_api()
{
    $isPublicApiRequest = (bool)ncore_retrievePOST('dm_public_api', ncore_retrieveGET('dm_public_api'));
    if ($isPublicApiRequest) {
        if (!defined('DM_IS_PUBLIC_API_REQUEST')) {
            define('DM_IS_PUBLIC_API_REQUEST', true);
        }
        /** @var ncore_ApiCore $api */
        $api = ncore_api();

        /** @var digimember_PublicApiController $controller */
        $controller = $api->load->controller('public_api');
        $controller->dispatch();
        die();
    }
}

add_filter( 'ncore_resolve_options', 'digimember_resolve_options', 10, 2 );
function digimember_resolve_options( $options, $what_options )
{
    switch ($what_options)
    {
        case 'product':
            /** @var digimember_ProductData $model */
            $model = ncore_api()->load->model( 'data/product' );
            return $model->options();

        case 'autojoin_autoresponder':
            /** @var digimember_AutoresponderHandlerLib $lib */
            $lib = ncore_api()->load->library( 'autoresponder_handler' );
            return $lib->autojoinAutoresponderOptions();

    }

    return $options;
}





add_action( 'allowed_redirect_hosts', 'digimember_allowed_redirect_hosts', 99999, 1 );
function digimember_allowed_redirect_hosts( $hosts )
{
    $hosts[] = 'digimember.de';
    $hosts[] = 'www.digimember.de';

    $hosts[] = 'digimember.com';
    $hosts[] = 'www.digimember.com';

    $hosts[] = 'digistore24.com';
    $hosts[] = 'www.digistore24.com';
    $hosts[] = 'doc.digistore24.com';
    $hosts[] = 'docs.digistore24.com';

    if (NCORE_DEBUG)
    {
        $hosts[] = 'dmdev.de';
        $hosts[] = 'dbdev.de';
        $hosts[] = 'dmalt.de';
        $hosts[] = 'digiwin.de';
        $hosts[] = 'digidev.de';
    }

    return $hosts;
}

add_filter( 'query_vars', 'digimember_query_vars' );
function digimember_query_vars( $vars ){

  $vars[] = 'dm_support_key';

  $vars[] = 'dm_ipn';
  $vars[] = 'ipn_id';

  return $vars;
}

function dm_shortcode_gutenberg_render($attributes) {
    global $wp;
    $dmShortcode = ncore_retrieve($attributes, 'dm_shortcode', '');
    if (strpos(home_url( add_query_arg( array(), $wp->request ) ), 'block-renderer') !== false) {
        $content = do_shortcode($dmShortcode);
        return $content ? $content : _digi('No preview available');
    }
    else {
        return $dmShortcode;
    }
}

function dm_shortcode_gutenberg_init() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }
    register_block_type( 'digimember/shortcode', array(
        'attributes' => array(
            'dm_shortcode' => array('type' => 'string'),
        ),
        'render_callback' => 'dm_shortcode_gutenberg_render'
    ) );
}
add_action( 'init', 'dm_shortcode_gutenberg_init' );

function dm_blocks_init() {
    $configLib = ncore_api()->load->config('html_include');
    $config = $configLib->get('dm-blocks');
    foreach ($config as $dmBlockConfig) {
        $block_renderer = ncore_api()->load->library('block_renderer');
        $block_renderer->registerBlock($dmBlockConfig);
    }
}

add_action( 'init', 'dm_blocks_init' );

$dm_shortcode_preview = '';
function dm_shortcode_preview_bufferStart() {
    global $dm_shortcode_preview;

    if (isset($_GET['dm_shortcode_preview'])) {
        /** @var digimember_AdminShortcodeController $controller */
        $controller = ncore_api()->load->controller( 'admin/shortcode' );
        $dm_shortcode_preview = ncore_retrieve($controller->getViewData(), $_GET['dm_shortcode_preview'], 'No preview available');
        add_action('shutdown', 'dm_shortcode_preview_bufferStop', PHP_INT_MAX);
        ob_start('dm_shortcode_preview_content');
    }
}
function dm_shortcode_preview_bufferStop() {
    @ob_end_flush();
}
function dm_shortcode_preview_content($html) {
    global $dm_shortcode_preview;

    $output = '';
    /** @noinspection BadExpressionStatementJS */
    /** @noinspection ThisExpressionReferencesGlobalObjectJS */
    preg_match_all('#<script(.*?)</script>#is', $html, $matches);
    foreach ($matches[0] as $value) {
        $output .= $value;
    }
    preg_match_all('#<link(.*?)\/?>#is', $html, $matches);
    foreach ($matches[0] as $value) {
        $output .= $value;
    }

     return '
<!DOCTYPE html>
<html class="no-js" lang="">

<head>
  <meta charset="utf-8">
  <title>Shortcode</title>
</head>

<body>
  ' . $output . '
    <div style="position: fixed; width: 100vw; height: 100vh; background: #FFFFFF; z-index: 9999999; display: flex; align-items: center; justify-content: center;">
        ' . $dm_shortcode_preview . '
    </div>
</body>

</html>
 ';
}
add_action( 'template_redirect', 'dm_shortcode_preview_bufferStart', 0 );
