<?php

// use Facebook\FacebookSession;

class digimember_FacebookConnectorLib extends ncore_Library
{
    const GRAPH_API_VERSION = 'v2.4';
    const BASE_GRAPH_URL = 'https://graph.facebook.com';

    const fb_permissions = 'email,public_profile'; // publish_actions
    // const fb_permissions_extended = 'publish_actions'; //publish_actions,manage_pages,status_update';

    public function renderFbAppSetupHint()
    {

        $site  = 'developer.facebook.com';
        $url   = "https://$site";

        $link = ncore_htmlLink( $url, $site, array( 'target'=> '_blank' ) );

        return _digi( 'Setup your Facebook app on %s.', $link );
    }

    public function getAccount( $access_token='' )
    {
        if (!$access_token)
        {
            /** @var digimember_UserData $model */
            $model = $this->api->load->model( 'data/user' );
            $user = $model->getCurrent();
            $access_token = @$user->fb_auth_token;
        }

        if (empty($access_token))
        {
            $void = $this->load_module( 'void' );
            return $void;
        }

        $account = $this->load_module( 'account' );

        $account->setAccessToken ($access_token );

        return $account;
    }

    public function getLoginButton()
    {
        return $this->load_module( 'login_button' );
    }

//    function canPostFeed( $granted_scopes )
//    {
//        return $this->hasScope( 'publish_actions', $granted_scopes );
//    }

    public function isFacebookConfigured()
    {
        /** @var digimember_BlogConfigLogic $config */
        $config = ncore_api()->load->model( 'logic/blog_config' );

        $facebook_enabled     = (bool) $config->get( 'facebook_enabled' );
        $facebook_app_id      =        $config->get( 'facebook_app_id' );
        $facebook_app_secrect =        $config->get( 'facebook_app_secrect' );

        $is_configured = $facebook_enabled && $facebook_app_id && $facebook_app_secrect;

        return $is_configured;
    }

    public function renderSetupHint()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $link = $model->adminMenuLink( $menu='', 'facebook' );

        return _digi( 'Go to %s (section Facebook) to setup your facebook app.', $link );
    }

    /**
     * @param $access_token
     * @param $access_lifetime
     * @param $granted_scopes
     * @return mixed
     * @throws Exception
     */
    public function login( $access_token, $access_lifetime, $granted_scopes )
    {
        if (empty($access_token)) {
            return false;
        }

        $account = $this->getAccount($access_token);
        if (!$account) {
            return false;
        }

        $fb_user = $account->userData();
        if (!$fb_user) {
            throw new Exception( _digi( 'Error logging in via Facebook' ) );
        }

        $fb_id      = ncore_retrieve( $fb_user, 'id' );
        $email      = ncore_retrieve( $fb_user, 'email' );
        $first_name = ncore_retrieve( $fb_user, 'first_name' );
        $last_name  = ncore_retrieve( $fb_user, 'last_name' );

        /** @var digimember_UserData $userData */
        $userData = $this->api->load->model( 'data/user' );
        $dm_user = $userData->getByFacebookId( $fb_id );
        $user_id = ncore_retrieve( $dm_user, 'user_id' );

        if (!$user_id)
        {
            $user_id = ncore_getUserIdByEmail( $email );
        }


        if ($user_id)
        {
            $userData->setFacebookData( $user_id, $fb_id, $email, $access_token, $access_lifetime, $granted_scopes);

            /** @var digimember_IpLockLogic $model */
            $model = $this->api->load->model( 'logic/ip_lock' );
            $model->checkLogin( $user_id );

            /** @var digimember_AccessLogic $model */
            $model = $this->api->load->model( 'logic/access' );
            $block_reason = $model->blockAccessReason( $user_id );
            if ($block_reason)
            {
                throw new Exception( _digi( 'Your account has been blocked, because there have been logins from too many devices (IPs). Please try again tomorrow.' )
                                     . ' ('._ncore('Error code: %s', $block_reason ) . ')' );
            }

            return $user_id;
        }

        if (!$this->auto_account_creation)
        {
            throw new Exception( 'You do not have an account for this site. Please register first!' );
        }

        /** @var digimember_PaymentHandlerLib $library */
        $library = $this->api->load->library( 'payment_handler' );

        $product_ids = $this->new_user_product_ids;

        $address = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
        );

        $library->signUp( $email, $product_ids, $address, $do_perform_login=false );

        $user_id = ncore_getUserIdByEmail( $email );

        return $user_id;
    }

    public function enableAutoAccountCreation( $products_for_new_fb_users='' )
    {
        $this->auto_account_creation = true;

        $product_ids = is_array($products_for_new_fb_users)
                       ? $products_for_new_fb_users
                       : explode( ',', $products_for_new_fb_users );
        foreach ($product_ids as $id)
        {
            $id = ncore_washInt( $id );
            if ($id<=0) {
                continue;
            }

            if (!in_array( $id, $this->new_user_product_ids)) {
                $this->new_user_product_ids[] = $id;
            }
        }
    }

    private $modules = array();

    private $auto_account_creation=false;
    private $new_user_product_ids=array();

    private function load_module( $type )
    {
        $module =& $this->modules[ $type ];
        if (isset($module)) {
            return $module;
        }

        $module = false;

        if (!$this->isFacebookConfigured())
        {
            return false;
        }

        $type = ncore_washText($type);

        $dir = dirname(__FILE__);

        require_once "$dir/helper.php";
        require_once "$dir/module/base.php";
        require_once "$dir/module/$type.php";

        $class_name = 'digimember_Facebook'.ncore_camelCase( $type );

        /** @var digimember_BlogConfigLogic $config */
        $config = ncore_api()->load->model( 'logic/blog_config' );

        $app_id     = $config->get( 'facebook_app_id' );
        $app_secret = $config->get( 'facebook_app_secrect' );

        // $use_extended_permissions = (bool) $config->get( 'facebook_use_extended_permissions' );

        $module = new $class_name( $this->api(), $this, $app_id, $app_secret ); //, $use_extended_permissions );

        return $module;
    }
}

