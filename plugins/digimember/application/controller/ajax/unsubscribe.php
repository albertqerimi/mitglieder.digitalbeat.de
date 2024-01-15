<?php

$load->controllerBaseClass( 'user/base' );

class digimember_AjaxUnsubscribeController extends ncore_UserBaseController
{
    function handleManageSubscriptions( $unsubscribe_key )
    {
        /** @var digimember_ActionLogic $actionLogic */
        $actionLogic = $this->api->load->model( 'logic/action' );
        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model( 'logic/html' );

        $user_id = $actionLogic->validateUnsubscribeKey( $unsubscribe_key );

        $is_opted_aout = $actionLogic->isOptedOutFromEmail( $user_id );
        $dialog = $is_opted_aout
                ? $this->createSuccessDialog()
                : $this->createAskConfirmationDialog( $unsubscribe_key );

        $js = $dialog->showDialogJs();

        $htmlLogic->jsOnLoad( $js );
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    protected function handle_exec_unsubscribe( $response )
    {
        /** @var digimember_ActionLogic $actionLogic */
        $actionLogic = $this->api->load->model( 'logic/action' );

        $unsubscribe_key = ncore_retrieveGET( 'key' );

        $user_id = $actionLogic->validateUnsubscribeKey( $unsubscribe_key );
        if ($user_id)
        {
            $actionLogic->doOptOutFromEmail( $user_id );

            $msg = _dgyou( 'You have been unsubscribed.' );
            $response->success( $msg );
        }
        else
        {
            $msg = _digi( 'This link is not valid. Please use the link from the email to unsubscribe.' );
            $response->error( $msg );
        }
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    protected function handle_set( $response )
    {
        $is_subscribe          = (int) ncore_isTrue( ncore_retrieveGET( 'is_subscribe' ) );
        $input_container_class = ncore_washText( ncore_retrieveGET( 'input_container_class' ) );

        $do_optout = !$is_subscribe;

        /** @var digimember_ActionLogic $actionLogic */
        $actionLogic = $this->api->load->model( 'logic/action' );
        $actionLogic->doOptOutFromEmail( $user_id='current', $do_optout );

        $js = "ncoreJQ( '.$input_container_class input[type=checkbox]' ).prop('checked', $is_subscribe );";

        $response->js( $js );
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

        $secure_events[] = 'exec_unsubscribe';

        return $secure_events;
    }

    private function createAskConfirmationDialog( $unsubscribe_key )
    {
        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model( 'logic/link' );
        $unsubscribe_url = $linkLogic->ajaxUrl( 'ajax/unsubscribe', 'exec_unsubscribe', array( 'key' => $unsubscribe_key ) );

        $meta = array(
            'type' => 'confirm',
            'ajax_dlg_id' => 'ajax_unsubscribe_confirm_dlg',

            'message' => _dgyou( 'Do you want to unsubscribe from these email messages?' ),
            'title'   => _digi( 'Question' ),
            'ok_button_label'     => _digi( 'Yes' ),
            'cancel_button_label' => _digi( 'No' ),
            'width' => '500px',

            'cb_js_code' => "ncoreJQ( this ).dmDialog( 'close' ); dmDialogAjax_FetchUrl( '$unsubscribe_url' ); ",
        );


        /** @var ncore_AjaxLib $lib */
        $lib = $this->api->load->library( 'ajax' );

        return $lib->dialog( $meta );

    }

    private function createSuccessDialog()
    {
        $meta = array(
            'type' => 'alert',
            'ajax_dlg_id' => 'ajax_unsubscribe_confirm_dlg',

            'message' => _dgyou( 'You have already been unsubscribed from these email messages.' ),
            'title'   => _digi( 'Success' ),
            'width' => '500px',
        );


        /** @var ncore_AjaxLib $lib */
        $lib = $this->api->load->library( 'ajax' );

        return $lib->dialog( $meta );

    }


}