<?php

function ncore_XssPassword()
{
    global $blog_id;

    $this_blog_id = empty($blog_id)
                  ? 0
                  : intval( $blog_id );

    static $cache;
    $xss_password =& $cache[ $this_blog_id ];

    if (empty($xss_password))
    {
        $session_key = 'ncore_xss_password';

        $session = ncore_api()->load->model( 'logic/session' );

        $xss_password = $session->get( $session_key );

        if (!$xss_password)
        {
            ncore_api()->load->helper( 'string' );
            $xss_password = ncore_randomString( 'alnum', 30 );

            $session->set( $session_key, $xss_password );
        }
    }

    return $xss_password;
}

function ncore_XssVariableName()
{
    return 'ncore_xss_password';
}

function ncore_XssPasswordHiddenInput()
{
    global $xss_password_check_disabled;

    if (!empty($xss_password_check_disabled))
    {
        return '';
    }

    $name = ncore_XssVariableName();
    $xss_password = ncore_XssPassword();
    return "<input type='hidden' name='$name' value='$xss_password' />";
}

function ncore_XssPasswordVerified()
{
    global $blog_id;

    $this_blog_id = empty($blog_id)
                  ? 0
                  : intval( $blog_id );

    static $cache;
    $passed =& $cache[ $this_blog_id ];
    if (isset($passed))
    {
        return $passed;
    }

    $name = ncore_XssVariableName();
    $session_xss_password = ncore_XssPassword();


    $posted_xss_password = ncore_retrieve( $_POST, $name );
    if (!$posted_xss_password)
    {
        $posted_xss_password = ncore_retrieve( $_GET, $name );
    }

    $passed = $session_xss_password != ''
           && $session_xss_password == $posted_xss_password;

    return $passed;
}
