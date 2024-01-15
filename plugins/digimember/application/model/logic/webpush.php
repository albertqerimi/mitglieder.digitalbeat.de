<?php

class digimember_WebpushLogic extends ncore_BaseLogic
{
    public function sendNotificationToUser( $recipient_user_obj_or_id, $webpush_message_obj_or_id )
    {
        $this->api->load->model( 'data/webpush_subscription' );
        $this->api->load->model( 'data/webpush_message' );
        $this->api->load->model( 'queue/webpush' );

        $user_id = ncore_userId( $recipient_user_obj_or_id );
        $message = $this->api->webpush_message_data->resolveToObj( $webpush_message_obj_or_id );
        if (!$message) {
            throw new Exception( _digi( 'The webpush message has been deleted.' ) );
        }

        $where = array( 'user_id' => $user_id );
        $all = $this->api->webpush_subscription_data->getAll( $where );

        if (!$all)
        {
            $user = ncore_getUserById( $user_id );
            $user_login = ncore_retrieve( $user, 'user_login', "User #$user_id" );
            throw new Exception( _digi( 'The user %s does not have an active subscription to web push notifications.', '<strong>' . $user_login . '</strong>' ) );
        }

        $success_count = 0;
        $failure_count = 0;

        $error_message = false;

        foreach ($all as $one)
        {
            try
            {
                $this->api->webpush_queue->addJob( $one );
                $success_count++;
            }
            catch (Exception $e)
            {
                $failure_count++;
                $error_message = $e->getMessage();
            }
        }

        if (!$success_count)
        {
            throw new Exception( $error_message);
        }
    }

    public function sendNotification( $subscription_obj_or_id, $webpush_message_obj_or_id )
    {
        $this->api->load->model( 'data/webpush_subscription' );
        $this->api->load->model( 'data/webpush_message' );

        $subscription = $this->api->webpush_subscription_data->resolveToObj( $subscription_obj_or_id );
        if (!$subscription) {
            return false;
        }

        if ($subscription->last_result == 'inactiv') {
            throw new Exception( _digi( 'The subscription has been cancelled by the subscriber.' ) );
        }

        $message = $this->api->webpush_message_data->resolveToObj( $webpush_message_obj_or_id );
        if (!$message) {
            return false;
        }

        list( $public_key, $private_key ) = $this->getPublicAndPrivateKey();

        $payload = $message->id;

        $args = array(
            'public_key'  => $public_key,
            'private_key' => $private_key,

            'endpoint' => $subscription->endpoint,
            'key'      => $subscription->key,
            'token'    => $subscription->token,
        );

        try
        {

            if ($this->omitRpcCalls()) {
                $api = dsvr_api();
                $webpush = $api->load->library( 'web_push' );
                $result = (array) $webpush->sendNotification( $public_key, $private_key, site_url(), $subscription->endpoint, $subscription->key, $subscription->token );
            }
            else
            {
                $rpc = $this->api->load->library( 'rpc_api' );
                $result = (array) $rpc->webPushApi( 'send_notification', $args );

            }
        }
        catch (Exception $e)
        {
            $this->api->webpush_subscription_data->setResult( $subscription, $success=false );
            throw $e;
        }

        $is_success = $result[ 'success' ] === true;
        if ($is_success)
        {
            $this->api->webpush_subscription_data->setResult( $subscription, $success=true );
        }
        else
        {
            $this->api->webpush_subscription_data->setResult( $subscription, $success=false );

            $message     = ncore_retrieve( $result, 'message', _digi( 'Error when sending web push notification' ) );
            $status_code = ncore_retrieve( $result, 'statusCode', 0 );

            $must_unsubscribe = $status_code == 410;
            if ($must_unsubscribe) {
                $this->api->webpush_subscription_data->delete( $subscription );
                $message = _digi( 'Subscription cancelled by user.' );
            }

            throw new Exception($message);
        }
    }

    public function getPublicAndPrivateKey()
    {
        try
        {
            $config = $this->api->load->model( 'logic/blog_config' );

            $keys = $config->get( 'webpush_keys' );

            if ($keys) {
                return array( $keys[ 'public_key' ], $keys[ 'private_key' ] );
            }

            if ($this->omitRpcCalls())
            {
                $api = dsvr_api();
                $webpush = $api->load->library( 'web_push' );

                list( $public_key, $private_key ) = $webpush->generatePublicAndPrivateKey();
            }
            else
            {
                $rpc    = $this->api->load->library( 'rpc_api' );
                $result = $rpc->webPushApi( 'generate_key', $args=array() );

                $public_key  = $result->public_key;
                $private_key = $result->private_key;
            }

            $keys = array(
                'public_key'  => $public_key,
                'private_key' => $private_key,
            );

            $config->set( 'webpush_keys', $keys );
            $this->api->log('api', 'Obtained new web push key pair');

            return array( $keys[ 'public_key' ], $keys[ 'private_key' ] );
        }
        catch (Exception $e)
        {
            $this->api->logError( 'api', $e->getMessage() );
            return array( false, false );
        }
    }

    public function canUse()
    {
        $is_ssl = !empty( $_SERVER[ 'HTTPS' ] ) || $_SERVER[ 'HTTP_HOST' ] == 'localhost';
        if (!$is_ssl)
        {
            return false;
        }

        return true;
    }

    public function notifySetupErrors()
    {
        if (!$this->canUse())
        {
            $msg = _digi( 'To send web push notifications, you need to enable SSL. That means, that your url must start with https://... instead of http://... .');
            ncore_flashMessage( NCORE_NOTIFY_WARNING, $msg);
        }
    }

//    private function messageToPayload( $webpush_message_obj_or_id )
//    {
//        $this->api->load->model( 'data/webpush_message' );

//        $rec = array(
//            'title'     => '',
//            'body'      => '',
//            'image_url' => '',
//        );

//        $message = $this->api->webpush_message_data->resolveToObj( $webpush_message_obj_or_id );
//        if ($message) {

//            $rec['title'] = $message->title;

//            if ($message->icon_image_id)
//            {
//                $rec['image_url'] = wp_get_attachment_url( $message->icon_image_id );

//            }

//            $rec['body']  = $message->message;
//        }

//        return base64_encode( json_encode( $rec ) );
//    }

    private function omitRpcCalls()
    {
        return 0 && NCORE_DEBUG && function_exists( 'dsvr_api' ); //why? discover later
    }


}