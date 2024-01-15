<?php

class digimember_InitCore extends ncore_InitCore
{
    public function init()
    {
        if (!parent::init()) {
            return false;
        }

        if (!ncore_isMainBlog())
        {
            return true;
        }

        $access_control_required = !ncore_isAdminArea();
        if ($access_control_required)
        {
            /** @var digimember_AccessLogic $model */
            $model = $this->api->load->model( 'logic/access' );
            $model->setupFilter();
        }

        $this->initOnLogin();

        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );

        if (ncore_isAjax())
        {
            $is_profit_builder_tinymce_work_around = !empty( $_REQUEST[ 'action' ] ) && $_REQUEST['action'] == 'pbuilder_edit';
            if ($is_profit_builder_tinymce_work_around) {
                $this->cbTinyMceInit();
            }
        }
        else
        {
            add_action('wp_footer', array( $this, 'cbFooter' ) );
            add_action( 'wp',       array( $this, 'cbWpObject' ) );

            add_action('init',array( $this, 'cbLoadMenus' ),PHP_INT_MAX);

            if (ncore_isAdminArea()) // && !ncore_isAdminPage( '*' ))
            {
                add_action('init',array( $this, 'cbOnAdminInit' ),PHP_INT_MAX);
                add_action( 'init', array($this, 'cbLoadTinyMceHelper'));
            }

            add_action('show_user_profile', array( $this, 'cbEditOwnProfile' ));
            add_action('edit_user_profile', array( $this, 'cbEditUserProfile' ));
            add_action( 'edit_user_profile_update', array( $this, 'cbUpdateUserProfile' ) );
            add_action( 'profile_update',           array( $this, 'cbUpdateUserProfile' ) );
            add_filter( 'wp_nav_menu_items',        array( $this, 'cbNavMenuItems'      ), 10, 2 );
            add_filter( 'digimember_cf_update_data', array( $this->api->load->model('data/user_settings'), 'updateCustomfieldsData'), 10, 2);
            add_filter( 'digimember_ipn_push_arcf_links', array( $this->api->load->model('data/user_settings'), 'pushArcfLinks'), 10, 2);
            add_filter( 'digimember_ipn_push_user_name', array( $this->api->load->model('data/user_settings'), 'pushUserName'), 10, 2);
        }

        do_action('digimember/loaded', true);

        return true;
    }

    public function cbUserInit()
    {
        parent::cbUserInit();

        $config = $this->api->load->model( 'logic/blog_config' );
        $turned_off = $config->get('disable_admin_navbar');
        if ($turned_off)
        {
            $user_area = !ncore_isAdminArea();
            $is_admin = ncore_canAdmin();

            if ($user_area && !$is_admin)
            {
                add_filter( 'show_admin_bar', 'ncore_filter_return_false' );
            }
        }

        $user_id = ncore_userId();
        if ($user_id)
        {
            $model = $this->api->load->model( 'logic/ip_lock' );
            $model->checkLogin( $user_id );

//            $model = $this->api->load->model( 'data/user_product' );
//            $html = $model->trackingCodeForUser( $user_id );
//            if ($html)
//            {
//                $model = $this->api->load->model( 'logic/html' );
//                $model->hiddenHtml( $html );
//            }
        }

    }

    public function cbNavMenuItems( $html, $args )
    {
        if (!$args && empty($args->menu)) {
            return $html;
        }

        $menu = is_string($args->menu)
              ? wp_get_nav_menu_object( $args->menu )
              : $args->menu;

        $is_valid = !empty( $menu ) && $menu instanceOf WP_Term;
        if (!$is_valid) {
            return $html;
        }

        /** @var digimember_BlogConfigLogic $config */
        $config = $this->api->load->model( 'logic/blog_config' );
        $is_for_me = $args && $args->menu && is_object($args->menu) && $args->menu->term_id == $config->get( 'lecture_menu_id' );
        if (!$is_for_me)
        {
            return $html;
        }

        /** @var digimember_CourseLogic $course_logic */
        $course_logic = $this->api->load->model( 'logic/course' );
        $rendered_menu = $course_logic->renderLectureMenu( $menu='current', $omit_ul_container=true );

        return $rendered_menu
               ? $rendered_menu
               : '<!-- the DigiMember course menu does not contain any lectures -->';
    }

    public function cbEditOwnProfile(WP_User $wpUser) {
        $controller = $this->api->load->controller( 'user/custom_profile_editor' , array(
            'wpUserID' => $wpUser->ID,
            'ownProfile' => true
        ));
        echo $controller->render();
    }

    public function cbEditUserProfile(WP_User $wpUser) {
        $controller = $this->api->load->controller( 'user/custom_profile_editor' , array(
            'wpUserID' => $wpUser->ID,
            'ownProfile' => false
        ));
        echo $controller->render();
    }

    public function cbUpdateUserProfile( $user_id, $old_data=array() )
    {
        $controller = $this->api->load->controller( 'user/custom_profile_editor' , array(
            'wpUserID' => $user_id
        ));
        $controller->render();

        $this->api->load->helper( 'string' );

        $user = ncore_getUserById( $user_id );

        $email = ncore_retrieve( $user, 'user_email' );
        $login = ncore_retrieve( $user, 'user_login' );

        $have_email_as_login = strpos( $login, '@' ) > 0 && strpos( $login, '.' ) > 0;

        $must_update = $email != $login && $have_email_as_login;
        if (!$must_update)
        {
            return;
        }

        static $updating;
        $updating = isset( $updating )
                  ? $updating
                  : false;

        if ($updating)
        {
            return;
        }

        $updating = true;

        $email = sanitize_user($email, true);
        if (!username_exists( $email )) {
            global $wpdb;
            $wpdb->update( $wpdb->users, array( "user_login" => $email ), array( "ID" => $user_id ) );
        }

        $nick = ncore_retrieve( $user, 'nickname' );
        $have_email_as_nickname = $nick == $login;
        if ($have_email_as_nickname)
        {
            update_user_meta( $user_id, 'nickname', $email );
        }

        $updating = false;
    }

    public function cbLoginRedirect( $redirect_to='', $request=array(), $user=null )
    {
        global $DM_IS_LOGIN_VIA_LOGINFORM;

        $can_admin = is_null($user)
                   ? ncore_canAdmin()
                   : ncore_canAdmin( $user );

        if ($can_admin && empty($DM_IS_LOGIN_VIA_LOGINFORM))
        {
            return $redirect_to;
        }

        $this->api->load->helper( 'url' );

        $is_login = (bool) ncore_retrieve( $user, 'ID' );

        $is_logout = !$is_login;

        if ($is_login)
        {
            $model = $this->api->load->model( 'logic/access' );

            $url = $model->loginUrl( $user );

            if ($url)
            {
                return ncore_resolveUrl($url);
            }
            else
            {
                return ncore_resolveUrl( ncore_removeArgs( $redirect_to, 'reauth' ) );
            }
        }

        return $redirect_to;
    }

    public function cbLoginCheckAccountLockPost( $result, $user_login, $password )
    {
        ignore_user_abort(true);

        if (empty($user_login)) {
            return $result;
        }

        $have_data = $password;

        $ip = ncore_clientIp( 'localhost' );

        $locked_at_unix  = ncore_cacheRetrieve( "lock $ip" );
        if ($locked_at_unix && $have_data) {
            /** @var digimember_BlogConfigLogic $model */
            $model = $this->api->load->model( 'logic/blog_config' );
            $limit_login_waittime = 60*$model->get( 'limit_login_waittime' );
            $is_limited = $model->get( 'limit_login_enabled' );

            $is_locked = ncore_isTrue($is_limited) && $locked_at_unix + $limit_login_waittime > time();
            if ($is_locked) {
                $this->api->load->helper( 'date' );
                $for_time = ncore_formatTimeSpan( $limit_login_waittime, 'timespan' );
                $msg      = _dgyou( 'You have too many failed login attempts. Please wait for %s.', $for_time );
                return new WP_Error( 'authentication_failed', $msg );
            }
        }

        $is_error = is_a( $result, 'WP_Error' );
        if ($is_error)
        {
            /** @var digimember_BlogConfigLogic $model */
            $model = $this->api->load->model( 'logic/blog_config' );
            $is_limited = $model->get( 'limit_login_enabled' );
            if (ncore_isTrue($is_limited) && $have_data)
            {
                $limit_login_count    = $model->get( 'limit_login_count' );
                $limit_login_waittime = 60*$model->get( 'limit_login_waittime' );

                /** @var ncore_IpLockData $model */
                $model = $this->api->load->model( 'data/ip_lock' );
                $is_locked = $model->isLocked( 'login', $limit_login_count, 3600 );

                if ($is_locked) {

                    ncore_cacheStore( "lock $ip", time(), $limit_login_waittime );
                }
            }

            return $result;
        }


        /** @var digimember_AccountLockLogic $model */
        $model = $this->api->load->model( 'logic/account_lock' );

        list( $lock_type, $lock_action ) = $model->getAccountLock( $user_login );

        switch ($lock_type)
        {
            case DIGIMEMBER_AL_PAGE:
            case DIGIMEMBER_AL_URL:
                $url = ncore_resolveUrl( $lock_action );
                ncore_redirect( $url );
                break;

            case DIGIMEMBER_AL_TEXT:
                return new WP_Error( 'authentication_failed', $lock_action );

            case DIGIMEMBER_AL_NONE:
            default:
            return $result;
        }
        return $result;
    }

    public function cbLoginCheckAccountLockPre( &$user_login, &$user_password )
    {
        ignore_user_abort(true);

        if (empty($user_login))
        {
            return;
        }

        /** @var digimember_AccountLockLogic $model */
        $model = $this->api->load->model( 'logic/account_lock' );

        list( $lock_type, $lock_action ) = $model->getAccountLock( $user_login );
        switch ($lock_type)
        {
            case DIGIMEMBER_AL_PAGE:
            case DIGIMEMBER_AL_URL:
                $url = ncore_resolveUrl( $lock_action );
                ncore_redirect( $url );
                break;

            case DIGIMEMBER_AL_TEXT:
            case DIGIMEMBER_AL_NONE:
            default:
        }
    }

    /**
     * @param string $user_login
     * @param bool | stdClass   $user
     */
    public function cbCountLogin( $user_login='', $user=false )
    {
        if ($user)
        {
            /** @var digimember_CounterData $counter_model */
            $counter_model = $this->api->load->model( 'data/counter' );
            $counter_model->countLogin( $user->ID );
        }
    }

    /**
     * @param string $user_login
     * @param bool | stdClass   $user
     */
    public function cbLogAccess( $user_login='', $user=false )
    {
        if ($user)
        {
            global $current_user;
            $current_user = $user;
            $model = $this->api->load->model( 'logic/event_subscriber' );
            $model->call( 'dm_on_access', $user->ID);
        }
    }

    public function cbFooter()
    {
        $this->_maybeRenderFooterLink();
    }

    public function cbWpObject()
    {
        $this->_maybeCallPageViewAction();
    }

    public function cbLoadMenus()
    {
        $is_logged_in = ncore_isLoggedIn();
        if (!$is_logged_in)
        {
            return;
        }

        $plugin_key = $this->api->pluginName();

        $menus = get_registered_nav_menus();

        foreach ($menus as $location => $label)
        {
            $location .= "-$plugin_key";
            $label = "$label - ". _digi( 'if logged in' );

            register_nav_menu( $location, $label );
        }


        $priority = 1; // required for sitepress-multilingual-cms by wpml.org

        // $have_wpml = defined( 'ICL_SITEPRESS_VERSION' );

        add_filter( "wp_nav_menu_args", array( $this, "cbLoadLoggedInMenus" ), $priority );

        // Enfold theme
        add_filter( "avf_append_burger_menu_location", array( $this, "cbRestoreMenuLocation" ), 1, 1 );
    }

    public function cbRestoreMenuLocation( $location )
    {
        $plugin_key = $this->api->pluginName();

        $pos = strrpos( $location, $plugin_key );
        if ($pos === false || $pos<=1)
        {
            return $location;
        }

        $suffix = substr( $location, $pos-1 );

        $is_me = $suffix === "-$plugin_key";
        if ($is_me)
        {
            $location = substr( $location, 0, $pos-1 );
        }

        return $location;
    }


    public function cbLoadLoggedInMenus( $args )
    {
        $plugin_key = $this->api->pluginName();

        $location = ncore_retrieve( $args, "theme_location" );

        if (!$location) return $args;

        $location_logged_in = $location . "-" . $plugin_key;

        $locations = get_nav_menu_locations();


        $have_location = isset( $locations[ $location_logged_in ] );
        if (!$have_location) return $args;

        $menu = wp_get_nav_menu_object( $locations[ $location_logged_in ] );
        $have_menu = $menu && $menu->count > 0;
        if ($have_menu)
        {
              $args["theme_location"] = $location_logged_in;
        }

        return $args;
    }

    public function cbOnAdminInit(){

        $config = $this->api->load->model( 'logic/blog_config' );
        $turned_off = $config->get('disable_admin_area');
        if ($turned_off)
        {
            $is_admin_area = ncore_isAdminArea();
            $is_admin      = ncore_canAccessAdminArea();
            $is_logged_in  = ncore_isLoggedIn();

            if ($is_admin_area && !$is_admin && $is_logged_in)
            {
                $url = $config->get('disable_admin_area_url', ncore_siteUrl());
                ncore_redirect( $url );
            }
        }

        $this->cbFeedbackInit();
        $this->cbShortcodesInit();
        $this->cbTinyMceInit();

        if (ncore_canAdmin())
        {
            $ds24 = $this->api->load->model( 'logic/digistore_connector' );
            $ds24->validateSetup();

            $this->_initLectureNavigation();
        }
    }

    public function cbFeedbackInit() {
        $licenseLib = $this->api->loadLicenseLib();
        if ($licenseLib->licenseStatus() == 'free') {
            $link = $this->api->load->model( 'logic/link' );
            $ajax_feedback_dialog_url = $link->ajaxUrl( 'post/feedback', 'deactivate' );
            $ajax_feedback_send_url = $link->ajaxUrl( 'post/feedback', 'send' );
            $args = array(
                'ajax_feedback_dialog_url' => $ajax_feedback_dialog_url,
                'ajax_feedback_send_url' => $ajax_feedback_send_url,
            );
            $html = $this->api->load->model( 'logic/html' );
            $html->includeJs( "feedback", $args );
        }
    }

    public function cbShortcodesInit() {
        $link = $this->api->load->model( 'logic/link' );
        $ajax_shortcodes_dialog_url = $link->ajaxUrl( 'post/shortcodes', 'dialog' );
        $ajax_shortcodes_list_url = $link->ajaxUrl( 'post/shortcodes', 'list' );
        $ajax_shortcodes_add_url = $link->ajaxUrl( 'post/shortcodes', 'add' );
        $args = array(
            'ajax_shortcodes_dialog_url' => $ajax_shortcodes_dialog_url,
            'ajax_shortcodes_list_url' => $ajax_shortcodes_list_url,
            'ajax_shortcodes_add_url' => $ajax_shortcodes_add_url,
        );
        $html = $this->api->load->model( 'logic/html' );
        $html->includeJs( "shortcodes", $args );
    }


    private $tinymce_inistialized = false;
    public function cbTinyMceInit()
    {
        if ($this->_mustDisableTinyMCE()) {
            return;
        }

        if (!$this->tinymce_inistialized)
        {
            add_filter('mce_external_plugins', array( $this, 'cbTinyMceInitRegisterPlugin' ) );
            add_filter('mce_buttons',          array( $this, 'cbTinyMceInitRegisterButton' ) );
        }
        $this->tinymce_inistialized = true;
    }

    public function cbLoadTinyMceHelper() {
        $link = $this->api->load->model( 'logic/link' );
        $ajax_dialog_url = $link->ajaxUrl( 'post/tinymce', 'add_shortcode' );
        $get_products_url = $link->ajaxUrl( 'post/tinymce', 'get_products' );
        $get_block_config_url = $link->ajaxUrl( 'post/tinymce', 'get_block_config' );
        $args = array(
            'ajax_dialog_url' => $ajax_dialog_url,
            'get_products_url' => $get_products_url,
            'get_block_config' => $get_block_config_url,
        );

        $html = $this->api->load->model( 'logic/html' );
        $html->includeJs( "tinymce_helper", $args );
    }

    public function cbTinyMceInitRegisterPlugin($plugin_array) {
        /** @var digimember_LinkLogic $html */
       $html = $this->api->load->model( 'logic/html' );

       $js_functions = apply_filters( 'ncore_tinymce_js_parser', array() );
       $js = '';
       foreach ($js_functions as $one)
       {
           $js .= "ncore_tinymce_register_parser( '$one' );";
       }
       $js_functions = apply_filters( 'ncore_tinymce_js_content_renderers', array() );
       foreach ($js_functions as $one)
       {
           $js .= "ncore_tinymce_register_content_renderer( '$one' );";
       }
       $html->jsOnLoad($js);

       $root_url = $this->api->pluginUrl();
       $jsfile = $root_url."webinc/js/tinymce_plugin.js";

       $plugin_array['digimember'] = $jsfile;

       return $plugin_array;
    }

    public function cbTinyMceInitRegisterButton( $buttons )
    {
        static $initialized;

        if (empty($initialized)) {
            $initialized = true;

            $js = "if (typeof digimember_tinymce_quicktag != 'undefined' && typeof QTags != 'undefined') QTags.addButton( 'tinymce_digimember_button', ' ', digimember_tinymce_quicktag );";
            $html = $this->api->load->model( 'logic/html' );
            $html->jsOnLoad( $js );
        }

        $insert_before_tries = array( 'link', 'wp_more', 'spellchecker' );
        $pos = false;
        foreach ($insert_before_tries as $button)
        {
            $pos = array_search( $button, $buttons );
            if ($pos !== false)
            {
                break;
            }
        }
        if ($pos===false)
        {
            $pos = 0;
        }

        $insert = array( 'digimember' );

        array_splice( $buttons, $pos, 0, $insert );

        return $buttons;
    }


    //
    // private
    //
    private function initOnLogin()
    {
        $callback = array( $this, 'cbLoginRedirect' );
        add_filter('login_redirect', $callback, 10, 3);

        $callback = array( $this, 'cbLoginCheckAccountLockPre' );
        add_filter('wp_authenticate', $callback, 999, 3 );

        $callback = array( $this, 'cbLoginCheckAccountLockPost' );
        add_filter('authenticate', $callback, 999, 4 );

        $callback = array( $this, 'cbCountLogin' );
        add_action('wp_login', $callback, 999, 2 );
        $callback = array( $this, 'cbLogAccess' );
        add_action('wp_login', $callback, 999, 2 );
    }

    private function _maybeRenderFooterLink()
    {
        global $DIGIMEMBER_AFFILIATE_FOOTER_LINK_DISABLED;
        if (!empty($DIGIMEMBER_AFFILIATE_FOOTER_LINK_DISABLED))
        {
            return;
        }

        $config = $this->api->load->model( 'logic/blog_config' );
        $show_link = $config->isAffiliateFooterLinkEnabled();
        if (!$show_link)
        {
            return;
        }

        $link = $this->api->load->model( 'logic/link' );

        $url      = $link->affiliateReferalUrl();

        $msg_tmpl = _digi( 'Powered by <a>%s</a>', $this->api->pluginDisplayName() );

        $title = _digi( 'Learn how to monetize your knowledge with %s for Wordpress.', $this->api->pluginDisplayName() );

        $find = "<a>";
        $repl = "<a href='$url' target='_blank' title=\"$title\">";

        $msg = str_replace( $find, $repl, $msg_tmpl );

        echo "<div id='ncore_footer_placeholder'></div><div id='ncore_footer' class='ncore_affiliate_footer'>$msg</div>";
    }

    private function _maybeCallPageViewAction()
    {
        $post = get_post();

        $is_a_page = $post && $post->post_type == 'page';
        if ($is_a_page)
        {
            $model = $this->api->load->model( 'logic/event_subscriber' );
            $model->call( 'dm_on_page_view', ncore_userId(), $post );
        }
    }

    private function _mustDisableTinyMCE()
    {

        $is_thrive_ovation_admin_area = ncore_retrieveGET( 'page' ) == 'tvo_admin_dashboard';
        if ($is_thrive_ovation_admin_area) {
            return true;
        }

        return false;
    }

    private function _initLectureNavigation()
    {
        $menu_name = _digi( '%s Lecture Menu', $this->api->pluginDisplayName() );
        $menu      = wp_get_nav_menu_object( $menu_name );

        if (!$menu)
        {
            $menu_id = wp_create_nav_menu( $menu_name );
            $is_error = $menu_id instanceof WP_Error;
            if (!$is_error)
            {
                $config = $this->api->load->model( 'logic/blog_config' );
                $config->set( 'lecture_menu_id', $menu_id );

                $msg = _digi('With the [MENU] you can show the user the lectures of his current course in any theme. The menu is filled automatically with the lectures of the user\'s current course. However if no course is active, a default menu is shown. You can edit this default menu in the wordpress admin area under Design - Menus (Menu "[MENU]"). There you can also remove this dummy menu entry.)');

                wp_update_nav_menu_item($menu_id, 0, array(
                    'menu-item-title' =>  str_replace( '[MENU]', $menu_name, $msg ),
                    'menu-item-classes' => 'hint',
                    'menu-item-url' => '/',
                    'menu-item-status' => 'publish')
                );
            }
        }
    }

}
