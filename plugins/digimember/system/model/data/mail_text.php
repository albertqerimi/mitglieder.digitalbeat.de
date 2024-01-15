<?php

class ncore_MailTextData extends ncore_BaseData
{
    public function getForHook( $hook, $ref_id )
    {
        $ref_id = ncore_washInt( $ref_id );

        $where = array(
            'hook' => $hook,
            'ref_id' => $ref_id,
        );

        $mail_text = $this->getWhere( $where );

        if ($mail_text)
        {
            return $mail_text;
        }

        /** @var digimember_MailHookLogic $model */
        $model = $this->api->load->model( 'logic/mail_hook' );
        list( $subject, $message ) = $model->defaultMailText( $hook );

        $data = $where;
        $data['subject'] = $subject;
        $data['body_html'] = $message;
        $id = $this->create( $data );

        $obj = $this->get( $id );

        return $obj;
    }

    public function getForProductId( $ref_id )
    {
        $ref_id = ncore_washInt( $ref_id );
        $where = array(
            'ref_id' => $ref_id,
        );
        $mail_text_data = $this->getWhere( $where );
        if ($mail_text_data)
        {
            return $mail_text_data;
        }
        return false;
    }

    public function copyForProduct($fromProduct, $toProduct) {
        $mailText = $this->getForProductId($fromProduct);
        if ($mailText) {
            $copy['subject'] = $mailText->subject;
            $copy['body_html'] = $mailText->body_html;
            $copy['hook'] = $mailText->hook;
            $copy['send_policy'] = $mailText->send_policy;
            $copy['attachment'] = $mailText->attachment;
            $copy['ref_id'] = $toProduct;
            $copy['table'] = $mailText->table;
            $copy['status'] = $mailText->status;
            $this->create($copy);
        }
    }

    public function setForHook( $hook, $ref_id, $subject, $message )
    {
        $obj = $this->getForHook( $hook, $ref_id );

        $id = $obj->id;

        $data = array(
            'subject'   => $subject,
            'body_html' => $message,
        );

        return $this->update( $id, $data );
    }

    //
    // protected section
    //
    protected function isUniqueInBlog() {

        return true;
    }

    protected function sqlBaseTableName()
    {
        return 'mail_text';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'subject' => 'string[255]',
        'body_html' => 'longtext',
        'hook' => 'string[31]',
        'send_policy' => 'string[31]',
        'attachment' => 'string[255]',
        'ref_id' => 'id',
       );

       $indexes = array( 'ref_id', 'hook' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    public function addAttachmentIfNeeded() {
        $row = false;
        global $wpdb;
        $table_name = $this->sqlTableName();
        $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$table_name."' AND column_name = 'attachment'"  );
        if ( is_array($row) && count($row) < 1 ) {
            $initCore = $this->api->init();
            $initCore->forceUpgrade();
            return true;
        }
        return false;
    }

}