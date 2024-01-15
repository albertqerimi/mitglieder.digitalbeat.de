<?php

class digimember_AccessLogic extends ncore_BaseLogic
{
    const max_access_denied_messages_per_page = 1;

    private static $current_product_id = false;

    private $CONTENT_FILTERS_TO_REMOVE_IF_ACCESS_IS_BLOCKED = array(

        // Thrive Visual Editor
        array(
            'filter'   => 'tve_editor_content',
            'priority' => array( 10, PHP_INT_MAX ),  // Thrive Visual Editor überschreibt jeglichen Content seiner Custom Posts / http://www.thrivethemes.com
        ),
    );

    private $CONTENT_FILTERS_TO_REMOVE_IF_ACCESS_IS_DELAYED = array(

        // Profit Builder
        array(
            'filter'   => array( 'pbuilder', 'replace_content' ),
            'priority' => 999,
        ),
    );

    private $POST_TYPES_NOT_TO_HANDLE_IN_THE_POSTS_FILTER = array(
        'coaching_cards' // for plugin Coaching Cards (http://successcoach.io)
    );

    private function onBlockedInsideTheLoop() {
        $GLOBALS['TVE_CONTENT_SKIP_ONCE'] = true; // für Thrive Visual Editor / http://www.thrivethemes.com
    }

    private $must_postcheck_content_access = false;

    public function setCurrentCourseProductId( $product_id )
    {
        $product_id = (int) $product_id;

        if (!$product_id) {
            return;
        }

        $must_update_settings = $product_id != self::$current_product_id;

        if ($must_update_settings) {
            /** @var ncore_UserSettingsData $userSettingsData */
            $userSettingsData = $this->api->load->model( 'data/user_settings' );
            $userSettingsData->set( 'cur_product_id', $product_id );
        }

        self::$current_product_id = $product_id;
    }

    public function resolveCurrentCourseProductId( $product_obj_or_id='current' )
    {
        $is_logged_in = ncore_userid()>0;
        if (!$is_logged_in) {
            return false;
        }

        $product_id = false;

        if (is_object($product_obj_or_id))
        {
            $product_id = ncore_retrieve( $product_obj_or_id, 'id' );
        }
        elseif (is_numeric( $product_obj_or_id ) && $product_obj_or_id > 0)
        {
            $product_id = $product_obj_or_id;
        }

        if (!$product_id)
        {
            $post = get_post();

            $post_id = ncore_retrieve( $post, array( 'ID', 'id' ) );

            if ($post_id) {
                /** @var digimember_PageProductData $pageProductData */
                $pageProductData = $this->api->load->model( 'data/page_product' );
                /** @var ncore_UserSettingsData $userSettingsData */
                $userSettingsData = $this->api->load->model( 'data/user_settings' );

                $product_id = ncore_retrieveGET( 'digimember_product_id', false );

                if (!$product_id) {
                    $product_id = $userSettingsData->get( 'cur_product_id' );
                }

                if ($product_id) {

                    $where = array( 'post_id' => $post_id, 'product_id' => $product_id, 'is_active' => 'Y' );

                    $is_valid = (bool) $pageProductData->getAll( $where );

                    if (!$is_valid) {
                        $product_id = false;
                    }
                }

                if (!$product_id)
                {
                    $where = array( 'post_id' => $post_id, 'is_active' => 'Y' );

                    $page_products = $pageProductData->getAll( $where );

                    switch (count($page_products))
                    {
                        case 0:
                            $product_id = false;
                            break;

                        case 1:
                            $product_id = $page_products[0]->product_id;
                            break;

                        default:
                            /** @var digimember_UserProductData $userProductData */
                            $userProductData = $this->api->load->model( 'data/user_product' );

                            $orders = $userProductData->getForUser();

                            foreach ( array_reverse( $orders ) as $order)
                            {
                                foreach ($page_products as $page_product)
                                {
                                    $is_match = $order->product_id = $page_product->product_id;
                                    if ($is_match) {
                                        $product_id = $order->product_id;
                                        break 2;
                                    }
                                }
                            }
                    }
                }
            }
        }

        $this->setCurrentCourseProductId( $product_id );

        return $product_id;
    }


    public function setupFilter()
    {
        add_filter( 'the_posts',                 array( $this, 'cbPostListFilter' ) );
        add_filter( 'wp_nav_menu_objects',       array( $this, 'cbPageListFilter' ) );
        add_filter( 'get_pages',                 array( $this, 'cbPageListFilter' ) );
        add_filter( 'wp',                        array( $this, 'cbRedirectingValidator' ) );
        add_filter( 'the_content',               array( $this, 'cbContentFilter' ), PHP_INT_MAX );
        // add_filter( 'tve_landing_page_content', array( $this, 'cbContentFilter' ), PHP_INT_MAX );

        add_filter( 'comments_open',             array( $this, 'cbBoolValidator' ) );
        add_filter( 'tcb_can_display_content',   array( $this, 'cbBoolValidator' ) );

        add_filter( 'widget_display_callback',   array( $this, 'cbDisplayWidgetsFilter' ), 10, 3 );


        add_filter( 'comments_template',         array( $this, 'cbCommentsTemplate' ), 1, 9999 );
        add_filter( 'comments_open',             array( $this, 'cbCommentsOpen' ), 2, 9999 );
        add_filter( 'get_comments_number',       array( $this, 'cbCommentsNumber' ), 2, 9999 );
        add_filter( 'pre_option_page_comments',  array( $this, 'cbPreOptionPagecomments' ), 1, 9999 );

        add_filter( 'template_include',          array( $this, 'cbTemplateInclude' ), PHP_INT_MAX, 1 );

        // add_filter( 'learn_press_get_courses',       array( $this, 'cbPostListFilter' ) );
        // add_filter( 'learn_press_course_object',     array( $this, 'cb_learn_press_course_object' ),  PHP_INT_MAX, 1 );
        // add_filter( 'learn_press_course_curriculum', array( $this, 'cb_learn_press_course_curriculum' ), PHP_INT_MAX, 3 );

        $this->registerCustomAccessFilters();
    }

    function cbTemplateInclude( $template_path )
    {
        if ($this->must_postcheck_content_access)
        {
            global $post;
            if ($post)
            {
                $result = $this->checkContentAccess( $post->post_type, $post->ID, $post_content='<dummy>', $force_full_access_denied_msg=true );

                $is_denied = $result && $result !== '<dummy>';

                if ($is_denied) {
                    global $DM_DENIED_MSG;
                    $DM_DENIED_MSG = $result;
                    return DIGIMEMBER_DIR . '/inc/forbidden.php';
                }
            }
        }

        return $template_path;
    }

    public function blockAccess( $user_id, $reason )
    {
        if (ncore_canAdmin( $user_id ))
        {
            $this->access_block_reason = '';
            return;
        }

        /** @var ncore_UserSettingsData $userSettingsData */
        $userSettingsData = $this->api->load->model( 'data/user_settings' );

        $date = ncore_dbDate( 'now', 'date' );

        $userSettingsData->setForUser( $user_id, 'access_limit_violated_at',     $date );
        $userSettingsData->setForUser( $user_id, 'access_limit_violated_reason', $reason );

        $this->access_block_reason = $reason;
    }

    public function unBlockAccess( $user_id )
    {

        /** @var ncore_UserSettingsData $userSettingsData */
        $userSettingsData = $this->api->load->model( 'data/user_settings' );
        $userSettingsData->setForUser( $user_id, 'access_limit_violated_at',     null );
        $userSettingsData->setForUser( $user_id, 'access_limit_violated_reason', null );

        $ipCounterModel = $this->api->load->model( 'data/ip_counter' );
        $ipCounterModel->deleteForUserId($user_id);
        /** @var digimember_BlogConfigLogic $blogConfig */
        $blogConfig = $this->api->load->model( 'logic/blog_config' );
        $blogConfig->set( 'limit_login_remove_email', '' );
    }

    public function blockAccessReason( $user_id='current' )
    {
        if ($this->access_block_reason!=='none')
        {
            return $this->access_block_reason;
        }

        if (!$user_id || $user_id==='current')
        {
            $user_id = ncore_userId();
        }

        if (ncore_canAdmin( $user_id ))
        {
            $this->access_block_reason = '';
            return '';
        }

        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $limit = $model->getIpAccessLimit();
        if (!$limit)
        {
            $this->access_block_reason = '';
            return '';
        }

        if (!$user_id)
        {
            $user_id = ncore_userId();
        }

        if (!$user_id)
        {
            return '';
        }

        /** @var ncore_UserSettingsData $userSettingsData */
        $userSettingsData = $this->api->load->model( 'data/user_settings' );

        $blocked_at = $userSettingsData->getForUser( $user_id, 'access_limit_violated_at' );

        if (!$blocked_at)
        {
            $this->access_block_reason = '';
            return '';
        }

        $date = ncore_dbDate( 'now', 'date' );
        if ($date != $blocked_at)
        {
            $this->access_block_reason = '';
            return '';
        }

        $this->access_block_reason = $userSettingsData->getForUser( $user_id, 'access_limit_violated_reason', 'unknown_reason' );

        return $this->access_block_reason;
    }

    public function loginUrl( $user_obj_or_id='current' )
    {
        if ($user_obj_or_id==='current') {
            $user_obj_or_id = ncore_userId();
        }

        $user_id = is_numeric( $user_obj_or_id )
                   ? $user_obj_or_id
                   : ncore_retrieve( $user_obj_or_id, array( 'ID', 'id' ) );
        if (!$user_id)
        {
            return false;
        }

        $product_model = $this->api->load->model( 'data/product' );
        $userpro_model = $this->api->load->model( 'data/user_product' );
        $counter_model = $this->api->load->model( 'data/counter' );

        $last_login_time = $counter_model->countLogin( $user_id );

        $user_products = array_reverse( $userpro_model->getForUser( $user_id ) );

        $login_url = false;

        foreach ($user_products as $one)
        {
            $product_id = $one->product_id;

            $product = $product_model->getCached( $product_id );
            if (!$product)
            {
                continue;
            }

            if ($login_url===false)
            {
                $login_url = $product->login_url;
            }

            $is_first_login = $one->has_visited_login_page == 'N';
            if ($is_first_login)
            {
                $data = array( 'has_visited_login_page' => 'Y' );
                $userpro_model->update( $one, $data );

                if ($product->first_login_url) {
                    return ncore_resolveUrl( $product->first_login_url );
                }
            }
        }

        return ncore_resolveUrl( $login_url );
    }


     private $removed_filters = array();

    private function disable3rdPartyContentFilters()
    {
        $third_party_content_filters = array(
            array(
                'function' => 'tve_clean_wp_editor_content',
                'priority' => -100,
            ),
            array(
                'class'        => 'SiteOrigin_Panels',
                'method'       => 'generate_post_content',
                'get_instance' => 'SiteOrigin_Panels::single',
                'priority'     => 10,
            ),
        );

        $filter_stack = array();

        foreach ($third_party_content_filters as $meta)
        {
            $callable = false;

            $have_function = !empty( $meta[ 'function' ] ) && function_exists( $meta[ 'function' ] );
            if ($have_function)
            {
                $callable = $meta[ 'function' ];
            }

            $have_class = !empty( $meta[ 'class' ] )
                       && !empty( $meta[ 'get_instance' ] )
                       && class_exists( $meta[ 'class' ] )
                       && is_callable( $meta[ 'get_instance' ] );
            if ($have_class)
            {
                $object = call_user_func(  $meta[ 'get_instance' ] );

                $callable = array( $object, $meta[ 'method' ] );
            }

            if (!$callable) {
                continue;
            }

            $priority = isset( $meta[ 'priority' ] )
                      ? $meta[ 'priority' ]
                      : 10;

            $is_removed = remove_filter( 'the_content', $callable, $priority );
            if ($is_removed) {
                $filter_stack[] = array( $callable, $priority );
            }
        }

        $this->removed_filters[] = $filter_stack;
    }

    private function enable3rdPartyContentFilters()
    {
        $filter_stack = array_pop( $this->removed_filters );
        foreach ($filter_stack as $function_prio)
        {
            list( $callable, $prio ) = $function_prio;
            add_filter( 'the_content', $callable, $prio );
        }
    }


    private function areCommentsVisible()
    {
        $post = get_post();

        if (!$post) {
            return true;
        }

        static $cache;

        $are_comments_visible =& $cache[ $post->ID ];
        if (isset($are_comments_visible)) {
            return $are_comments_visible;
        }

        list( $access_type, $wait_days, $product_id ) = $this->accessTypeComments( $post->post_type, $post );

        $must_check_comments = $access_type !== DIGI_ACCESS_FULL && $product_id!==false;
        if (!$must_check_comments) {
            $are_comments_visible = true;
            return $are_comments_visible;
        }

        $model = $this->api->load->model( 'data/product' );
        $product = $model->get( $product_id );

        $are_comments_visible = !$product
                             || $product->are_comments_protected == 'N';

        return $are_comments_visible;
    }

    public function cbCommentsTemplate( $template )
    {
        return $this->areCommentsVisible()
               ? $template
               : dirname(dirname(dirname(dirname( __FILE__ )))) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'comments.php';
    }

    public function cbPreOptionPagecomments( $enabled )
    {
        return $this->areCommentsVisible()
               ? $enabled
               : 0;
    }
    public function cbCommentsNumber( $count, $post_id )
    {
        return $this->areCommentsVisible()
               ? $count
               : 0;
    }
    public function cbCommentsOpen( $open, $post_id )
    {
        return $this->areCommentsVisible()
               ? $open
               : false;
    }

    public function cbDisplayWidgetsFilter( $instance, $wp_widgets_obj, $args )
    {
        $is_digimember_widget = $wp_widgets_obj && is_a( $wp_widgets_obj, 'ncore_WidgetClass' );
        if (!$is_digimember_widget) {
            return $instance;
        }

        $visible_for                  = ncore_retrieve( $instance, 'dm_visible' );
        $required_owned_product_ids   = ncore_retrieve( $instance, 'dm_owned_product_ids' );
        $page_of_product_ids          = ncore_retrieve( $instance, 'dm_pages_of_product_ids' );

        $is_logged_in = ncore_userId() > 0;

        switch ($visible_for) {
            case 'logged_out':
                if ($is_logged_in) {
                    return false;
                }
                break;
            case 'logged_in':
                if (!$is_logged_in) {
                    return false;
                }
                break;
        }

        if ($required_owned_product_ids) {

            if (!$is_logged_in) {
                return false;
            }

            static $my_product_ids;
            if (!isset($my_product_ids))
            {
                /** @var digimember_UserProductData $model */
                $model = $this->api->load->model( 'data/user_product' );
                $user_products = $model->getForUser( 'current' );

                $this->api->load->helper( 'array' );
                $my_product_ids = ncore_retrieveValues( $user_products, 'product_id' );
            }

            if (!$my_product_ids) {
                return false;
            }

            $required_owned_product_ids = explode( ',', $required_owned_product_ids );

            $owns_product = in_array( 'all', $required_owned_product_ids );
            if (!$owns_product) {
                $owns_product = (bool) array_intersect( $required_owned_product_ids, $my_product_ids );
            }

            if (!$owns_product) {
                return false;
            }
        }

        if ($page_of_product_ids)
        {
            if (!$is_logged_in) {
                return false;
            }

            $page = get_post();

            if (!isset($page->post_type) || $page->post_type != 'page') {
                return false;
            }

            static $page_products;
            if (!isset($page_products)) {
                /** @var digimember_PageProductData $model */
                $model = $this->api->load->model( 'data/page_product' );
                $page_products = $model->getForPage( $page->post_type, $page->ID, true );
            }

            if (!$page_products) {
                return false;
            }

            $page_of_product_ids = explode( ',', $page_of_product_ids );
            $is_page_of_product = in_array( 'all', $page_of_product_ids );

            if (!$is_page_of_product) {
                foreach ($page_products as $one)
                {
                    if (in_array( $one->product_id, $page_of_product_ids )) {
                        $is_page_of_product = true;
                        break;
                    }
                }
            }

            if (!$is_page_of_product) {
                return false;
            }
        }

        return $instance;
    }

    public function cbPostListFilter( $posts )
    {
        if (!$posts)
        {
            return array();
        }

        $allowed_posts = $this->filterList( $posts, $allowAllPages=true );

        $must_handle_denied_access = count( $allowed_posts ) == 0
                                  && !ncore_isInSidebar();
        if ($must_handle_denied_access)
        {
            if (empty($posts)) {
                return array();
            }



            $post = $posts[0];

            $post_id   =  ncore_retrieve( $post, 'ID' );
            $post_type =  ncore_retrieve( $post, 'post_type' );
            $content   =  ncore_retrieve( $post, 'post_content' );

            $is_handled = !in_array( $post_type, $this->POST_TYPES_NOT_TO_HANDLE_IN_THE_POSTS_FILTER );

            if ($is_handled) {

                $this->checkRedirectAccess($post_type, $post_id);

                $post->post_content = $this->checkContentAccess($post_type, $post_id, $content);

                if (empty($post->post_excerpt)) {
                    $theme = wp_get_theme();
                    if ($theme->name != 'Twenty Twenty') {
                        $post->post_excerpt = apply_filters('get_the_excerpt', $content);
                    }
                }

                $this->clear3rdPartyContentFiltersForBlockedContent();

                return array($post);
            }
        }

        return $allowed_posts;
    }

    public function cbPageListFilter( $pages )
    {
        return $this->filterList( $pages, $allowAllPages=false);
    }

    private function filterList( $pages, $allowAllPages )
    {
        $readablePages = array();

        foreach ($pages as $page) {

            $post_type = $page->post_type;

            if ($allowAllPages && $post_type=='page')
            {
                $readablePages[] = $page;
                continue;
            }

            list( $access_type, $wait_days, $product_id ) = $this->accessType( $post_type, $page );

            $access_granted = $access_type != DIGI_ACCESS_NONE;

            if ($access_granted)
            {
                $readablePages[] = $page;
            }
        }

        return $readablePages;
    }

/*
    public function cb_learn_press_course_object( $course )
    {
        list( $access_type, $wait_days, $product_id ) = $this->accessType( "lp_course", $course );

        $has_access = $access_type === DIGI_ACCESS_FULL;
        if ($has_access) {
            return $course;
        }

        $course->post_content = $this->checkContentAccess( $course->post_type, $course->ID, $course->post_type, $force_full_access_denied_msg=true );

        return $course;
    }

    public function cb_learn_press_course_curriculum( $curriculum, $course_id, $secion_id )
    {
        list( $access_type, $wait_days, $product_id ) = $this->accessType( "lp_course", $course_id );

        $has_access = $access_type === DIGI_ACCESS_FULL;
        if ($has_access) {
            return $curriculum;
        }

        return  false;
    }
*/

    public function cbContentFilter( $content )
    {
        if (!$this->isAccessCheckEnabled())
        {
            return $content;
        }

        list( $post_id, $post_type ) = ncore_currentPostIdAndType();

        if ($post_id) {
            return $this->checkContentAccess( $post_type, $post_id, $content );
        }
        else
        {
            $this->must_postcheck_content_access = true;

            // I do not rember what theme/plugin requires this filter to return '' for $post_id == 0.
            //
            // I have tested:
            // ( -   = does not have $post_id 0
            //   c   = has $post_id 0, but expects $contents to be returned
            //   !!! = has $post_id 0, but expects '' to be returned )
            //
            // BuddyPress:      c
            // OptimizePress 2: -
            // ProfitBuilder:   -
            //
            // Christian / 08/26/2015

            return $content; //   '';
        }
    }

    public function cbRedirectingValidator()
    {
        list( $post_id, $post_type ) = ncore_currentPostIdAndType();

        $this->checkRedirectAccess( $post_type, $post_id );
    }

    public function cbBoolValidator( $access_allowed=true )
    {
        if (!$access_allowed)
        {
            return false;
        }

        list( $post_id, $post_type ) = ncore_currentPostIdAndType();

        list( $access_type, $wait_days, $product_id ) = $this->accessType( $post_type, $post_id );

        return $access_type == DIGI_ACCESS_FULL;
    }

    static private $access_cache = array();
    private $access_block_reason = 'none';

    public function accessType( $post_type='current', $obj_or_id='current', $user_id='current' )
    {
        return apply_filters('digimember/access/accessType', $this->_accessType($post_type, $obj_or_id, $user_id), $post_type, $obj_or_id, $user_id);
    }

    /**
     * Seperate accesscheck for the comments section to prevent access overwrite on elementor posts
     * implemented for issue/DM-237
     * @param string $post_type
     * @param string $obj_or_id
     * @param string $user_id
     * @return array|mixed
     */
    public function accessTypeComments( $post_type='current', $obj_or_id='current', $user_id='current' )
    {
        return $this->_accessType($post_type, $obj_or_id, $user_id);
    }

    public function _accessType( $post_type='current', $obj_or_id='current', $user_id='current' )
    {
        if ($user_id==='current')
        {
            $user_id = ncore_userId();
        }

        $is_blocked = (bool) $this->blockAccessReason( $user_id );
        if ($is_blocked)
        {
            return array( DIGI_ACCESS_NONE, $wait_days = false, $product_id=false, $must_ask=false, $unlock_datetime=false );
        }

        list( $post_type, $post_id ) = $this->_resolvePostType( $post_type, $obj_or_id );
        if (!$post_id)
        {
            return array( DIGI_ACCESS_FULL, $wait_days = false, $product_id=false, $must_ask=false, $unlock_datetime=false );
        }

        $blog_id = ncore_blogId();

        $access_info =& self::$access_cache[ $blog_id ][ $user_id ][ $post_type ][ $post_id ];

        if (isset($access_info))
        {
            return $access_info;
        }

        /** @var digimember_PageProductData $page_product_model */
        $page_product_model = $this->api->load->model( 'data/page_product' );

        $full_access = array( DIGI_ACCESS_FULL, $wait_days = false, $product_id=false, $must_ask=false, $unlock_datetime=false );

        $type_is_handled_by_me = $page_product_model->isPostTypeHandled( $post_type );
        if (!$type_is_handled_by_me)
        {
            return $access_info=$full_access;
        }

        if (current_user_can('manage_options') )
        {
            return $access_info=$full_access;
        }

        /** @var digimember_UserProductData $user_product_model */
        $user_product_model = $this->api->load->model( 'data/user_product' );

        $postProducts = $page_product_model->getForPage( $post_type, $post_id, $active_only=true );

        if (!$postProducts)
        {
            return $access_info = $full_access;
        }

        $userProducts = $user_product_model->getForUser( $user_id );

        $is_in_menu      = false;
        $wait_days       = false;
        $product_id      = false;
        $unlock_datetime = false;

        $preview_product_id=false;

        $access_with_waiver = false;

        $product_accessinfo_mapping = array();
        foreach ($postProducts as $postProduct)
        {
            if (!$product_id)
            {
                $product_id = $postProduct->product_id;
            }

            $have_match = false;

            foreach ($userProducts as $userProduct)
            {
                $matches = $postProduct->product_id == $userProduct->product_id;
                if (!$matches)
                {
                    continue;
                }

                $have_match = true;

                list( $one_is_readable, $one_is_in_menu, $one_wait_days, $one_has_preview, $one_ask_for_waiver, $one_unlock_datetime, $one_has_error_handling_priority ) = $this->_checkAccess( $postProduct, $userProduct );

                $product_accessinfo_mapping[] = array(
                    'product_id' => $postProduct->product_id,
                    'is_readable' => $one_is_readable,
                    'is_in_menu' => $one_is_in_menu,
                    'wait_days' => $one_wait_days,
                    'has_preview' => $one_has_preview,
                    'ask_for_waiver' => $one_ask_for_waiver,
                    'unlock_datetime' => $one_unlock_datetime,
                    'has_error_handling_priority' => $one_has_error_handling_priority
                );
                if ($one_is_readable==='skip')
                {
                    continue;
                }

                $product_id = $postProduct->product_id;

                if ($one_is_readable)
                {
                    if ($one_ask_for_waiver) {
                        $product_accessinfo_mapping[array_key_last($product_accessinfo_mapping)]['ask_for_waiver'] = array( DIGI_ACCESS_WAIVER, $one_wait_days, $product_id );
                        continue;
                    }
                    else
                    {
                        return $access_info = $full_access;
                    }
                }

                if ($wait_days === false)
                {
                    $wait_days       = $one_wait_days;
                    $product_accessinfo_mapping[array_key_last($product_accessinfo_mapping)]['unlock_datetime'] = $one_unlock_datetime;
                }
                elseif ($one_wait_days !== false && $one_wait_days < $wait_days)
                {
                    $wait_days       = $one_wait_days;
                    $product_accessinfo_mapping[array_key_last($product_accessinfo_mapping)]['unlock_datetime'] = $one_unlock_datetime;
                }
            }

            if (!$have_match)
            {
                list( $one_is_readable, $one_is_in_menu, $one_wait_days, $one_has_preview, $one_ask_for_waiver, $one_unlock_datetime, $one_has_error_handling_priority ) = $this->_checkAccess( $postProduct, $userProduct=false );
                $product_accessinfo_mapping[] = array(
                    'product_id' => $postProduct->product_id,
                    'is_readable' => $one_is_readable,
                    'is_in_menu' => $one_is_in_menu,
                    'wait_days' => $one_wait_days,
                    'has_preview' => $one_has_preview,
                    'ask_for_waiver' => $one_ask_for_waiver,
                    'unlock_datetime' => $one_unlock_datetime,
                    'has_error_handling_priority' => $one_has_error_handling_priority
                );

                if ($one_is_readable === 'skip')
                {
                    continue;
                }

                if ($one_is_readable)
                {
                    return $access_info = $full_access;
                }

                $product_id = $postProduct->product_id;

                if ($one_has_preview)
                {
                    $preview_product_id = $product_id;
                }

                if ($one_is_in_menu)
                {
                    $is_in_menu = true;
                }
            }
        }

        list( $product_id, $is_in_menu, $wait_days, $preview_product_id, $access_with_waiver, $unlock_datetime) = $this->getPriorizedAccessinfo($product_accessinfo_mapping);

        if ($access_with_waiver) {
            return $access_with_waiver;
        }

//        if (!$this->mayShowInMenu( $post_type ))
//        {
//            $is_in_menu = false;
//        }

//        $access_type = $is_in_menu
//                     ? DIGI_ACCESS_MENU
//                     : ($preview_product_id
//                        ? DIGI_ACCESS_PREV
//                        : DIGI_ACCESS_NONE);
//
//        if ($access_type==DIGI_ACCESS_PREV)
//        {
//            $product_id = $preview_product_id;
//        }

        if ($is_in_menu && $preview_product_id) {
            $access_type = DIGI_ACCESS_PREV;
        }
        else {
            $access_type = $is_in_menu
                ? DIGI_ACCESS_MENU
                : ($preview_product_id
                    ? DIGI_ACCESS_PREV
                    : DIGI_ACCESS_NONE);
        }

        if ($access_type==DIGI_ACCESS_PREV)
        {
            $product_id = $preview_product_id;
        }

        return $access_info = array( $access_type, $wait_days, $product_id, $unlock_datetime );
    }

    private function getPriorizedAccessinfo($accessInfoMapping) {
        $atLastOneInMenu = false;
        foreach ($accessInfoMapping as $accessInfo) {
            if ($accessInfo['is_in_menu']) {
                $atLastOneInMenu = true;
            }
        }
        foreach ($accessInfoMapping as $accessInfo) {
            if ($accessInfo['has_preview']) {
                $preview_id = $accessInfo['product_id'];
            }
            else {
                $preview_id = false;
            }
            if ($accessInfo['is_readable']) {
                if ($atLastOneInMenu) {
                    $accessInfo['is_in_menu'] = true;
                }
                return array($accessInfo['product_id'], $accessInfo['is_in_menu'], $accessInfo['wait_days'], $preview_id, $accessInfo['ask_for_waiver'], $accessInfo['unlock_datetime'] );
            }
        }
        foreach ($accessInfoMapping as $accessInfo) {
            if ($accessInfo['has_preview']) {
                $preview_id = $accessInfo['product_id'];
            }
            else {
                $preview_id = false;
            }
            if ($accessInfo['wait_days'] && $accessInfo['wait_days'] > 0) {
                if ($atLastOneInMenu) {
                    $accessInfo['is_in_menu'] = true;
                }
                return array($accessInfo['product_id'], $accessInfo['is_in_menu'], $accessInfo['wait_days'], $preview_id, $accessInfo['ask_for_waiver'], $accessInfo['unlock_datetime'] );
            }
        }
        foreach ($accessInfoMapping as $accessInfo) {
            if ($accessInfo['has_preview']) {
                $preview_id = $accessInfo['product_id'];
            }
            else {
                $preview_id = false;
            }
            if ($accessInfo['has_error_handling_priority']) {
                if ($atLastOneInMenu) {
                    $accessInfo['is_in_menu'] = true;
                }
                return array($accessInfo['product_id'], $accessInfo['is_in_menu'], $accessInfo['wait_days'], $preview_id, $accessInfo['ask_for_waiver'], $accessInfo['unlock_datetime'] );
            }
        }
        $firstProductMapping = $accessInfoMapping[0];
        if ($firstProductMapping['has_preview']) {
            $preview_id = $firstProductMapping['product_id'];
        }
        else {
            $preview_id = false;
        }
        if ($atLastOneInMenu) {
            $firstProductMapping['is_in_menu'] = true;
        }
        return array($firstProductMapping['product_id'], $firstProductMapping['is_in_menu'], $firstProductMapping['wait_days'], $preview_id, $firstProductMapping['ask_for_waiver'], $firstProductMapping['unlock_datetime'] );
    }


    private function _resolvePostType( $post_type='current', $obj_or_id='current' )
    {
        if ($obj_or_id === 'current')
        {
            $post = get_post();
            if ($post) {
                $obj_or_id = $post;
                $post_type = $post->post_type;
            }
            else
            {
                $obj_or_id = false;
                $post_type = 'page';
            }
        }

        if ($post_type === 'current')
        {
            $post = get_post( $obj_or_id );
            $post_type = $post
                       ? $post->post_type
                       : 'page';
        }

        $is_id = is_numeric( $obj_or_id );
        if ($is_id)
        {
            $post_id = $obj_or_id;
            return array( $post_type, $post_id );
        }

        $obj = $obj_or_id;

        $model = $this->api->load->model( 'data/page_product' );
        $type_valid = in_array( $post_type, $model->postTypes() );

        if ($type_valid)
        {
            $post_id = ncore_retrieve( $obj, 'ID' );
            return array( $post_type, $post_id );
        }

        $post_id   = ncore_retrieve( $obj, 'object_id' );
        $post_type = ncore_retrieve( $obj, 'object' );

        return array( $post_type, $post_id );
    }


    private function _checkAccess( $postProduct, $userProduct )
    {
        $product_id = $postProduct->product_id;

        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );

        $product = $productData->getCached( $product_id );

        if (!$product)
        {
            return array( $is_readable='skip', $is_in_menu=false, $wait_days=false, $has_preview=false, $ask_for_waiver=false, $unlock_datetime=false, $has_error_handling_priority=false );
        }

        if (!$product->published)
        {
            return array( $is_readable=false, $is_in_menu=false, $wait_days=false, $has_preview=false, $ask_for_waiver=false, $unlock_datetime=false, $has_error_handling_priority=false );
        }

        /** @var digimember_HasPreviewCacheData $hasPreviewCacheData */
        $hasPreviewCacheData = $this->api->load->model( 'data/has_preview_cache' );
        $has_preview = $hasPreviewCacheData->hasPreview( $postProduct->post_id );

        $is_logged_in = ncore_isLoggedIn();
        $has_error_handling_priority = $product->access_denied_priority == 'Y' ? true : false;
        if (!$is_logged_in)
        {
            $is_in_menu = $product->show_in_menu_if_logged_out;
            return array( $is_readable=false, $is_in_menu, $wait_days=false, $has_preview, $ask_for_waiver=false, $unlock_datetime=false, $has_error_handling_priority );
        }

        if (!$userProduct || $userProduct->is_active=='N')
        {
            $is_in_menu = $product->show_in_menu_if_not_bought;
            return array( $is_readable=false, $is_in_menu, $wait_days=false, $has_preview, $ask_for_waiver=false, $unlock_datetime=false, $has_error_handling_priority );
        }

        if ($userProduct->is_access_too_early)
        {
            return array( $is_readable='skip', $is_in_menu=false, $wait_days=false, $has_preview=false, $ask_for_waiver=false, $unlock_datetime=false, $has_error_handling_priority );
        }

        if ($userProduct->is_access_too_late)
        {
            return array( $is_readable='skip', $is_in_menu=false, $wait_days=false, $has_preview=false, $ask_for_waiver=false, $unlock_datetime=false, $has_error_handling_priority );
        }

        if ($userProduct->is_access_expired)
        {
            return array( $is_readable=false, $is_in_menu=false, $wait_days=false, $has_preview, $ask_for_waiver=false, $unlock_datetime=false, $has_error_handling_priority );
        }

        $is_unlocked = $this->_matchesUnlockPolicy( $product, $postProduct, $userProduct );
        if (!$is_unlocked) {
            return array( $is_readable='skip', $is_in_menu=false, $wait_days=false, $has_preview=false, $ask_for_waiver=false, $unlock_datetime=false, $has_error_handling_priority );
        }

        $must_ask_for_waiver = ncore_isTrue( $product->is_right_of_withdrawal_waiver_required )
                            && ncore_isFalse( $userProduct->is_right_of_rescission_waived );

        if (!$postProduct->unlock_day)
        {
            $age_in_days = $userProduct->age_in_days;
            $unlock_datetime = false;
            $unlock_day = 0;
        }
        else
        {
            switch ($product->unlock_mode)
            {
                case 'fix_date':

                    $this->api->load->helper( 'date' );
                    static $now_unix, $now_hour, $now_date;
                    if (!isset($now_unix))
                    {
                        $now_unix = time() +  3600 * get_option('gmt_offset');
                        $now_hour = date( 'H:i:s', $now_unix );
                        $now_date = ncore_dbDate( $now_unix, 'date' );
                    }

                    $product_unlock_datetime = $product->unlock_start_date;
                    list( $product_unlock_date, $product_unlock_time ) = ncore_retrieveList( ' ', $product->unlock_start_date );
                    $product_content_unlock_datetime = ncore_dateAddDays($product_unlock_date, $postProduct->unlock_day).' '.$product_unlock_time;

                    if (($diffInSeconds = ncore_unixDiffSeconds(ncore_unixDate($product_unlock_datetime), ncore_unixDate($product_content_unlock_datetime))) > 0) {
                        $daysleft = ncore_dateDiffDaysCeil($product_content_unlock_datetime, 'now', false);
                        $unlock_day = $daysleft;
                        $unlock_datetime = $product_content_unlock_datetime;
                        $age_in_days = 0;
                    }
                    else {
                        $unlock_day = 0;
                        $unlock_datetime = false;
                        $age_in_days = ncore_dateDiffDays( $product_unlock_date, $now_date, false );
                    }
                    break;
                case 'order_date':
                default:
                    $unlock_day  = $postProduct->unlock_day;
                    $age_in_days = $userProduct->age_in_days;
                    $unlock_datetime = false;


            }
        }

        $is_readable_all_the_time = $unlock_day <= 0;
        if ($is_readable_all_the_time)
        {
            return array( $is_readable=true, $is_in_menu=true, $wait_days=false, $has_preview=false, $must_ask_for_waiver, $unlock_datetime=false, $has_error_handling_priority );
        }

        $days_left = $unlock_day - $age_in_days;

        $is_readable_now = $days_left <= 0 || !$this->haveWaitDays();

        if ($is_readable_now)
        {
            return array( $is_readable=true, $is_in_menu=true, $wait_days=false, $has_preview=false, $must_ask_for_waiver, $unlock_datetime=false, $has_error_handling_priority );
        }

        $is_in_menu = $product->show_in_menu_if_locked;
        return array( $is_readable=false, $is_in_menu, $days_left, $has_preview, $ask_for_waiver=false, $unlock_datetime, $has_error_handling_priority );
    }


    private function mayShowInMenu( $post_type )
    {
        return $post_type != 'post';
    }


    private function checkContentAccess( $post_type, $post_id, $content, $force_full_access_denied_msg=false )
    {
        $is_this_an_excerpt_call = !in_the_loop() && !$content;
        if ($is_this_an_excerpt_call) {
            return '';
        }

        list( $access_type, $wait_days, $product_id, $unlock_datetime ) = $this->accessType( $post_type, $post_id );

        if ($access_type == DIGI_ACCESS_FULL)
        {
            return $content;
        }

        if ($access_type == DIGI_ACCESS_WAIVER)
        {
            $this->_handleAccessWaiver( $product_id );
            if (!$wait_days)
            {
                return $content;
            }
        }

        if ($access_type == DIGI_ACCESS_PREV)
        {
            $controller = $this->api->load->controller( 'shortcode' );
            $shortcode = $controller->shortCode( 'preview' );

            list( $preview, $protected ) = ncore_retrieveList( "<!-- $shortcode -->", $content, 2, true );

            $this->_trimHtmlCommentParts( $preview );

            if ($protected)
            {
                if ($this->isContentShownEvenIfPageLocked($content)) {
                    return $content;
                }

                if ($this->isLockedHintHidden( $content ))
                {
                    return '';
                }

                list( $message, $css, $redirect_url ) = $wait_days > 0
                         ? $this->renderWaitDaysMessage( $product_id, $wait_days, $unlock_datetime )
                         : $this->renderSalesLetter( $product_id );

                if ($redirect_url) {
                    ncore_redirect( $redirect_url );
                }

                return "$preview<div class='$css'>$message</div>";
            }

            return $content;
        }

        if ($this->isContentShownEvenIfPageLocked($content)) {
            return $content;
        }
        elseif ($wait_days === false)
        {
            $show_full_access_denied_msg = in_the_loop() || $force_full_access_denied_msg;

            if ($show_full_access_denied_msg)
            {
                $this->onBlockedInsideTheLoop();

                static $access_denied_msg_rendered;

                if (empty($access_denied_msg_rendered)) {
                    $access_denied_msg_rendered = 1;
                } else {
                    $access_denied_msg_rendered++;
                }

                if ($access_denied_msg_rendered>self::max_access_denied_messages_per_page) {
                    return '';
                }
            }

            $is_logged_in = 0&&ncore_isLoggedIn(); //why? discover later
            if ($is_logged_in || !$show_full_access_denied_msg)
            {
                $css = 'digimeber_content_locked';
                $message = _digi( 'Sorry! You don\'t have access to this page.' );
            }
            else
            {
                if ($product_id) {
                    $model = $this->api->load->model( 'data/product' );
                    $product = $model->get( $product_id );

                    $show_text = $product->access_denied_type == DIGIMEMBER_AD_TEXT
                              && $product->access_denied_text;

                    if ($show_text)
                    {
                        return do_shortcode( $product->access_denied_text );
                    }
                }

                $settings = array( 'redirect_url' => ncore_currentUrl() );
                $controller = $this->api->load->controller( 'user/login_form', $settings );

                ob_start();
                $controller->dispatch();
                return ob_get_clean();
            }
        }
        elseif ($this->isLockedHintHidden( $content ))
        {
            return '';
        }
        else
        {
            list( $message, $css, $redirect_url ) = $this->renderWaitDaysMessage( $product_id, $wait_days, $unlock_datetime, $post_id );

            if ($redirect_url) {
                ncore_redirect( $redirect_url );
            }
        }

        return "<p class='digimember_access_denied $css'>$message</p>";
    }


    private function checkRedirectAccess( $post_type, $post_id )
    {
        list( $access_type, $wait_days, $product_id, $unlock_datetime ) = $this->accessType( $post_type, $post_id );

        $will_be_unlocked_automatically = $wait_days !== false;

        if ($access_type == DIGI_ACCESS_WAIVER)
        {
            $this->_handleAccessWaiver( $product_id );
        }

        $must_redirect = $access_type != DIGI_ACCESS_FULL
                      && $access_type != DIGI_ACCESS_PREV
                      && !$will_be_unlocked_automatically;

        if (!$must_redirect)
        {
            return;
        }

        $this->api->load->model( 'data/product' );
        $product = $this->api->product_data->getCached( $product_id );
        if (!$product)
        {
            return;
        }

        switch ($product->access_denied_type)
        {
            case DIGIMEMBER_AD_URL:
                $url = $product->access_denied_url;
                break;

            case DIGIMEMBER_AD_PAGE:
                $page_id = $product->access_denied_page;

                $url = $page_id;

                $have_id = is_numeric( $page_id ) && $page_id > 0;
                if ($have_id)
                {
                    list( $access_type, $wait_days, $product_id ) = $this->accessType( 'page', $page_id );
                    if ($access_type != DIGI_ACCESS_FULL)
                    {
                        return;
                    }
                }
                break;

            case DIGIMEMBER_AD_LOGIN:
            CASE DIGIMEMBER_AD_TEXT:
            default:
                return;
        }

        if ($url)
        {
            $this->api->load->helper( 'url' );
            $url = ncore_resolveUrl( $url );
        }
        else
        {
            $url = ncore_siteUrl();
        }

        ncore_redirect( $url );
        exit;
    }


    private function isLockedHintHidden( $content )
    {
         if (ncore_isOptimizePressPage()) {
            $controller = $this->api->load->controller( 'shortcode' );
            $shortcode = $controller->shortcode('op_locked_hint');

            $shortcode = "<!-- $shortcode -->";

            $have_shortcode = strpos( $content, $shortcode ) !== false;

            return !$have_shortcode;
        }

        return false;
    }

    private function isContentShownEvenIfPageLocked( &$content )
    {
         if (ncore_isOptimizePressPage()) {
            $controller = $this->api->load->controller( 'shortcode' );
            $shortcode = $controller->shortcode('op_show_always');

            $shortcode = "<!-- $shortcode -->";

            $have_shortcode = strpos( $content, $shortcode ) !== false;

            if ($have_shortcode) {
                $this->_trimHtmlCommentParts( $content );
            }

            return $have_shortcode;
        }

        return false;
    }

    private function renderWaitDaysMessage( $product_id, $wait_days, $unlock_datetime=false, $post_id=null )
    {
        $model = $this->api->load->model( 'data/product' );
        $product = $model->get( $product_id );

        $message = '';
        $css     = '';
        $url     = '';

        $content_later_type = ncore_retrieve( $product, 'content_later_type', DIGIMEMBER_AD_TEXT );

        switch ($content_later_type)
        {
            case DIGIMEMBER_AL_PAGE:
                $url = ncore_resolveUrl( $product->content_later_page );
                break;

            case DIGIMEMBER_AL_URL:
                $url = ncore_resolveUrl( $product->content_later_url );
                break;

            case DIGIMEMBER_AD_TEXT:
            default:

                $this->api->load->helper( 'date' );

                if ($unlock_datetime)
                {
                    $time_unix    = time() + 3600 * get_option('gmt_offset');
                    $date         = ncore_formatDate( $unlock_datetime );
                    $wait_seconds = strtotime( $unlock_datetime ) - $time_unix;

                    list( $unlock_date, $time ) = ncore_retrieveList( ' ', $unlock_datetime, 2, true );
                    $is_today    = $unlock_date == ncore_dbDate( 'now', 'date' );
                    $is_tomorrow = $unlock_date == ncore_dbDate( $time_unix+86400, 'date' );

                    if (ncore_stringEndsWith( $time, ':00')) {
                        $time = substr( $time, 0, -3 );
                    }

                    if ($is_today)
                    {
                        $css = 'digimeber_wait_1_more_day';
                        $message = _digi( 'This content will be unlocked today at %s.', $time );
                    }
                    elseif ($is_tomorrow)
                    {
                        $css = 'digimeber_wait_1_more_day';
                        $message = _digi( 'This content will be unlocked today at %s.', $time );
                    }
                    else
                    {
                        $css = sprintf( 'digimeber_wait_%s_more_days', $wait_days );
                        $message = _digi( 'This content will be unlocked on %s at %s.', $date, $time );
                    }

                }
                else
                {
                    $wait_seconds = 86400*$wait_days;
                    $date         = ncore_formatDate( time() + $wait_seconds );
                    $time         = '0:00';

                    if ($wait_days == 1)
                    {
                        $css = 'digimeber_wait_1_more_day';
                        $message = _digi( 'This content will be unlocked tomorrow.' );
                    }
                    else
                    {
                        $css = sprintf( 'digimeber_wait_%s_more_days', $wait_days );
                        $message = _digi( 'This content will be unlocked in %s days.', $wait_days );
                    }
                }

                $content_later_msg = trim(ncore_retrieve( $product, 'content_later_msg' ));
                $have_custom_msg = $content_later_msg
                                && strlen( trim( strip_tags( $content_later_msg ) ) ) >= 3;
                if ($have_custom_msg) {

                    $in_days = ncore_formatTimeSpan( $wait_seconds, 'in' );

                    $find = array( '[DAYS]', '[DATE]', '[TIME]', '[IN_DAYS]' );
                    $repl = array( $wait_days, $date,  $time,    $in_days );

                    $this->clear3rdPartyContentFiltersForDelayedContent();

                    $this->disableAccessCheck();
                    $this->disable3rdPartyContentFilters();
                    if (ncore_isOptimizePressPageForId($post_id)) {
                        $message = str_replace( $find, $repl, $content_later_msg );
                    }
                    else {
                        $message = apply_filters( 'the_content', str_replace( $find, $repl, $content_later_msg ) );
                    }
                    $this->enable3rdPartyContentFilters();
                    $this->enableAccessCheck();
                }
        }



        return array( $message, $css, $url );
    }

    private static $access_check_disabled = 0;
    private function disableAccessCheck()
    {
        self::$access_check_disabled++;
    }
    private function enableAccessCheck()
    {
        self::$access_check_disabled--;
        if (self::$access_check_disabled < 0) {
            self::$access_check_disabled = 0;
        }
    }

    private function isAccessCheckEnabled()
    {
        return self::$access_check_disabled == 0;
    }

    private function renderSalesLetter(  $product_id )
    {
        $model = $this->api->load->model( 'data/product' );
        $product = $model->get( $product_id );

        $sales_letter = ncore_retrieve( $product, 'sales_letter' );

        $css = 'digimember_salesletter';

        $sales_letter = apply_filters( 'the_content', $sales_letter );

        return array( $sales_letter, $css, $url=false );
    }

    private static $have_wait_days = array();
    private function haveWaitDays()
    {
        $have_it =& self::$have_wait_days[ ncore_blogId() ];

        if (!isset( $have_it) )
        {
            $model = $this->api->load->model( 'logic/features' );
            $have_it = $model->canContentsBeUnlockedPeriodically();
        }
        return $have_it;
    }

    private function registerCustomAccessFilters()
    {
        /** @var digimember_CustomAccessFiltersLib $customAccessFilters */
        $customAccessFilters = $this->api->load->library('custom_access_filters');
        $customAccessFilters->registerFilters(function() {
            list($post_id) = ncore_currentPostIdAndType();
            return [$post_id, !$this->cbBoolValidator()];
        });
    }


    private function clear3rdPartyContentFiltersForBlockedContent()
    {
        $this->clear3rdPartyContentFilters( $this->CONTENT_FILTERS_TO_REMOVE_IF_ACCESS_IS_BLOCKED );
    }

    private function clear3rdPartyContentFiltersForDelayedContent()
    {
        $this->clear3rdPartyContentFilters( $this->CONTENT_FILTERS_TO_REMOVE_IF_ACCESS_IS_DELAYED );
    }


    private function clear3rdPartyContentFilters( $filters_to_remove )
    {
        static $are_filters_removed;

        if (!empty($are_filters_removed)) {
            return;
        }
        $are_filters_removed = true;

        foreach ($filters_to_remove as $rec)
        {
            $filter     = $rec[ 'filter' ];
            $priorities = $rec[ 'priority' ];

            $have_plain_function = is_string( $filter );
            $have_object         = is_array( $filter );

            if ($have_plain_function && !function_exists( $filter )) {
                continue;
            }
            elseif ($have_object)
            {
                list( $object_name, $method_name ) = $filter;

                if (empty($GLOBALS[ $object_name ])) {
                    continue;
                }

                $filter = array( $GLOBALS[ $object_name ], $method_name );
            }
            else {
                // configuration error
                continue;
            }

            if (!is_array($priorities)) {
                $priorities = array( $priorities );
            }

            foreach ($priorities as $priority) {
                $success = remove_filter( 'the_content', $filter, $priority );
            }
        }
    }

    private function _trimHtmlCommentParts( &$contents )
    {
        $begin = substr( $contents, 0, 5 );
        $end   = substr( $contents, -5 );

        $must_remove_begin = $begin == ' -->';
        if ($must_remove_begin) {
            $contents = substr( $contents, 5 );
        }

        $must_remove_end = $end == '<!-- ';
        if ($must_remove_end) {
            $contents = substr( $contents, 0, -5 );
        }
    }


    private function _matchesUnlockPolicy( $product, $postProduct, $userProduct )
    {
        switch ($product->unlock_policy)
        {
            case 'day':
                $post = get_post( $postProduct->post_id );

                $post_date  = ncore_dbDate( $post->post_date, 'date' );
                $order_date = ncore_dbDate( $userProduct->order_date, 'date' );

                $is_match = $post_date >= $order_date;
                break;

            case 'month':
                $post = get_post( $postProduct->post_id );

                $post_date  = ncore_dbDate( $post->post_date, 'date' );
                $order_date = ncore_dbDate( $userProduct->order_date, '1st_day_of_month' );

                $is_match = $post_date >= $order_date;
                break;

            case 'last_post':
                $post = get_post( $postProduct->post_id );

                $post_dateTime  = ncore_dbDate( $post->post_date, 'full' );
                $order_dateTime = ncore_dbDate( $userProduct->order_date, 'full' );

                if($post_dateTime >= $order_dateTime){
                    $is_match = true;
                    break;
                }

                $pageProductData = $this->api->load->model( 'data/page_product' );
                $where = array( 'product_id' => $product->id, 'is_active' => 'Y' );
                $pageProducts = $pageProductData->getAll( $where );
                $include = [];
                foreach ($pageProducts as $pageProduct) {
                    $include[] = $pageProduct->post_id;
                }

                $args = array(
                    'date_query' => array(
                        array(
                            'before'     => $userProduct->order_date,
                            'inclusive' => false,
                        )
                    ),
                    'post_status' => 'publish',
                    'post_type' => $post->post_type,
                    'posts_per_page' => 1,
                    'order' => 'DESC',
                    'orderby' => 'date',
                );
                if (count($include) > 0) {
                    $args['include'] = $include;
                }

                remove_filter( "pre_get_posts", 'digimember_supress_filter', 100 );
                $items = get_posts($args);
                add_filter( "pre_get_posts", 'digimember_supress_filter', 100 );

                if (count($items) > 0 && $items[0]->ID == $post->ID) {
                    $is_match = true;
                }
                else {
                    $is_match = false;
                }
                break;


            default:
                $is_match = true;
        }

        return $is_match;
    }

    private function _handleAccessWaiver( $product_obj_or_id )
    {
        if (!$product_obj_or_id) {
            return;
        }

        $this->api->load->model( 'data/product' );
        $product = $this->api->product_data->resolveToObj( $product_obj_or_id );
        if (!$product) {
            return;
        }

        $page_id = ncore_retrieve( $product, 'right_of_withdrawal_waiver_page_id' );

        if (!$page_id)
        {
            return;
        }

        $redirect_url = ncore_resolveUrl( $page_id );
        $redirect_url = ncore_addArgs( $redirect_url, array( 'dm_product_id' => $product->id, 'dm_redirect_to' => ncore_currentUrl()) , '&', true );
        ncore_redirect( $redirect_url );
    }
}
