<?php

require_once dirname(__FILE__).'/digimember.php';

// Overwrite potential 404 on plugin php files
header("HTTP/1.1 200 OK");
header("Status: 200 OK") ;

$api = ncore_api();

$is_active = $api->pluginIsActive();

if (!$is_active)
{
    die('ERROR: Plugin is not active');
}

$key = ncore_retrieve( $_GET, array( 'ds_login', 'key' ) );

$model = $api->load->model( 'data/one_time_login' );
$model->performOneTimeLogin( $key );