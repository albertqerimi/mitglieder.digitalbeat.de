<?php

$load->controllerBaseClass( 'user/base' );

class digimember_AjaxWebpushController extends ncore_UserBaseController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }

    function handle_store_subscription( /** @noinspection PhpUnusedParameterInspection */ $response )
    {
        $action   = ncore_retrieveGET( 'action' );
        $key      = ncore_retrieveGET( 'key' );
        $token    = ncore_retrieveGET( 'token' );
        $endpoint = ncore_retrieveGET( 'endpoint' );

        $must_delete = $action === 'delete';

        /** @var digimember_WebpushSubscriptionData $webpushSubscriptionData */
        $webpushSubscriptionData = $this->api->load->model( 'data/webpush_subscription' );

        if ($must_delete)
        {
            $webpushSubscriptionData->unsubscribe( $key, $token, $endpoint );
        }
        else
        {
            $webpushSubscriptionData->subscribe( $key, $token, $endpoint );
        }
    }


    function handle_track_click( /** @noinspection PhpUnusedParameterInspection */ $response )
    {
        $final_url  = ncore_retrieveGET( 'url', site_url() );
        $track_key  = ncore_retrieveGET( 'track' );

        /** @var digimember_WebpushQueue $webpushQueue */
        $webpushQueue = $this->api->load->model( 'queue/webpush' );
        $webpushQueue->markAsClicked( $track_key );

        wp_redirect( $final_url);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    function handle_get_message( $response )
    {
        $key      = ncore_retrieveGET( 'key' );
        $token    = ncore_retrieveGET( 'token' );
        $endpoint = ncore_retrieveGET( 'endpoint' );


        /** @var digimember_WebpushMessageData $webpushMessageData */
        $webpushMessageData = $this->api->load->model( 'data/webpush_message' );
        /** @var digimember_WebpushSubscriptionData $webpushSubscriptionData */
        $webpushSubscriptionData = $this->api->load->model( 'data/webpush_subscription' );
        /** @var digimember_WebpushQueue $webpushQueue */
        $webpushQueue = $this->api->load->model( 'queue/webpush' );


        $row     = $webpushSubscriptionData->getQueueEntry( $key, $token, $endpoint );
        $message = false;

        if ($row)
        {
            $webpushQueue->markAsRead( $row );

            $message = $webpushMessageData->get( $row->message_id );
        }

        $data = array();
        if ($message)
        {
            $data = (array) $webpushMessageData->render( $message, $row );

            $data[ 'status' ] = 'SUCCESS';
        }
        else
        {
            $data[ 'status' ] = 'ERROR';
        }

        $data[ 'service_worker_version' ] = $this->api->pluginVersion();

        $response->setResponseObject( $data );
    }

    protected function handleAjaxEvent( $event, $response )
    {
        $handler = "handle_$event";
        if (method_exists( $this, $handler )) {
            $this->$handler( $response );
        }
    }

    protected function secureAjaxEvents()
    {
        $secure_events = parent::secureAjaxEvents();

        $secure_events[] = 'get_message';
        $secure_events[] = 'track_click';

        return $secure_events;
    }


}