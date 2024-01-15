<?php

class digimember_WebhookLogic extends ncore_LinkLogic
{
    function render_url( $webhook_obj_or_id )
    {
        $this->api->load->model( 'data/webhook' );

        $webhook = $this->api->webhook_data->resolveToObj( $webhook_obj_or_id );
        if (!$webhook) {
            return '';
        }

        $params = [
            'dm_webhook' => $webhook->id . '_' . $webhook->auth_key,
        ];

        return ncore_addArgs( site_url(), $params );
    }

    function handleRequest()
    {
        try
        {
            $this->api->load->helper( 'string' );

            $id_auth = ncore_retrieveGET( 'dm_webhook' );

            $webhook = $this->_get_by_id_and_key( $id_auth );
            if (!$webhook) {
                return;
            }

            if (ncore_isFalse( $webhook->is_active ) )
            {
                $this->_show_error( $webhook, _digi( 'The webhook is not active. Edit the webhook and set the input field is_active to YES.' ) );
            }

            if (isset($webhook->webhook_type)) {
                switch ($webhook->webhook_type) {
                    case 'newOrder':
                        $this->newOrderAction($webhook);
                        break;
                    case 'cancelOrder':
                        $this->cancelOrderAction($webhook);
                        break;
                    default:
                        $this->newOrderAction($webhook);
                        break;
                }
            }
            else {
                $this->newOrderAction($webhook);
            }
        }
        catch (Exception $e)
        {
            $this->_show_error( $webhook, $e->getMessage() );
        }

    }

    private function newOrderAction ($webhook) {
        $email       = ncore_retrieveREQUEST( $webhook->param_email );
        $first_name  = ncore_retrieveREQUEST( $webhook->param_first_name );
        $last_name   = ncore_retrieveREQUEST( $webhook->param_last_name );

        if (!$email) {
            $this->_show_error( $webhook, _digi( 'The email was not given as GET or POST parameter %s.', $webhook->param_email ) );
        }

        switch ($webhook->add_product_method)
        {
            case 'by_url':
                $product_ids = ncore_retrieveREQUEST( $webhook->param_product );
                if (!$product_ids) {
                    $this->_show_error( $webhook, _digi( 'No product ids were given as GET or POST parameter %s.',  $webhook->param_product ) );
                }
                break;

            case 'by_hook':
            default:
                $product_ids = ncore_explodeAndTrim( $webhook->product_ids_comma_seperated );
        }

        switch ($webhook->add_order_id_method)
        {
            case 'by_url':
                $order_id = ncore_retrieveREQUEST( $webhook->param_order_id );
                break;

            case 'by_hook':
            default:
                $order_id = $webhook->order_id;
        }

        switch ($webhook->add_password_method)
        {
            case 'by_url':
                $password = ncore_retrieveREQUEST( $webhook->param_password );
                break;

            case 'by_hook':
            default:
                $password = '';
        }

        $library = $this->api->load->library( 'payment_handler' );
        $address = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
        );
        $welcome_msg_sent = $library->signUp( $email, $product_ids, $address, $do_perform_login=false, $order_id, $password );
        die( 'OK' );
    }

    private function cancelOrderAction ($webhook) {
        $email = ncore_retrieveREQUEST( $webhook->param_email );

        if (!$email) {
            $this->_show_error( $webhook, _digi( 'The email was not given as GET or POST parameter %s.', $webhook->param_email ) );
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->_show_error( $webhook, _digi('%s is not a valid email address', $email) );
        }

        $user_id = ncore_getUserIdByEmail($email);

        switch ($webhook->add_order_id_method)
        {
            case 'by_url':
                $order_id = ncore_retrieveREQUEST( $webhook->param_order_id );
                break;

            case 'by_hook':
            default:
                $order_id = $webhook->order_id;
        }
        switch ($webhook->access_stops_on_method)
        {
            case 'delayed':
                $accessStopsOn = ncore_retrieveREQUEST($webhook->param_access_stops_on , false);
                if (!$accessStopsOn) {
                    $this->_show_error( $webhook, _digi( 'The date the access should stop was not given as GET or POST parameter %s.',  $webhook->param_access_stops_on ) );
                }
                break;
            case 'now':
            default:
            $accessStopsOn = date('Y-m-d');
        }

        $paymentLib = $this->api->load->library('payment_handler');
        try {
            $paymentLib->onCancelWebhook(0, $order_id, $user_id, $accessStopsOn);
        } catch (Exception $e) {
            die( 'Could not cancel order.' );
        }
        die( 'OK' );
    }

    private function _show_error( $webhook, $message, $http_code=400 )
    {
        http_response_code( $http_code );
        $title = $this->api->pluginDisplayName() . ' - ' . _digi( 'webhook' ) . ' ' . $webhook->name . ' (#'.$webhook->id .')';

        die( "$title: $message" );
    }

    private function _get_by_id_and_key( $id_auth )
    {
        $this->api->load->model( 'data/webhook' );
        $this->api->load->helper( 'encryption' );

        list( $id, $auth_key ) = ncore_retrieveList( '_', $id_auth, 2, true );

        $webhook = $this->api->webhook_data->get( $id );

        $is_valid = $webhook
                 && $auth_key
                 && ncore_hashCompare( $webhook->auth_key, $auth_key );

        return $is_valid
               ? $webhook
               : false;
    }
}