<?php


// http://www.clickbank.com/help/affiliate-help/affiliate-tools/instant-notification-service/

class digimember_PaymentHandler_PluginClickbank extends digimember_PaymentHandler_PluginBase
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
                'label' => _digi3('Clickbank product nos' ),
                'array' => $this->productOptions(),
                'hint' => _digi('Seperate multiple product ids by commas.'),
          ),
        );
    }

    protected function parameterNameMap()
    {
        return array(
            // clickbank => digimember
            'ctransaction'   => 'event_type',
            'ccustemail'     => 'email',
            'cproditem'      => 'product_code',
            'ctransreceipt'  => 'order_id',

            'ccustfirstname' => 'first_name',
            'ccustlastname'  => 'last_name',

            'ccustaddr1'     => 'street',
            'ccustzip'       => 'zip_code',
            'ccuststate'     => 'state',
            'ccustcity'      => 'city',
            'ccustcc'        => 'country'
        );
    }

    protected function eventMap()
    {
        return array(
                'TEST' => EVENT_CONNECTION_TEST,

                'SALE' => EVENT_SALE,
                'BILL' => EVENT_SALE,
                'UNCANCEL-REBILL' => EVENT_SALE,
                'RFND' => EVENT_REFUND,
                'CGBK' => EVENT_REFUND,
                'INSF' => EVENT_REFUND,
                'CANCEL-REBILL' => EVENT_MISSED_PAYMENT,

                'TEST_SALE' => EVENT_SALE,
                'TEST_BILL' => EVENT_SALE,
                'TEST_UNCANCEL-REBILL' => EVENT_SALE,
                'TEST_RFND' => EVENT_REFUND,
                'TEST_CGBK' => EVENT_REFUND,
                'TEST_INSF' => EVENT_REFUND,
                'TEST_CANCEL-REBILL' => EVENT_MISSED_PAYMENT,

            );
    }

    public function instructions()
    {
       $instructions = parent::instructions();

       $instructions[] = _digi3('Click on <em>Save changes</em> below to create a notification URL.');
       $instructions[] = _digi3( 'Copy the notification URL below to the clipboard.' );

       $instructions[] = _digi3( '<strong>In Clickbank</strong> go to <em>Account Settings - My site</em>. There select <em>Advanced Tools - Edit</em>.' );

       $instructions[] = _digi3( 'If you have a new Clickbank acount, you need to perform these steps <em>once</em>:' )
. '<ol><li>'
    . _digi3( 'Right of <em>Instant Notification URL</em> click on <em>Request access</em>.' ) . '</li><li>'
    . _digi3( 'Review the instructions and select <em>Yes</em> in all dropdown lists.' ) . '</li><li>'
    . _digi3( 'Scroll to the bottom of the <em>Terms of Use</em> and check the checkbox.' ) . '</li><li>'
    . _digi3( 'Hit <em>Save Changes & Request API Access</em>.' ) . '</li><li>'
    . _digi3( 'Right of <em>Advanced Tools</em> click again on <em>Edit</em>.' ) . '</li></ol>';

       $instructions[] = _digi3( 'Paste the notification URL from DigiMember into one of the two inputs <em>Instant notification URL</em>.' );
       $instructions[] = _digi3( 'As <em>version</em> select 2.1.' );
       $instructions[] = _digi3( 'Save your changes.' );
       $instructions[] = _digi3( 'On <em>Account Settings - My Products</em> note column <em>Item</em>. Write down the item number for each product you want to sell with DigiMember.' );
       $instructions[] = _digi3( '<strong>In Digimeber</strong> locate the <em>Product Ids</em> input. Enter the Clickbank itemnumber for each product you want to sell with DigiMember.' );
       $instructions[] = _digi3( 'Save your changes.' );

       return $instructions;
    }


    public function orderIdsAreOfSameOrder( $order_id_a, $order_id_b )
    {
        if ($order_id_a == $order_id_b)
        {
            return true;
        }

        list( $a ) = explode( '-', $order_id_a );
        list( $b ) = explode( '-', $order_id_b );

        return strlen($a) >= 6
            && $a == $b;
    }

//    private function ipnVerification() {

//        $secretKey="YOUR SECRET KEY";
//        $pop = "";
//        $ipnFields = array();
//        foreach ($_POST as $key => $value) {
//            if ($key == "cverify") {
//                continue;
//            }
//            $ipnFields[] = $key;
//        }
//        sort($ipnFields);
//        foreach ($ipnFields as $field) {
                    // if Magic Quotes are enabled $_POST[$field] will need to be
            // un-escaped before being appended to $pop
//            $pop = $pop . $_POST[$field] . "|";
//        }
//        $pop = $pop . $secretKey;
//        $calcedVerify = sha1(mb_convert_encoding($pop, "UTF-8"));
//        $calcedVerify = strtoupper(substr($calcedVerify,0,8));
//        return $calcedVerify == $_POST["cverify"];
//    }
}






