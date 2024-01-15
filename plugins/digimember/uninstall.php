<?php

require_once dirname(__FILE__).'/digimember.php';

$is_unistalling = defined('WP_UNINSTALL_PLUGIN') && WP_UNINSTALL_PLUGIN;

if ($is_unistalling)
{
    $api->init()->uninstall();
}


