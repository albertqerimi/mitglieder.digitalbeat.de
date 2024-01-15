<?php

class digimember_WebpushSubscriptionData extends ncore_BaseData
{
    const error_limit = 10;

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }

    public function lastResultOptions()
    {
        return array(
            'none'    => '-',
            'ok'      => _digi( 'success' ),
            'error'   => _digi( 'error' ),
            'inactiv' => _digi( 'inactive' ),
        );
    }

    public function setResult( $subscription_obj_or_id, $is_success )
    {
        $subscription = $this->resolveToObj( $subscription_obj_or_id );
        if (!$subscription) {
            return;
        }

        if ($is_success)
        {
            $data = array(
                'last_result'   => 'ok',
                'last_sent'     => ncore_dbDate( 'now' ),
                'success_count' => 1+$subscription->success_count,
            );
        }
        else
        {
            $result = $subscription->error_count > self::error_limit
                    ? 'inactiv'
                    : 'error';

            $data = array(
                'last_result'   => $result,
                'last_sent'     => ncore_dbDate( 'now' ),
                'error_count'   => 1+$subscription->error_count,
            );
        }

        $this->update( $subscription, $data );
    }

    public function subscribe( $key, $token, $endpoint )
    {
        $id = $this->subscription_id( $key, $token, $endpoint );

        $where = array( 'subscription_id' => $id );
        $all   = $this->getAll( $where );

        $user_id = ncore_userId();

        if ($all)
        {
            foreach ($all as $index => $one)
            {
                $is_first = $index == 0;
                if ($is_first) {
                    $must_update_user_id = $user_id > 0 && empty( $one->user_id );
                    if ($must_update_user_id) {
                        $data = array( 'user_id' => $user_id );
                        $this->update( $one, $data );
                    }
                }
                else
                {
                    $this->delete( $one );
                }
            }
        }
        else
        {
            $data = array(
                'subscription_id' => $id,
                'key'             => $key,
                'token'           => $token,
                'endpoint'        => $endpoint
            );

            if ($user_id) {
                $data[ 'user_id' ] = $user_id;
            }

            $this->create( $data );
        }

    }

    public function unsubscribe( $key, $token, $endpoint )
    {
        $id = $this->subscription_id( $key, $token, $endpoint );

        $where = array( 'subscription_id' => $id );
        $all   = $this->getAll( $where );
        foreach ($all as $one)
        {
            $this->delete( $one );
        }
    }

    public function getQueueEntry( $key, $token, $endpoint )
    {
        $this->api->load->model( 'queue/webpush' );

        $id = $this->subscription_id( $key, $token, $endpoint );

        $where = array( 'subscription_id' => $id );

        $all   = $this->getAll( $where );

        $sort = array();
        $rows = array();

        foreach ($all as $one)
        {
            $row = $this->api->webpush_queue->getForSubscription( $one );

            $rows[] = $row;
            $sort[] = $row->id;
        }

        array_multisort( $sort, SORT_DESC, $rows );

        return $rows
               ? $rows[0]
               : false;
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'webpush_subscription';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'subscription_id' => 'string[63]',
            'user_id'         => 'id',
            'key'             => 'text',
            'token'           => 'string[255]',
            'endpoint'        => 'text',

            'last_sent'       => 'lock_date',
            'last_result'     => 'string[7]',

            'error_count'     => 'int',
            'success_count'   => 'int',
       );

       $indexes = array( 'subscription_id', 'user_id' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function buildObject( $obj )
    {
        parent::buildObject( $obj );
    }


    protected function hasTrash()
    {
        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values[ 'last_result' ] = 'none';

        return $values;
    }

    protected function hasModified()
    {
        return true;
    }

    //
    // private
    //
    private function subscription_id( $key, $token, $endpoint )
    {
        $forbidden = array( ' ', '+', '=' );
        return substr( str_replace( $forbidden, '', md5( "$key|$token|$endpoint" ).$token ), 0, 63 );
    }


}
