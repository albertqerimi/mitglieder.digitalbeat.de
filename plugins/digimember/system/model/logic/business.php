<?php

class ncore_BusinessLogic extends ncore_BaseLogic
{
    public function passwordMinLength()
    {
        return 7;
    }

    public function newPasswordHint( $seperator = '<p>' )
    {
        $pw_min_length = $this->passwordMinLength();
        $characters = '! " ? $ % ^ & ) .';
        $hint = _ncore( 'At least %s characters.<p>For better security use upper and lower characters, digits and symbols like: %s.', $pw_min_length, $characters );
    }

    public function sendRequestNewPasswordMail( $email, $redirect_url='' )
    {
        $user_id = ncore_getUserIdByEmail( $email );

        if (!$user_id)
        {
            return false;
        }

        $this->api->load->helper( 'string' );
        $key = ncore_randomString( 'alnum', 32 );

        $blog_config = $this->api->load->model( 'logic/blog_config' );

        $life_time_hours = $this->passwordRequestExpireDays() * 24;

        $blog_config->set( "pw_request_${user_id}_key",  $key, $life_time_hours );
        $blog_config->delete( "pw_sent_${user_id}_at" );

        $model = $this->api->load->model( 'data/one_time_login' );

        $params = array(
            'user_id'  => $user_id,
            'user_key' => $key,
            'confirm_password'  => 'yes',
        );

        if ($redirect_url)
        {
            $redirect_url = ncore_removeArgs( $redirect_url, array( 'user_id', 'user_key', 'confirm_password' ) );
        }
        else
        {
            $redirect_url = ncore_siteUrl();
        }



        $redirect_url = ncore_addArgs( $redirect_url, $params, '&', $url_encode=false );

        $url = $model->setOneTimeLogin( 0, $redirect_url, '&' );

        $model = $this->api->load->model('logic/mail_hook');
        $params = array(
                    'url' => $url,
                  );
        $model->sendMail( $email, NCORE_MAIL_HOOK_NEW_PASSWORD, $ref_id=0, $params );

        return true;
    }

    public function passwordRequestExpireDays()
    {
        return 3;
    }

    public function validateNewPasswordConfirmation()
    {
        $confirm = ncore_retrieve( $_GET, 'confirm_password' );
        if ($confirm != 'yes' )
        {
            return null;
        }

        $user_id  = ncore_retrieve( $_GET, 'user_id' );
        $user_key = ncore_retrieve( $_GET, 'user_key' );

        if (!$user_id || !$user_key)
        {
            return null;
        }

        $blog_config = $this->api->load->model( 'logic/blog_config' );

        $key_matches = $user_key == $blog_config->get( "pw_request_${user_id}_key" );
        if (!$key_matches)
        {
            $sent_at = $blog_config->get( "pw_sent_${user_id}_at" );
            return (bool) $sent_at;
        }

        $blog_config->delete( "pw_request_${user_id}_key" );

        $model = $this->api->load->model( 'data/user' );
        $password = $model->getPassword( $user_id );
        if (!$password) {

            $this->api->load->helper( 'string' );
            $password = ncore_randomString( 'password', NCORE_PASSWORD_LENGTH );

            $model->setPassword( $user_id, $password, $do_store_password=true, $do_update_wp_pw=true );
        }

        $user = ncore_getUserById( $user_id );

        $login = ncore_retrieve( $user, 'user_login' );
        $this->api->log( 'mail', _ncore( 'User %s requested a new password.', $login ) );

        $email = $user->user_email;

        $config = $this->api->load->model( 'logic/blog_config' );
        $login_url  = $config->loginUrl();

        $model = $this->api->load->model('logic/mail_hook');
        $params = array(
                    'username' => $email,
                    'password' => $password,
                    'loginurl' => $login_url,
                  );
        $model->sendMail( $email, NCORE_MAIL_HOOK_PASSWORD_SENT, $ref_id=0, $params );

        $life_time_hours = $this->passwordRequestExpireDays() * 24;

        $blog_config->set( "pw_sent_${user_id}_at", time(), $life_time_hours );

        return true;
    }

}