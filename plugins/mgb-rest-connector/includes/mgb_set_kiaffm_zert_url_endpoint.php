<?php
function button_kiaffm_zert() {
    $user_id = get_current_user_id();
    $link = get_user_meta($user_id, 'kiaffm_zert_url', true);
    
    $inactive_css_class = "";

    // 119 is the digimember ID of the Zertifikatslehrgang: KI Consultant

    $user_orderdate = new DateTime(mgb_get_orderdate($user_id, 119));
    $today = new DateTime("now");
    $interval = $user_orderdate->diff($today);

    $days_since_order = $interval->days;

    if ((!$link) || ($days_since_order < 28)){
        $inactive_css_class = "mgb-btn-inactive";
    } 
    $button_html = '
    
    <div class="elementor-element elementor-element-b09167a elementor-align-left mgb-btn mgb-btn-primary '. $inactive_css_class .' elementor-widget__width-auto elementor-widget elementor-widget-button" data-id="b09167a" data-element_type="widget" data-widget_type="button.default">
	<div class="elementor-widget-container">
		<div class="elementor-button-wrapper">
			<a href="'. $link .'" class="elementor-button-link elementor-button elementor-size-sm" role="button" style="margin-top:20px">
				<span class="elementor-button-content-wrapper">
					<span class="elementor-button-text">Zertifikat Herunterladen</span>
				</span>
			</a>
		</div>
	</div>
</div>

    ';

    return $button_html; 

   
 }
 add_shortcode('button_kiaffm_zert', 'button_kiaffm_zert');



add_action( 'rest_api_init', function () {
    register_rest_route( 'mgb/v1', '/set_kiaffm_zert_url', array(
        'methods' => 'GET',
        'callback' => 'set_kiaffm_zert_url_endpoint',
        'permission_callback' => 'kiaffm_zert_url_api_key',
    ) );
});

function set_kiaffm_zert_url_endpoint(WP_REST_Request $request) {
    $params = $request->get_params();
    $user = get_user_by('email', $_GET['userParam']);
    $user_id =  $user->ID;
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
    if ( update_user_meta( $user_id, 'kiaffm_zert_url', $link ) ) {
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

function kiaffm_zert_url_api_key( $request ) {
    $params = $request->get_params();
    $api_key = $params['api_key'];
    $api_key_check = '4711caf4694fc1e440b3';


    // Zu überprüfender API KEY
    if ( $api_key !== $api_key_check ) {
        return new WP_Error( 'unauthorized', 'Invalid API key', array( 'status' => 401 ) );
    }
    return true;
}
