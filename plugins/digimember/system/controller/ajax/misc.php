<?php

$load->controllerBaseClass( 'user/base' );

class ncore_AjaxMiscController extends ncore_UserBaseController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }

    function handle_action_delayed( $response )
    {
        $action_id = ncore_retrieveGET( 'action_id' );
        $signature = ncore_retrieveGET( 'signature' );
        $user_id   = ncore_retrieveGET( 'user_id' );

        $is_valid = $action_id && $user_id && $signature;

        if (!$is_valid){
            return;
        }


        $model = $this->api->load->model( 'logic/action' );

        $model->queueDelayedAction( $user_id, $action_id, $signature );
    }

    protected function handleAjaxEvent( $event, $response )
    {
        $handler = "handle_$event";
        if (method_exists( $this, $handler )) {
            $this->$handler( $response );
        }
    }


}