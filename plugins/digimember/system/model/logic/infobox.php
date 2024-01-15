<?php

final class ncore_InfoboxLogic extends ncore_BaseLogic
{
    public function refresh()
    {
        $this->fetchData( $force_reload=true );
    }

    public function cronDaily() {

        $data = $this->fetchData();

        $refresh_at = ncore_retrieve( $data, 'refresh_at_unix' );
        $must_reload = $refresh_at < $this->_time();
        if ($must_reload) {
            $this->fetchData( $force_reload=true);
        }
    }

    public function setup()
    {
        add_action( 'wp_dashboard_setup', array( $this, 'cbSetupDashboard' ) );
        add_action( 'admin_init',         array( $this, 'cbSetupAdminArea' ) );
    }

    public function cbSetupDashboard()
    {
        if (!ncore_canAdmin()) {
            return;
        }

        remove_action( 'welcome_panel', 'wp_welcome_panel' );
        add_action( 'welcome_panel', array( $this, 'cbAdminWelcomePanel' ), 1 );

        $this->_maybeUnhideWelcomePanel();
        $this->_maybeUnhideAdminNotices();
    }

    public function cbSetupAdminArea()
    {
        add_action( 'admin_notices', array( $this, 'cbAdminNotice' ), 1 );
    }

    public function cbAdminNotice()
    {
        $msg = $this->fetchMessage( NCORE_IB_LOCATION_ADMIN_NOTICE );
        if ($msg)
        {
            echo $this->_renderAminNotice( $msg );
        }

        $model = $this->api->load->model( 'data/user_settings' );
        $time = $model->get( 'infobox_last_popup_time', 0 );

        $wait_seconds = 60*$this->setting( 'popup_wait_time_minutes', 1440 );

        $is_visible = !$time || $time < $this->_time()-$wait_seconds || $this->_doReset();

        $msg = $is_visible
               ? $this->fetchMessage( NCORE_IB_LOCATION_ADMIN_POPUP )
               : false;

        if ($msg)
        {
            $model->set( 'infobox_last_popup_time', $this->_time() );

            echo $this->_renderPopup( $msg );
        }
    }

    public function cbAdminWelcomePanel()
    {
        $msg = $this->fetchMessage( NCORE_IB_LOCATION_WELCOME_PANEL );

        if (!$msg) {
            wp_welcome_panel();
            return;
        }

        if (!$this->canHideWelcomePanel()) {
            echo "
<style>
.welcome-panel-close { display: none; }
#welcome-panel { padding-top: 5px; }
</style>
";
        }

        echo $this->_renderWelcomePanel( $msg );
    }

    public function renderPreview( $location, $msg )
    {
        switch ($location) {
            case NCORE_IB_LOCATION_WELCOME_PANEL:
                $contents = $this->_renderWelcomePanel( $msg, $force_visible=true );

                $close = _ncore( 'Dismiss' );

                return "<div id='welcome-panel' class='welcome-panel'>
<a class='welcome-panel-close' onclick='return false;' href='#'>$close</a>
$contents
</div>";

            case NCORE_IB_LOCATION_ADMIN_NOTICE:
                return $this->_renderAminNotice( $msg, $force_visible=true );

            case NCORE_IB_LOCATION_ADMIN_POPUP:
            default:
                return $this->_renderPopup( $msg, $force_visible=true  );
        }
    }

    private function setting( $key, $default='' )
    {
        $data = $this->fetchData();

        $settings = ncore_retrieve( $data, 'settings', array() );

        return ncore_retrieve( $settings, $key, $default );
    }

    private function fetchMessage( $location )
    {
        $data = $this->fetchData();

        $all_locations_messages = ncore_retrieve( $data, 'messages' );

        $server_time_offset = ncore_retrieve( $data, 'server_time_offset', 0 );

        $messages = ncore_retrieve( $all_locations_messages, $location );

        if (!$messages) {
            return false;
        }

        foreach ($messages as $index => $one)
        {
            $is_visible = $this->_isVisible( $one, $server_time_offset );
            if (!$is_visible) {
                unset( $messages[ $index ] );
            }
        }

        $msg = $this->_randomPickByWeight( $messages );

        return $msg;
    }

    private function _isVisible( $msg, $server_time_offset=0 )
    {
        $screen = get_current_screen();

        $page = $screen->base == 'dashboard'
              ? 'dashboard'
              : $screen->parent_base;

        $is_negated = ncore_retrieve( $msg, 'show_policy' ) == 'exclude';
        $show_on = ncore_retrieve( $msg, 'show_on', array( 'all' ) );

        if (in_array( 'all', $show_on ))
        {
            $is_visible = true;
        }
        else
        {
            $is_visible = in_array( $page, $show_on );
        }

        if ($is_negated) {
            $is_visible = !$is_visible;
        }

        if (!$is_visible) {
            return false;
        }

        $hide_on = ncore_retrieve( $msg, 'hide_on', array() );
        $is_hidden = in_array( $page, $hide_on );
        if ($is_hidden) {
            return false;
        }

        $now = ncore_dbDate( $this->_time()+$server_time_offset );
        $from = ncore_retrieve( $msg, 'show_from' );
        if ($from && $from > $now ) {
            return false;
        }

        $until = ncore_retrieve( $msg, 'show_until' );
        if ($until && $until < $now ) {
            return false;
        }

        return true;
    }

    private function _randomPickByWeight( $messages ) {

        if (!$messages || !is_array($messages)) {
            return false;
        }

        if (count($messages)==1) {
            return end($messages);
        }

        $zero_weight_msgs = array();

        $total_weight = 0;
        foreach ($messages as $index => $one)
        {
            $weight = ncore_retrieve( $one, 'weight' );
            if ($weight) {
                $total_weight += $weight;
            }
            else
            {
                unset( $messages[$index] );
                $zero_weight_msgs[] = $one;
            }
        }
        if ($total_weight) {
            $rand = rand( 1, $total_weight );
            foreach ($messages as $one)
            {
                $weight = ncore_retrieve( $one, 'weight', 10 );

                $rand -= $weight;

                if ($rand<=0) {
                    return $one;
                }
            }
        }

        $count = count($zero_weight_msgs);
        $index = rand( 0, $count-1 );

        return $zero_weight_msgs[ $index ];
    }

    private function fetchData( $force_reload = false)
    {
        static $data;

        if (isset($data) && !$force_reload) {
            return $data;
        }

        try
        {
            if (!$force_reload && !$this->_doReset())
            {
                $data = ncore_cacheRetrieve( 'infobox' );

                $is_valid = $data && $data->invalid_at_unix > $this->_time();
                if ($is_valid) {
                    return $data;
                }
            }

            $rpc = $this->api->load->library( 'rpc_api' );

            $config = $this->api->load->model( 'logic/blog_config' );
            $license_key = $config->get( 'license_code' );

            $model = $this->api->load->model( 'logic/features' );
            $args = $model->getLicenseArgs();

            $lib = $this->api->loadLicenseLib();

            $args[ 'license_status' ] = $lib->licenseStatus();

            $args[ 'language' ]           = get_bloginfo('language');
            $args[ 'locale' ]             = get_locale();

            $args[ 'license_key' ]        = $license_key;
            $args[ 'license_url']         = ncore_licenseUrl();
            $args[ 'wordpress_version' ]  = get_bloginfo( 'version' );
            $args[ 'php_version' ]        = phpversion();

            $args[ 'package_name' ]       = $this->api->pluginName();
            $args[ 'package_version' ]    = $this->api->pluginVersion();

            $args[ 'age_in_days' ] = $this->_ageInDays();

            $data = $rpc->infoboxApi( 'fetch', $args );

            if ($data)
            {
                $data->server_time_offset = ncore_unixDate( $data->current_server_time ) - $this->_time();

                $data->refresh_at_unix = ncore_unixDate( $data->refresh_at ) - $data->server_time_offset;
                $data->invalid_at_unix = ncore_unixDate( $data->invalid_at ) - $data->server_time_offset;

                $lifetime = max( 3600, $data->refresh_at_unix - $this->_time() - 10 );

                $this->_sanitizeMessages( $data->messages );
            }
            else
            {
                $lifetime = 900;
                $reload_at = $this->_time() + $lifetime;

                $data = new stdClass();
                $data->refresh_at_unix = $reload_at;
                $data->invalid_at_unix = $reload_at;
            };

            ncore_cacheStore( 'infobox', $data, $lifetime );
        }
        catch (Exception $e) {

            $retry_after_seconds = 3600 + rand( 0, 3600 );

            $data = ncore_cacheRetrieve( 'infobox' );
            if (empty($data)) {
                $data = new stdClass();
            }

            $retry_at = $this->_time()+$retry_after_seconds;

            $data->refresh_at_unix = $retry_at;
            $data->invalid_at_unix = $retry_at;

            ncore_cacheStore( 'infobox', $data, $retry_after_seconds );

            $this->api->logError('api', _ncore( 'Error contacting the infobox server:' ) . ' ' . $e->getMessage() );
        }

        return $data;
    }

    private function _maybeUnhideAdminNotices( $force=false )
    {
        return $this->_maybeUnhideInfobox( 'infobox_admin_notice', 'keep_admin_notice_closed_days', $force );
    }

    private function _maybeUnhideWelcomePanel( $force=false )
    {
        return $this->_maybeUnhideInfobox( 'welcome_panel', 'keep_welcome_panel_closed_days', $force );
    }

    private function _maybeUnhideInfobox( $window, $close_days_settings_key, $force )
    {
        $close_window = $this->api->load->model( 'logic/close_window' );
        $is_closed    = $close_window->isWindowClosed( $window );
        if (!$is_closed) {
            return $is_visible=true;
        }

        if (!$this->canHideWelcomePanel()) {
            $close_window->setClosedWindow( $window, false );
            return $is_visible=true;
        }

        $model = $this->api->load->model( 'data/user_settings' );
        $last_reset = $model->get( "closed_at_$window", false );

        $days = $this->setting( 'keep_admin_notice_closed_days', 60 );

        $must_reset = $force || !$last_reset || $last_reset < $this->_time()-$days*86400 || $this->_doReset();
        if ($must_reset)
        {
            $close_window->setClosedWindow( $window, false );
            $model->set( "closed_at_$window", $this->_time() );

            return $is_visible=true;
        }

        return $is_visible=false;
    }

    private function canHideWelcomePanel()
    {
        return true;

//        static $can_hide;
//        if (!isset($can_hide))
//        {
//            $lib = $this->api->load->library( 'license_validator' );
//            $status = $lib->licenseStatus();
//            $can_hide = $status == NCORE_LICENSE_VALID;
//        }
//
//        return $can_hide;
    }


    private function _renderAminNotice( $msg, $force_visible=false )
    {
        $is_visible = $this->_maybeUnhideAdminNotices($force_visible);
        if (!$is_visible) {
            return '';
        }

        $can_close = $this->canHideWelcomePanel();

        if ($can_close)
        {
            /** @var ncore_CloseWindowLogic $model */
            $model = $this->api->load->model( 'logic/close_window' );
            $close_js = $model->renderCloseWindowJs( 'infobox_admin_notice' );
            $close_js = "ncoreJQ(this).parents('.dm-admin-notice').slideUp(); $close_js; return false;";
        }
        else
        {
            $close_js  = '';
        }

        $html     = ncore_retrieve( $msg, 'html' );
        $headline = ncore_retrieve( $msg, 'title' );
        $links    = ncore_retrieve( $msg, 'links' );


        $index = array_search( 'close', $links  );
        if ($index!==false)
        {
            unset( $links[ $index ] );
        }

        $css = $headline
             ? 'dm-admin-notice-with-headline'
             : 'dm-admin-notice-without-headline';

        $css .= $links
              ? ' dm-admin-notice-with-links'
              : ' dm-admin-notice-without-links';

        $headline = ($headline) ? '<label class="dm-headline">' . $headline . '</label>' : '';
        $html = ($html) ? '<p>' . $html . '</p>' : '';

        $closeButton = $close_js ? '
<button class="dm-btn dm-btn-icon dm-btn-error dm-admin-notice-close-button" onclick="' . $close_js . '" data-title="' . _ncore( 'Dismiss' ) . '">
    <span class="dm-icon icon-cancel-circled"></span>
</button>
        ' : '';


        $linksHtml = '';
        if ($links) {
            foreach ($links as $label => $url) {
                $js = "window.open('$url'); return false;";
                $linksHtml .=  "<button type=\"button\" value=\"$label\" class=\"dm-btn dm-btn-primary\" onclick=\"$js;\"> ";
            }
        }

        $out = '
<div class="dm-alert ' . $css . ' dm-admin-notice">
    <div class="dm-alert-content">
        ' . $headline . '
        ' . $html . '
    </div>
    <div class="dm-alert-buttons">
        ' . $linksHtml . '
        ' . $closeButton . '
    </div>
</div>
';
        return $out;
    }

    private function _renderWelcomePanel( $msg, $force_visible=false )
    {
        if ($this->canHideWelcomePanel())
        {
            $model = $this->api->load->model( 'logic/close_window' );
            $close_js = $model->renderCloseWindowJs( 'welcome_panel' );
        }
        else
        {
            $close_js = '';
        }

        $html     = ncore_retrieve( $msg, 'html' );
        $headline = ncore_retrieve( $msg, 'title' );
        $links    = ncore_retrieve( $msg, 'links' );

        if (!$close_js) {
            $index = array_search( 'close', $links  );
            if ($index!==false)
            {
                unset( $links[ $index ] );
            }
        }

        $css = $headline
             ? 'ncore_with_headline'
             : 'ncore_without_headline';

        $css .= $links
              ? ' ncore_with_links'
              : ' ncore_without_links';

        $out = "<div class='ncore_infobox_welcome_panel_container $css' style='background-color: #f8fafb'>"; //DM-324 overwrite for background color of the infopanels. to prevent WP welcome-panel color shining through

        if ($headline)
        {
            $out .= "<h3>$headline</h3>";
        }

        if ($html)
        {
            $out .=  "<div class='ncore_infobox_welcome_panel_inner'>$html</div>";
        }

        if ($links)
        {
            $out .=  "<div class='ncore_infobox_welcome_panel_links'>";

            foreach ($links as $label => $url)
            {
                $is_close_button = $url === 'close';
                if ($is_close_button) {
                    if (!$close_js) {
                        continue;
                    }
                    $js = "$close_js; return false;";
                    $out .=  "<input type=\"submit\" value=\"$label\" class=\"button\" onclick=\"$js;\"> ";
                }
                else
                {
                    $js = "window.open('$url'); return false;";
                    $out .=  "<input type=\"submit\" value=\"$label\" class=\"button button-primary\" onclick=\"$js;\"> ";
                }
            }

            $out .=  "</div>";
        }

        $out .=  "</div>";

        return $out;
    }

    private function _renderPopup( $msg, $force_visible=true )
    {
        $width  = ncore_retrieve( $msg, 'container_width'  );
        $height = ncore_retrieve( $msg, 'container_height' );

        if (!$width)  $width  = $this->setting( 'popup_width',  600 );
        if (!$height) $height = $this->setting( 'popup_height', 400 );

        $meta = array(
                    'type'          => 'infobox',
                    'title'         => ncore_retrieve( $msg, 'title', '&nbsp;' ),
                    'width'         => $width,
                    'height'        => $height,
                    'dialogClass'   => 'ncore_infobox_dialog',
                    'message'       => ncore_retrieve( $msg, 'html' ),
                    'links'         => ncore_retrieve( $msg, 'links' ),
             );

        $lib = $this->api->load->library( 'ajax' );
        $dialog = $lib->dialog( $meta );
        $dialog->open();

        return '';
    }

    private function _doReset() {
        return !empty($_GET['reset'] ) || !empty($_GET['reload']);
    }

    private function reset() {

        $model = $this->api->load->model( 'data/user_settings' );
        $model->set( 'infobox_last_popup_time', 0 );

        $this->_maybeUnhideWelcomePanel( $force = true );
        $this->_maybeUnhideAdminNotices( $force = true );
    }

    private function _ageInDays() {

        $config = $this->api->load->model( 'logic/blog_config' );
        $start_date_unix = $config->get( 'infobox_start_date' );

        if ($start_date_unix) {
            return max( floor( ($this->_time() - $start_date_unix) / 86400 ), 0 );
        }
        else {
            $start_date_unix = $this->_time();
            $config->set( 'infobox_start_date', $start_date_unix );

            return 0;
        }
    }

    private function _time() {
        static $time;
        if (!isset($time)) {
            $time = ncore_serverTime();
        }
        return $time;
    }

    private function _sanitizeMessages( &$messages )
    {
        if (empty($messages) || !is_array($messages)) {
            return;
        }

        if (!defined('DIGIMEMBER_AFFILIATE') || !DIGIMEMBER_AFFILIATE) {
            return;
        }

        foreach ($messages as $type => $list)
        {
            if (empty($list) || !is_array($list)) {
                continue;
            }

            foreach ($list as $index => $msg)
            {
                $html  =& $messages[ $type ][ $index ][ 'html' ];
                $links =& $messages[ $type ][ $index ][ 'links' ];

                $this->_sanitizeHtml( $html );

                if (!empty($links) && is_array($links)) {
                    foreach ($links as $label => $url)
                    {
                         $this->_sanitizeUrl( $links[ $label ] );
                    }
                }


            }


        }
    }


    private function _sanitizeUrl( &$url )
    {
        $affiliate_domains = array( 'digimember.de', 'digimember.de', 'digistore24.com', 'www.digistore24.com' );

        $domain = strtolower( parse_url( $url, PHP_URL_HOST ) );

        $is_affiliate_domain = in_array( $domain, $affiliate_domains );
        if (!$is_affiliate_domain) {
            return;
        }

        $args = array( 'aff' => DIGIMEMBER_AFFILIATE );
        if (DIGIMEMBER_CAMPAIGNKEY) {
            $args['cam']= DIGIMEMBER_CAMPAIGNKEY;
        }

        $url = ncore_addArgs( $url, $args, '&', false );
    }

    private function _sanitizeHtml( &$html )
    {
        $is_match = preg_match_all( '/href=[\'\"](.*?)[\'\"]/', $html, $matches );
        if (!$is_match) {
            return;
        }

        $find = array();
        $repl = array();

        foreach ($matches[0] as $index => $old_found)
        {
            $old_url = $matches[1][$index];
            $new_url = $old_url;

            $this->_sanitizeUrl( $new_url );

            if ($old_url == $new_url) {
                continue;
            }

            $new_found = str_replace( $old_url, $new_url, $old_found );

            $find[] = $old_found;
            $repl[] = $new_found;
        }

        $html = str_replace( $find, $repl, $html );
    }

}


