<?php

class digimember_WebpushMessageData extends ncore_BaseData
{
    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }

    public function options( $where=array())
    {
        return $this->asArray( 'name', 'id', $where );
    }

    public function render( $message_obj_or_id, $queue_entry_obj_or_id )
    {
        $this->api->load->model( 'queue/webpush' );
        $entry = $this->api->webpush_queue->resolveToObj( $queue_entry_obj_or_id );

        $message = $this->resolveToObj( $message_obj_or_id );

        $tag   = parse_url( site_url(),  PHP_URL_HOST );
        $title = $message->title;
        $body  = $message->message;

        $icon  = $url = wp_get_attachment_url( $message->icon_image_id );
        $badge = $url = wp_get_attachment_url( $message->badge_image_id );
        $image = $url = wp_get_attachment_url( $message->msg_image_id );




        $is_local_url = !$message->target_url || is_numeric($message->target_url);

        $target_url   = ncore_resolveUrl( $message->target_url );
        if (!$target_url) {
            $target_url = site_url();
        }

        $ph   = $this->placeholders( $entry->subscription_id );

        $track_key = $this->api->webpush_queue->trackingKey( $entry );

        $track_url = $this->api->link_logic->ajaxUrl( 'ajax/webpush', 'track_click', array( 'url' => $target_url, 'track' => $track_key) );

        // see https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
        $data = array(
            'tag'   => $tag,
            'title' => str_replace( array_keys( $ph ), array_values( $ph ), $title ),
            'icon'  => $icon,
            'body'  => str_replace( array_keys( $ph ), array_values( $ph ), $body  ),
            'url'   => $track_url,

            'badge' => $badge,
            'image' => $image,

            // Chrome only
            'requireInteraction' => 1,

        );

        return $data;
    }

    public function incCounter( $message_obj_or_id, $counter, $delta=1 )
    {
        $id      = ncore_washInt( $this->resolveToId( $message_obj_or_id ) );
        $counter = ncore_washText( $counter );
        $delta   = ncore_washInt ( $delta );

        $table_name = $this->sqlTableName();

        $sql = "UPDATE $table_name
                SET $counter = $counter + $delta
                WHERE id = $id";

        $this->db()->query( $sql );
    }

    public function resetStats( $message_obj_or_id )
    {
        $data = array(
            'count_sent'    => 0,
            'count_shown'   => 0,
            'count_clicked' => 0,

            'count_started' => ncore_dbDate(),
        );
        $this->update( $message_obj_or_id, $data );
    }

    public function placeholders( $webpush_subscription_obj_or_id = false )
    {
        $user    = false;
        $user_id = false;

        $first_name = '';
        $last_name  = '';


        if ($webpush_subscription_obj_or_id) {
            $this->api->load->model( 'data/webpush_subscription' );
            $obj = $this->api->webpush_subscription_data->resolveToObj( $webpush_subscription_obj_or_id );
            $user_id = ncore_retrieve( $obj, 'user_id' );
            $user    = ncore_getUserById( $user_id );
        }

        if ($user)
        {
            $first_name = get_user_meta( $user_id, "first_name", true );
            $last_name  = get_user_meta( $user_id, "last_name",  true );

            if (!$first_name) {
                $first_name = ncore_retrieve( $user, "display_name" );
                $last_name  = '';
            }
        }

        return array(
            '[FIRST_NAME]' => $first_name,
            '[LAST_NAME]'  => $last_name,
        );
    }



    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'webpush_message';
    }


    protected function sqlTableMeta()
    {
       $columns = array(
            'name'                   => 'string[127]',
            // 'is_active'              => 'yes_no_bit',

            'title'                  => 'string[127]',
            'icon_image_id'          => 'id',
            'msg_image_id'           => 'id',
            'badge_image_id'         => 'id',
            'message'                => 'text',
            'target_url'             => 'string[255]',

            'send_to_what'           => 'string[15]',
            'send_to_user_id'        => 'id',
            'send_to_product_ids'    => 'text',


            'count_sent'    => 'int',
            'count_shown'   => 'int',
            'count_clicked' => 'int',
            'count_started' => 'datetime',
       );

       $indexes = array( /*'order_id', 'product_id', 'email'*/ );

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

    protected function sqlExtraColumns()
    {
        return array(
            'quota_shown'   => 'IF( count_sent  > 0, 100 * count_shown   / count_sent, 0 )',
            'quota_clicked' => 'IF( count_shown > 0, 100 * count_clicked / count_shown, 0 )',
            'quota_total'   => 'IF( count_sent  > 0, 100 * count_clicked   / count_sent, 0 )',

        );
    }

    protected function notCopiedColumns()
    {
        $cols = parent::notCopiedColumns();

        $cols[] = 'count_sent';
        $cols[] = 'count_shown';
        $cols[] = 'count_clicked';
        $cols[] = 'count_started';

        return $cols;
    }


    protected function hasTrash()
    {
        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        // $values[ 'is_active' ] = 'N';
        $values[ 'send_to_user_id' ] = ncore_userId();

        $values[ 'count_sent' ]    = 0;
        $values[ 'count_shown' ]   = 0;
        $values[ 'count_clicked' ] = 0;
        $values[ 'count_started' ] = ncore_dbDate();

        return $values;
    }

    protected function hasModified()
    {
        return true;
    }

}
