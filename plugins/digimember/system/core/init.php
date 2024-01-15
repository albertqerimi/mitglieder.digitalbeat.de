<?php

class ncore_InitCore extends ncore_Class
{
    public function init()
    {
        if ( defined( 'WP_UNINSTALL_PLUGIN' ) && WP_UNINSTALL_PLUGIN )
        {
            return false;
        }

        $this->initOnLogin();

        if (!ncore_isMainBlog())
        {
            return true;
        }

        $this->api->load->autoLoad();

        $is_wordpress_upgrading = defined( 'WP_INSTALLING' ) && WP_INSTALLING;

        if ($is_wordpress_upgrading)
        {
            $is_plugin_ready = $this->api->isPluginSetup();

            if (!$is_plugin_ready)
            {
                $this->cbPluginActivate();
                $is_plugin_ready = $this->api->isPluginSetup();
            }
        }

        global $ncore_unique_actions_added;
        if (empty($ncore_unique_actions_added)) {
            $ncore_unique_actions_added = true;
        }

        if (ncore_isAdminArea())
        {
            global $ncore_infoboxes_initialized;
            if (empty($ncore_infoboxes_initialized))
            {
                if (function_exists('dm_api'))
                {
                    $ncore_infoboxes_initialized = true;
                    $digimember_api = dm_api();
                    /** @var ncore_InfoboxLogic $model */
                    $model = $digimember_api->load->model( 'logic/infobox' );
                    $model->setup();
                }
            }
        }

        $model = $this->api->load->model( 'logic/session' );
        $model->init();

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'cbCheckAvialableVersion' ) );
        add_filter( 'upgrader_pre_install', array( $this, 'cbUpgraderPreInstall' ), 10, 2 );
        add_filter( 'plugins_api', array( $this, 'cbPluginsApi' ), 10, 3 );

        if ( ncore_isAdminArea() )
        {
            add_action( 'admin_menu', array(  $this, 'cbAdminController' ) );

            register_activation_hook(   $this->api->pluginMainFile(), array( $this, 'cbPluginActivate' ) );
            register_deactivation_hook( $this->api->pluginMainFile(), array( $this, 'cbPluginDeactivate' ) );

            add_action( 'admin_init', array( $this, 'cbAdminInit' ), 10, 3 );

            $filter = "plugin_action_links_".$this->api->pluginMainFile();
            add_filter( $filter, array( $this, "cbPluginActions" ), 10, 4);
            add_filter( 'widget_types_to_hide_from_legacy_widget_block', array($this, 'hideDigimemberWidgetsInLegacyBlock') );


            $model = $this->api->load->model( 'logic/features' );
            $model->maybeRenderAdminNotices();
            add_action('render_admin_notice', array( $model, 'renderAdminNotice' ), 1, 4);

            ncore_flashMessageInit();
            add_action('admin_init', array( $this, 'cbWelcomeScreenDoActivationRedirect'));
            add_action('admin_menu', array($this, 'cbWelcomeScreenPages'));
            add_action('admin_head', array($this, 'cbWelcomeScreenRemoveMenus'));
        }
        else
        {
            add_filter( 'wp_loaded', array( $this, 'cbUserInit' ) );
        }

        $model = $this->api->load->model( 'logic/cronjob' );
        $model->runJobs();


        if (ncore_isAdminArea())
        {
            $plugin = $this->api->pluginName();
            $page = ncore_retrieve( $_GET, 'page' );
            $is_my_page = ncore_stringStartsWith( $page, $plugin );
            if ($is_my_page)
            {
                add_filter( 'admin_body_class', array( $this, 'cbAdminBodyClass' ) );
            }
        }

        add_action( 'wp_ajax_ncore_ajax_action',        array( $this, 'cbHandleAjaxEvent' ) );
        add_action( 'wp_ajax_nopriv_ncore_ajax_action', array( $this, 'cbHandleAjaxEvent' ) );

        do_action( 'ncore_init' );

        $this->api->load->helper( 'cron' );
        $is_cron_script_run     = ncore_isCronjob();

        if ($is_cron_script_run || $is_wordpress_upgrading)
        {
            $this->checkUpgrade();
        }

        add_action( 'dynamic_sidebar_before',   array( $this, 'cb_DynamicSidebarBefore' ) );
        add_action( 'dynamic_sidebar_after',    array( $this, 'cb_DynamicSidebarAfter'  ) );

        return true;
    }

    function hideDigimemberWidgetsInLegacyBlock( $widget_types ) {
        $widget_types[] = 'ncore_account_';
        $widget_types[] = 'ncore_login_';
        $widget_types[] = 'ncore_signup_';
        $widget_types[] = 'ncore_menu_';
        $widget_types[] = 'ncore_lecture_buttons_';
        $widget_types[] = 'ncore_lecture_progress_';
        $widget_types[] = 'ncore_webpush_';
        return $widget_types;
    }

    function cb_DynamicSidebarBefore()
    {
        global $ncore_sidebar_level;
        if (empty($ncore_sidebar_level)) {
            $ncore_sidebar_level = 1;
        }
        else
        {
            $ncore_sidebar_level++;
        }
    }

    function cb_DynamicSidebarAfter()
    {
        global $ncore_sidebar_level;
        $ncore_sidebar_level--;
    }

    public function isInSidebar()
    {
        return $this->is_in_sidebar >= 1;
    }

    public function uninstall()
    {
        try
        {
            $lib = $this->api->loadLicenseLib();
            $lib->clearLicense();

            $this->teardownModels();
        }
        catch (Exception $e)
        {
        }
    }

    private function initOnLogin()
    {
        $callback = array( $this, 'cbSyncUser' );
        add_action('wp_login', $callback, 999, 2 );
    }

    public function cbSyncUser( $user_login='', $user=false )
    {
        if ($user)
        {
            /** @var digimember_UserData $model */
            $model = $this->api->load->model( 'data/user' );
            $model->maybeCreateForWpUser( $user->ID );
        }
    }

    public function cbHandleAjaxEvent() {

        $this->api->load->library('ajax');

        $plugin_name     = ncore_retrieve( $_POST, 'ncore_plugin'       );
        $controller_name = ncore_retrieve( $_POST, 'ncore_controller'   );
        $event           = ncore_retrieve( $_POST, 'ncore_event'        );
        $xss_password    = ncore_retrieve( $_POST, 'ncore_xss_password' );

        unset( $_POST['ncore_plugin' ]   );
        unset( $_POST['ncore_controller' ]   );
        unset( $_POST['ncore_event' ]        );
        unset( $_POST['ncore_xss_password' ] );

        $settings = array();
        if (!empty($_POST) && is_array($_POST)) {
            foreach ($_POST as $key => $value)
            {
                $is_controller_setting = ncore_stringStartsWith( $key, 'ncore_ctr_settings_' );
                if ($is_controller_setting) {
                    unset($_POST[ $key ] );
                    $key = str_replace( 'ncore_ctr_settings_', '', $key );
                    $settings[ $key ]= $value;
                }
            }
        }


        $args = $_POST;

        try
        {
            if (!$controller_name)
            {
                throw new Exception( 'Ajax controller missing.' );
            }

            if (!$event)
            {
                throw new Exception ( 'Ajax event missing.' );
            }

            $api = ncore_api( $plugin_name );

            $controller = $api->load->controller( $controller_name, $settings );
            if (!$controller) {
                throw new Exception( 'Invalid ajax controller name.' );
            }


            $must_verify_xss_password = $controller->mustVerifyXssPassword( $event );
            if ($must_verify_xss_password)
            {
                $api->load->helper( 'xss_prevention' );
                $is_verified = $xss_password && $xss_password == ncore_XssPassword();
                if (!$is_verified) {
                    throw new Exception( 'Ajax request not authorized. Do you have cookies enabled? If so, please scan your computer for viruses.' );
                }
            }

            $response = $controller->dispatchAjax( $event, $args );

            $response->output();
        }

        catch (Exception $e)
        {
            $response = new ncore_AjaxResponse( $this->api );
            $response->error( 'An ajax error occurred: ' . $e->getMessage() );
            $response->output();
        }
    }

    public function cbAdminBodyClass( $classes ) {

        if (is_array($classes)) {
            $classes[] = 'ncore';
            return $classes;
        }

        if (is_string($classes)) {
            if ($classes) $classes.= ' ';
            $classes .= 'ncore';
            return $classes;
        }

        return $classes;
    }

    public function cbAdminInit()
    {
        $this->checkUpgrade();
    }

    public function cbUserInit()
    {
    }

    public function cbPluginActivate()
    {
        $config         = $this->api->load->config( 'general' );
        $min_version    = $config->get( 'required_min_php_version' );
        $cur_version    = phpversion();
        $version_to_low = version_compare( $cur_version, $min_version, '<' );
        if ( $version_to_low )
        {
            die( "This plugin requires PHP $min_version or higher" );
        }

        $min_version    = $config->get( 'required_min_wordpress_version' );
        $cur_version    = get_bloginfo( 'version' );
        $version_to_low = version_compare( $cur_version, $min_version, '<' );
        if ( $version_to_low )
        {
            die( "This plugin requires wordpress $min_version or higher" );
        }

        $obstacles = $this->api->getPluginActivationObstacles();
        if ( $obstacles )
        {
            die( "Cannot activate plugin: $obstacles" );
        }

        $extensions = $config->get( 'required_php_extensions' );
        if (!empty($extensions))
        {
            $missing_extensions = array();
            foreach ($extensions as $one)
            {
                $have_extension = extension_loaded ( $one );
                if (!$have_extension) {
                    $missing_extensions[] = $one;
                }
            }
            if ($missing_extensions)
            {
                $missing_extensions = implode( ', ', $missing_extensions );
                die( "Cannot activate plugin, because these php extensions are missing: $missing_extensions" );
            }
        }

        $this->upgrade();
        set_transient( 'digimember_welcome_screen_activation_redirect', true, 30 );
    }

    public function cbWelcomeScreenDoActivationRedirect() {
        $model = $this->api->load->model( 'logic/blog_config' );
        $welcomeScreenShown = $model->get( 'welcome_screen_shown' );

        if ( ! get_transient( 'digimember_welcome_screen_activation_redirect' ) ) {
            return;
        }
        if ($welcomeScreenShown !== "") {
            return;
        }
        delete_transient( 'digimember_welcome_screen_activation_redirect' );
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            return;
        }
        $model->set( 'welcome_screen_shown', true );
        wp_safe_redirect( add_query_arg( array( 'page' => 'digimember-welcome-screen' ), admin_url( 'index.php' ) ) );
    }

    public function cbWelcomeScreenPages() {
        add_dashboard_page(
            _ncore("Welcome to DigiMember"),
            _ncore("Welcome to DigiMember"),
            'read',
            'digimember-welcome-screen',
            array($this, 'cbWelcomeScreenContent')
        );
    }

    public function cbWelcomeScreenContent() {
        $lang = substr(get_locale(), 0, 2);
        $bulkActions = $this->cbWelcomeScreenFooter();
        if ($lang == "de") {
            $link = "https://digimember-hilfe.de/docs/quick-start-guide/";
        }
        else {
            $link = "https://docs.digimember.com/docs/reference-book/rb-installation/";
        }
        $output = "";
        $output .= "<div class=\"wrap\">";
        $output .= "<h2><bold>"._ncore("Welcome to DigiMember")."</bold></h2>";
        $output .= "<p>"._ncore("With DigiMember you are able to provide complex member areas, download products and membership solutions easily.")."</p>";
        $output .= "<p>"._ncore("For a better beginning, we got a manual for you, which guides you through the first steps in DigiMember. Click %s, to open the manual on our help page.", "<a href=\"".$link."\" target=\"_blank\">"._ncore("here")."</a>")."</p>";
        $output .= "<p>"._ncore("We wish you much fun and success with DigiMember!")."</p>";
        $output .= "<p>"._ncore("Your DigiMember team")."</p>";
        $output .= implode(" | ", $bulkActions);
        $output .= "</div>";


        echo $output;
    }

    public function cbWelcomeScreenFooter() {
        return array(
            'goto_digimember' => sprintf(
                '<a href="%s" target="_parent">%s</a>',
                self_admin_url( 'admin.php?page=digimember' ),
                _ncore( 'Go to DigiMember' )
            ),
            'plugins_page'    => sprintf(
                '<a href="%s" target="_parent">%s</a>',
                self_admin_url( 'plugins.php' ),
                __( 'Go to Plugins page' )
            ),
        );
    }

    public function cbWelcomeScreenRemoveMenus() {
        remove_submenu_page( 'index.php', 'digimember-welcome-screen' );
    }



    public function cbPluginDeactivate()
    {
    }

    public function cbAdminController()
    {
        $this->setupAdminMenu();
        $this->setupPageMeta();
    }

    public function cbUpgraderPreInstall( $return, $plugin )
    {
        $this->api->load->library( 'license_validator' );
        $this->api->load->library( 'rpc_api' );

        return $return;
    }

    public function cbCheckAvialableVersion( $checked_data )
    {
        $is_updating = !file_exists(__FILE__);
        if ($is_updating) {
            return $checked_data;
        }

        try
        {
            $lib = $this->api->loadLicenseLib();
            if (!$lib->licenseCheckEnabled()) {
                return $checked_data;
            }
        }
        catch (Exception $e)
        {
            return $checked_data;
        }

        if (empty($checked_data) || !is_object( $checked_data )) {
            // don't know how to handle this!
            return $checked_data;
        }

        if (empty($checked_data->checked)) {
            $checked_data->checked = array();
        }

        $plugin_file = $this->api->pluginMainFile();

        $current_version = ncore_retrieve( $checked_data->checked, $plugin_file );

        list( $affiliate, $campaignkey ) = $this->api->blog_config_logic->getAffiliate();

        $args = array(
             'package_name'    => $this->api->pluginName(),
             'package_version' => $this->api->pluginVersion(),
             'affiliate'       => $affiliate,
             'campaignkey'     => $campaignkey,
        );

        if ( NCORE_TESTING )
        {
            $args[ 'environment' ] = 'test';
        }

        $rpc = $this->api->load->library( 'rpc_api' );

        try
        {
            $result = $rpc->pluginApi( 'version_check', $args );

            $have_new_version = (bool) ncore_retrieve( $result, 'new_version' );

            if ( $have_new_version )
            {
                $checked_data->response[ $plugin_file ] = $result;
            }
        }

        catch ( Exception $e )
        {
            $this->api->logError( 'plugin', 'Error connecting to the upgrade server - upgrade check for plugin failed: ' . $e->getMessage() );
        }

        return $checked_data;
    }

    public function cbPluginsApi( $def, $action, $params )
    {
        $plugin  = ncore_retrieve( $params, 'slug' );
        $version = ncore_retrieve( $params, 'version' );

        $is_for_me = $plugin == $this->api->pluginName();
        if ( !$is_for_me )
        {
            return $def;
        }

        if ($action != 'plugin_information')
        {
            return $def;
        }

        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        list( $affiliate, $campaignkey ) = $model->getAffiliate();

        /** @var ncore_RpcApiLib $rpc */
        $rpc = $this->api->load->library( 'rpc_api' );

        try
        {
            $args = array(
                'package_name'    => $plugin,
                'package_version' => $version,
                'affiliate'       => $affiliate,
                'campaignkey'     => $campaignkey,
            );
            $result = $rpc->pluginApi( $action, $args );
        }

        catch ( Exception $e )
        {
            $msg = _ncore( 'Error connecting to the upgrade server:' ) . ' ' . $e->getMessage();

            $result     = new WP_Error( 'plugins_api_failed', $msg );

            $this->api->logError( 'plugin', $msg );
        }

        return $result;
    }

    public function cbLoadAdminController()
    {
        $menu = $this->loadMenu();

        $page = $this->getPage();

        $meta = false;
        foreach ( $menu as $slug => $one )
        {
            $is_hidden = ncore_retrieve( $one, 'hide', false );
            if ( $is_hidden )
            {
                continue;
            }

            $found = $slug === $page;
            if ( $found )
            {
                $meta = $one;
                break;
            }

            $submenu = ncore_retrieve( $one, 'submenu', array ());
            foreach ( $submenu as $slug => $one )
            {
                $is_hidden = ncore_retrieve( $one, 'hide', false );
                if ( $is_hidden )
                {
                    continue;
                }

                $found = $slug === $page;
                if ( $found )
                {
                    $meta = $one;
                    break 2;
                }
            }
        }

        $final_controller = ncore_retrieve( $meta, 'controller' );
        $final_plugin     = ncore_retrieve( $meta, 'plugin' );
        $final_id         = false;

        $subpages = ncore_retrieve( $one, 'subpages', array ());
        foreach ( $subpages as $paramname => $controller )
        {
            $id = ncore_retrieve( $_GET, $paramname, false );
            if ( $id === false )
            {
                $id = ncore_retrieve( $_POST, $paramname, false );
            }

            if ( $id )
            {
                $final_id         = $id;
                $final_controller = $controller;
                break;
            }
        }

        if ( $final_controller )
        {
            $api = $final_plugin
                 ? ncore_api( $final_plugin )
                 : $this->api;

            $controller = $api->load->controller( $final_controller );
            if ( $final_id )
            {
                $controller->setElementId( $final_id );
            }
            $controller->dispatch();
        }
    }

    function cbPluginActions( $actions, $plugin_file, $plugin_data, $context )
    {
        list( $slug, ) = explode( ".", basename( $plugin_file ) );
        $url = menu_page_url( $slug, false );

        if ($url)
        {
            $must_fix_wp_quirk = strpos( $url, 'toplevel_page_') !== false;
            if ($must_fix_wp_quirk)
            {
                $url = str_replace( "/toplevel_page_$slug", "/admin.php", $url );
            }

            $link = "<a href=\"".$url."\">"._ncore("Settings")."</a>";
            array_unshift($actions, $link);
        }
        return $actions;
    }


    private $menu = false;

    private function setupDbTables()
    {
        $models = $this->api->load->allModels( array(
             'system',
            'application'
        ), array(
             'data',
            'queue'
        ) );

        foreach ( $models as $one )
        {
            $one->setup();
        }
    }

    private function teardownModels()
    {
        $models = $this->api->load->allModels( array(
             'system',
             'application'
        ), array(
             'data',
            'queue'
        ) );

        foreach ( $models as $one )
        {
            $one->teardown();
        }
    }

    private function checkUpgrade()
    {
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );

        $installed_version = $this->api->pluginVersion();

        $stored_version = $model->get( 'installed_version' );

        $is_downgrade = $installed_version
                     && $stored_version
                     && $stored_version[0] > $installed_version[0];

        $is_upgrade = version_compare( $stored_version, $installed_version, '<' );

        $must_install =  $is_upgrade || $is_downgrade;
        if ( $must_install )
        {
            $this->api->log( 'plugin', _ncore( 'Upgrading %s from version %s to %s.' ), $this->api->pluginLogName(), $stored_version, $installed_version );
            $this->upgrade();
        }
    }

    private function upgrade()
    {
        $this->setupDbTables();

        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );

        $installed_version = $this->api->pluginVersion();

        $model->set( 'installed_version', $installed_version );


        /** @var digimember_BlogConfigLogic $settings */
        $settings     = $this->api->load->model( 'logic/blog_config' );
        $install_time = $settings->get( 'plugin_install_time' );
        if ( !$install_time )
        {
            $settings->set( 'plugin_install_time', ncore_serverTime() );
        }

        $settings->set( 'plugin_upgrade_time', ncore_serverTime() );
    }

    public function forceUpgrade() {
        $this->upgrade();
    }


    private function setupPageMeta()
    {
        $config     = $this->api->load->config( 'menu' );

        $page_metas = $config->get( 'page_meta', array ());

        foreach ( $page_metas as $meta )
        {
            extract( $meta );

            if (!empty($hide)) {
                continue;
            }

            $controller_obj = $this->api->load->controller( $controller );

            if (!$controller_obj->isActive()) {
                continue;
            }

            if (!empty($post_type)) {
                $post_types = is_array( $post_type )
                              ? $post_type
                              : array( $post_type );
            }
            elseif (!empty($post_types_callback)) {
                $post_types = ncore_callUserFunction( $post_types_callback );
            }
            else
            {
                $post_types = array();
            }

            foreach ( $post_types as $post_type )
            {
                $html_id = ncore_id( $controller );

                $callback = array( $controller_obj, 'cbMetaBoxInit' );

                $headline = $this->parseTitle( $headline );

                add_meta_box( $html_id, $headline, $callback, $post_type, $position, $priority );

                add_action('save_post', array( $controller_obj, 'cbMetaBoxSave' ) );
            }
        }
    }

    private function setupAdminMenu()
    {
        $my_callback = array(
             $this,
            'cbLoadAdminController'
        );

        $main_menu = $this->loadMenu();

        foreach ( $main_menu as $main_menu_slug => $menu )
        {
            $is_hidden = ncore_retrieve( $menu, 'hide', false );
            if ( $is_hidden )
            {
                continue;
            }

            $have_menu_entry = $main_menu_slug !== 'no_menu';

            extract( $menu );

            $page_title = $this->parseTitle( $page_title );
            $menu_title = $this->parseTitle( $menu_title );

            $hook = add_menu_page( $page_title, $menu_title, $capabilities, $main_menu_slug, $my_callback, 'div', ncore_wpMenuPosition( 1 ) );
            add_submenu_page( $main_menu_slug, $page_title, $menu_entry, $capabilities, $main_menu_slug, $my_callback );

            if ( !isset( $submenu ) )
            {
                continue;
            }

            foreach ( $submenu as $sub_menu_base_slug => $one )
            {
                $is_hidden = ncore_retrieve( $one, 'hide', false );
                if ( $is_hidden )
                {
                    continue;
                }

                $has_menu_entry = isset( $one[ 'menu_entry' ] );

                extract( $one );

                $plugin     = ncore_retrieve( $one, 'plugin' );
                $cb_content = ncore_retrieve( $one, 'cb_content', $my_callback );
                $cb_scripts = ncore_retrieve( $one, 'cb_scripts' );
                $cb_styles  = ncore_retrieve( $one, 'cb_styles' );

                $parent_slug = $has_menu_entry ? $main_menu_slug : null;

                $sub_menu_slug = $plugin
                                ? $sub_menu_base_slug
                                : $main_menu_slug . '_' . $sub_menu_base_slug;

                $handle = add_submenu_page( $parent_slug, $page_title, $menu_entry, $capabilities, $sub_menu_slug, $cb_content );

                if ($cb_scripts)
                {
                    add_action( "admin_print_scripts-$handle", $cb_scripts );
                }
                if ($cb_styles)
                {
                    add_action( "admin_print_styles-$handle", $cb_styles );
                }
            }
        }
    }

    private function loadMenu()
    {
        if ( $this->menu === false )
        {
            $config = $this->api->load->config( 'menu' );

            $this->menu = $config->get( 'admin_pages' );
        }

        return $this->menu;
    }

    private function getPage()
    {
        $page = ncore_retrieve( $_GET, 'page' );
        if ( !$page )
        {
            $page = ncore_retrieve( $_POST, 'page' );
        }

        $pluginName = $this->api->pluginName();

        $length = strlen( $pluginName );

        $prefix = substr( $page, 0, $length + 1 );

        if ( $page != $pluginName && $prefix == $pluginName . '_' )
        {
            $page = substr( $page, $length + 1 );
        }

        return $page;
    }

    private function parseTitle( $title )
    {
        return str_replace( '[PLUGIN]', $this->api->pluginDisplayName(), $title );
    }


}