<?php

define( 'NCORE_IS_AJAX',  false );
define( 'NCORE_IS_IPN',   true );
define( 'WP_USE_THEMES',  false );
define( 'WP_ADMIN',       true  ); // prevent 404 not found errors and redirects
$_SERVER['PHP_SELF'] = '/wp-admin/digimember/ipn.php'; // prevent php notice message (Undefined offset) in vars.php
define( 'DONOTCACHEPAGE', 1 );


require_once dirname(__FILE__).'/digimember.php';

function digi_retrieveIpnId()
{
    if (isset($_GET['dm_ipn']))
    {
        return $_GET['dm_ipn'];
    }

    if (isset($_POST['ipn_id']))
    {
        return $_POST['ipn_id'];
    }

    if (isset($_GET['ipn_id']))
    {
        return $_GET['ipn_id'];
    }

    return false;
}


// Overwrite potential 404 on plugin php files
header("HTTP/1.1 200 OK");
header("Status: 200 OK") ;

$api = ncore_api();

$is_active = $api->pluginIsActive();
if (!$is_active)
{
    die('ERROR: Plugin is not active');
}

$have_support_info_request = isset( $_GET['dm_support_key'] );
if ($have_support_info_request)
{
    /** @var ncore_SupportLogic $model */
    $model = $api->load->model( 'logic/support' );
    $model->handleSupportInfoRequest();
}


$have_post = isset($_POST) && count($_POST);

if ($have_post)
{
    $apilog = $api->load->model( 'data/apilog' );
    $apilog->log( 'ipn' );
}
elseif (NCORE_DEBUG)
{
    $apilog = $api->load->model( 'data/apilog' );
    $apilog->replay( 'ipn' );
}

$ipn_id = digi_retrieveIpnId();
if (!$ipn_id)
{
    die('ERROR: Invalid ipn url - see DigiMember admin settings for correct ipn url.');
}

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

