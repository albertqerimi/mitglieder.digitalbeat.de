<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts()
{
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        [
            'hello-elementor-theme-style',
        ],
        '1.0.0'
    );

   wp_enqueue_style('material-styles',wp_upload_dir()['baseurl'] . '/elementor/custom-icons/material_solid-1/css/material_solid.css');



   wp_enqueue_style('material-outline',wp_upload_dir()['baseurl'] . '/elementor/custom-icons/material_outline-2/css/material_outline.css');
	

   /* Andrej work in progress
   if ( is_page( 6419 ) ) { // Ersetzen Sie PAGE_ID durch die ID Ihrer Seite
    wp_enqueue_script( 'ajax-skript', get_stylesheet_directory_uri() . '/includes/assets/js/webhook.js' );
    }   
   */ 
}

add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20);

/**
 * Redirect Users to login 
 *
 * @return void
 */

function custom_login() {
    
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        return;
    }
    
    $login_uri = "/login"; 
    $allowed_uris = array(
        "/about-us/",
        "/newsletter-thank-you/",
        "/newsletter-confirmation/",
        "/newsletter-unsubscribe/",
        "/privacy-policy/",
        "/legal-notice/"
    );

    $referrer = $_SERVER['HTTP_REFERER'];
    $current_uri = $_SERVER['REQUEST_URI'];

    if ( !is_user_logged_in() && 
        $login_uri != "/".basename( $_SERVER['REQUEST_URI']) 
        && !strstr($referrer,'login') 
        && !strstr($_SERVER['REQUEST_URI'],'action=rp') 
        && !strstr($_SERVER['REQUEST_URI'],'dm_ipn') 
        && !strstr($_SERVER['REQUEST_URI'],'mgb/v1') 
        && !strstr($_SERVER['REQUEST_URI'],'Zapier')
	&& !strstr($_SERVER['REQUEST_URI'],'usrlkpapi/v1')
        && !in_array($current_uri, $allowed_uris)
        
        ) 
    {
        wp_redirect($login_uri); 
        exit();
    }

}
add_action('init','custom_login');

/**
 * the elementor login widget does not provide handling of login fails, this function redirects the users back to 
 * the login page and provides a request parameter which we can use with the dynamic conditions plugin
 * @return void
 */

function elementor_form_login_fail( $username ) {
    $referrer = $_SERVER['HTTP_REFERER'];  // where did the post submission come from?
    // if there's a valid referrer, and it's not the default log-in screen
    if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') ) {
        //redirect back to the referrer page, appending the login=failed parameter and removing any previous query strings
        wp_redirect(preg_replace('/\?.*/', '', $referrer) . '/?login=failed' );
        exit;
    }
}
add_action( 'wp_login_failed', 'elementor_form_login_fail' );

/**
 * load some additional helpers devided into admin and sitewide functions 
 * 
 * @return void
 */

if (is_admin()) {
    require get_stylesheet_directory() . '/includes/admin-helpers.php';
}

require get_stylesheet_directory() . '/includes/helpers.php';

/**
 * registers request parameters for module navigation 
 * 
 * @return void
 */

function themeslug_query_vars($qvars) 
{ 
    $qvars[] = 'modul';
    $qvars[] = 'webinar';
    return $qvars;
}    

add_filter('query_vars', 'themeslug_query_vars');


/**
 * Display condition for mgb products
 *
 * @param object $manager Elementor's locations manager.
 *
 * @see `elementor/theme/before_do_{$location}` hook.
 */

function mgb_single_elementor_template($manager)
{   
   $template_id =  mgb_get_template_id();   
  
   $theme_builder = ElementorPro\Modules\ThemeBuilder\Module::instance();
   $current_template = $theme_builder->get_conditions_manager()->get_documents_for_location('single');

   $wanted_template = $theme_builder->get_document($template_id); //Setting ID of the template to display.
   if (is_bool($wanted_template)) {
        #print_r($manager->locations_queue);
        #echo ("templ-id:$template_id");
        $post_id = get_the_ID(); // Retrieves the current post ID.
        $document = \Elementor\Plugin::$instance->documents->get_doc_for_frontend($post_id); // Creates an Elementor Document using the current post ID.
        $document->print_content(); // Prints the content using Elementor's method.
    
    } else {
        $wanted_template->print_content(); // Display wanted template.
    }
   $manager->set_is_printed('single', key($current_template)); // Notify Manager that the single location was rendered.   
}

function mgb_get_template_id(){
    global $post;

    if (!$post) {
        return;
    }

    $post_id = $post->ID;

    //break if not Post
    $post_type = get_post_type($post_id);
    if ($post_type !== 'gruender_product') {
        return;
    }

    //match Template ID based on product type

    $product_category = get_field('kategorie', $post->ID); 

    $template_id = match($product_category){
        'buch' => 335,                      // Buch Produktübersicht
        'komplett-produkt' => 300,          // KP Product Overview
        'live-coaching' => 309,             // Live-Coaching Produktübersicht
        'kickstart-coaching' => 361,        // Kickstart-Coaching Produktübersicht
        'traffic-masterplan' => 365,        // Traffic Masterplan Produktübersicht
        'zertifizierungslehrgang' => 348      // KI Zertifizierung Produktübersicht
    };

    //but if we are inside a module use a module template instead. Note we got a special template for each module 2 because it has completly different content
    if (('traffic-masterplan' == $product_category) && (get_query_var('modul') != "")){
      
        $template_id = 368;      // Traffic Masterplan Modul
       
    }   
    
    if (('kickstart-coaching' == $product_category) && (get_query_var('modul') != "")){
      
        $template_id = 338;
        $user_id = wp_get_current_user()->ID;
        if (!mgb_has_product($user_id, DM_KSC_MODULE_2) && get_query_var('modul') == 2) return;
        if (!mgb_has_product($user_id, DM_KSC_MODULE_3) && get_query_var('modul') == 3) return;    

    }
    
    if (('live-coaching' == $product_category) && (get_query_var('modul') != "")){
     
        $template_id = match(get_query_var('modul')){
            "1" => 318,       // Live-Coaching Modul Mediathek
            "2" => 341,       // Live-Coaching Modul Toolbox
            "3" => 344,      // Live-Coaching Modul Boni
        };
        $user_id = wp_get_current_user()->ID;
        if (!mgb_has_product($user_id, DM_IC_GOLD) && !mgb_has_product($user_id, DM_IC_PLATIN) && get_query_var('modul') == 1) return;
        if (!mgb_has_product($user_id, DM_IC_GOLD) && !mgb_has_product($user_id, DM_IC_PLATIN) && get_query_var('modul') == 2) return;
        if (!mgb_has_product($user_id, DM_IC_PLATIN) && get_query_var('modul') == 3) return;  

    }
    
    // the product "KI Affiliate Marketer" is a modified komplett-produkt and needs a custom product overview template.
    if (('komplett-produkt' == $product_category) && (get_query_var('modul') == "") 
            && (get_field('komplett_produkt_daten', $post->ID)['kp_id']== "KIAFFM")){
    
        // returns ID of the Elementor Template for "KI Aff Marketer Produktübersicht"
        return 371;    
    }   

    if (('komplett-produkt' == $product_category) && (get_query_var('modul') != "")){
                
        // extra module template to display module 1 with customer websites
        $module2_template = match(get_field('komplett_produkt_daten', $post->ID)['kp_id']){
          'KICERT' => 338,
           default => 338,
        };
        
        // if not module 2 show default module template
        $template_id = match(get_query_var('modul')){
            "1" => $module2_template,
            default => 338,
        };       

    }

    if (('zertifizierungslehrgang' == $product_category) && (get_query_var('modul') != "")){
    
        $template_id = match(get_query_var('modul')){
            "1" => 351,             // KI Potentialanalyse Modul
            "result" => 355,         // KI Potentialanalyse Ergebnisseite
            default => 338          // KP Modul
           
        };
    
    }        
    return $template_id;
}

add_action('elementor/theme/before_do_single', 'mgb_single_elementor_template', 99);

add_action( 'wp_enqueue_scripts', function() {
    
    $template_id =  mgb_get_template_id();
    if ( ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
        return;
    }
    $css_file = new \Elementor\Core\Files\CSS\Post($template_id);
    $css_file->enqueue();
}, 5 );


//MGB search replace placeholder
function mgb_search_replace($content)
{
    // {{user}}
    $content = str_replace('{{user}}', wp_get_current_user()->display_name, $content);
    return $content;
}
add_filter('the_content', 'mgb_search_replace', 99, 1);
add_filter('elementor/frontend/the_content', 'mgb_search_replace', 99, 1);


//Logout redirect

add_action('wp_logout','ps_redirect_after_logout');
function ps_redirect_after_logout(){
         wp_redirect( '/login/' );
         exit();
}


/**
 * Create taxonomy zusatzkategorie to enable cluster-filter on lessons
 */
function mgb_create_zusatzkategorie_taxonomy() {
	// Add new taxonomy, make it hierarchical (like categories)
	$labels = array(
		'name'              => _x( 'Zusatzkategorien', 'taxonomy general name', 'gruender' ),
		'singular_name'     => _x( 'Zusatzkategorie', 'taxonomy singular name', 'gruender' ),
		'search_items'      => __( 'Suche Zusatzkategorie', 'mgb' ),
		'all_items'         => __( 'Alle Zusatzkategorien', 'mgb' ),
		'parent_item'       => __( 'Eltern Zusatzkategorie', 'mgb' ),
		'parent_item_colon' => __( 'Eltern Zusatzkategorie:', 'mgb' ),
		'edit_item'         => __( 'Bearbeite Zusatzkategorie', 'mgb' ),
		'update_item'       => __( 'Update Zusatzkategorie', 'mgb' ),
		'add_new_item'      => __( 'Füge neue Zusatzkategorie hinzu', 'mgb' ),
		'new_item_name'     => __( 'Neue Zusatzkategorie', 'mgb' ),
		'menu_name'         => __( 'Zusatzkategorie', 'mgb' ),
	);

	$args = array(
		'hierarchical'       => true,
		'labels'             => $labels,
		'public'             => false,
		'show_ui'            => true,
		'show_admin_column'  => true,
		'show_in_nav_menus'  => true,
		'query_var'          => true,
		'publicly_queryable' => false,
		'show_in_rest'       => true,
		'rewrite'            => array( 'slug' => 'zusatzkategorie' ),
	);

	register_taxonomy( 'Zusatzkategorie', 
                        array( 'post' ),
                        $args );
}
add_action( 'init', 'mgb_create_zusatzkategorie_taxonomy', 0 );


add_filter( 'http_request_host_is_external', '__return_true' );
