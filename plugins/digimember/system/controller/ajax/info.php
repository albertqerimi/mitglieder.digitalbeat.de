<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass( 'user/base' );

class ncore_AjaxInfoController extends ncore_UserBaseController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    function handle_get_blog_name( $response )
    {
        if (!ncore_userId()){
            return;
        }

        $blog_id       = ncore_retrieveGET( 'blog_id' );
        $target_div_id = ncore_retrieveGET( 'target_div_id' );
        $not_found_msg = ncore_retrieveGET( 'not_found_msg' );

        $html = '';

        if ($blog_id)
        {
            $blog_name = ncore_getBlogDomain( $blog_id );

            $html = $blog_name
                  ? $blog_name
                  : $not_found_msg;
        }

        $response->html( $target_div_id, $html );
    }

    function handle_model_close_window( $response )
    {
        $plugin = ncore_retrieveGET( 'domain' );
        $window = ncore_retrieveGET( 'window' );
        $closed = ncore_retrieveGET( 'closed' );

        /** @var ncore_CloseWindowLogic $model */
        $model = $this->api->load->model( 'logic/close_window' );
        $model->setClosedWindow( $window, $closed, $plugin );
    }

    protected function handleAjaxEvent( $event, $response )
    {
        $handler = "handle_$event";
        if (method_exists( $this, $handler )) {
            $this->$handler( $response );
        }
    }


}