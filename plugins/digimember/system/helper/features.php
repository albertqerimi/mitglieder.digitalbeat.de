<?php

function ncore_isLicenseServer()
{
	return defined('NCORE_IS_LICENSE_SERVER') && NCORE_IS_LICENSE_SERVER;
}


function ncore_hasProductQuantities()
{
    return ncore_isLicenseServer();
}

function ncore_haveDigistoreUpgrades()
{
    return true;
}

function ncore_haveBackups() {
    return function_exists( 'dbk_api' );
}

function ncore_isNetworkMasterSite() {

    if (!is_multisite()) {
        return false;
    }

    global $blog_id;

    return $blog_id == 1;
}

//function ncore_haveProductTrackingCode()
//{
//    return ncore_isLicenseServer() || ncore_isNetworkMasterSite();
//}

function ncore_haveExtendedShortcodes()
{
    return ncore_isLicenseServer();
}

function ncore_hasFacebookApp()
{
    return ncore_api()->edition() == 'DE';
}

function ncore_getDmNickname($userData) {
    $config = ncore_api()->load->model( 'logic/blog_config' );
    $policy = $config->get('nickname_policy_in_dm');
    if ($policy == 'firstname_lastname') {
        $output = array();
        if ($userData['first_name'] != '') {
            $output[] = $userData['first_name'];
        }
        if ($userData['last_name'] != '') {
            $output[] = $userData['last_name'];
        }
        if (count($output) > 0) {
            return implode(" ", $output);
        }
        return $userData['email'];
    }
    elseif ($policy == 'firstname-lastname') {
        $output = array();
        if ($userData['first_name'] != '') {
            $output[] = $userData['first_name'];
        }
        if ($userData['last_name'] != '') {
            $output[] = $userData['last_name'];
        }
        if (count($output) > 0) {
            return implode("-", $output);
        }
        return $userData['email'];
    }
    elseif ($policy == 'email_pre_at') {
        return strtok($userData['email'], '@');
    }
    elseif ($policy == 'random') {
        return substr(md5($userData['email']), -12);
    }
    else {
        return $userData['email'];
    }
}


