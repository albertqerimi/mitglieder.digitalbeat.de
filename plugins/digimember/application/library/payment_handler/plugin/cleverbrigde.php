<?php

// http://www-q.affilibank.de/itns/

class digimember_PaymentHandler_PluginCleverbrigde extends digimember_PaymentHandler_PluginBase
{

    protected function methods()
    {
        return array( METHOD_INPUT, METHOD_POST, METHOD_GET );
    }

    public function formMetas()
    {
        return array(
          array(
                'name' => 'product_code_map',
                'type' => 'map',
                'label' => _digi3('%s product ids', 'Cleverbridge' ),
                'array' => $this->productOptions(),
                'hint' => _digi('Seperate multiple product ids by commas.'),
          ),
        );
    }

    protected function parameterNameMap()
    {
        return array(
            // cleverbridge => digimember
            'StatusId'      => 'event_type',
            '1_ProductId'   => 'product_code',

            '1_RecurringBillingOriginalPurchaseId' => 'order_id',  // must be before (!) PurchaseId
            'PurchaseId'                           => 'order_id', 

            'BillingEmail'     => 'email',
            'BillingFirstname' => 'first_name',
            'BillingLastname'  => 'last_name',
            
//            'CreationTime' => 'PaymentArriveTime',
        );
    }

    protected function eventMap()
    {
        return array(
                'WOF' => EVENT_CONNECTION_TEST, // New offline payment order
                'PAY' => EVENT_SALE,            // Paid order
                'TST' => EVENT_SALE,            // Test order
                'WPO' => EVENT_CONNECTION_TEST, // New purchase order
                'REG' => EVENT_CONNECTION_TEST, // Registration
                'REF' => EVENT_REFUND,          // Refunded (full) / Partially refunded
                'CHB' => EVENT_REFUND,          // Chargeback / Return direct debit
                'DEC' => EVENT_CONNECTION_TEST, // Online payment declined
                'QUO' => EVENT_CONNECTION_TEST, // New quote
                'AWR' => EVENT_CONNECTION_TEST, // Awaiting release
                'CDC' => EVENT_CONNECTION_TEST, // Customer contact data changed
                'RCH' => EVENT_REFUND,          // Recurring billing on hold
                'RCG' => EVENT_CONNECTION_TEST, // Recurring billing on grace
                'RCA' => EVENT_SALE,            // Subscription reinstated
                'RCC' => EVENT_REFUND,          // Recurring billing canceled
                'RCR' => EVENT_CONNECTION_TEST, // Subscription reminder charge
                'RPE' => EVENT_CONNECTION_TEST, // Subscription reminder payment option expired
                'ROP' => EVENT_CONNECTION_TEST, // Subscription reminder offline payment
                'NAF' => EVENT_CONNECTION_TEST, // New affiliate signup
                'NPA' => EVENT_CONNECTION_TEST, // New partner signup
                'ERR' => EVENT_CONNECTION_TEST, // Error (for example, key generation error)        
            );
    }

    public function instructions()
    {
       $instructions = parent::instructions();

       $instructions[] = _digi3('Locate the notification URL below and copy it to the clipboard.');
       $instructions[] = _digi3('Open the Cleverbrigde\'s %s.', '<strong>Commerce Assistant</strong>');
       $instructions[] = _digi3('In the left column, the box %s click on %s.', '<em>Channel Management</em>', '<em>Product lists</em>');
       $instructions[] = _digi3('Create a new product list (type: %s) and add all products %s will handle.', 'Include List', $this->api->pluginDisplayName());
       $instructions[] = _digi3('In the main menu, select %s. Then open the tab %s.', '<em>Administration - Edit Account</em>', '<em>Notification</em>');
       $instructions[] = _digi3('Click on %s and enter these settings:', 'Add Notification');
       $instructions[] = 'Transport Layer: HTTP Post';
       $instructions[] = 'Document type: Key value';
       $instructions[] = 'Product List: <em>' . _digi3( 'Select the list you created in step %s', 4 ) . '</em>';
       $instructions[] = 'HTTP URL: <em>' . _digi3( 'The notification URL from step %s', 1 ) . '</em>';
       
       
       $tab = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; ';
       $instructions[] = 'Notify for: <em>' . _digi3( 'Select the following entries') . "</em><br />
${tab}Paid<br />
${tab}Refunded<br />
${tab}Partially Refunded<br />
${tab}Chargeback<br />
${tab}Returned direct debug<br />
${tab}Test order<br />
${tab}Subscription on hold<br />
${tab}Subscription instated<br />
${tab}Subscription deactivated";
       $instructions[] = _digi3('Save your changes.');
       $instructions[] = _digi3('<strong>Here in DigiMember</strong> enter the %s product id for the appropriate products.', 'Cleverbridge' );

       return $instructions;
    }



}