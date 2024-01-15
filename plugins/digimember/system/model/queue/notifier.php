<?php

class ncore_NotifierQueue extends ncore_BaseQueue
{
    public function reset( $job_id )
    {
        $data = array();
        $data[ 'next_try_at' ]   = ncore_dbDate( time() + 600 );
        $data[ 'locked_at' ]     = null;

        $data[ 'msg_level' ] = 0;
        $data[ 'processed_at' ] = null;
        $data[ 'first_msg_at' ] = null;

        $this->update( $job_id, $data );

        return true;
    }

    public function sendNow( $job_id, $msg_level = false )
    {
        $data = array();
        $data[ 'next_try_at' ]   = ncore_dbDate();
        $data[ 'locked_at' ]     = null;

        $this->update( $job_id, $data );

        $result = $this->execJob( $job_id );

        return $result;
    }

    public function addJob( $user_id, $msg_key, $context, $params )
    {
        $data = array(
            'user_id'           => $user_id,
            'plugin'            => $this->api->pluginBaseName(),
            'context'           => $context,
            'msg_key'           => $msg_key,
            'msg_level'         => 0,
            'params_serialized' => serialize( $params ),
        );

        $where = array(
            'user_id'           => $user_id,
            'plugin'            => $this->api->pluginBaseName(),
            'msg_key'           => $msg_key,
            'context'           => $context,
        );
        $entries = $this->getAll( $where, $limit=false, 'IF(processed_at IS NULL, 0, 1) ASC, id DESC'  );
        if ($entries) {
            $id = false;
            foreach ($entries as $one)
            {
                if (!$id) {
                    $this->update( $one, $data );
                    $id = $one->id;
                }
                else
                {
                    $this->delete( $one->id );
                }
            }
        }
        else
        {
            $id = $this->create( $data );
        }

        if (NCORE_DEBUG) {
            $this->execJob( $id );
        }
    }

    public function clearJob( $user_id, $msg_key )
    {
        $where = array(
            'user_id'           => $user_id,
            'plugin'            => $this->api->pluginBaseName(),
            'msg_key'           => $msg_key,
        );

        $this->deleteWhere( $where );
    }


//    public function currentJob( $user_id, $msg_key )
//    {
//        $where = array(
//            'user_id'           => $user_id,
//            'plugin'            => $this->api->pluginBaseName(),
//            'msg_key'           => $msg_key,
//        );
//
//        $entry = $this->getWhere( $where,'IF(processed_at IS NULL, 0, 1) ASC, id DESC' );
//
//        return $entry;
//    }
//


    //
    // protected section
    //

    protected function hasTrash()
    {
        return true;
    }

    protected function keepTimeDays()
    {
        return false;
    }

    protected function process( $obj )
    {
        try
        {
            $plugin        = $obj->plugin;
            $user_id       = $obj->user_id;
            $msg_key       = $obj->msg_key;
            $context       = $obj->context;
            $msg_level     = $obj->msg_level;
            $first_msg_at  = $obj->first_msg_at;

            $is_just_queued = empty( $first_msg_at );

            $params = @unserialize( $obj->params_serialized );

            if (!$first_msg_at) {
                $first_msg_at = ncore_dbDate();
            }

            $api = ncore_api( $plugin );
            $model = $api->load->model( 'logic/notifier' );
            $msg = $model->mailTemplate( $msg_key, $context, $msg_level );

            $have_message = (bool) $msg;
            if (!$have_message) {
                return true;
            }

            $must_postpone = $msg_level == 0 && $msg->day >= 1 && $is_just_queued;
            if ($must_postpone)
            {
                $next_msg_level = $msg_level;
                $next_day       = $msg->day;
                $is_sent        = true;
                $next_message   = $msg;
            }
            else
            {
                $emails  = $this->_resolveUserId( $user_id );
                $is_sent = (bool) $emails;
                foreach ($emails  as $user_id => $email)
                {
                    if (!$this->_exec( $email, $msg, $params )) {
                        $is_sent = false;
                    }
                }

                $next_message = $model->mailTemplate( $msg_key, $context, $msg_level+1 );

                $next_msg_level = $msg_level + 1;
                $next_day       = ncore_retrieve( $next_message, 'day', 0 );
            }

            $data = array();
            $data['msg_level']    = $next_msg_level;
            $data['first_msg_at'] = $first_msg_at;
            $data['is_sent']      = ncore_toYesNoBit( $is_sent );

            if (!$next_message) {

                $this->update( $obj, $data );
                return true;
            }

            $first_msg_at_unix = ncore_unixDate( $first_msg_at );
            $next_msg_at_unix  = max( time() + 70000, $first_msg_at_unix + $next_day * 86400 );
            $data['next_try_at']  = ncore_dbDate( $next_msg_at_unix );

            $this->update( $obj, $data );

            return 'ignore';
        }

        catch (Exception $e)
        {
            $this->api->logError( 'mail', _ncore( 'Failed to notify user %s with message key %s: %s' ), $user_id, $msg_key, $e->getMessage() );

            return false;
        }


    }

    protected function sqlBaseTableName()
    {
        return 'notifier_queue';
    }

    protected function isUniqueInBlog() {

        return true;
    }

    protected function sqlTableMeta()
    {
       $meta = parent::sqlTableMeta();

       $meta['columns'][ 'started_at' ]        = 'lock_date';
       $meta['columns'][ 'user_id' ]           = 'string[10]'; // 'admin' or user id
       $meta['columns'][ 'plugin' ]            = 'string[31]';
       $meta['columns'][ 'context' ]           = 'string[31]';
       $meta['columns'][ 'msg_key' ]           = 'string[31]';
       $meta['columns'][ 'msg_level' ]         = 'int';
       $meta['columns'][ 'first_msg_at' ]      = 'lock_date';
       $meta['columns'][ 'params_serialized' ] = 'text';

       $meta['columns'][ 'is_sent' ] = 'yes_no_bit';

       $meta['indexes'][] = 'user_id';

       return $meta;
    }

    protected function onGiveUp( $row, $tries_so_far )
    {
        $user_id = $row->user_id;
        $msg_key = $row->msg_key;

        $message = _ncore( 'Gave up notifying user #%s with msg key %s after %s tries.', $user_id, $msg_key, $tries_so_far );

        $this->api->logError( 'mail', $message );
    }

    protected function onFailure( $row, $tries_so_far, $tries_left )
    {
        $user_id = $row->user_id;
        $msg_key = $row->msg_key;


        $message =  _ncore( 'Could not notify user %s with message key %s.', $user_id, $msg_key );

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


        $this->api->log( 'mail', $message );
    }

    //
    // private section
    //
    private function _exec( $email, $msg, $params )
    {
        $find = array();
        $repl = array();

        foreach ($params as $key => $value)
        {
            $find[] = '{' . $key . '}';
            $repl[] = $value;
        }

        $subject = str_replace( $find, $repl, $msg->subject );
        $body    = str_replace( $find, $repl, $msg->body );

        $mailer = $this->api->load->library( 'mailer' );

        $mailer->to( $email );
        $mailer->subject( $subject );
        $mailer->html( $body );

        try
        {
            $success = $mailer->send();
        }
        catch (Exception $e)
        {
            $success = false;
        }

        return $success;
    }

    private function _resolveUserId( $user_id )
    {
        if (is_numeric($user_id)) {

            $user = ncore_getUserById( $user_id );
            $email = $user->user_email;

            return array( $user_id => $email );
        }

        if ($user_id!=='admin')
        {
            return array();
        }


        $args = array( 'role' => 'administrator' );
        $users = get_users( $args );
        if (!$users) {
            return array();
        }

        $result = array();
        foreach ($users as $one)
        {
            $result[ $one->ID ] = $one->user_email;
        }

        return $result;
    }


}