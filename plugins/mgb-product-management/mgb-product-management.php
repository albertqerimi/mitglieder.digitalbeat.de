<?php
/**
 * Plugin Name:       MGB Product Management
 * Plugin URI:        https://www.digitalbeat.de/
 * Description:
 * Version:           1.2.0
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Author:            AD / JB
 * Author URI:        
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:
 */

defined('ABSPATH') or die();

if (!class_exists(MgbProductManagement::class)) {
    class MgbProductManagement
    {
        public function __construct()
        {
            register_activation_hook(plugin_dir_path(__FILE__), [$this, '']);

            add_filter( 'custom_menu_order', [$this, 'reorder_admin_menu'] );
            add_filter( 'menu_order', [$this, 'reorder_admin_menu'] );

            add_action('init', [$this, 'register_mgb_product_cpt']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
            add_filter('acf/load_field/name=upsell_buch_digimember_produkt', [$this, 'load_digimember_product_ids']);
            
            add_action( 'elementor/frontend/after_register_scripts', [$this, 'add_mgb_custom_domain_script'] );

            add_action('rest_api_init',[$this, 'rest_check_domain']);
            add_action('rest_api_init',[$this, 'rest_order_domain']);

            //include post classes and custom domain class
            add_action('init', [$this, 'include_classes']);
            
            //elementor mgb product query
            add_action('elementor/query/mgb_user_products', [$this, 'mgb_query_user_products']);
        }

        function add_mgb_custom_domain_script() {
            // depends on jquery
            wp_register_script( 'domain_popup', plugin_dir_url(__FILE__) . '/assets/domain_popup.js', [ 'jquery' ] );
            wp_enqueue_script( 'domain_popup' );
          
        }  

        function rest_check_domain() {
            register_rest_route('mgb/v1', '/check-domain/(?P<domain>[a-zA-Z0-9-%]+)/(?P<tld>\w+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'mgb_checkdomains'],
            'permission_callback' => '__return_true'
            ));
        }
       
        function rest_order_domain() {
            register_rest_route('mgb/v1', '/order-domain', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'mgb_orderdomain'],
            'permission_callback' => '__return_true'
            ));
        }
       
        function mgb_orderdomain(WP_REST_Request $request) {
                  
            $parameters = json_decode($request->get_body(), true);
                    
            $domain          = $parameters['selected_domain'];
            $protoid         = $parameters['proto_id'];
            $kptype          = $parameters['kptype'];
            $user_id         = $parameters['uid'];
            
            // 1. needed for DSK additional form data
            // 2. needed for WBK as additional flag to toggle upsell webinars activation on product server
            $dmv             = 0;
       
            if ("dsk"==$kptype){
                $dmv = $parameters['dmv'];
            } 

            if ("wbk"==$kptype){
                
                $wbk_upsell_id = 81;
                if (mgb_has_product($user_id, $wbk_upsell_id) ? $dmv = 1 : $dmv = 0);

            }

            $kp = new MgbKomplettProdukt($kptype);

            error_log($domain. " " . $protoid. " " . $kptype. " " . $user_id);

            return $kp->OrderDomain($user_id, $domain, $protoid,$dmv);

        }
        

       
        function mgb_checkdomains(WP_REST_Request $request) {

            $domain = $request['domain'];
            $tld = $request['tld'];
            //error_log("user has chosen: ". $domain . "with TLD: ". $tld); 
            
            $user_domain = new MgbCustomDomain($domain,$tld); 

            if ($user_domain->is_free()){
               
                return 1;

            } else {
            
                return 0;

            }

            //return random_int(0,1);
 
        }
 
        function plugin_activation()
        {
            $this->register_mgb_product_cpt();
            flush_rewrite_rules();
        }

        function register_mgb_product_cpt()
        {

            $labels = [
               
                'name' => 'Produkte',
                'singular_name' => 'gruender-produkte',
                'add_new' => 'Neues Produkt erstellen',
                'all_items' => 'Produkte',
                'add_new_item' => 'Neues Produkt erstellen',
                'edit_item' => 'Produkt bearbeiten',
                'view_item' => 'Produkt anschauen',
                'search_item' => 'Produkte suchen',
                'not_found' => 'Keine Produkte gefunden',
                'not_found_in_trash' => 'Keine Produkte im Papierkorb gefunden',
                'parent_item_colon' => 'Parent Item'                
            ];
            $args = [
                'labels' => $labels,
                'menu_icon' => 'dashicons-money-alt',
                'menu_position' => 2,
                'public' => true,
                'has_archive' => true,
                'publicly_queryable' => true,
                'query_var' => true,
                'show_in_rest' => true,
                'rewrite' => array('slug' => 'produkte'),
                'capability_type' => 'post',
                'hierarchical' => false,
                'supports' => [
                    'title',
                    'revisions',
                    'custom-fields',
                ],
                'exclude_from_search' => false,
                'taxonomies' => [],
            ];
            register_post_type('gruender_product', $args);
        }

        function reorder_admin_menu( $__return_true ) {
            return array(
                 'index.php', // Dashboard
                 'separator1', // --Space--
                 'edit.php?post_type=gruender_product',
                 'upload.php', // Media
                 'separator2', // --Space--
                 'edit.php?post_type=page', // Pages
                 'edit.php', // Posts
                 'themes.php', // Appearance
                 'edit-comments.php', // Comments
                 'users.php', // Users
                 'plugins.php', // Plugins
                 'tools.php', // Tools
                 'options-general.php', // Settings
           );
        }
        

        /**
         * enqueue admin script for backend
         * @return void
         */
        function enqueue_admin_scripts()
        {
            wp_enqueue_script(
                'mgb-admin-script',
                plugin_dir_url(__FILE__) . '/assets/admin_script.js',
                ['jquery'],
            );
        }

        /**
         * Add Digimember Product to book select field
         * @param $field
         * @return mixed
         */
        function load_digimember_product_ids($field)
        {

            // reset choices
            $field['choices'] = [];

            $products = digimember_listProducts();

            foreach ($products as $product) {
                $field['choices'][$product->id] = $product->name;
            }

            return $field;
        }

        function include_classes(){

            //book functions & shortcodes
            include_once 'inc/book-template-functions.php';
            
            //kp functions & shortcodes
            include_once 'inc/kp-template-functions.php';

            //include custom domain class
            include_once 'inc/mgb_custom_domain.php';
        
        }

        function mgb_query_user_products($query){
           
            $query->set('post_type', 'gruender_product');

            //show all products to admins
            if(current_user_can('manage_options')){
                return;
            }
         
            //get current user id
            $current_user_id = wp_get_current_user();

            //get user products
            $product_ids = mgb_list_products($current_user_id->ID);
            $user_products = [];

            if ($product_ids){
                foreach ($product_ids as $product_id) {
                    if (dm_to_post($product_id)) $user_products = array_merge ($user_products, dm_to_post($product_id));
                }
                $user_products = array_values(array_unique($user_products));
                
                /*echo "<span style='color:white'>";
                print_r($user_products);
                echo "</span>";*/

                $query->set('post__in', $user_products);
                
            } else {

                // if user has no products then make sure the query returns zero posts
                $query->set('post_type', 'no_products');

            }            
              
        }

    }

    new MgbProductManagement();
}