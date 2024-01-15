<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * collection of misc helper functions 
 *
 * 
 */


// returns true or false 

function mgb_has_product($user_id, $product_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'digimember_user_product';

    $sql = 'SELECT EXISTS(SELECT 1 FROM ' . $table . ' WHERE user_id=' . $user_id . ' AND product_id=' . $product_id . ' AND is_active = "Y");'; 
   
    $result = boolval($wpdb->get_var($sql));
    
    return $result;
}

// returns order date as YYYY-MM-DD without time

function mgb_get_orderdate($user_id, $product_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'digimember_user_product';

    $sql = 'SELECT date(order_date) as orderdate FROM ' . $table . ' WHERE user_id=' . $user_id . ' AND product_id=' . $product_id . ' AND is_active = "Y";'; 
   
    $result = $wpdb->get_results($sql);

    if (empty($result)) {
        
        return "2022-08-01";
    
    }
    
    return  $result[0]->orderdate;
}

// returns array of product_ids(string) or false 

function mgb_list_products($user_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'digimember_user_product';

    $sql = 'SELECT product_id FROM ' . $table . ' WHERE user_id=' . $user_id . ' AND is_active = "Y";';
    
    $result = array_merge(...$wpdb->get_results($sql, 'ARRAY_N'));
    
    if (empty($result)) {
        
        return false;
    
    }

    return $result;
}


function post_to_dm($post_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'digimember_page_product';

    $sql = 'SELECT product_id FROM ' . $table . ' WHERE post_id=' . $post_id . ' AND is_active = "Y" LIMIT 1;';
    $result = $wpdb->get_var($sql);
   
    if (empty($result)) {
        
        return false;
    
    }

    return intval($result);
}

function dm_to_post($product_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'digimember_page_product';

    $sql = 'SELECT post_id FROM ' . $table . ' WHERE product_id=' . $product_id . ' AND is_active = "Y";';
    $result = array_merge(...$wpdb->get_results($sql, 'ARRAY_N'));
   
    if (empty($result)) {
        
        return false;
    
    }

    return $result;
}

// add custom css class to body-tag  

add_filter( 'body_class','mgb_body_class' );

function mgb_body_class( $classes ) {

    $classes = array();
    
    if ('gruender_product' != get_post_type()){

        return $classes;

    }

    $body_css = get_field('body_css');

    $classes[] = $body_css; 
 
    return $classes;
 
}

// breadcrumb

function mgb_render_breadcrumb(){

    global $post;
    
    if ("0" == get_query_var('modul','0') ? $modul=false : $modul=true);

    $breadcrumb = "<ul class='mgb-breadcrumb'>";
    $breadcrumb .= "<li><a href='". get_home_url() ."'>Home</a></li>";
    
    if ($modul){

        $product_category = get_field('kategorie', $post->ID); 

        if ($product_category == 'kickstart-coaching'){

            $breadcrumb .= "<li><a href='". get_permalink($post->ID) ."'>".$post->post_title."</a></li>";
            $breadcrumb .= "<li>".get_field('kickstart_coaching_daten', $post->ID)['kp_module'][get_query_var('modul') - 1]['modul_title']."</li>";

        }

        if (($product_category == 'komplett-produkt') || (($product_category == 'zertifizierungslehrgang'))){

            $breadcrumb .= "<li><a href='". get_permalink($post->ID) ."'>".$post->post_title."</a></li>";
            $breadcrumb .= "<li>".get_field('komplett_produkt_daten', $post->ID)['kp_module'][get_query_var('modul') - 1]['modul_title']."</li>";

        }

        if ($product_category == 'traffic-masterplan'){

            $breadcrumb .= "<li><a href='". get_permalink($post->ID) ."'>".$post->post_title."</a></li>";
            $breadcrumb .= "<li>".get_field('traffic_masterplan_daten', $post->ID)['kp_module'][get_query_var('modul') - 1]['modul_title']."</li>";

        }

        if ($product_category == 'live-coaching'){

            $modul = get_query_var('modul','0');
            $breadcrumb .= "<li><a href='". get_permalink($post->ID) ."'>".$post->post_title."</a></li>";
            $modulname = match($modul){
                "1" => "Mediathek", 
                "2" => "Toolbox",
                "3" => "Boni", 
            };
            
            $breadcrumb .= "<li>". $modulname ."</li>";

        }

        
    } else {

        $breadcrumb .= "<li>".$post->post_title."</li>";

    }
        
    $breadcrumb .= "</ul>"; 

    return $breadcrumb;
}

add_shortcode('mgb_breadcrumb', 'mgb_render_breadcrumb');


// redirect logged in users from login or login-newsletter website to home

add_action('init' , function() {
    
    $uri = $_SERVER['REQUEST_URI'];
    
    if( (($uri == '/login/') || ($uri == '/login-newsletter/')) && is_user_logged_in() ) {
        wp_redirect(get_home_url());
        exit();
    }
});


