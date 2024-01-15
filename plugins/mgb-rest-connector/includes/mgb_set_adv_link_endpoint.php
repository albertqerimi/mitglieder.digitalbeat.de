<?php
function button_set_adv_link() {
    $user_id = get_current_user_id();
    $link = get_user_meta($user_id, 'adv_link_key', true);
    return $link;
 }
 add_shortcode('button_adv_link', 'button_set_adv_link');



add_action( 'rest_api_init', function () {
    register_rest_route( 'mgb/v1', '/set_adv_link', array(
        'methods' => 'GET',
        'callback' => 'set_adv_link_endpoint',
        'permission_callback' => 'adv_check_api_key',
    ) );
});

function set_adv_link_endpoint(WP_REST_Request $request) {
    $params = $request->get_params();
    $user = get_user_by( 'ID', $_GET['userParam'] );
    $user_id =  $_GET['userParam'];
    $link = $_GET['linkParam'];
    $parsed_url = parse_url( $link );
    


    // Zu überprüfender Benutzer
    if (!$user) {
        return new WP_Error( 'invalid_user_id', 'Die angegebene Benutzer-ID existiert nicht.', array( 'status' => 404 ) );
    }
    // Zu überprüfender Link
    if ( ! $parsed_url || empty( $parsed_url['scheme'] ) || empty( $parsed_url['host'] ) ) {
        return new WP_Error( 'invalid_link', 'Der angegebene Link entspricht nicht dem Linkformat', array( 'status' => 404 ) );
    }
    // Eintragen des Links in die Usermeta
    if ( update_user_meta( $user_id, 'adv_link_key', $link ) ) {
    } else {
        return new WP_Error( 'entry_failed', 'Fehler beim Eintragen des Links', array( 'status' => 404 ) );
    }

    // Feedback Message
    $response = array(
        'status' => 'success',
        'message' => 'Feedback submitted successfully',
        'userParam' => $_GET['userParam'],
        'linkParam' => $_GET['linkParam'],
    );

    return rest_ensure_response( $response );

}

function adv_check_api_key( $request ) {
    $params = $request->get_params();
    $api_key = $params['api_key'];
    $api_key_check = 'caf4694fc1e440b3aa74e498a00c6424';


    // Zu überprüfender API KEY
    if ( $api_key !== $api_key_check ) {
        return new WP_Error( 'unauthorized', 'Invalid API key', array( 'status' => 401 ) );
    }
    return true;
}
