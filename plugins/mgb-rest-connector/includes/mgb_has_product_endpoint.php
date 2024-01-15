<?php

/**
 * this endpoint allows to check if a user has an active product or not
 */

defined( 'ABSPATH' ) || exit;

add_action('rest_api_init', function () {
    register_rest_route( 'mgb/v1', '/hasproduct', array(
      'methods' => 'GET',
      'callback' => 'mgb_has_product_endpoint',
      'permission_callback' => '__return_true',
    ));
} );


function mgb_has_product_endpoint(WP_REST_Request $request) {
          
    global $wpdb;

    $rest_key = 'ai1sdn5aisoh3cioash5ciDFGasncuibfuisnbfsd645ofdwa23qpd4kpSAFHASDFO';

    if ($rest_key!=$request['key']){

        return "WRONG KEY PROVIDED";

    }    

    $user_id = intval($request['uid']);
    $prod_id = intval($request['pid']);

    $table = $wpdb->prefix . 'digimember_user_product';

    $sql = 'SELECT EXISTS(SELECT 1 FROM ' . $table . ' WHERE user_id=' . $user_id . ' AND product_id=' . $prod_id . ' AND is_active = "Y");'; 
   
    $active_result = boolval($wpdb->get_var($sql));
    
    
    if ($active_result){

        return "ACTIVE";

    }

    $sql = 'SELECT EXISTS(SELECT 1 FROM ' . $table . ' WHERE user_id=' . $user_id . ' AND product_id=' . $prod_id . ' AND is_active = "N");'; 
   
    $passive_result = boolval($wpdb->get_var($sql));
    
    if ($passive_result){

        return "PASSIVE";

    }
    
    return "ORDER OF USER $user_id AND PRODUCT WITH ID $prod_id NOT FOUND";

}