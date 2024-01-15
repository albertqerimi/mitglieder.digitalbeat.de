<?php

class digimember_WebpushQueue extends ncore_BaseQueue
{
    public function trackingKey( $queue_entry_obj_or_id )
    {
        $obj = $this->resolveToObj( $queue_entry_obj_or_id );
        if (!$obj) {
            return '';
        }

        return $obj->id . '_' . $obj->track_key;
    }


    public function addJob( $webpush_msg_obj_or_id )
    {
        $this->api->load->model( 'data/webpush_message' );

        $obj = $this->api->webpush_message_data->resolveToObj( $webpush_msg_obj_or_id );
        if (!$obj)
        {
            throw new Exception( _digi( 'Flash message has been deleted.' ) );
        }

        $what = $obj->send_to_what;

        $user_ids = array();

        switch ($what)
        {
            case 'one':
                $recipient_user = ncore_getUserById( $obj->send_to_user_id );
                if (!$recipient_user)
                {
                    throw new Exception( _ncore( 'Please enter a valid user name in field %s', '<strong>' . _digi('Recipient user' ) . '</strong>' ) );
                }

                $user_ids[] = $recipient_user->ID;
                break;

            case 'with_product':
                /** @var digimember_UserProductData $user_product_model */
                $user_product_model = $this->api->load->model( 'data/user_product' );
                $user_ids = $user_product_model->getOwnersOf( $obj->send_to_product_ids );
                break;
            case 'without_product':
                /** @var digimember_UserProductData $user_product_model */
                $user_product_model = $this->api->load->model( 'data/user_product' );
                $user_ids = $user_product_model->getNonOwnersOf( $obj->send_to_product_ids );
                break;
            case 'all':
                $this->api->load->model( 'queue/webpush' );
                $this->_addJob( 'all', $obj );
                return;

        }

        $this->api->load->model( 'data/webpush_subscription' );
        $count = 0;
        $do_exec_now = count($user_ids) == 1;

        foreach ($user_ids as $user_id)
        {
            $all = $this->api->webpush_subscription_data->getAll( array( 'user_id' => $user_id ));
            if ($all)
            {
                $count++;
            }

            foreach ($all as $one)
            {
                $job_id = $this->_addJob( $one, $obj );
                if ($do_exec_now)
                {
                    $this->execJob( $job_id );
                }
            }
        }

        return $count;
    }

    public function getForSubscription( $subscription_obj_or_id )
    {
        $this->api->load->model( 'data/webpush_subscription' );
        $subscription_id = $this->api->webpush_subscription_data->resolveToId( $subscription_obj_or_id );

        $where = array( 'subscription_id' => $subscription_id );
        $limit = '0,1';
        $order = 'id DESC';

        $all = $this->getAll( $where, $limit, $order );

        return $all
               ? $all[0]
               : false;
    }

    public function markAsRead( $queue_entry_obj_or_id )
    {
        $modified = $this->_markAs( $queue_entry_obj_or_id, 'delivered' );

        if ($modified)
        {
            $entry = $this->resolveToObj( $queue_entry_obj_or_id );

            $this->api->load->model( 'data/webpush_message' );
            $this->api->webpush_message_data->incCounter( $entry->message_id, 'count_shown' );
        }

        return $modified;
    }

    public function markAsClicked( $track_key )
    {
        list( $id, $key ) = ncore_retrieveList( '_', $track_key );
        $modified = $this->_markAs( $id, 'clicked', array( 'track_key' => $key ) );

        if ($modified)
        {
            $entry = $this->get( $id );

            $this->api->load->model( 'data/webpush_message' );
            $this->api->webpush_message_data->incCounter( $entry->message_id, 'count_clicked' );
        }

        return $modified;
    }

    //
    // protected section
    //
    protected function keepTimeDays()
    {
        return 30;
    }

    protected function defaultValues()
    {
        $this->api->load->helper( 'string' );

        $values = parent::defaultValues();

        $values[ 'track_key' ] = ncore_randomString( 'alnum', 23 );

        return $values;
    }

    protected function process( $row )
    {
        try
        {
            $subscription_id  = $row->subscription_id;
            $message_id       = $row->message_id;

            $must_queue_all = $subscription_id === 'all';
            if ($must_queue_all) {
                $this->addJobForAll( $message_id );
            }
            elseif($subscription_id > 0)
            {
                $this->api->load->model( 'logic/webpush' );
                $this->api->webpush_logic->sendNotification( $subscription_id, $message_id );

                $this->api->load->model( 'data/webpush_message' );
                $this->api->webpush_message_data->incCounter( $row->message_id, 'count_sent' );
            }

            return true;
        }

        catch (Exception $e)
        {
            $subscription_id = $row->subscription_id;
            $msg             = $e->getMessage();

            $this->api->logError( 'ipn', _digi( 'Failed to send email to subscription #%s: %s' ), $subscription_id, $msg );

            return false;
        }
    }

    protected function sqlBaseTableName()
    {
        return 'webpush_queue';
    }

    protected function sqlTableMeta()
    {
       $meta = parent::sqlTableMeta();

       $meta['columns'][ 'message_id' ]       = 'id';
       $meta['columns'][ 'subscription_id' ]  = 'string[15]';
       $meta['columns'][ 'delivered' ]        = 'lock_date';
       $meta['columns'][ 'clicked' ]          = 'lock_date';
       $meta['columns'][ 'track_key' ]        = 'string[23]';

       $meta['indexes'][] = 'subscription_id';

       return $meta;
    }

    protected function onGiveUp( $row, $tries_so_far )
    {
        $subscription_id  = $row->subscription_id;

        $message = _digi( 'Gave up web push notification for subscrption id #%safter %s tries.', $subscription_id, $tries_so_far );

        $this->api->logError( 'ipn', $message );
    }

    protected function onFailure( $row, $tries_so_far, $tries_left )
    {
        $subscription_id = $row->subscription_id;

        $message =  _digi( 'Could not send web push notification for subscription #%s.', $subscription_id );

        $message .= ' ';

        if ($tries_so_far == 1)
        {
            $message .=  _digi( '1 try so far.' );
        }
        else
        {
            $message .=  _digi( '%s tries so far.', $tries_so_far );
        }

        $message .= ' ';

        if ($tries_left == 1)
        {
            $message .=  _digi( '1 try left.' );
        }
        else
        {
            $message .=  _digi( '%s tries left.', $tries_left );
        }


        $this->api->log( 'ipn', $message );
    }

    //
    // private section
    //
    private function _addJob( $subscription_obj_or_id_or_all, $message_obj_or_id )
    {
        $this->cancelJob( $subscription_obj_or_id_or_all );

        $this->api->load->model( 'data/webpush_message' );
        $this->api->load->model( 'data/webpush_subscription' );

        $message_id = $this->api->webpush_message_data->resolveToId( $message_obj_or_id );

        if (!$message_id) {
            return false;
        }

        if ($subscription_obj_or_id_or_all === 'all')
        {
            $subscription_id = 'all';
        }
        else
        {
            $subscription_id = $this->api->webpush_subscription_data->resolveToId( $subscription_obj_or_id_or_all );

            if (!$subscription_id) {
                return false;
            }
        }

        $data = array(
            'subscription_id'  => $subscription_id,
            'message_id'       => $message_id,
        );

        return $this->create( $data );
    }

    private function cancelJob( $subscription_obj_or_id_or_all ){

        $this->api->load->model( 'data/webpush_subscription' );

        $where = array(
            'processed_at'    => null,
        );

        if ($subscription_obj_or_id_or_all !== 'all')
        {
            $subscription_id = $this->api->webpush_subscription_data->resolveToId( $subscription_obj_or_id_or_all );
            if (!$subscription_id) {
                return;
            }

            $where[ 'subscription_id' ] = $subscription_id;
        }

        $all = $this->getAll( $where );
        if (!$all) {
            return;
        }

        $data = array( 'processed_at' => ncore_dbDate() );
        foreach ($all as $one)
        {
            $this->update( $one, $data );
        }

    }

    private function _markAs( $queue_entry_obj_or_id, $col, $where=array() )
    {
        $is_modified = false;

        $row = $this->resolveToObj( $queue_entry_obj_or_id );

        if (empty( $row->$col))
        {
            $where[ $col ] = null;

            $data  = array( $col => ncore_dbDate() );
            $is_modified = $this->update( $row, $data, $where );
        }

        return $is_modified;
    }

    private function addJobForAll( $message_obj_or_id )
    {
        $this->cancelJob( 'all' );

        $this->api->load->model( 'data/webpush_subscription' );
        $all = $this->api->webpush_subscription_data->getAll();
        foreach ($all as $one)
        {
            $this->_addJob( $one, $message_obj_or_id );
        }
    }
}