<?php

// http://www-q.affilibank.de/itns/


/* Beispiel-Datensatz:
$_POST = Array
(
    [transaction] => SALE
    [custfullname] => Klaus Meier
    [custfirstname] => Klaus
    [custlastname] => Meier
    [custzip] => 00000
    [custshippingzip] => 00000
    [custemail] => name@some-mail-domain.com
    [proditem] => 1
    [prodtitle] => Instant Happyniss
    [prodtype] => STANDARD
    [billno] => 1
    [prodid] => 7772
    [transaffiliate] => none
    [subid] =>
    [data1] =>
    [data2] =>
    [orderamount] => 1
    [accountamount] => 1
    [taxamount] => 0
    [shippingamount] => 0
    [transpaymentmethod] => PYPL
    [currency] => EUR
    [transvendor] => meier
    [transrole] => VENDOR
    [transreceipt] => 210536068
    [transtime] => 1418381562
    [noticeversion] => 4.0
    [verify] => 2C123A40
)*/


class digimember_PaymentHandler_PluginAffilibank extends digimember_PaymentHandler_PluginBase
{

    protected function methods()
    {
        return array( METHOD_POST );
    }

    public function formMetas()
    {
        return array(
          array(
                'name' => 'product_code_map',
                'type' => 'map',
                'label' => _digi3('%s product ids', 'Affilicon' ),
                'array' => $this->productOptions(),
                'hint' => _digi('Seperate multiple product ids by commas.'),
          ),
        );
    }

    protected function parameterNameMap()
    {
        return array(
            // affilibank => digimember
            'transaction'    => 'event_type',
            'atransaction'   => 'event_type',
            'proditem'       => 'product_code',
            'prodid'         => 'product_code',
            'aproditem'      => 'product_code',
            'aprodid'        => 'product_code',


            'aboid'          => 'order_id', // must be before (!) transreceipt
            'aaboid'         => 'order_id', // must be before (!) transreceipt
            'transreceipt'   => 'order_id',
            'atransreceipt'  => 'order_id',

            'custemail'      => 'email',
            'acustemail'     => 'email',
            'custfirstname'  => 'first_name',
            'acustfirstname' => 'first_name',
            'custlastname'   => 'last_name',
            'acustlastname'  => 'last_name',
            'aboend'         => 'access_stops_on',
            'aaboend'        => 'access_stops_on',
        );
    }

    protected function eventMap()
    {
        return array(
                'SALE'          => EVENT_SALE,
                'R-SALE'        => EVENT_SALE,
                'BILL'          => EVENT_SALE,
                'CANCEL-REBILL' => EVENT_REFUND,
                'RFND'          => EVENT_REFUND,
                'CGBK'          => EVENT_REFUND,

                'TEST_SALE'          => EVENT_SALE,
                'TEST_R-SALE'        => EVENT_SALE,
                'TEST_BILL'          => EVENT_SALE,
                'TEST_CANCEL-REBILL' => EVENT_REFUND,
                'TEST_RFND'          => EVENT_REFUND,
                'TEST_CGBK'          => EVENT_REFUND,
            );
    }

    public function instructions()
    {
       $instructions = parent::instructions();

       $instructions[] = _digi3('Locate the notification URL below and copy it to the clipboard.');

       $is_ssl = !empty( $_SERVER[ 'HTTPS' ] )
              && $_SERVER[ 'HTTPS' ] != 'off';
       if ($is_ssl) {
            $instructions[] = _digi3('Please note: If DigiMember does not react on Affilicon sales, try to replace https:// by http:// in the notification URL, because Affilicon does not accept self signed SSL certificates.');
       }
       $instructions[] = _digi3('<strong>In Affilicon </strong> select the <em>My products</em> tab.');
       $instructions[] = _digi3('Perform the following steps for each product you want to sell with DigiMember:' );
       $instructions[] = _digi3('Locate a product in the Affilicon product table. If you don\'t have one, create one.' );
       $instructions[] = _digi3('Click on the <em>Edit</em> button of the product.' );
       $instructions[] = _digi3('Select the tab <em>Connections</em>.' );
       $instructions[] = _digi3('As connection type select <em>DigiMember</em>' );
       $instructions[] = _digi3('Paste the notification URL into the field <em>notification URL</em>.');
       $instructions[] = _digi3('<strong>Here in DigiMember</strong> enter the affilibank item number for the appropriate products.');

       return $instructions;
    }



}
