<?php

class ncore_FeaturesLogic extends ncore_BaseLogic
{
    public function getLicenseArgs()
    {
        $args = array();

        $args['number_of_members'] = $this->curMemberCount();
        $args['total_members']     = ncore_memberCount();

        return $args;
    }

    public function hasFreeVersion() {
        return true;
    }

    public function hasProVersion() {
        return !$this->hasFreeVersion();
    }

    public function maybeRenderAdminNotices()
    {
        if (ncore_isAjax())
        {
            return;
        }

        $this->admin_notices = $this->getAdminNotices();

        if ($this->admin_notices)
        {
            add_action( 'admin_notices', array( $this, 'cbLicenseWarningHint' ) );

            $lib = $this->api->loadLicenseLib();
            $lib->getLicense( $force_reload = true );
        }

        $featureNoticeModel = $this->api->load->model('logic/features_admin_notice');
        $this->features_admin_notices = $featureNoticeModel->getAdminNotices();

        if ($this->features_admin_notices)
        {
            add_action( 'admin_notices', array( $this, 'cbFeatureWarning' ) );
        }
    }

    public function cbLicenseWarningHint()
    {
        echo "<div>";

        foreach ($this->admin_notices as $one)
        {
            $type = $one->type;
            $text = $one->text;

            echo ncore_renderMessage( $type, $text );
        }

        echo '</div>';
    }

    public function cbFeatureWarning()
    {
        foreach ($this->features_admin_notices as $one)
        {
            $type = $one->type;
            $text = $one->text;
            $footer = $one->footer;
            $class = 'notice notice-'.$type;
            if ($one->closeable){
                $class .= " is-dismissible";
            }
            $message = $text;
            $footerOutput = $footer ? implode(" | ", $footer) : '';
            printf( '<div class="%1$s"><p>%2$s</p><p>%3$s</p></div>', esc_attr( $class ), esc_html( $message ), $footerOutput );
        }
    }

    public function renderAdminNotice($text, $type = 'warning' , $closeable = true, $footer = false) {
        switch ($type) {
            case 'warning':
                $type = NCORE_NOTIFY_WARNING;
                break;
            case 'error':
                $type = NCORE_NOTIFY_ERROR;
                break;
            case 'info':
                $type = NCORE_NOTIFY_INFO;
                break;
            case 'success':
                $type = NCORE_NOTIFY_SUCCESS;
                break;
            default:
                $type = NCORE_NOTIFY_WARNING;
                break;
        }
        $class = 'notice notice-'.$type;
        if ($closeable){
            $class .= " is-dismissible";
        }
        $footerOutput = $footer ? implode(" | ", $footer) : '';
        printf( '<div class="%1$s"><p>%2$s</p><p>%3$s</p></div>', esc_attr( $class ), esc_html( $text ), $footerOutput );
    }

    protected function getAdminNotices()
    {
        $lib = $this->api->load->library( 'license_validator' );
        $status = $lib->licenseStatus();

        $is_valid = $status == NCORE_LICENSE_VALID || $status == NCORE_LICENSE_FREE;
        if ($is_valid)
        {
            return array();
        }

        $is_option_page = ncore_retrieve( $_GET, 'page' ) == $this->api->pluginName();
        $have_post      = !empty( $_POST['ncore_license_code'] );
        if ( $is_option_page && $have_post )
        {
            return self::ADMIN_NOTICES_HIDDEN;
        }

        $this->api->load->helper( 'date' );

        $plugin_name = $this->api->pluginDisplayName();

        $model = $this->api->load->model( 'logic/link' );
        $url   = $model->adminPage();

        $lib = $this->api->loadLicenseLib();

        $license         = $lib->getLicense();
        $warning_ends_at = ncore_retrieve( $license, 'warning_ends_at' );

        if ( $warning_ends_at )
        {
            $date = ncore_formatDate( $warning_ends_at );
        }
        else
        {
            $date = ncore_formatDate( time() );
        }


        $is_in_grace = $status != NCORE_LICENSE_ERROR;

        if ($this->api->isFreePluginVersionAvailable())
        {
            $message = $is_in_grace
                     ? _ncore( 'The license of [PRO] is not valid and so the plugin will be set back to [FREE] on [DATE]. Please enter a valid license code in the <a>[PLUGIN] settings</a>.' )
                     : _ncore( '[PRO] has been disabled and set back to [FREE], because the license is not valid. Please enter a valid license code in the <a>[PLUGIN] settings</a>.' );
        }
        else
        {
            $message = $is_in_grace
                     ? _ncore( 'The license of the [PLUGIN] plugin is not valid and the plugin will be deactivated on [DATE]. Please enter a valid license code in the <a>[PLUGIN] settings</a>.' )
                     : _ncore( 'The [PLUGIN] plugin has been deactivated, because the license is not valid. Please enter a valid license code in the <a>[PLUGIN] settings</a>.' );
        }


        $free_name = $this->api->pluginNameFree();
        $pro_name  = $this->api->pluginNamePro();

        $find = array(
             '<a>',
            '[PLUGIN]',
            '[FREE]',
            '[PRO]',
            '[DATE]'
        );
        $repl = array(
             "<a href='$url'>",
            $plugin_name,
            $free_name,
            $pro_name,
            $date
        );

        $message = str_replace( $find, $repl, $message );

        $msg = new stdClass();
        $msg->type = NCORE_NOTIFY_ERROR;
        $msg->text = $message;

        return array( $msg );
    }

    public function curMemberCount()
    {
        return ncore_memberCount();
    }


    const ADMIN_NOTICES_HIDDEN = false;

    private $admin_notices = array();
    private $features_admin_notices = array();


}
