<?php

class ncore_LicenseValidator_PluginDownload extends ncore_LicenseValidator_PluginBase
{
    public function licenseCheckEnabled() {
        return true;
    }

    public function getLicenseCode()
    {
        /** @var digimember_BlogConfigLogic $config */
        $config = $this->api->load->model( 'logic/blog_config' );
        $license_key = $config->get( 'license_code' );
        return $license_key;
    }

    public function getLicense( $force_reload=false )
    {
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $raw_license = $model->get( 'system_cache' );
        if (!$raw_license)
        {
            return false;
        }

        $old_level = error_reporting( 0 );
        $license = unserialize( base64_decode( ( $raw_license ) ) );
        error_reporting( $old_level );

        $is_valid = $license
                 && is_object($license)
                 && isset($license->license_key)
                 && !$force_reload
                 && isset($license->revalidate_at)
                 && $license->revalidate_at > time();

        if (!$is_valid)
        {
            /** @var digimember_BlogConfigLogic $config */
            $config = $this->api->load->model( 'logic/blog_config' );
            $license_key = $config->get( 'license_code' );

            if ($license_key)
            {
                /** @var ncore_TimerData $model */
                $model = $this->api->load->model( 'data/timer' );

                $can_fetch = $model->runNow( 'license_fetch_if_error', $force_reload ? 240 : 900 );

                if ($can_fetch)
                {
                    try
                    {
                        $raw_license = $this->fetchLicense( $license_key );

                        $old_level = error_reporting( 0 );
                        $license = unserialize( base64_decode( ( $raw_license ) ) );
                        error_reporting( $old_level );
                    }
                    catch (Exception $e)
                    {
                        $msg = _ncore( 'Error contacting license server: %s', $e->getMessage() );
                        $this->api->logError( 'plugin', $msg );
                    }
                }
            }
        }

        if (!$license)
        {
            return false;
        }

        return $license;
    }

    public function fetchLicense( $license_key )
    {
        try
        {
            /** @var ncore_RpcApiLib $rpc */
            $rpc = $this->api->load->library( 'rpc_api' );

            $config          = $this->api->load->config( 'general' );
            $license_version = $config->get( 'license_version' );

            /** @var digimember_FeaturesLogic $model */
            $model = $this->api->load->model( 'logic/features' );
            $args = $model->getLicenseArgs();

            $args[ 'license_key' ]        = $license_key;
            $args[ 'license_url']         = ncore_licenseUrl();
            $args[ 'license_version' ]    = $license_version;
            $args[ 'wordpress_version' ]  = get_bloginfo( 'version' );
            $args[ 'php_version' ]        = phpversion ();
            $args[ 'language' ]           = get_bloginfo('language');
            $args[ 'locale' ]             = get_locale();

            $args[ 'package_name' ]       = $this->api->pluginName();
            $args[ 'package_version' ]    = $this->api->pluginVersion();

            $response = $rpc->licenseApi( 'fetch', $args );

            $raw_license = ncore_retrieve( $response, 'license', false );

            if ($raw_license)
            {
                $this->storeLicense( $raw_license );
                return $raw_license;
            }
        }
        catch (Exception $e)
        {
            $this->api->logError( 'api', _ncore( 'Error fetching license: %s', $e->getMessage() ) );
        }

        return false;
    }

    public function clearLicense()
    {
        $license = $this->getLicense();
        $license_key = ncore_retrieve( $license, 'license_key' );

        if ($license_key)
        {
            $this->api->load->helper( 'url' );
            /** @var ncore_RpcApiLib $rpc */
            $rpc = $this->api->load->library( 'rpc_api' );

            $config = $this->api->load->config( 'general' );
            $license_version = $config->get( 'license_version' );

            $args = array();
            $args[ 'license_key' ] = $license_key;
            $args[ 'license_url'] = ncore_licenseUrl();
            $args[ 'license_version' ] = $license_version;

            try
            {
                $rpc->licenseApi( 'free', $args );
            }
            catch (Exception $e)
            {
                $this->api->logError( 'api', _ncore( 'Error freening license key: %s', $e->getMessage() ) );
            }
        }

        $raw_license = false;
        $this->storeLicense( $raw_license );
    }

    public function licenseStatus()
    {
        $this->loadFeatures();
        return $this->license_status;
    }

    public function getLicenseErrors( $force_reload = false)
    {
        /** @var digimember_BlogConfigLogic $config */
        $config = $this->api->load->model( 'logic/blog_config' );

        $log_success = false;
        $license_key = '';

        try
        {
            $license_key = $config->get( 'license_code' );
            if ($license_key)
            {
                $license     = $this->getLicense();

                $match = $license_key == ncore_retrieve( $license, 'license_key' );

                $revalidate_at  = ncore_retrieve( $license, 'revalidate_at' );
                $must_revaliate = $revalidate_at && $revalidate_at < time();

                $must_fetch_license = !$license || !$match || $must_revaliate || $force_reload;
                if ( $must_fetch_license )
                {
                    $this->fetchLicense( $license_key );
                    $log_success = true;
                }
            }
            else
            {
                $this->clearLicense();
            }

            $errormsg = $this->_getLicenseErrors();
        }
        catch (Exception $e)
        {
            $errormsg = $e->getMessage();
            if (!$errormsg) {
                $errormsg = $this->_getLicenseErrors();
            }
            if (!$errormsg) {
                $errormsg = 'Unknown Exception when checking license.';
            }

            $this->clearLicense();
        }

        if ($errormsg && $license_key)
        {
            /** @var ncore_TimerData $model */
            $model = $this->api->load->model( 'data/timer' );

            $message = _ncore('%s license invalid: %s', $this->api->pluginDisplayName(), $errormsg );

            $this->api->logError( 'plugin', $message );

            $send_mail = $model->runNow( 'license_error_notify', 3*86300 );

            if ($send_mail)
            {
                $subject = _ncore( '%s: License error', $this->api->pluginDisplayName() );
                $subject .= _ncore(" on %s", $_SERVER['HTTP_HOST']);
                $recipient = get_bloginfo('admin_email');

                /** @var ncore_MailerLib $mailer */
                $mailer = $this->api->load->library( 'mailer' );
                $mailer->subject( $subject );
                $mailer->text( $message );
                $mailer->to( $recipient );
                $mailer->send();
            }
        }
        elseif ($log_success)
        {
            $this->api->log( 'plugin', _ncore( '%s license %s is valid and activated.', $this->api->pluginDisplayName(), $license_key ) );
        }

        return $errormsg;
    }

    public function getFeature( $feature )
    {
        $license = $this->getLicense();

        $features = ncore_retrieve( $license, 'features' );

        $has_feature = ncore_retrieve( $features, $feature, false );

        return $has_feature;
    }

    private $license_status;

    private function storeLicense( $raw_license )
    {
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $model->set( 'system_cache', $raw_license );
    }

    private function loadFeatures()
    {
        if (empty($this->license_status))
        {
            $this->license_status = NCORE_LICENSE_ERROR;

            $license = $this->getLicense();

            if (!$license)
            {
                $this->license_status = NCORE_LICENSE_FREE;
                return;
            }

            $license_errors = $this->_getLicenseErrors();
            $license_valid = $license_errors === false;

            if (!$license_valid)
            {
                return;
            }

            $grace_ends_at = ncore_retrieve( $license, 'grace_ends_at' );
            $graced_ended = $grace_ends_at && $grace_ends_at < time();

            $this->license_status = $graced_ended
                                  ? NCORE_LICENSE_GRACE
                                  : NCORE_LICENSE_VALID;

        }
    }

    private function _getLicenseErrors()
    {
        $license = $this->getLicense();

        $license_url = ncore_retrieve( $license, 'license_url' );
        $license_key = ncore_retrieve( $license, 'license_key' );

        /** @var digimember_BlogConfigLogic $config */
        $config = $this->api->load->model( 'logic/blog_config' );
        $stored_license_key = $config->get( 'license_code' );

        $key_valid = $stored_license_key
                  && $stored_license_key == $license_key;

        if (!$license_key)
        {
            $plugin_name = $this->api->pluginDisplayName();

            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model( 'logic/link' );
            $url   = $model->adminPage();

            $message_templ = _ncore( '<a>Click here</a> to enter a valid license code.' );

            $find = array(
                 '<a>',
                '[PLUGIN]',
            );
            $repl = array(
                 "<a href='$url'>",
                $plugin_name,
            );

            return str_replace( $find, $repl, $message_templ );

        }

        if (!$license)
        {
            return _ncore( 'The license key is invalid. No license found for key %s.', $stored_license_key );
        }

        if (!$key_valid)
        {
            return _ncore( 'Stored license does not match license key %s. Please review the license key in the plugin settings and save them again.', $license_key );
        }

        $domain_valid = ncore_licenseUrl() == $license_url;

        if (!$domain_valid)
        {

            $url =  $this->api->edition() == 'US'
                ?   'https://digimember.com/memberarea-licences/'
                :   'https://digimember.de/mitgliederbereich-lizenzen/';

            return _ncore( 'License %s is not for URL %s.', $license_key, ncore_licenseUrl() )
                . '<p>'
                . _ncore( 'The license was used for a different domain.' )
                . '</p><p>'
                . ncore_linkReplace( _ncore( 'To free the license<ol><li>visit the <a>DigiMember membership area</a>,</li><li>locate the license in the license list</li><li>and press the reset button to free the license.</li></ol>' ), $url )
                . '</p>';

        }

        $warning_ends_at = ncore_retrieve( $license, 'warning_ends_at' );
        $warning_ended = $warning_ends_at && $warning_ends_at < time();
        if ($warning_ended)
        {
            return _ncore( 'License %s is expired. This may happen also to permanent licenses if the contact to the license server is blocked.', $license_key  );
        }

        $features = ncore_retrieve( $license, 'features', array() );
        $packages = explode( "\n", ncore_retrieve( $features, 'package' ) );
        $is_for_me = in_array( $this->api->pluginName(), $packages );
        if (!$is_for_me)
        {
            return _ncore( 'License %s is not for %s.', $license_key, $this->api->pluginName() );
        }

        return false;
    }
}
