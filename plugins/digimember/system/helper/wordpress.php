<?php

function ncore_blogId()
{
    return get_current_blog_id();
}

function ncore_isLoggedIn()
{
    return is_user_logged_in();
}


function ncore_isInSidebar()
{
    global $ncore_sidebar_level;
    return isset($ncore_sidebar_level) && $ncore_sidebar_level>=1;
}

function ncore_userId( $user_obj_or_id = 'current' )
{
    if (is_object($user_obj_or_id))
    {
        return ncore_washInt( ncore_retrieve( $user_obj_or_id, array( 'ID', 'id' ) ) );
    }

    if (is_numeric( $user_obj_or_id ) && $user_obj_or_id>0)
    {
        return ncore_washInt( $user_obj_or_id );
    }

	static $user_id_cache;

	if (isset($user_id_cache))
	{
		return $user_id_cache;
	}

    $user_id = function_exists('get_current_user_id')
			 ? get_current_user_id()
			 : 0;

	if ($user_id)
	{
		// cronjobs logs may call ncore_userId()
		// before the user session has been started.
		$user_id_cache = $user_id;
	}

	return $user_id;
}

function ncore_userFirstName()
{
	$name = get_user_meta( get_current_user_id(), "first_name", true );

	if (!$name) {
		$name = ncore_retrieve( wp_get_current_user(), "display_name" );
	}

	return $name;
}

function ncore_userName()
{
    $user = wp_get_current_user();
    return ncore_retrieve( $user, 'user_login' );
}


function ncore_getUserById( $user_id='current' )
{
    if ($user_id === 'current')
    {
        $user_id = ncore_userId();
    }

    if (is_object($user_id))
    {
        return $user_id;
    }

    if (!$user_id) {
        return false;
    }
    $userdata = WP_User::get_data_by( 'id', $user_id );
    return $userdata;
}


function ncore_userImage( $user_id, $avatar_size )
{
	$image_html = get_avatar( $user_id, $avatar_size );

	return $image_html;
}


function ncore_isAdminArea( $set_is_admin=null)
{
    static $is_admin;

    if (!is_null($set_is_admin))
    {
        $is_admin = (bool) $set_is_admin;
    }

    if (!isset($is_admin))
    {
        if (ncore_isAjax())
        {
            $is_admin = ncore_retrievePOST( 'ncore_is_admin' );
            if (!$is_admin) {
                $is_admin = ncore_retrieveGET( 'ncore_is_admin' );
            }

            if ($is_admin)
            {
                $is_admin = $is_admin === 'Y';
            }
            else
            {
                $is_admin = is_admin();
            }
        }
        else
        {
            $is_admin = is_admin();
        }
    }

    return $is_admin;
}

/**
 * @param string $textForAdminArea
 * @param string $textForUserArea
 * @return string
 */
function ncore_adminConditional($textForAdminArea, $textForUserArea)
{
    return ncore_isAdminArea() ? $textForAdminArea : $textForUserArea;
}

function ncore_getPages( $type='page' )
{
    static $cache;

    $pages =& $cache[ $type ];

    if (isset($pages)) {
        return $pages;
    }

    switch ($type)
    {
        case 'page':
            $pages = get_pages( array( "sort_order" => "ASC", "sort_column" => "menu_order", "hierarchical" => 0, "post_type" => 'page' ) );
            break;

        case 'post':
            $pages = array();

            $query = array(
                'posts_per_page' => PHP_INT_MAX,
            );
            $loop = new WP_Query($query);

            while ( $loop->have_posts() )
            {
                $loop->the_post();
                global $post;
                $pages[] = $post;
            }
            break;

        default:
            global $wpdb;

            $hierarchical_post_types = get_post_types( array( 'hierarchical' => true ) );

            $is_hierarchical = in_array( $type, $hierarchical_post_types);

            if ($is_hierarchical)
            {
                $pages = get_pages( array( "sort_order" => "ASC", "sort_column" => "menu_order", "hierarchical" => 0, "post_type" => $type ) );
            }
            else
            {
                $type = ncore_washText( $type );

                $query = "SELECT * FROM $wpdb->posts
                          WHERE post_type='$type'
                            AND post_status != 'auto-draft'
                            AND post_status != 'trash'
                          ORDER BY menu_order ASC";

                $pages = $wpdb->get_results($query);
            }
    }

	return $pages;
}

function ncore_getAllPages( $type='page' )
{
    static $cache;

    $pages =& $cache[ $type ];

    if (isset($pages)) {
        return $pages;
    }
    global $wpdb;
    $type = ncore_washText( $type );

    $query = "SELECT * FROM $wpdb->posts
              WHERE post_type='$type'
                AND post_status != 'auto-draft'
                AND post_status != 'trash'
              ORDER BY menu_order ASC";

    $pages = $wpdb->get_results($query);

    return $pages;
}

function ncore_getCurrentPageId()
{
	global $post;

	return ncore_retrieve( $post, 'ID', false );
}

function ncore_getCurrentPageType()
{
	global $post;

	return ncore_retrieve( $post, 'post_type', false );
}

function ncore_currentPostIdAndType()
{
	if (!function_exists('get_post')) {
		return array( 0, '' );
	}

	$post = get_post();
	if (empty($post)) {
		return array( 0, '' );
	}

	$post_id   = ncore_retrieve( $post, 'ID' );
	$post_type = ncore_retrieve( $post, 'post_type' );

	return array( $post_id, $post_type );
}

function ncore_getUserIdByEmail( $email )
{
    return email_exists( $email );
}

function ncore_getUserIdByName( $login_name )
{
    return ncore_retrieve( ncore_getUserBy('login', $login_name), 'ID', false);
}

function ncore_getUserBy( $key, $value )
{
    if ($value == 'current' && $key=='id')
    {
        $value = ncore_userId();
    }

    $must_include_wp_include_file = !function_exists( 'get_user_by' );
    if ($must_include_wp_include_file)
    {
        require_once ABSPATH . 'wp-includes/pluggable.php';
    }
    return get_user_by( $key, $value );
}



function ncore_setSessionUser( $user_id )
{
    if (!$user_id) {
        return;
    }

    wp_set_current_user($user_id);
    $user = new WP_User( $user_id );
    do_action( "wp_login", $user->user_login, $user );
    /** @var digimember_CounterData $counter_model */
    $counter_model = ncore_api()->load->model( 'data/counter' );
    $counter_model->countLogin( $user_id );

    if (!headers_sent())
    {
        wp_set_auth_cookie($user_id);
    }
    else
    {
        /** @var ncore_OneTimeLoginData $model */
        $model = ncore_api()->load->model( 'data/one_time_login' );
        $url = $model->setOneTimeLogin( $user_id, 'ajax' );

        $js = "dmDialogAjax_FetchUrl( '$url', true )";
        /** @var ncore_HtmlLogic $model */
        $model = ncore_api()->load->model( 'logic/html' );
        $model->jsOnLoad( $js );
    }
}

function ncore_filter_return_false()
{
    return false;
}

function ncore_wp_authenticate( $login, $password )
{
    $user = wp_authenticate( $login, $password );

    if ( is_wp_error($user) ) {
        $try_user = get_user_by( 'email', $login );
        if ($try_user) {
            $login = $try_user->user_login;
            $user = wp_authenticate( $login, $password );
        }
    }

    if ( is_wp_error($user) ) {

        $trimmed_password = trim( $password );
        $must_try_trimmed_pw = $password != $trimmed_password && strlen($trimmed_password) >= 8;
        if ($must_try_trimmed_pw) {
            return ncore_wp_authenticate( $login, $trimmed_password );
        }

        $code = $user->get_error_code();

        $msg = $code === 'incorrect_password'
             ? _ncore( '<strong>ERROR</strong>: The password you entered for the username <strong>%1$s</strong> is incorrect. <a href="%2$s" title="Password Lost and Found">Lost your password</a>?', $login, wp_lostpassword_url() )
             : $user->get_error_message();

        throw new Exception( $msg );
    }

    return $user;
}


function ncore_wp_login( $login, $password, $remember=false )
{
    $creds = array(
                "user_login"    => $login,
                "user_password" => $password,
                "remember"      => $remember
            );

    $user = wp_signon( $creds, false );

    if ( is_wp_error($user) ) {
        $try_user = get_user_by( 'email', $login );
        if ($try_user) {
            $creds[ 'user_login'] = $try_user->user_login;
            $user = wp_signon( $creds, false );
        }

    }

    if ( is_wp_error($user) ) {

        $code = $user->get_error_code();

        $msg = $code === 'incorrect_password'
             ? _ncore( '<strong>ERROR</strong>: The password you entered for the username <strong>%1$s</strong> is incorrect. <a href="%2$s" title="Password Lost and Found">Lost your password</a>?', $login, wp_lostpassword_url() )
             : $user->get_error_message();

        throw new Exception( $msg );
    }

    return $user;
}


function ncore_getBlogDomain( $blog_id )
{
    if (!$blog_id) {
        return '';
    }

    $blog = new StdClass;
    $blog->id   = $blog_id;
    $blog->name = '';

    $blog = apply_filters( 'ncore_get_blog_data', $blog );

    return $blog->name;
}

function ncore_blogExists( $blog_id )
{
    global $wpdb;

    $sql = "SELECT blog_id FROM $wpdb->blogs WHERE blog_id = $blog_id AND deleted = '0'";

    $have_blog = 0 < $wpdb->get_var( $sql );

    return $have_blog;
}


function ncore_wpMenuPosition( $position ) {

    static $used_wp_positions = array();

    $wp_start_pos = 99.16327; // make (allmost) sure, the wp  menu position is not used by another plugin

    $wp_pos = $wp_start_pos + $position / 1000000;

    while (in_array( $wp_pos, $used_wp_positions) )
    {
        $wp_pos += 0.00000001;
    }
    $used_wp_positions[] = $wp_pos;

    return "$wp_pos";
}

function ncore_wpSwitchToBlog( $blog_id ) {

    if (is_multisite())
    {
        switch_to_blog( $blog_id );
    }
}

function ncore_wpRestoreCurrentBlog() {

    if (is_multisite())
    {
        restore_current_blog();
    }
}

global $ncore_main_blog_id;
if (!isset($ncore_main_blog_id))
{
    global $blog_id;
    $ncore_main_blog_id = empty($blog_id)
                    ? 0
                    : $blog_id;
}

function ncore_isMainBlog()
{
    global $ncore_main_blog_id, $blog_id;
    return $ncore_main_blog_id == $blog_id;
}


function ncore_isOptimizePressPage() {
    return (defined( 'OP_PAGEBUILDER' ) && OP_PAGEBUILDER) || (defined('OP3_VERSION') && OP3_VERSION);
}

function ncore_isOptimizePressPageForId($pageId) {
    if (class_exists('\OPBuilder\Support\Tools')) {
        return \OPBuilder\Support\Tools::isOPPage($pageId);
    }
    return false;
}

function ncore_maxUploadFilesize()
{
    $sizes = array();
    $sizes[] = ini_get( 'upload_max_filesize' );
    $sizes[] = ini_get( 'post_max_size' );

    $min_size = false;

    foreach ($sizes  as $sSize)
    {
        if (is_numeric( $sSize)) {
            $iValue = (int) $sSize;
        }
        else
        {
            $sSuffix = substr($sSize, -1);
            $iValue = (int) substr($sSize, 0, -1);
            switch(strtoupper($sSuffix)){
            case 'P':
                $iValue *= 1024;
            case 'T':
                $iValue *= 1024;
            case 'G':
                $iValue *= 1024;
            case 'M':
                $iValue *= 1024;
            case 'K':
                $iValue *= 1024;
                break;
            }
        }

        if ($min_size===false || $min_size<$iValue)
        {
            $min_size = $iValue;
        }
    }

    return $min_size;
}


function ncore_memberCount( $force_reload=false )
{
    $count = $force_reload
           ? false
           : ncore_cacheRetrieve( 'blog_member_count' );

    if ($count===false) {

        global $wpdb;

        if (is_multisite())
        {
            $id = get_current_blog_id();
            $blog_prefix = $wpdb->get_blog_prefix($id);

            $sql = "SELECT COUNT(ID) as c
                    FROM $wpdb->users u
                    INNER JOIN $wpdb->usermeta m
                      ON u.ID = m.user_id
                    WHERE m.meta_key = '{$blog_prefix}capabilities'";
        }
        else
        {
            $sql = "SELECT COUNT(ID) as c FROM $wpdb->users";
        }

        $count = $wpdb->get_var( $sql );

        ncore_cacheStore( 'blog_member_count', $count, 80000 );
    }

    if (defined('THE_DIGIDEVEL_LIVES') && THE_DIGIDEVEL_LIVES)
    {
        $model = ddvl_api()->load->model( 'data/testdata' );
        $count = $model->testData( 'total_members', $count );
    }

    return $count;
}


function ncore_searchUsers( $search_for, $compare='LIKE' )
{
    $is_like = strtoupper(trim($compare)) == 'LIKE';

    $operator = $is_like
              ? 'LIKE'
              : '=';

    if ($is_like) {
        $search_for = '%' . $search_for . '%';
    }

    global $wpdb;

    if (is_multisite())
    {
        $id = get_current_blog_id();
        $blog_prefix = $wpdb->get_blog_prefix($id);

        $sql = "SELECT u.ID
                FROM $wpdb->users u
                INNER JOIN $wpdb->usermeta m
                  ON u.ID = m.user_id
                WHERE m.meta_key = '{$blog_prefix}capabilities'
                  AND (u.user_login $operator %s
                       OR u.user_email $operator %s)";
    }
    else
    {
        $sql = "SELECT u.ID
                FROM $wpdb->users u
                WHERE (u.user_login $operator %s
                       OR u.user_email $operator %s)";
    }

    $query = $wpdb->prepare( $sql, $search_for, $search_for );

    $user_ids = $wpdb->get_col( $query );

    return $user_ids;
}

function ncore_disableCaching()
{
    $contants = array(
        'DONOTCACHEPAGE' => 1,
    );

    foreach ($contants as $k => $v)
    {
        if (!defined( $k ))
        {
            define( $k, $v );
        }
    }
}

function ncore_deleteWpUser( $user_id  )
{
    $is_multisite = is_multisite();

    if ($is_multisite) {
        if (!function_exists('remove_user_from_blog'))
        {
            require_once ABSPATH.'wp-includes/ms-functions.php';
        }

        remove_user_from_blog($user_id );
    }
    else
    {
        if (!function_exists('wp_delete_user'))
        {
            require_once ABSPATH.'wp-admin/includes/user.php';
        }

        wp_delete_user( $user_id );
    }
}

function ncore_getUserManagementPage() {
    return admin_url('/users.php');
}


