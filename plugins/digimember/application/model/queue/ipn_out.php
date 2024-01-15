<?php

class digimember_IpnOutQueue extends ncore_BaseQueue
{
    public function addJob( $user_id, $product_id, $email, $address, $order_id, $force_dbl_optin=false, $newsletter_choice='none' )
    {
        // if ($newsletter_choice === 'optout')
        // {
        //     return;
        // }
        
        $data = array(
            'user_id'            => $user_id,
            'product_id'         => $product_id,
            'email'              => $email,
            'first_name'         => ncore_retrieve( $address, 'first_name' ),
            'last_name'          => ncore_retrieve( $address, 'last_name' ),
            'order_id'           => $order_id,
            'force_double_optin' => ($force_dbl_optin?'Y':'N'),
        );

        $id = $this->create( $data );

        $config = $this->api->load->model( 'logic/blog_config' );
        $is_enabled = (bool)  $config->get( "have_external_wp_cron_call", false );
        if (!$is_enabled) {
            $this->execJob( $id );
        }
    }



    //
    // protected section
    //
    protected function process( $data )
    {
        try
        {
            $lib = $this->api->load->library( 'autoresponder_handler' );

            $lib->subscribeForOrder( $data );

            return true;
        }

        catch (Exception $e)
        {
            $email = $data->email;
            $msg = $e->getMessage();

            $this->api->logError( 'ipn', _digi( 'Failed to subscribe %s to newsletter: %s' ), $email, $msg );

            return false;
        }


    }

    protected function sqlBaseTableName()
    {
        return 'ipn_out_queue';
    }

    protected function sqlTableMeta()
    {
       $meta = parent::sqlTableMeta();

       $meta['columns'][ 'product_id' ]         = 'id';
       $meta['columns'][ 'first_name' ]         = 'string[63]';
       $meta['columns'][ 'last_name' ]          = 'string[63]';
       $meta['columns'][ 'email' ]              = 'string[63]';
       $meta['columns'][ 'order_id' ]           = 'string[31]';
       $meta['columns'][ 'force_double_optin' ] = 'yes_no_bit';
       $meta['columns'][ 'user_id' ]            = 'int';

        return $meta;
    }

    protected function onGiveUp( $row, $tries_so_far )
    {
        $email = $row->email;

        $message = _digi( 'Gave up subscribing to newsletter for %s after %s tries.', $email, $tries_so_far );

        $this->api->logError( 'ipn', $message );
    }

    protected function onFailure( $row, $tries_so_far, $tries_left )
    {
        $email = $row->email;

        $message =  _digi( 'Could not subscribe to newsletter for %s.', $email );

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


}