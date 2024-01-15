<?php

/*
 * Administration -> Addition Options
 *
 * Headline: Order Information
 *
 * Notification Frequence: for every order
 * Notification type: XML format only
 * Format: XML schema (v 2.3)
 *
 *
 * Write to authors@shareit.com and ask them to set the "XML Notifcation URL" to the ipn url.
 *
 * Don't forget to tell them your shre it costumer number.
 *
 *
 *
 * https://payment.neise-gmbh.de/shareit-IjwEny093Efhs/
 */

class digimember_PaymentHandler_PluginShareit extends digimember_PaymentHandler_PluginBase
{
    public function initRequest()
    {
        parent::initRequest();

        if (NCORE_DEBUG && isset( $_POST['xml'] ))
        {
            $xml = trim( str_replace( "\\\"", "\"", $_POST['xml'] ) );
        }
        else
        {
            $xml = trim( file_get_contents( 'php://input' ) );
        }

        // if (NCORE_DEBUG) $xml = $this->testDataPurchase();

        $this->post_data = $this->parseXml( $xml );
    }

    public function instructions()
    {
       $instructions = parent::instructions();


//        $instructions[] = ncore_linkReplace(
//                            _digi3( '<strong>IMPORTANT</strong>: Unfortunately the possibilities ShareIt offers are very restricted. You only may attach one single wordpress installation to ShareIt. ShareIt does not handle subscriptions very well. We strongly encourage you to use <a>Digistore24</a>. It offers more flexibility, is very easy to setup and handles all payments including subscriptions very well. <a>Sign up now</a>.' ),
//                            'https://www.digistore24.com',
//                            'https://www.digistore24.com',
//                            $as_popup = true
//                          )
//                          . '<p>&nbsp;</p>';

        $instructions[] = _digi3( '<strong>In ShareIt</strong> create a product for each product you want to sell with DigiMember. Write down the ShareIt product number for each product.' );
        $instructions[] = _digi3( 'Also visit <em>Administration -Additional Options</em>. Locate the headline <em>Order Information</em>.' );
        $instructions[] = _digi3( 'There set <em>Notification frequency</em> to <em>for every order</em>.' );
        $instructions[] = _digi3( 'Set <em>Notification type</em> to <em>XML format only</em>.' );
        $instructions[] = _digi3( 'Set <em>Format</em> to <em>XML schema (%s)</em>.', 'v. 2.3' );
        $instructions[] = _digi3( '<strong>In DigiMember</strong> enter the ShareIt product number in the field <em>Product ids</em>. Do this for each product you want to sell with ShareIt. Leave the other input fiels empty.' );
        $instructions[] = _digi3( 'Enter your ShareIt Customer id in the appropriate input field above.' );
        $instructions[] = _digi3( 'Click on <em>Send email to ShareIt</em> to automatically send an email to the Shareit customer support. This is required to set the notification URL in your ShareIt account.' );
        $instructions[] = _digi3( 'Please give the ShareIt customer support three workdays to respond to your request. Then you\'re done.' );

       return $instructions;
    }


    public function orderIdsAreOfSameOrder( $order_id_a, $order_id_b )
    {
        $rebill_policy = $this->data( 'shareit_rebill_mode', 'allow_double_purchases' );

        $have_rebilling_payments = $rebill_policy == 'treat_double_as_rebilling';
        if ($have_rebilling_payments)
        {
            return true;
        }

        return parent::orderIdsAreOfSameOrder( $order_id_a, $order_id_b );
    }

    protected function getRequestArg( $postname, $methods='default', $array_allowed=false )
    {
        return ncore_retrieve( $this->post_data, $postname, false );
    }

    public function formMetas()
    {
        $rebill_modes = array(
            'allow_double_purchases'    => _digi3( 'Allow multiple purchases of same product' ),
            'treat_double_as_rebilling' => _digi3( 'Multiple payments for the same product are treated as subscription payments' ),
        );

        return array(

          array(
                'name' => 'product_code_map',
                'type' => 'map',
                'label' => _digi3('ShareIt product ids' ),
                'array' => $this->productOptions(),
                'hint' => _digi('Seperate multiple product ids by commas.'),
          ),
          array(
                'name' => 'shareit_customer_id',
                'type' => 'int',
                'label' => _digi('ShareIt customer id' ),
                'depends_on' => array( 'engine' => 'shareit' ),
                'tooltip' => _digi('Your ShareIt customer id.'),
            ),

          array(
                'name' => 'shareit_rebill_mode',
                'type' => 'select',
                'label' => _digi3('Duplicate payments policy' ),
                'depends_on' => array( 'engine' => 'shareit' ),
                'tooltip' => _digi3('ShareIt does not handle subscriptions very well.|You need to decide, if you want to either allow a buyer to buy the same product twice or more often, or if you want to have subscriptions.'),
                'options' => $rebill_modes,
            ),
        );
    }

    protected function methods()
    {
        return array(
            METHOD_POST
        );
    }

    private function parseXml($xml)
    {
        $xml = trim( $xml );

        if (!$xml)
        {
            return array( 'event_type' => EVENT_CONNECTION_TEST );
        }



        $error_level = error_reporting( 0 );
        $parsed = (array) simplexml_load_string($xml);
        error_reporting( $error_level );

        if (!$parsed || !is_array( $parsed) )
        {
            return array();
        }

        $notification_type = key( $parsed );
        $xmlObj = $parsed[ $notification_type ];
        if (!$xmlObj)
        {
            return array();
        }

        $purchase_id = (int) $xmlObj->Purchase->PurchaseId;

        // $newsletter = $this->parseBool($xmlObj->Purchase->CustomerData->SubscribeNewsletter);

        $contact = $xmlObj->Purchase->CustomerData->BillingContact;

        // $salutation = $this->parseSalutation($contact->Salutation);
        // $company    = (string) $contact->Company;
        $firstname  = (string) $contact->FirstName;
        $lastname   = (string) $contact->LastName;
        $email      = (string) $contact->Email;

//        $street1 = (string) $contact->Address->Street1;
//        $street2 = (string) $contact->Address->Street2;
//        $city    = (string) $contact->Address->City;
//        $state   = (string) $contact->Address->State;
//        $stateId = (string) $contact->Address->StateId;

//        $postcode  = (string) $contact->Address->PostalCode;
//        $countryId = (string) $contact->Address->CountryId;
//        $country   = (string) $contact->Address->Country;

        $productItemRecs = array();

        $itemObj   = $xmlObj->Purchase->PurchaseItem;

        if (is_a($itemObj, 'SimpleXMLElement'))
        {
            $itemCount = method_exists( $itemObj, 'count')
                       ? $itemObj->count()                 // php 5.3 or higher
                       : count( $itemObj->children() );    // php 5.2
        }
        else
        {
            $itemCount = 0;
        }

        $product_codes = array();

        for ($i = 0; $i < $itemCount; $i++)
        {

            $obj = $xmlObj->Purchase->PurchaseItem[ $i ];

            $product_codes[] = (string) $obj->ProductId;

            //     'productName' => (string) $obj->ProductName,
            //     'quantity' => (int) $obj->Quantity,
        }

        $paymentStatus = (string) $xmlObj->Purchase->PaymentStatus;

        $paid_statusse = array( 'complete', 'test payment arrived', 'incomplete' );
        $reversed_statusse = array( 'refunded', 'charged back', 'partly refunded', 'no encashment', 'cancellation', 'partial charged back' );

        $event_types = $this->eventMap();
        $event = ncore_retrieve( $event_types, $notification_type, EVENT_CONNECTION_TEST );

        $is_paid = in_array( $paymentStatus, $paid_statusse );
        $is_reversed = in_array( $paymentStatus, $reversed_statusse );

        if ($event == EVENT_SALE)
        {
            if ($is_reversed)
            {
                $event = EVENT_REFUND;
            }
            elseif (!$is_paid)
            {
                $event = EVENT_CONNECTION_TEST;
            }
        }

        return array(
            'event_type' => $event,
            'email' => $email,
            'order_id' => $purchase_id,
            'product_code' => $product_codes,

            'first_name' => $firstname,
            'last_name' => $lastname,
        );
    }

    protected function eventMap()
    {
        return array(
                // 'connection_test' => EVENT_CONNECTION_TEST,
                'OrderNotification' => EVENT_SALE,
                'ChargebackReversal' => EVENT_SALE,
                'InvoicePaid' => EVENT_SALE,

                'RefundDone' => EVENT_REFUND,
                'FraudRefundDone' => EVENT_REFUND,
                'Chargeback' => EVENT_REFUND,
                'NoEncashment ' => EVENT_REFUND,
                'Cancellation' => EVENT_REFUND,

                'RebillingCancelled' => EVENT_MISSED_PAYMENT,
                'RebillingDeactivated' => EVENT_MISSED_PAYMENT,
            );
    }

     private function testDataPurchase()
     {
            $purchase_id = time();

            return '<?xml version="1.0" encoding="utf-8"?>
<e5Notification xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://xml.element5.com/2.3/ordernotification.xsd">
        <OrderNotification>
                <Purchase>
                        <PurchaseId>'.$purchase_id.'</PurchaseId>
                        <PurchaseDate>2012-04-27T19:00:57</PurchaseDate>
                        <PurchaseOrigin>online</PurchaseOrigin>
                        <PaymentCompleteDate>2012-04-28T00:43:37</PaymentCompleteDate>
                        <PaymentStatus>complete</PaymentStatus>
                        <SequentialInvoiceNo>e5-DE-2012-0000015781</SequentialInvoiceNo>
                        <CustomerData>
                                <BillingContact>
                                        <Salutation>MRS</Salutation>
                                        <LastName>Meier</LastName>
                                        <FirstName>Claudia</FirstName>
                                        <Email>claudia.meier@aaggxx.de</Email>
                                        <Fax>A</Fax>
                                        <Address>
                                                <Street1>Hauptstraße 12</Street1>
                                                <City>Dortstadt</City>
                                                <PostalCode>447332</PostalCode>
                                                <CountryId>DE</CountryId>
                                                <Country>Germany</Country>
                                        </Address>
                                </BillingContact>
                                <DeliveryContact>
                                        <Salutation>MRS</Salutation>
                                        <LastName>Meier</LastName>
                                        <FirstName>Claudia</FirstName>
                                        <Email>claudia.meier@aaggxx.de</Email>
                                        <Fax>A</Fax>
                                        <Address>
                                                <Street1>Hauptstraße 12</Street1>
                                                <City>Dortstadt</City>
                                                <PostalCode>447332</PostalCode>
                                                <CountryId>DE</CountryId>
                                                <Country>Germany</Country>
                                        </Address>
                                </DeliveryContact>
                                <CustomerPaymentData>
                                        <PaymentMethod>Visa</PaymentMethod>
                                        <Currency>EUR</Currency>
                                </CustomerPaymentData>
                                <Language>German</Language>
                                <RegName>Claudia Meier</RegName>
                                <SubscribeNewsletter>false</SubscribeNewsletter>
                        </CustomerData>
                        <PurchaseItem>
                                <RunningNo>1</RunningNo>
                                <ProductId>203498112</ProductId>
                                <ProductName>Test product DVD</ProductName>
                                <NotificationNo>8</NotificationNo>
                                <DeliveryType>Postal Mail</DeliveryType>
                                <Currency>EUR</Currency>
                                <Quantity>1</Quantity>
                                <ProductSinglePrice>0.00</ProductSinglePrice>
                                <VatPct>19.00</VatPct>
                                <Discount>0.00</Discount>
                                <ExtendedDownloadPrice>0.00</ExtendedDownloadPrice>
                                <ManuelOrderPrice>0.00</ManuelOrderPrice>
                                <ShippingPrice>10.08</ShippingPrice>
                                <ShippingVatPct>19.00</ShippingVatPct>
                                <YourProductId>TestDVD</YourProductId>
                        </PurchaseItem>
                </Purchase>
        </OrderNotification>
</e5Notification>';
    }
}