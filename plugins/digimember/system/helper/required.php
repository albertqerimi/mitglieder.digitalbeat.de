<?php

function ncore_localUrl(  $path = '' )
{
    static $base_path;
    if (!isset($base_path))
    {
        $url = site_url( '/' );

        $base_path = '/' . trim( parse_url( $url, PHP_URL_PATH ), '/' );
    }

    if (!$path)
    {
        return $base_path;
    }

    $is_absolute = $path[0] == '/';

    return $is_absolute
           ? $base_path . $path
           : "$base_path/$path";
}

function ncore_siteUrl(  $path = '' )
{
    $is_ssl = is_ssl();
    $scheme = $is_ssl ? 'https' : 'http';

    if (!$is_ssl
        && force_ssl_admin()
        && function_exists( 'ncore_isAdminArea' )
        && ncore_isAdminArea())
    {
        $scheme = 'https';
    }

    $url = site_url( $path, $scheme );

    $have_no_scheme = $url && $url[0] == '/' && strlen($url)>=5 && $url[1] == '/';
    if ($have_no_scheme) {
        $url = $scheme . ':' . $url;
    }

    return $url;
}

function ncore_buildPostURL($post_id,$args = array()) {
    return ($post_id != 0) ? ncore_addArgs(get_post_permalink($post_id),$args,'&') : '';
}

function ncore_getTimezones() {
    static $regions = array(
        DateTimeZone::AFRICA,
        DateTimeZone::AMERICA,
        DateTimeZone::ANTARCTICA,
        DateTimeZone::ASIA,
        DateTimeZone::ATLANTIC,
        DateTimeZone::AUSTRALIA,
        DateTimeZone::EUROPE,
        DateTimeZone::INDIAN,
        DateTimeZone::PACIFIC,
    );

    $timezones = array();
    foreach ($regions as $region) {
        $timezones = array_merge( $timezones, DateTimeZone::listIdentifiers( $region ) );
    }

    $timezone_offsets = array();
    foreach ($timezones as $timezone) {
        $tz = new DateTimeZone($timezone);
        $timezone_offsets[$timezone] = $tz->getOffset(new DateTime);
    }

    asort($timezone_offsets);

    $timezone_list = array();
    foreach ($timezone_offsets as $timezone => $offset) {
        $offset_prefix = $offset < 0 ? '-' : '+';
        $offset_formatted = gmdate('H:i',abs($offset));

        $pretty_offset = 'GMT'.$offset_prefix.$offset_formatted;

        $timezone_list[$timezone] = '('.$pretty_offset.') '.$timezone;
    }

    return $timezone_list;
}

function ncore_readFileContents($file_path) {
    return (file_exists($file_path)) ? file_get_contents($file_path) : '';
}

function ncore_isSerialized($data, $strict = true) {
    if (function_exists('is_serialized')) {
        return is_serialized($data, $strict);
    }

    // SOURCE: WORDPRESS functions.php
    // if it isn't a string, it isn't serialized.
    if ( ! is_string( $data ) ) {
        return false;
    }
    $data = trim( $data );
    if ( 'N;' == $data ) {
        return true;
    }
    if ( strlen( $data ) < 4 ) {
        return false;
    }
    if ( ':' !== $data[1] ) {
        return false;
    }
    if ( $strict ) {
        $lastc = substr( $data, -1 );
        if ( ';' !== $lastc && '}' !== $lastc ) {
             return false;
        }
    }
    else {
        $semicolon = strpos( $data, ';' );
        $brace     = strpos( $data, '}' );
        // Either ; or } must exist.
        if ( false === $semicolon && false === $brace )
             return false;
        // But neither must be in the first X characters.
        if ( false !== $semicolon && $semicolon < 3 )
             return false;
        if ( false !== $brace && $brace < 4 )
             return false;
    }
    $token = $data[0];
    switch ( $token ) {
        case 's' :
             if ( $strict ) {
                if ( '"' !== substr( $data, -2, 1 ) ) {
                    return false;
                }
             }
             elseif ( false === strpos( $data, '"' ) ) {
                return false;
             }
             // or else fall through
        case 'a' :
        case 'O' :
            return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
        case 'b' :
        case 'i' :
        case 'd' :
            $end = $strict ? '$' : '';
            return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
    }
    return false;
}

function ncore_getWPHeader() {
    return (function_exists('get_header')) ? get_header() : '';
}

function ncore_getWPFooter() {
    return (function_exists('get_footer')) ? get_footer() : '';
}

function ncore_canAdmin( $user_obj_or_id='current', $blog_id='current' )
{
    if ($user_obj_or_id==='current')
    {
        $user_id        = ncore_userId();
        $user_obj_or_id = $user_id;
    }
    elseif (is_object( $user_obj_or_id ))
    {
        $user_id = ncore_retrieve( $user_obj_or_id, array( 'ID', 'id' ) );
    }
    else
    {
        $user_id = $user_obj_or_id;
    }

    if (!$user_id)
    {
        return false;
    }

    $is_current_blog = $blog_id === 'current';
    if ($is_current_blog) {
        $blog_id = ncore_blogId();
    }
    $blog_id = ncore_washInt( $blog_id );

    static $cache;
    $can_admin =& $cache[$blog_id][$user_id];

    if (!isset($can_admin))
    {
        if ($is_current_blog)
        {
            $can_admin = user_can( $user_id, 'manage_options' );
        }
        else
        {
            ncore_wpSwitchToBlog( $blog_id );
            $can_admin = user_can( $user_id, 'manage_options' );
            ncore_wpRestoreCurrentBlog();
        }
    }

    return $can_admin;
}

function ncore_canAccessAdminArea( $user_obj_or_id='current', $blog_id='current' )
{
    return ncore_userHasRole( array( 'administrator', 'editor' ), $user_obj_or_id, $blog_id );
}

function ncore_userHasRole( $role_or_roles, $user_obj_or_id='current', $blog_id='current' )
{
    if ($user_obj_or_id==='current')
    {
        $user_id        = ncore_userId();
    }
    elseif (is_object( $user_obj_or_id ))
    {
        $user_id = ncore_retrieve( $user_obj_or_id, array( 'ID', 'id' ) );
    }
    else
    {
        $user_id = $user_obj_or_id;
    }

    if (!$user_id)
    {
        return false;
    }

    $is_current_blog = $blog_id === 'current';
    if ($is_current_blog) {
        $blog_id = ncore_blogId();
    }
    $blog_id = ncore_washInt( $blog_id );

    static $cache;
    $roles_of_user =& $cache[$blog_id][$user_id];

    $roles_to_test = is_array( $role_or_roles )
                   ? $role_or_roles
                   : explode( ',' , $role_or_roles );

    if (!isset($roles_of_user))
    {
        if (!$is_current_blog)
        {
            ncore_wpSwitchToBlog( $blog_id );
        }

        $user = get_userdata( $user_id );

        $roles_of_user = ncore_retrieve( $user, 'roles', array() );

        if (!$is_current_blog)
        {
            ncore_wpRestoreCurrentBlog();
        }
    }

    foreach ($roles_to_test as $role)
    {
        $role = trim( $role );

        $has_role = in_array( $role, $roles_of_user );

        if ($has_role)
        {
            return true;
        }
    }

    return false;
}

function ncore_canNetworkAdmin( $user_id=0 )
{
    if (!$user_id)
    {
        $user_id = ncore_userId();
    }

    if (!$user_id)
    {
        return false;
    }

    static $cache;
    $can_admin =& $cache[$user_id];

    if (!isset($can_admin))
    {
        $can_admin = user_can( $user_id, 'manage_network' );
    }

    return $can_admin;
}

function ncore_isAjax()
{
    if (defined('NCORE_IS_AJAX') && NCORE_IS_AJAX) {
        return true;
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return true;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return true;
    }

    return false;
}

/**
 * Returns if a sepcified $id is existent in a $list that is seperated by $seperator
 * @param string|int $id
 * @param string|array $list
 * @param string $seperator ',' by default
 * @return bool true if $id or 'all' is in $list, false otherwise
 */
function ncore_idIsInList( $id, $list, $seperator=',') {

    if ($list === 'all') {
        return true;
    }
    if (!is_array($list)) {
        $list = explode( $seperator, $list );
    }

    if (in_array( 'all', $list, true)) {
        return true;
    }

    if (!$id) {
        return false;
    }

    return in_array( $id, $list );
}


/**
 * Returns $default (NULL by default) if $val is NULL, else returns $val itself.
 * @param string $val must be called by reference
 * @param null $default
 * @return ?string
 */
function ncore_val( $val, $default = NULL ) {
    return $val !== '' ? $val : $default;
}

/**
 * Returns true if $value is true and false if it is false.
 * @param string, int, bool $value expects something with the meaning of "true" or "false" e.g. 'Y', 1, true or 'N', 0, "", false
 * @return bool
 */
function ncore_isTrue( $value )
{
    if ($value === 'Y') return true;
    if ($value === 'N') return false;

    return (bool) $value;
}

/**
 * Returns true if $value is false and false if it is true,
 * @param String $value expects something with the meaning of "true" or "false" e.g. 'Y', 1, true or 'N', 0, "", false
 * @return bool
 */
function ncore_isFalse( $value )
{
    return !ncore_isTrue( $value );
}

/**
 * Returns 'Y' if $value is true and 'N' otherwise
 * @param string|bool|int $value
 * @return string 'Y' or 'N' are returned
 */
function ncore_toYesNoBit( $value )
{
    return ncore_isTrue( $value )
           ? 'Y'
           : 'N';
}

function ncore_id( $basename = 'id', $format='numeric' )
{
    if ($format==='alpha')
    {
        static $counter;
        if (empty($counter)) {
            $counter = 1;
        } else {
            $counter ++;
        }
        $id = $basename;
        $counter_str = "$counter";
        $len = strlen($counter_str);
        for ($i=0; $i<$len; $i++)
        {
            $id .= chr( ord('a')+ (int) $counter_str[$i]-1 );
        }
        return $id;
    }



    static $last_id;
    if ( !isset( $last_id ) )
    {
        $last_id = (time() % 1000) . sprintf( '%07d', rand( 0, 1000000 ) );
    }
    $last_id++;


    $baseName = str_replace( array(
         "\\",
        '/',
        ' '
    ), '_', $basename );

    $html_id = strtolower( $baseName . '_' . $last_id );

    return $html_id;
}


function _ncore( $string, $var1 = false, $var2 = false, $var3 = false )
{
    static $initialized;
    if (!$initialized)
    {
        $initialized = true;
        $dir = NCORE_SYSTEM_OWNER;
        load_plugin_textdomain( 'ncore', false, "$dir/languages" );
    }

    return $var1 === false ? __( $string, 'ncore' ) : sprintf( __( $string, 'ncore' ), $var1, $var2, $var3 );
}


function ncore_wordGlue()
{
    $default_glue = ' ';

    $glues_by_lang = array(
        'de' => '-',
    );

    $lang = substr( get_locale(), 0, 2 );

    return ncore_retrieve( $glues_by_lang, $lang, $default_glue );
}


function ncore_camelCase( $string_with_underscores, $seperator='' )
{
    $camel_cased = '';

    $tokens = explode( '_', str_replace( '/', '_', $string_with_underscores ) );

    foreach ( $tokens as $one )
    {
        if ( $one )
        {
            if ($camel_cased) {
                $camel_cased .= $seperator;
            }
            $camel_cased .= ucfirst( $one );
        }
    }

    return $camel_cased;
}


function ncore_sanitizeDbDate( &$date )
{
    if (!isset($date)) {
        return;
    }

    if (!$date) {
        $date = 'NULL';
        return;
    }

    $is_null = strpos( $date, '0000-00-00' ) !== false;
    if ($is_null) {
        $date = 'NULL';
        return;
    }

    $is_null = strpos( $date, '-' ) === false;
    if ($is_null) {
        $date = 'NULL';
        return;
    }
}

function ncore_dbDate( $readable_date = 'now', $format='full', $timezone = 'default' )
{
    $unix_date = ncore_unixDate( $readable_date );

    $current_time_zone = ncore_setServerTimeZone( $timezone );

    switch ($format)
    {
        case 'date':
            $format = "Y-m-d";
            break;

        case 'full':
            $format = "Y-m-d H:i:s";
            break;

        case '1st_day_of_month':
            $format = "Y-m-01";
            break;
    }

    $date_valid = $unix_date > 0;
    $date       = $date_valid ? date( $format, $unix_date ) : null;

    ncore_unsetServerTimeZone( $current_time_zone );

    return $date;
}

function ncore_unixDate( $readable_date = 'now' )
{
    $current_time_zone = ncore_setServerTimeZone();

    if ( $readable_date == 'now' )
    {
        $date_unix = ncore_serverTime();
    }
    elseif ( is_numeric( $readable_date ) )
    {
        $date_unix = $readable_date;
    }
    else
    {
        $date_unix = strtotime( $readable_date );
    }

    ncore_unsetServerTimeZone( $current_time_zone );

    return $date_unix;
}



function ncore_serverTime()
{
    $current_time_zone = ncore_setServerTimeZone();

    $now = time();

    ncore_unsetServerTimeZone( $current_time_zone );

    return $now;
}

function ncore_stringStartsWith($string, $substring)
{
    if ( !is_string($string) || !is_string($substring) || !$string || !$substring )
    {
        return false;
    }

    if ( $string[ 0 ] != $substring[ 0 ] )
    {
        return false;
    }

    $have_mb = function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' );
    if ($have_mb)
    {
        $len = mb_strlen( $substring );
        $head = mb_substr( $string, 0, $len );
    }
    else
    {
        $len = strlen( $substring );
        $head = substr( $string, 0, $len );
    }

    $matches = $head == $substring;
    return $matches;
}

function ncore_stringEndsWith( $string, $substring )
{
    if ( !$string || !$substring )
    {
        return false;
    }

    $have_mb = function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' );
    if ($have_mb)
    {
        $len = mb_strlen( $substring );
        $tail = mb_substr( $string, -$len );
    }
    else
    {
        $len = strlen( $substring );
        $tail = substr( $string, -$len );
    }

    $matches = $tail == $substring;
    return $matches;
}




function ncore_dump()
{
    if ( NCORE_DEBUG )
    {
        foreach ( func_get_args() as $arg )
        {
            echo '<pre>';
            print_r( $arg );
            echo '</pre>';
        }
        exit;
    }
}


function ncoreDebugTrace()
{
    $trace = debug_backtrace();

    $text = '';

    $i = 1;

    foreach ( $trace as $call )
    {
        $file     = ncore_retrieve( $call, 'file' );
        $line     = ncore_retrieve( $call, 'line' );
        $function = ncore_retrieve( $call, 'function' );
        $class    = ncore_retrieve( $call, 'class' );
        $args     = ncore_retrieve( $call, 'args', array ());

        if ( $file )
        {
            $root_dir = ncore_api()->rootDir();
            $file     = str_replace( $root_dir, '', $file );
        }

        if ( $class )
        {
            $function = $class . '::' . $function;
        }

        $text .= "$i) $function    ($file:$line)
Args = ";

        foreach ( $args as $arg )
        {
            $is_object = is_object( $arg );

            if ( $is_object )
            {
                $class = get_class( $arg );
                $text .= "    Object: $class\n";
            }
            else
            {
                ob_start();
                echo "    ";
                var_dump( $arg );
                $arg_text = ob_get_clean();

                $text .= substr( $arg_text, 0, 50 );
            }
        }

        $text .= "\n\n";

        $i++;
    }

    return $text;
}

function ncore_stripSlashes( $value )
{
    if ( is_array( $value ) )
    {
        $value = array_map( 'ncore_stripSlashes', $value );
    }
    elseif ( is_object( $value ) )
    {
        $vars = get_object_vars( $value );
        foreach ( $vars as $key => $data )
        {
            $value->{$key} = ncore_stripSlashes( $data );
        }
    }
    else
    {
        $value = stripslashes( $value );
    }

    return $value;
}

function ncore_minifyHtml( $html )
{
    if (NCORE_DEBUG)
    {
        return $html;
    }

    return str_replace( array( "\r\n", "\r", "\n" ), ' ', $html );
}

function ncore_minifyJs( $js_code )
{
    if (!NCORE_MINIFY_JS)
    {
        return $js_code;
    }

    // Must "minify" code to work with plugins/themes, who add <p> for linebreaks
    // e.g. the Sterling Theme on digimember.de

    $js_code = str_replace( array( "\r\n", "\r", "\n" ), "\n", trim($js_code) );

    $lines = explode( "\n", $js_code );
    $result = '';
    foreach ($lines as $line)
    {
        $line = trim( $line );
        if ($line)
        {
            $result .= ' ' . trim( $line );
        }
    }

    if ($result)
    {
        $result = trim( $result, ";" ) . ';';
    }

    return $result;
}

function ncore_retrieveMaybeArray($array_or_object, $key_or_keys, $default = '')
{
    // Do not handle array of keys right now, might add later (jsiebern, 2020-10-05)
    if (is_array($key_or_keys) || (is_string($key_or_keys) && strpos($key_or_keys, '[') === false )) {
       return ncore_retrieve($array_or_object, $key_or_keys, $default);
    }
    $matches = null;
    preg_match('/([a-zA-Z0-9-_]*)\[([a-zA-Z0-9-_\[\]]*)\]/m', $key_or_keys, $matches);
    if (is_array($matches) && count($matches) == 3) {
        $key = $matches[1];
        $subKey = $matches[2];
        $value = ncore_retrieve($array_or_object, $key, $default);
        if (is_array($value)) {
            return ncore_retrieveMaybeArray($value, $subKey, $default);
        }
    }
    return ncore_retrieve($array_or_object, $key_or_keys, $default);
}

function ncore_retrieveGET( $key, $default='' )
{
    static $get;

    if (!isset($get))
    {
        $get = ncore_stripSlashes( $_GET );
    }

    $value = ncore_retrieveMaybeArray( $get, $key );
    if (!$value) {
        $value = ncore_retrieve( $get, "amp;$key", $default );
    }

    return $value;

}

function ncore_retrievePOST( $key, $default='' )
{
    static $post;

    if (!isset($post))
    {
        $post = ncore_stripSlashes( $_POST );
    }

    return ncore_retrieveMaybeArray( $post, $key, $default );
}

function ncore_retrieveJSON( $key, $default = '' )
{
    static $json;

    if (!isset($json)) {
        try {
            $json = json_decode(file_get_contents( 'php://input' ), true);
        } catch (Exception $e) {
            $json = false;
        }
    }
    if ($json === false) {
        return $default;
    }

    return ncore_retrieveMaybeArray( $json, $key, $default );
}

function ncore_retrieveREQUEST( $key, $default='' )
{
    $value = ncore_retrievePOST( $key, false );
    if ($value === false)
    {
        $value = ncore_retrieveGET( $key, false );
    }
    if ($value === false) {
        $value = ncore_retrieveJSON( $key, $default );
    }

    return $value;
}

function ncore_cacheStore( $cache_key, $value, $expire_seconds=3600 )
{
    global $ncore_static_cache;

    if (empty($ncore_static_cache)) {
        $ncore_static_cache[ $cache_key ] = $value;
    }

    set_transient( 'N1C_'.$cache_key, $value, $expire_seconds );
}

function ncore_cacheRetrieve( $cache_key )
{
    global $ncore_static_cache;

    $value =& $ncore_static_cache[ $cache_key ];

    if (!isset($value)) {
        $value = get_transient( 'N1C_'.$cache_key );
    }

    return $value;
}

function ncore_getEmailConfig()
{
    /** @var digimember_BlogConfigLogic $config */
    $config = ncore_api()->load->model( 'logic/blog_config' );

    $use_smtp_mail = (bool) $config->get( 'use_smtp_mail' );

    $smtp_host = $config->get( 'smtp_host' );
    $smtp_port = $config->get( 'smtp_port' );

    $smtp_security  = $config->get( 'smtp_secure_type' );
    $smtp_user_name = $config->get( 'smtp_user_name' );
    $smtp_user_pass = $smtp_user_name
                    ? $config->get( 'smtp_user_password' )
                    : '';

    $sender_email = $config->get( 'mail_sender_email' );
    if ( !$sender_email )
    {
        $sender_email = get_bloginfo('admin_email');
    }

    $sender_name = $config->get( 'mail_sender_name' );
    if (!$sender_name) {
        $sender_name = $sender_email;
    }

    $reply_email  = $config->get( 'mail_reply_email' );

    if (!$smtp_host || !$smtp_port)
    {
        $use_smtp_mail = false;
    }

    return array( $use_smtp_mail,
                  $smtp_host,
                  $smtp_port,
                  $smtp_security,
                  $smtp_user_name,
                  $smtp_user_pass,
                  $sender_email,
                  $sender_name,
                  $reply_email  );
}


function ncore_getServerTimeZone()
{
    $server_time_zone = ini_get( 'date.timezone' );
    return $server_time_zone;
}

function ncore_setServerTimeZone( $timezone='default' ) {

    $current_time_zone = date_default_timezone_get();

    $server_time_zone = $timezone === 'default'
                        ? ncore_getServerTimeZone()
                        : $timezone;

    if ($server_time_zone) {
        date_default_timezone_set( $server_time_zone );
        return $current_time_zone;
    }

    return false;
}

function ncore_unsetServerTimeZone( $local_time_zone ) {
    if ($local_time_zone) {
        date_default_timezone_set( $local_time_zone );
    }
}

/**
 * @param string $icon
 * @param string $fallback
 * @return string
 */
function ncore_getIconClassFromMap($icon, $fallback = '')
{
    $iconMap = [
        'success' => 'ok-circled',
        'error' => 'attention-circled',
        'info' => 'info-circled'
    ];
    return ncore_retrieve($iconMap, $icon, $fallback ? $fallback : $icon);
}

/**
 * @param string      $type error | success | info
 * @param string      $title
 * @param string      $icon
 * @param string      $message
 * @param string      $buttons
 * @param bool|string $dismissKey
 * @return string
 */
function ncore_htmlAlert($type, $title, $icon = '', $message = '', $buttons = '', $dismissKey = false)
{
    $icon = $icon ? '
<div class="dm-alert-icon">
    <span class="dm-icon icon-' . ncore_getIconClassFromMap($icon, 'info-circled'). ' dm-color-' . $type . '"></span>
</div>
' : $icon;
    $message = $message ? '
<p>
    ' . $message . '
</p>
' : $message;
    $dismissButton = '';
    if ($dismissKey) {
        /** @var ncore_CloseWindowLogic $model */
        $model = ncore_api()->load->model( 'logic/close_window' );
        list ($label, $js) = $model->attachCloseButton( $dismissKey );
        $dismissButton = '<button class="dm-btn dm-btn-icon dm-btn-error dm-admin-notice-close-button" onclick="' . $js . '" data-title="' . $label . '">
    <span class="dm-icon icon-cancel-circled"></span>
</button>';
    }
    $buttons = $buttons || $dismissButton ? '
<div class="dm-alert-buttons">
    '. $buttons .'
    '. $dismissButton .'
</div>
' : $buttons;

    return '
<div class="dm-alert dm-alert-' . $type . '">
    ' . $icon . '
    <div class="dm-alert-content">
        <label>' . $title . '</label>
        ' . $message . '
    </div>
    ' . $buttons . '
</div>
';
}


function ncore_renderMessage( $msg_type, $msg_text, $tag='div', $dismiss_key=false )
{
    $typeMap = array(
        NCORE_NOTIFY_INFO     => 'info',
        NCORE_NOTIFY_SUCCESS  => 'success',
        NCORE_NOTIFY_WARNING  => 'warning',
        NCORE_NOTIFY_ERROR    => 'error',
    );
    $iconMap = array(
        NCORE_NOTIFY_INFO     => 'info',
        NCORE_NOTIFY_SUCCESS  => 'success',
        NCORE_NOTIFY_WARNING  => 'error',
        NCORE_NOTIFY_ERROR    => 'error',
    );

    $type = $typeMap[$msg_type];
    $icon = $iconMap[$msg_type];

    ncore_api()->load->helper('html_input');

    $buttons = '';
    if (strpos($msg_text, 'bsp;<butt') !== false) {
        $split = preg_split('/\&nbsp;<button(.*)<\/button>/', $msg_text, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $button = ncore_retrieve($split, 1, '');
        $button = $button ? '<button' . $button . '</button>' : '';
        $buttons .= $button;
        $msg_text = ncore_retrieve($split, 0, '');
    }
    return ncore_htmlAlert($type, $msg_text, $icon, '', $buttons, $dismiss_key);
}



function ncore_flashMessage( $msg_type, $msg_text, $msg_key=false, $dismiss_key=false )
{
    if ($dismiss_key) {
        /** @var ncore_CloseWindowLogic $model */
        $model = ncore_api()->load->model( 'logic/close_window' );
        $is_closed = $model->isWindowClosed( $dismiss_key );
        if ($is_closed) {
            return;
        }
    }

    /** @var ncore_SessionLogic $model */
    $model = ncore_api()->load->model( 'logic/session' );

    $msg_list = $model->get( 'flash_messages' );

    if (empty($msg_list)) {
        $msg_list = array();
    }

    foreach ($msg_list as $one)
    {
        if ($one['text'] == $msg_text
            || ($msg_key && $one['key']==$msg_key)) {
            return;
        }
    }

    $msg_list[] = array(
        'type'        => $msg_type,
        'text'        => $msg_text,
        'key'         => $msg_key,
        'dismiss_key' => $dismiss_key,
    );

    $model->set( 'flash_messages', $msg_list );
}

function ncore_getFlashMessages() {
    /** @var ncore_SessionLogic $model */
    $model = ncore_api()->load->model( 'logic/session' );
    $msg_list = $model->get( 'flash_messages', array() );
    if ($msg_list) {
        $model->set( 'flash_messages' );
    }

    return $msg_list;
}

function ncore_resetFlashMessages()
{
    ncore_getFlashMessages();
}

function ncore_renderFlashMessages() {

    $msg_list = ncore_getFlashMessages();

    $html = '';

    foreach ($msg_list as $msg)
    {
        $type = ncore_retrieve( $msg, 'type' );
        $text = ncore_retrieve( $msg, 'text' );

        $dismiss_key = ncore_retrieve( $msg, 'dismiss_key' );;

        $html .= ncore_renderMessage( $type, $text, $tag='default', $dismiss_key );
    }

    return $html;
}

function ncore_printFlashMessages()
{
    echo ncore_renderFlashMessages();
}

function ncore_flashMessageInit()
{
    static $initialized;

    if (empty($initialized)) {
        $initialized = true;
        add_action( 'admin_notices', 'ncore_printFlashMessages' );
    }
}


function ncore_callUserFunction( $meta, $args=array() )
{
    $model  = @$meta['model'];
    $lib    = array_key_exists('lib', $meta) ? @$meta['lib']: false;
    $method = @$meta['method'];

    if ($model && is_string($model)) {

        $api = $meta['api'];

        if (is_string($api)) {
            $api = $api();
        }

        $model = $api->load->model( $model );
    }

    if ($lib && is_string($lib)) {
        $api = $meta['api'];

        if (is_string($api)) {
            $api = $api();
        }

        $lib = $api->load->library( $lib );
    }

    $obj = $model ? $model : $lib;

    $callable = array( $obj, $method );

    return call_user_func_array( $callable, $args );
}


function ncore_assert( $condition_that_must_be_true, $error_hint='' )
{
    if ($condition_that_must_be_true) {
        return;
    }

    if (!$error_hint) {
        $error_hint = 'An assertation failed';
    }

    trigger_error( $error_hint );
}

function ncore_clientIp( $void_value='', $use_clear_text=false )
{
    $ip_or_list = ncore_retrieve( $_SERVER, array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ), $void_value );

    $ip_or_list = ncore_washText( $ip_or_list, ':.' );

    $ips = explode( ' ', $ip_or_list );

    $ip_v4 = false;
    $ip_v6 = false;

    foreach ($ips as $ip) {
        $is_v4 = strpos( $ip, '.' ) !== false;
        if ($is_v4) {
            $ip_v4 = $ip;
            continue;
        }

        $is_v6 = strpos( $ip, ':' ) !== false;
        if ($is_v6) {
            $ip_v6 = $ip;
            continue;
        }
    }

    $ip = $ip_v4 ? $ip_v4 : $ip_v6;

    if (!$use_clear_text)
    {
//        $mockRandom = random_int(1,2000);
//        $ip = md5( substr( $ip, 1 ).$mockRandom );
        $ip = md5( substr( $ip, 1 ) );
    }

    return $ip;
}

function ncore_setcookie($name, $value = "", $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false)
{
    if (function_exists('is_php_version_compatible')) {
        if (is_php_version_compatible('7.3')) {
            return setcookie($name, $value, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => 'Lax',
            ]);
        }
    }

    return setcookie($name, $value, $expire, $path . '; samesite=Lax', $domain, $secure, $httponly);
}
