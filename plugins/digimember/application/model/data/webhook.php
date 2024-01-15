<?php

class digimember_WebhookData extends ncore_BaseData
{
    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'webhook';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name'                        => 'string[127]',
            'auth_key'                    => 'string[63]',

            'product_ids_comma_seperated' => 'string[255]',

            'add_product_method'          => 'string[7]',
            'add_password_method'         => 'string[7]',
            'add_order_id_method'         => 'string[7]',
            'access_stops_on_method'      => 'string[7]',

            'param_email'                 => 'string[47]',
            'param_first_name'            => 'string[47]',
            'param_last_name'             => 'string[47]',
            'param_product'               => 'string[47]',
            'param_order_id'              => 'string[47]',
            'param_password'              => 'string[47]',
            'param_access_stops_on'       => 'string[47]',

            'is_active'                   => 'yes_no_bit',
            'order_id'                    => 'string[31]',
            'webhook_type'                => 'string[31]',
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


    protected function hasTrash()
    {
        return true;
    }

    public function typeLabels()
    {
        $labels = array(
            'newOrder' => _digi('Create order'),
            'cancelOrder' => _digi('Deactivate order'),
        );
        return $labels;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values[ 'param_email' ]        = 'email';
        $values[ 'param_first_name' ]   = 'firstname';
        $values[ 'param_last_name' ]    = 'lastname';
        $values[ 'param_product' ]      = 'product_id';
        $values[ 'param_order_id' ]     = 'order_id';
        $values[ 'param_password' ]     = 'password';
        $values[ 'param_access_stops_on' ] = 'access_stops_on';

        $values[ 'is_active' ]          = 'Y';
        $values[ 'order_id' ]           = _digi( 'Webhook' );
        $values[ 'webhook_type' ]       = 'newOrder';

        $values[ 'add_product_method' ]  = 'by_hook';
        $values[ 'add_password_method' ] = 'by_hook';
        $values[ 'add_order_id_method' ] = 'by_hook';
        $values[ 'access_stops_on_method' ] = 'now';

        $this->api->load->helper( 'string' );
        $values[ 'auth_key' ] = ncore_randomString( 'alnum', 60 );

        return $values;
    }

    protected function hasModified()
    {
        return true;
    }


}
