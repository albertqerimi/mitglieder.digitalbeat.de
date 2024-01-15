<?php

class digimember_LinkLogic extends ncore_LinkLogic
{
    public function certificateDownload( $certicate_obj_or_id, $user_obj_or_id_or_recipient_name='current' )
    {
        if (is_object($certicate_obj_or_id))
        {
            $id = ncore_retrieve( $certicate_obj_or_id, 'id', false );
        }
        elseif (is_numeric($certicate_obj_or_id) && $certicate_obj_or_id > 0)
        {
            $id = $certicate_obj_or_id;
        }
        else
        {
            $id = $certicate_obj_or_id;
        }

        $have_name = is_string( $user_obj_or_id_or_recipient_name )
            && !is_numeric( $user_obj_or_id_or_recipient_name )
            && $user_obj_or_id_or_recipient_name !== 'current';

        $args = array( 'dm_certificate' => $id );

        if ($have_name)
        {
            $full_name = $user_obj_or_id_or_recipient_name;
            $args[ 'dm_recipient_name' ] = $full_name;
        }
        else
        {
            $user_obj_or_id = $user_obj_or_id_or_recipient_name;
            if ($id >= 1)
            {
                $this->api->load->model( 'data/exam_certificate' );
                $args[ 'dm_pw' ] = $this->api->exam_certificate_data->certificateDownloadPassword( $id, $user_obj_or_id );
            }
        }

        return ncore_addArgs( site_url(), $args, '&', true );
    }

    // @obsolete
    public function downloadUrl( $package, $affiliate='', $campaignkey='', $environment='', $mode='normal')
    {
        $package      = ncore_washText( $package );

        $affiliate   = ncore_washText( $affiliate );
        $campaignkey = ncore_washText( $campaignkey );

        $params = $this->affiliateGetParams(  $affiliate, $campaignkey );

        if (function_exists( 'dsvr_api' ) && $mode == 'normal') {

            $lib = dsvr_api()->load->library( 'package' );
            return $lib->downloadUrl( $package, $environment, $params );
        }

        $url = str_replace( '[PACKAGE]', $package, DIGIMEMBER_DOWNLOAD_URL );

        $url = str_replace( '[SERVER_BASE_URL]', $this->api->licenseServerBaseUrl(), $url );


        return ncore_addArgs( $url, $params, '&' );
    }



    function upgradeButton( $label='', $style='button' )
    {
        if (!$label) {
            $label = $this->api->isFreePluginVersionAvailable()
                ? _digi( 'Upgrade to %s!', $this->api->pluginNamePro() )
                : _digi( 'Upgrade now!' );
        }

        $this->api->load->helper( 'html_input' );
        $url = $this->buyUrl();

        $attr = array(
            'as_popup' => true,
        );

        $find  = '[PRO]';
        $repl  = $this->api->pluginNamePro();
        $label = str_replace( $find, $repl, $label );

        return $style==='link'
            ? ncore_htmlLink( $url, $label, $attr )
            : ncore_htmlButtonUrl( $label, $url, $attr );
    }

    function upgradeHint( $message, $label='', $tag='p' )
    {
        $this->api->load->helper('html_input');
        $button = $this->upgradeButton( $label );
        return ncore_htmlAlert('info', $message, 'warning', $label, $button);
    }

    public function buyUrl( $arg_sep='&', $encode_args=false )
    {
        $params = $this->affiliateGetParams();

        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $url = $model->hasFreeVersion()
             ? $this->dmBuyUrl()
             : $this->dmUpgradeUrl();

        $url = str_replace( '[SERVER_BASE_URL]', $this->api->licenseServerBaseUrl(), $url );

        return ncore_addArgs( $url, $params, $arg_sep, $encode_args );
    }

    public function dmBuyUrl(){
        return $this->api->edition() =='US' ? '[SERVER_BASE_URL]/digimember-pro-upgrade' : '[SERVER_BASE_URL]/gopro';
    }

    public function dmUpgradeUrl() {
        return $this->api->edition() == 'US' ? '[SERVER_BASE_URL]/digimember-pro-upgrade' : '[SERVER_BASE_URL]/upgrade';
    }

    public function digistoreReference() {
        return $this->api->edition() == 'US' ? '318515' : '759';
    }

    public function digistorePromoLinkProductId() {
        return $this->api->edition() == 'US' ? '256199' : '22481';
    }

    public function digistoreReferenceUrl()
    {
        if ($this->api->edition() == 'US') {
            $url = 'https://www.digistore24.com/en/home/extern/register/join/';
        } else {
            $url = 'https://www.digistore24.com/join/';
        }
        return $url . $this->digistoreReference();
    }

    public function digistoreSignupUrl()
    {
        list( $affiliate, $campaignkey ) = $this->api->blog_config_logic->getAffiliate();

        return str_replace( 'AFFILIATE', $affiliate,  NCORE_DIGISTORE_SIGNUPLINK );
    }

    public function ipnCall( $obj_or_id, $product_id=false, $arg_sep='&amp;', $use_deep_link=false )
    {
        $api = $this->api;

        $must_use_index_php_for_paypal_button = $product_id > 0;

        $base_url = $must_use_index_php_for_paypal_button
                  ? ncore_siteUrl( 'index.php' )
                  : ncore_siteUrl();

        if ($must_use_index_php_for_paypal_button)
        {
            $is_slash_required = 'N';
        }
        else
        {
            $config_key_base        = 'ipn_' . md5(ncore_siteUrl());
            $config_key_url         = $config_key_base . '_url';
            $config_key_is_required = $config_key_base . '_req';

            $config = $this->api->load->model( 'logic/blog_config' );
            $tested_base_url    = $config->get( $config_key_url );
            $is_slash_required  = $config->get( $config_key_is_required );

            if (!$is_slash_required)
            {
                $tested_base_url = $base_url;

                $base_url_without_slash = rtrim( $base_url, '/' );
                $base_url_with_slash    = $base_url_without_slash . '/';

                $args = array( 'redirection' => 0 );

                $response = @wp_remote_get( $base_url_with_slash, $args );
                $code_with_slash = @wp_remote_retrieve_response_code( $response );

                $response = @wp_remote_get( $base_url_without_slash, $args );
                $code_without_slash = @wp_remote_retrieve_response_code( $response );

                if ($code_with_slash == 200 && $code_without_slash == 200)
                {
                    $is_slash_required = '-';
                }
                elseif ($code_with_slash == 200)
                {
                    $is_slash_required = 'Y';
                }
                elseif ($code_without_slash == 200)
                {
                    $is_slash_required = 'N';
                }
                else
                {
                    $is_slash_required = '?';
                }

                $config->set( $config_key_url,         $tested_base_url,   1 );
                $config->set( $config_key_is_required, $is_slash_required, 1 );
            }
        }

        switch ($is_slash_required)
        {
            case 'Y': $base_url = rtrim( $base_url, '/' ) . '/'; break;
            case 'N': $base_url = rtrim( $base_url, '/' );       break;

            case '?':
            case '-':
            default:
                // empty
        }

        $model = $api->load->model( 'data/payment' );

        $ipn_config = $model->resolveToObj( $obj_or_id );

        $id            = ncore_retrieve( $ipn_config, 'id' );
        $call_password = ncore_retrieve( $ipn_config, 'callback_pw' );

        if (!$id || !$call_password)
        {
            return '';
        }

        $args = array(
            'dm_ipn' => $id,
            'dm_pw'  => $call_password,
        );

        if ($product_id)
        {
            $args['product_code'] = $product_id;
        }

        $this->api->load->helper( 'url' );
        $url = ncore_addArgs( $base_url, $args, $arg_sep  );

        return $url;
    }

    public function createProduct()
    {
        return $this->adminPage( 'products' );
    }

    public function affiliateReferalUrl()
    {
        $config = $this->api->load->model( 'logic/blog_config' );

        $affiliate_id = $config->get( 'affiliate_digistore_id' );
        if (!$affiliate_id)
        {
            $affiliate_id = $config->get( 'affiliate_digibank_id' );
        }
        $product_id = $this->digistorePromoLinkProductId();

        if ($affiliate_id)
        {
            $domain = str_replace( array( 'http://', 'https://' ), '', ncore_siteUrl() );
            $campaignKey = base64_encode($domain);
            return str_replace([
                '[PROMO_PRODUCT_ID]',
                '[AFFILIATE]',
                '[CAMPAIGNKEY]',
            ], [
                $product_id,
                $affiliate_id,
                $campaignKey,
            ], DIGIMEMBER_AFFILITE_PROMOLINK);
        }
        else
        {
            return str_replace( '[SERVER_BASE_URL]', $this->api->licenseServerBaseUrl(), DIGIMEMBER_GENERIC_PROMOLINK );
        }
    }

    private function affiliateGetParams( $affiliate='', $campaignkey='' )
    {
        if (!$affiliate) {
            list( $affiliate, $campaignkey ) = $this->api->blog_config_logic->getAffiliate();
        }

        $params = array();

        if ($affiliate) {
            $params[ DM_GET_PARAM_AFFILIATE ] = $affiliate;
            $params[ 'aff' ] = $affiliate; // for direct links to digistore24 - just in case DM_GET_PARAM_AFFILIATE is modified

            if ($campaignkey) {
                $params[ DM_GET_PARAM_CAMPAIGNKEY ] = $campaignkey;
            }
        }

        return $params;
    }

    /**
     * @param string $product maropost|klicktipp
     * @param string $type info|order
     * @return bool|string
     */
    public function productInfoUrl( $product, $type )
    {
        switch ($product) {
            case 'maropost':
//                $id = defined( 'DM_MAROPOST_AFFILIATE_ID' ) ? DM_MAROPOST_AFFILIATE_ID : 3158;

                switch ($type) {
                    case 'info': return 'https://maropost.com';
                    default:     return 'https://maropost.com';
                }

            default:
                return parent::productInfoUrl($product, $type);
        }
    }
}