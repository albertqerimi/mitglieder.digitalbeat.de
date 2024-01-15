<?php

class digimember_IpLockLogic extends ncore_BaseLogic
{
    const session_update_interval = 3600;
    const session_count_interval  = 86400;
    const store_log_days = 90;

    public function checkLogin( $user_id )
    {
        if ($user_id<=0)
        {
            return false;
        }

        $ip = ncore_clientIp();

        $cookie_name = str_replace( '.', '_', "dm_session_${user_id}_${ip}" );

        $session_count_timestamp = ncore_retrieve( $_COOKIE, $cookie_name, 0 );

        $must_update = abs( time() - $session_count_timestamp ) > self::session_update_interval;

        if ($must_update)
        {
            if (!headers_sent()) {
                ncore_setcookie( $cookie_name, time(), 0, '/' );
            }

            /** @var digimember_IpCounterData $model */
            $model = $this->api->load->model( 'data/ip_counter' );
            $model->count( $user_id );

            $count = $model->getForUser( $user_id );

            /** @var digimember_BlogConfigLogic $model */
            $model = $this->api->load->model( 'logic/blog_config' );
            $limit = $model->getIpAccessLimit();

            $limit_violated = $limit > 0 && $count > $limit;

            if ($limit_violated)
            {
                /** @var digimember_AccessLogic $model */
                $model = $this->api->load->model( 'logic/access' );
                $model->blockAccess( $user_id, 'ip_access_limit' );
            }

            return !$limit_violated;
        }

        return true;
    }

    //
    // protected section
    //
   
}