<?php

class digimember_PaymentData extends ncore_BaseData
{
    public function getDS24ProviderId()
    {
        $config = $this->api->load->model( 'logic/blog_config' );

        $id = $config->get( 'ds24_payprov_id' );

        if (!$id) {
            $id = $this->createDS24PayProvider();
            $config->set( 'ds24_payprov_id', $id );

        }

        return $id;
    }

    public function getDs24config( $do_reset=false ) {

        $config = $this->api->load->model( 'logic/blog_config' );

        $id = $config->get( 'ds24_payprov_id' );

        if (!$id) {
            $id = $this->createDS24PayProvider();
            $config->set( 'ds24_payprov_id', $id );
            $do_reset = false;
        }

        if ($do_reset) {
            $pw = $this->generateCallbackPassword();
            $data = array( 'callback_pw' => $pw );
            $this->update( $id, $data );
        }

        $obj = $this->get( $id );
        if (!$obj)
        {
            $id = $this->createDS24PayProvider();
            $config->set( 'ds24_payprov_id', $id );
        }

        return $obj;
    }



    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'payment';
    }

    protected function defaultOrder()
    {
        return 'id ASC';
    }

    protected function hasTrash()
    {
        return true;
    }

    protected function hasModified()
    {
        return true;
    }

    public function status( $row )
    {
        $status = parent::status( $row );

        $is_deleted = $status == 'deleted';

        $is_active = $row->is_active == 'Y';

        if ($is_deleted)
        {
            return 'deleted';
        }

        if ($is_active)
        {
            return 'active';
        }

        return $status;
    }

    public function statusLabels()
    {
        $labels = parent::statusLabels();

        $labels['active'] = _digi( 'Active' );
        $labels['created'] = _digi( 'Not active' );

        return $labels;
    }

    public function setupChecklistDone()
    {
        $where = array( 'is_active' => 'Y' );
        $all = $this->getAll( $where );
        $done = (bool) $all;
        return $done;
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'engine' => 'string[31]',
        'is_active' => 'yes_no_bit',
        'callback_pw' => 'string[31]',
        'product_code_map' => 'text',
        'data_serialized' => 'text',
        'is_visible' => 'yes_no_bit',
       );

       $indexes = array();
       // $indexes = array( 'code' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values['is_active']  = 'Y';
        $values['is_visible'] = 'Y';
        $values['data_serialized'] = '';

        $values['callback_pw'] = $this->generateCallbackPassword();

        $api = $this->api;
        $lib = $api->load->library( 'payment_handler' );
        $values['engine'] = $lib->defaultProvider();

        return $values;
    }

    protected function buildObject( $object )
    {
        parent::buildObject( $object );

        if ($object->data_serialized)
        {
            $data = unserialize( $object->data_serialized );
            $object->data = $data;
        }
        else
        {
            $object->data = array();
        }
    }

    protected function onBeforeSave( &$data )
    {
        $x=7;
    }

    private function generateCallbackPassword()
    {
        $this->api->load->helper( 'string' );
        return ncore_randomString( 'alnum', 30 );
    }

    private function createDS24PayProvider()
    {
        $data = array();
        $data['callback_pw'] = $this->generateCallbackPassword();
        $data['engine' ]     = 'digistore_api';
        $data['is_visible' ] = 'N';

        return $this->create( $data );
    }
}