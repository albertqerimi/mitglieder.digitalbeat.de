<?php

class ncore_ActionOutQueue extends ncore_BaseQueue
{
    public function haveUnexpiredAction( $wp_user_id_or_email, $action_id )
    {
         $where = array(
            'user_id'      => $wp_user_id_or_email,
            'action_id'    => $action_id,
            'expire_at >=' => ncore_dbDate( 'now', 'date' ),
         );

         $have_action = (bool) $this->getAll( $where );

         return $have_action;
    }

    public function addJob( $wp_user_id_or_email, $action_obj_or_id, $is_test_run=false )
    {
        $this->api->load->model( 'data/action' );
        $action = $this->api->action_data->resolveToObj( $action_obj_or_id );
        if (!$action) {
            return;
        }

        $have_entry = $is_test_run
                    ? false
                    : $this->haveUnexpiredAction( $wp_user_id_or_email, $action->id );
        if ($have_entry)
        {
            return;
        }

        $this->api->load->model( 'logic/action' );
        $expire_days = $is_test_run
                       ? -1
                       : $this->api->action_logic->actionExpireDays( $action->condition_type );

        $data = array(
            'user_id'     => $wp_user_id_or_email,
            'action_id'   => $action->id,
            'expire_at'   => ncore_dbDate( time() + 86400*$expire_days, 'date' ),
        );

        $id = $this->create( $data );

        $config = $this->api->load->model( 'logic/blog_config' );
        $is_enabled = (bool) $config->get( "have_external_wp_cron_call", false );
        if ($is_test_run || !$is_enabled)
        {
            $this->execJob( $id );
        }
    }



    //
    // protected section
    //
    protected function isUniqueInBlog()
    {
        return true;
    }

    protected function keepTimeDays()
    {
        return 183;
    }

    protected function purgeQueue()
    {
        parent::purgeQueue();

        $queue_table = $this->sqlTableName();

        $model = $this->api->load->model( 'data/action' );

        $action_table = $model->sqlTableName();

        $sql = "DELETE `$queue_table`
                FROM `$queue_table`
                LEFT JOIN `$action_table`
                    ON `$queue_table`.action_id = `$action_table`.id

                WHERE `$action_table`.id IS NULL";

        $this->db()->query($sql);
    }

    protected function process( $data )
    {
        try
        {
            $model = $this->api->load->model( 'logic/action' );

            $wp_user_id_or_email   = $data->user_id;
            $action_id             = $data->action_id;

            $result = $model->execute( $wp_user_id_or_email, $action_id );

            return $result;
        }

        catch (Exception $e)
        {
            $email = $data->email;
            $msg = $e->getMessage();

            $this->api->logError( 'ipn', _ncore( 'Failed to subscribe %s to newsletter: %s' ), $email, $msg );

            return false;
        }


    }

    protected function sqlBaseTableName()
    {
        return 'action_out_queue';
    }

    protected function sqlTableMeta()
    {
       $meta = parent::sqlTableMeta();

       $meta['columns'][ 'action_id' ] = 'id';
       $meta['columns'][ 'user_id' ]   = 'string[127]';

       $meta['columns'][ 'expire_at' ] = 'date';

       //$meta['columns'][ 'facebook_status' ]   = 'string[15]';
       $meta['columns'][ 'webpush_status' ]    = 'string[15]';
       $meta['columns'][ 'email_status' ]      = 'string[15]';
       $meta['columns'][ 'klicktipp_status' ]  = 'string[15]';

       $meta['indexes'][] = 'user_id';

       return $meta;
    }

    protected function onGiveUp( $row, $tries_so_far )
    {
        $action_id = $row->action_id;
        $user_id   = $row->user_id;

        $message = _ncore( 'Gave up executing action #%s for user #%s after %s tries.', $action_id, $user_id, $tries_so_far );

        $this->api->logError( 'autorsponder', $message );
    }

    protected function onFailure( $row, $tries_so_far, $tries_left )
    {
        $action_id = $row->action_id;
        $user_id   = $row->user_id;


        $message = _ncore( 'Failed to execute action #%s for user #%s.', $action_id, $user_id );

        $message .= ' ';

        if ($tries_so_far == 1)
        {
            $message .=  _ncore( '1 try so far.' );
        }
        else
        {
            $message .=  _ncore( '%s tries so far.', $tries_so_far );
        }

        $message .= ' ';

        if ($tries_left == 1)
        {
            $message .=  _ncore( '1 try left.' );
        }
        else
        {
            $message .=  _ncore( '%s tries left.', $tries_left );
        }


        $this->api->log( 'autorsponder', $message );
    }

    //
    // private section
    //


}