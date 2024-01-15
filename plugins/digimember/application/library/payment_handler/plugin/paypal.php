<?php

class digimember_PaymentHandler_PluginPaypal extends digimember_PaymentHandler_PluginBase
{
    public function ipnType()
    {
        return DIGIMEMBER_IPN_PER_PRODUCT;
    }

    public function formMetas()
    {
        return array(
        );
    }

    public function instructions()
    {
        $instructions = parent::instructions();

        $url = ncore_siteUrl();
        $example_url = rtrim($url,'/') . '/...';

        $instructions[] = _digi3('Click on <em>Save changes</em> below to create a notification URL.');
        $instructions[] = _digi3('Log into PayPal and goto Tools &rarr; PayPal buttons.');
        $instructions[] = _digi3('For each product create a PayPal payment button.');
        $instructions[] = _digi3('Under <em>Step 3: Customize advanced features (optional)</em> add an <em>advanced variable</em> named <strong>notify_url</strong>.');
        $instructions[] = _digi3('Set <strong>notify_url</strong> to the product\'s url (shown here below the product). E.g. notify_url=%s', $example_url );
        $instructions[] = _digi3('Add the PayPal button to your sales page and you are done.' );
        $instructions[] = _digi3('Note: If your site URL starts with http<strong>s</strong>://... (instead of http://...) AND you did not buy a SSL certificate, PayPal may report an error. Then try using http://... in your notification URL.' );

        return $instructions;
    }

    public function getEventType()
    {
        if (!$this->event_type)
        {
            $this->event_type = $this->_getEventType();
        }

        return $this->event_type;
    }

    protected function methods()
    {
        return array( METHOD_POST, METHOD_GET );
    }

    protected function parameterNameMap()
    {
        return array(
            'event' => 'event_type',

            'EMAIL' => 'email',
            'payer_email' => 'email',

            'FIRSTNAME' => 'first_name',
            'first_name' => 'first_name',

            'LASTNAME' => 'last_name',
            'last_name' => 'last_name',

            'product_code' => 'product_code',

            'orderID' => 'order_id',
            'custom' => 'order_id',
            'txn_id' => 'order_id',
        );
    }



    protected function eventMap()
    {
        return array(
                'connection_test'   => EVENT_CONNECTION_TEST,
                'on_payment'        => EVENT_SALE,
                'on_refund'         => EVENT_REFUND,
                'on_chargeback'     => EVENT_REFUND,
                'on_missed_payment' => EVENT_MISSED_PAYMENT,
            );
    }

    private $event_type=false;

    private function _getEventType()
    {
        $have_post = count( $_POST );

        $request = $have_post
                   ? $_POST
                   : $_GET;

        $paypal_transaction_type = ncore_retrieve($request, 'txn_type');
        $paypal_case_type        = ncore_retrieve($request, 'case_type');
        $paypal_payment_status   = ncore_retrieve($request, 'payment_status');
        $amount = ncore_retrieve( $request, 'mc_gross' );

        $is_complaint = $paypal_case_type == 'complaint'
                     || $paypal_case_type == 'dispute';

        $do_ignore = $is_complaint;

        if ($do_ignore)
        {
            return EVENT_CONNECTION_TEST;
        }

        $is_chargeback = !$paypal_transaction_type && $paypal_case_type == 'chargeback';
        if ($is_chargeback)
        {
            return EVENT_REFUND;
        }

        $is_chargeback = $paypal_payment_status === 'Reversed' && $amount < 0;
        if ($is_chargeback)
        {
            return EVENT_REFUND;
        }


        $is_refund = $paypal_payment_status === 'Refunded';
        if ($is_refund)
        {
            return EVENT_REFUND;
        }

        $map = array(
            'cart' => EVENT_SALE,
            'express_checkout' => EVENT_SALE,
            'recurring_payment' => EVENT_SALE,
            'subscr_payment' => EVENT_SALE,
            'web_accept' => EVENT_SALE
        );

        $our_tt = ncore_retrieve($map, $paypal_transaction_type);

        if ($our_tt == EVENT_SALE)
        {
            switch ($paypal_payment_status)
            {
                case 'Completed':
                    return EVENT_SALE;

                case 'Pending':
                    return EVENT_CONNECTION_TEST;

                case 'Refunded':
                    return EVENT_REFUND;

                case 'Denied':
                default:
                    return EVENT_CONNECTION_TEST;
            }

        }

        return EVENT_CONNECTION_TEST;

    }


}