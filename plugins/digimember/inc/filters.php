<?php

add_filter( "pre_get_posts", 'digimember_supress_filter', 100 );
function digimember_supress_filter( $query ) {

    $must_supress = !ncore_isAdminArea() && !ncore_canAdmin();

    if ($must_supress) {
        $query->set('suppress_filters', false);
    }

    return $query;
}

add_filter('manage_users_columns', 'digimember_manage_users_columns');
function digimember_manage_users_columns( $columns ) {

    $columns['dm_login_counter']   = _digi( 'Number of logins' );
    $columns['dm_login_last_date'] = _digi( 'Last login' );
    $columns['user_registered']    = _digi( 'Registered' );
    $columns['dm_can_autologin']   = _digi( 'Can autologin' );

    return $columns;
}

add_action('manage_users_custom_column',  'digimember_manage_users_custom_column', 10, 3);
function digimember_manage_users_custom_column($value, $column_name, $user_id) {

    static $model, $user_model, $last_user_id, $count, $last_login;
    if (empty($model))
    {
        $api        = dm_api();
        $model      = $api->load->model( 'data/counter' );
        $user_model = $api->load->model( 'data/user' );
        $api->load->helper( 'date' );
    }

    $must_reload = empty($last_user_id) || $last_user_id != $user_id;
    if ($must_reload)
    {
        list( $count, $last_login ) = $model->getLoginCounter( $user_id );
        $last_user_id = $user_id;
    }

    switch ($column_name)
    {
        case 'dm_can_autologin':
            $can_auto_login = $user_model->canAutoLogin( $user_id );

            $tooltip = $can_auto_login
                     ? _digi( 'Autologin is enabled, because the user still has is auto generated password.' )
                     : _digi( 'Autologin is disabled, because the user changed his password.' );

            return $icon = ncore_icon( $can_auto_login ? 'yes' : 'no', $tooltip );

        case 'dm_login_counter':
            return $count;

        case 'dm_login_last_date':
            if (!$last_login)
            {
                return '-';
            }

            $now_unix  = time();
            $date_unix = strtotime( $last_login );
            $seconds   = max( 0, $now_unix - $date_unix );

            $age  = ncore_formatTimeSpan( $seconds, $message='ago', 'days+' );
            $date = ncore_formatDateTime( $date_unix );

            return "<abbr title=\"$date\">$age</abbr>";

        case 'user_registered':
            if ($value)
            {
                return $value;
            }
            $user = ncore_getUserById( $user_id );
            $registered_at = ncore_retrieve( $user, 'user_registered' );

            $now_unix   = time();
            $date_unix  = strtotime( $registered_at );
            $seconds    = max( 0, $now_unix - $date_unix );

            $age  = ncore_formatTimeSpan( $seconds, $message='ago', 'days+' );
            $date = ncore_formatDateTime( $date_unix );

            return "<abbr title=\"$date\">$age</abbr>";
    }

    return $value;

}

add_filter( 'manage_users_sortable_columns', 'digimember_manage_users_sortable_columns' );
function digimember_manage_users_sortable_columns( $columns ) {

    $columns['dm_login_counter']   = 'dm_login_counter';
    $columns['dm_login_last_date'] = 'dm_login_last_date';
    $columns['user_registered']    = 'dm_user_registered';

    return $columns;
}


// add_action('pre_user_query', 'digimember_pre_user_query');
add_action( 'pre_user_query', 'digimember_pre_user_query' );
function digimember_pre_user_query( $query ) {

    $order_by = $query->get( 'orderby' );
    switch ($order_by)
    {
        case 'dm_login_counter':
        case 'dm_login_last_date':

            global $wpdb;

            $wp_user = $wpdb->users;

            $model = dm_api()->load->model( 'data/counter' );
            $dm_counter = $model->sqlTableName();

            $column_map = array(
                'dm_login_counter'   => "$dm_counter.count",
                'dm_login_last_date' => "$dm_counter.modified",
            );


            $query->query_from .= " LEFT JOIN $dm_counter ON ($wp_user.ID = $dm_counter.user_id AND $dm_counter.name='login')";

            $column = $column_map[ $order_by ];


            $query->set( 'orderby', $column );

            $dir = $query->get('order') === 'DESC'
                 ? 'ASC'
                 : 'DESC';

            $query->query_orderby = "ORDER BY $column $dir";

            break;

        case 'dm_user_registered':

            $dir = $query->get('order') === 'DESC'
                 ? 'ASC'
                 : 'DESC';

            $query->set( 'orderby', 'user_registered' );
            $query->query_orderby = "ORDER BY user_registered $dir";
            break;
    }
}

add_filter('ds24_affiliate_for_link_generator', 'digimember_ds24_affiliate_for_link_generator');
function digimember_ds24_affiliate_for_link_generator( $preset_affiliate )
{
    return digimember_getDs24AffiliateName();
}

add_filter( 'plugin_locale', 'digimember_locale', 10, 2 );
function digimember_locale( $locale, $domain )
{
    if ($locale == 'de_DE') {
        return $locale;
    }

    $is_de = $locale[0] == 'd' && $locale[1] == 'e';
    if (!$is_de) {
        return $locale;
    }

    $my_domains = array( 'ncore', 'digimember', 'digimember-3rd-party', 'digimember_you', 'digimember_you_du_lower', 'digimember_you_du_upper' );
    if (!in_array( $domain, $my_domains))
    {
        return $locale;
    }

    return 'de_DE';
}

add_action( 'delete_user', 'digimember_delete_user' );
function digimember_delete_user( $user_id )
{
    $user_obj = get_userdata( $user_id );
    $email = $user_obj->user_email;
    
    $api = dm_api();
    $api->load->model( 'data/user' );
    $api->user_data->delete( $user_id );
    
    $lib = $api->load->library( 'autoresponder_handler' );
    $lib->maybeUnsubscribeDeletedUser( $email );
}


function digimember_enqueue_script() {
    wp_enqueue_script("jquery");
}
add_action( 'wp_enqueue_scripts', 'digimember_enqueue_script' );

