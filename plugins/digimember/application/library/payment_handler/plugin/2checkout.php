<?php

class digimember_PaymentHandler_Plugin2checkout extends digimember_PaymentHandler_PluginBase
{
    public function instructions()
    {
       $instructions = parent::instructions();

        $instructions[] = _digi3( '<strong>Here in DigiMember</strong> click on <em>Save changes</em> below to create a notification URL.');
        $instructions[] = _digi3( 'Copy the notification URL below to the clipboard.' );
        $instructions[] = _digi3( 'Open <em>DigiMember - Products</em> to view the product list. Write down the product ids of the products you want to sell via 2CheckOut.' );
        $instructions[] = _digi3( '<strong>In 2CheckOut</strong> go to <em>Notification - Settings</em>.' );
        $instructions[] = _digi3( 'Paste the notification URL in the field <em>Global URL</em>.' );
        $instructions[] = _digi3( 'Click on <em>Apply</em>, then on <em>Enable All Notification</em>, then on <em>Save Settings</em>.' );
        $instructions[] = _digi3( 'In the menu click on <em>products</em>.' );
        $instructions[] = _digi3( 'Edit your products. For each DigiMember product enter the DigiMember product id in the field product id.' );
        $instructions[] = _digi3( 'That\'s it. You\'re done.' );

       return $instructions;
    }

    public function formMetas()
    {
        return array();
    }

    public function orderIdsAreOfSameOrder( $order_id_a, $order_id_b )
    {
        return $order_id_a == $order_id_b;
    }

    protected function methods()
    {
        return array( METHOD_POST );
    }

    public function getProductIds()
    {
        $model = ncore_api()->load->model( 'data/product' );
        $product_ids = array();

        $args = $this->getRequestArgArray();
        foreach ($args as $key => $value)
        {
            $is_product_id = preg_match( '/^item_id_[0-9]+$/', $key );

            $have_product_id = in_array( $value, $product_ids );

            if ($is_product_id && !$have_product_id)
            {
                $product_id = $value;

                $is_valid = (bool) $model->get( $product_id );

                if ($is_valid)
                {
                    $product_ids[ $product_id ] = 1;
                }
            }
        }

        if (!$product_ids)
        {
            $this->exception( _digi('No valid item ids found. Make sure, you have the same item ids in 2CheckOut and DigiMember.' ) );
        }

        return $product_ids;
    }


    protected function parameterNameMap()
    {
        $map = array(
            // 2checkout     => digimember
            'message_type'   => 'event_type',
            'customer_email' => 'email',
            'sale_id'        => 'order_id',

            'customer_first_name' => 'first_name',
            'customer_last_name'  => 'last_name',

            // 'item_id_1' => 'product_code',  -- getProductIds()
        );

        return $map;
    }

    protected function eventMap()
    {
        return array(
                'ORDER_CREATED'                 => EVENT_SALE,
                'RECURRING_INSTALLMENT_SUCCESS' => EVENT_SALE,
                'RECURRING_COMPLETE'            => EVENT_SALE,
                'RECURRING_RESTARTED'           => EVENT_SALE,

                'REFUND_ISSUED'                 => EVENT_REFUND,

                'RECURRING_INSTALLMENT_FAILED ' => EVENT_MISSED_PAYMENT,
                'RECURRING_STOPPED'             => EVENT_MISSED_PAYMENT,

                'FRAUD_STATUS_CHANGED'          => EVENT_CONNECTION_TEST,
                'SHIP_STATUS_CHANGED'           => EVENT_CONNECTION_TEST,
                'INVOICE_STATUS_CHANGED'        => EVENT_CONNECTION_TEST,

            );
    }


}