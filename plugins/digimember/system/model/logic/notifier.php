<?php

class ncore_NotifierLogic extends ncore_BaseLogic
{
    public function send( $msg_key, $context, $params, $user_id='admin'  )
    {
        if ($user_id === 'current') {
            $user_id = ncore_userId();
        }

        $model = $this->api->load->model( 'queue/notifier' );
        $model->addJob( $user_id, $msg_key, $context, $params );
    }

    public function clear( $msg_key, $context, $user_id='admin' )
    {
        if ($user_id === 'current') {
            $user_id = ncore_userId();
        }

        $model = $this->api->load->model( 'queue/notifier' );
        $model->clearJob( $user_id, $msg_key );
    }

    public function mailTemplate( $msg_key, $context, $msg_number )
    {
        $list =& $this->mail_templates[$msg_key.'/'.$context];

        if (!isset($list))
        {
            $list = $this->mailTemplates( $msg_key, $context );
        }

        $msg = ncore_retrieve( $list, $msg_number, false );
        if (!$msg) {
            return false;
        }

        return $msg;
    }

    protected function mailTemplates( $msg_key, $context )
    {
        return array();
    }

    private $mail_templates = array();
}
