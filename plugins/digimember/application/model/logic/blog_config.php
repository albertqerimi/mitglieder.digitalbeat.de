<?php

class digimember_BlogConfigLogic extends ncore_BlogConfigLogic
{

    public function exampleRandomString( $type='alnum_lower', $length=12)
    {
        $key = "random_str_${type}_${length}";
        $randomstring = $this->get( $key );
        if (!$randomstring)
        {
            $this->api->load->helper( 'string' );
            $randomstring = ncore_randomString( $type, $length );
            $this->set( $key, $randomstring );
        }

        return $randomstring;
    }

    public function setDigistoreAffiliate( $digistore_id )
    {
        $this->set( 'affiliate_digistore_id', $digistore_id );
    }

    public function getIpAccessLimit()
    {
        $value = $this->get( 'ip_access_limit_default' );

        $have_manual_setting = $value!='' && !is_numeric($value);

        if ($have_manual_setting)
        {
            $value = $this->get( 'ip_access_limit_manual' );
        }

        return intval( $value );
    }

//    public function haveLegacyShortCodes()
//    {
//        $have_them = $this->get( 'have_legacy_short_codes' );
//
//        switch ($have_them) {
//            case 'Y': return true;
//            case 'N': return false;
//            default:
//                $blog_installed_at = $this->get( 'plugin_install_time', 0 );
//
//                $default = $blog_installed_at <= 1421923694; // 2015-01-22
//
//                $this->set( 'have_legacy_short_codes', $default ? 'Y' : 'N' );
//
//                return $default;
//        }
//    }

    public function loginUrl()
    {
        $loginurl = $this->get('loginurl');
        if (empty($loginurl))
        {
            $loginurl = ncore_siteUrl();
        }
        return $loginurl;
    }

    public function isAffiliateFooterLinkEnabled()
    {
        if ($this->get( 'show_affiliate_link' ))
        {
            return true;
        }

        $model = $this->api->load->model( 'logic/features' );
        return !$model->canAffiliateFooterLinkBeDisabled();
    }

    protected function defaultValues()
    {
        $default_values = parent::defaultValues();

        $default_values['show_affiliate_link'] = '1';
        $default_values['disable_admin_navbar'] = '1';
        $default_values['disable_admin_area'] = '0';
        $default_values['ip_access_limit_default'] = '10';
        $default_values['ip_access_limit_manual'] = '10';
        $default_values['loginurl'] = '';
        $default_values['lang_personal_du'] = 'N';
        $default_values['use_free_url_for_login_page'] = 'N';
        $default_values['use_error_handling_prioritization'] = 'N';
        $default_values['disable_admin_area_url'] = ncore_siteUrl();

        $default_values['limit_login_enabled'] = 'Y';
        $default_values['limit_login_count'] = '10';
        $default_values['limit_login_waittime'] = '10';

        return $default_values;
    }

    private $_can_unlock_periodically;
}