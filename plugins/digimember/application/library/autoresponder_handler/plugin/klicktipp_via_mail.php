<?php

class digimember_AutoresponderHandler_PluginKlicktippViaMail extends digimember_AutoresponderHandler_PluginBase
{
    const email_field_name = 'E-Mail';

    public function unsubscribe( $email )
    {
    }

    public function getPersonalData( $email )
    {
        return array();
    }

    public function subscribe( $recipient_email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        $use_email_field = $this->data( 'use_email_field', 'N'  ) == 'Y';

        $tag_email = $use_email_field
                   ? self::email_field_name // $this->data( 'tag_email'  );
                   : '';
        $tag_first_name = 'Vorname'; //$this->data( 'tag_first_name' );
        $tag_last_name = 'Nachname'; // $this->data( 'tag_last_name' );


        // $tag_date = $this->data( 'tag_date' );
        // $tag_product_id = $this->data( 'tag_product_id' );
        // $tag_product_name = $this->data( 'tag_product_name' );

        $EQ=" = ";
        $LF="\r\n";

        $body = '';

        if ($tag_email)
        {
            $body .= "$tag_email$EQ$recipient_email$LF";
        }
        $body .= "$tag_first_name$EQ$first_name$LF";
        $body .= "$tag_last_name$EQ$last_name$LF";


        /*if ($tag_date)
        {
            $date = date( 'Y-m-d' );
            $body .= "$tag_date$EQ$date$LF";
        }

        if ($tag_product_id && $product_id)
        {
            $body .= "$tag_product_id$EQ$product_id$LF";
        }

        $product_name = false;
        if ($product_id)
        {
            $model = ncore_api()->load->model( 'data/product' );
            $product = $model->get( $product_id );
            $product_name = ncore_retrieve( $product, 'name' );
        }

        if ($tag_product_name && $product_name)
        {
            $body .= "$tag_product_name$EQ$product_id: $product_name$LF";
        }*/

        $list_email = $this->data( 'notify_email' );
        $subject = $this->data( 'notify_subject' );

        $must_set_sender_mail = $tag_email == '';

        if ($must_set_sender_mail)
        {
            $header = "From: \"$first_name $last_name\" <$recipient_email>\r\nReply-To: $recipient_email\r\nX-Mailer: PHP/" . phpversion();
            $success = mail( $list_email, $subject, $body, $header, "-f $recipient_email");

            if (!$success)
            {
                if ($use_email_field)
                {
                    throw new Exception( _ncore( 'Could not send email to %s: %s', $list_email, error_get_last() ) );
                }
                else
                {
                    throw new Exception( _digi3( 'Could not send the email to %s. Set option %s to <em>YES</em> and follow instructions step %s to %s. Then try again.', $list_email, '<em>'.$this->_useEmailFieldLabel().'</em>', 9, 12 ) );
                }
            }

        }
        else
        {
            $mailer = ncore_api()->load->library('mailer');

            $mailer->to( $list_email );
            $mailer->subject( $subject );
            $mailer->text( $body );

            $success = $mailer->send();

            if (!$success)
            {
                throw new Exception( _ncore( 'Could not send email to %s: %s', $list_email, $mailer->lastMailError() ) );
            }
        }
    }

    public function formMetas()
    {
        $example_email = _ncore( 'MY_MAILLINGLIST@%s', $this->arEmailDomain() );

//        $tagname_firstname = _digi3( 'Firstname' );
//        $tagname_lastname = _digi3( 'Lastname' );
//        $tagname_orderdate = _digi3( 'Orderdate' );
//        $tagname_product_id = _digi3( 'Productid' );
//        $tagname_product_name = _digi3( 'Productname' );

        return array(
             array(
                'name' => 'notify_email',
                'type' => 'email',
                'label' => $this->inputLabelArEmail(),
                'rules' => 'email',
                'hint'  => _digi ('E.g. %s', $example_email ),
                'default_domain' => $this->arEmailDomain(),
            ),
             array(
                'name' => 'notify_subject',
                'type' => 'text',
                'label' => $this->inputLabelArSubject(),
                'rules' => 'defaults',
                'hint'  => _digi ('E.g. %s', $this->inputExampleSubscribeSubject() ),
                'default' => $this->inputExampleSubscribeSubject(),
            ),
             array(
                'name' => 'use_email_field',
                'type' => 'yes_no_bit',
                'label' => $this->_useEmailFieldLabel(),
                'rules' => 'defaults',
                'tooltip'  => _digi3( 'Default is <em>No</em>. Set this to <em>Yes</em>, if you get an error when testing your settings. If so, you need to create a user defined field in KlickTipp. See the instructions (Steps %d to %d) above.', 9, 12),
                'default'  => 'N',
                'hint' => _digi3('Default is <em>No</em>.' ),
            ),
/*             array(
                'name' => 'tag_first_name',
                'type' => 'text',
                'label' => _digi3( 'Name of the first name field' ),
                'rules' => 'defaults',
                'tooltip'  => _digi3( 'If not empty, a tag for the buyer\'s first name will be added.' )
                            . ' ' . _ncore( 'Default is: %s', $tagname_firstname ),
                 'default'  => $tagname_firstname,
            ),

            array(
                'name' => 'tag_last_name',
                'type' => 'text',
                'label' => _digi3( 'Name of the last name field' ),
                'rules' => 'defaults',
                'tooltip'  => _digi3( 'If not empty, a tag for the buyer\'s last name will be added.' )
                              . ' ' . _ncore('Default is: %s', $tagname_lastname ),
                 'default' => $tagname_lastname,
            ),

            array(
                'name' => 'tag_product_id',
                'type' => 'text',
                'label' => _digi3( 'Name of the  product id field' ),
                'rules' => 'defaults',
                'tooltip'  => _digi3( 'If not empty, a tag for the id of the ordered product will be added.' ),
                // 'default' => $tagname_product_id,
            ),

            array(
                'name' => 'tag_product_name',
                'type' => 'text',
                'label' => _digi3( 'Name of the product name field' ),
                'rules' => 'defaults',
                'tooltip'  => _digi3( 'If not empty, a tag for the name of the ordered product will be added.' ),
                // 'default' => $tagname_product_name,
            ),

            array(
                'name' => 'tag_date',
                'type' => 'text',
                'label' => _digi3( 'Name for order date field' ),
                'rules' => 'defaults',
                'tooltip'  => _digi3( 'If not empty, a tag for the ordered date will be added.' ),
                // 'default' => $tagname_orderdate,
            ),*/
        );
    }

    public function instructions()
    {
        $model = $this->api->load->model( 'logic/link' );
        $this->api->load->helper( 'string' );
        $this->api->load->helper( 'url' );

        $info_url  = $model->productInfoUrl( 'klicktipp', 'info' );
        $order_url = $model->productInfoUrl( 'klicktipp', 'order' );


        $config = $this->api->load->model( 'logic/blog_config' );

        $example_email = _ncore( 'MY_MAILLINGLIST@%s', $this->arEmailDomain() );
        list( $random_name, ) = explode( '@', $example_email) ;

        $input_label_email = $this->inputLabelArEmail();
        $input_label_subject = $this->inputLabelArEmail();

        return array(
            ncore_linkReplace( _digi3('<strong>In <a>KlickTipp</a></strong> select <em>Automation - Tags - Create Tag</em>. Enter a name for the tag (e.g. "bought product xy") and click on <em>Save</em>.'), $info_url ),
            _digi3('In the menu select <em>List building - Subscription via email</em>. Enter a name and a unique email address.' ),
            _digi3('Also enter a subject for joining the autoresponder (e.g. %s) and for leaving (e.g. %s). Click on <em>Save</em>.', $this->inputExampleSubscribeSubject(), $this->inputExampleUnubscribeSubject() ),
            _digi3('In the menu select <em>List building - Overview</em> and open the subscription source from step 2+3 again. Add the tag created in step 1.' ),
            _digi3('<strong>Here in DigiMember</strong> enter the complete autoreponder\'s email (including the @%s).', $this->arEmailDomain()),
            _digi3('Enter also the subject for <strong>joining</strong> the autoreponders. The subject for leaving is not used.' ),
            _digi3('Click <em>Save</em>. Then click <em>Test settings</em>. For the test use an email address <strong>not</strong> from %s. Pick an email address of another domain (e.g. your.name@gmail.com).', ncore_2ndLvlDomain() ),
            _digi3('<strong>If you get a success message, you are done.</strong>' ).'<hr />',
            _digi3('If you get an error message, you may need to perform these additional steps:' ),
            _digi3('Enable the option <em>%s</em> below and save your changes.', _digi3( 'Use email field' ) ),
            ncore_linkReplace( _digi3('<strong>In <a>KlickTipp</a></strong> select <em>ContactCloud - New field</em>. Enter these values:<br />'
                  . '&nbsp;Name: %s<br />'
                  . '&nbsp;Type: email address<br />'
                  . '&nbsp;Parameter for subscription by email: %s<br />', self::email_field_name, self::email_field_name ), $info_url ),
            _digi3('Then click on <em>Save</em> and test your settings again.'),
            );


                //'tooltip'  => _digi3( 'If used, the default value is E-Mail. In KlickTipp add a user defined field of this name and set the <em>parameter for subscription via e-mail</em> to this value.'),
    }

    protected function textLabel()
    {
        return 'KlickTipp';
    }

    private function arEmailDomain()
    {
        return 'Klick-Tipp.com';
    }

    private function inputExampleSubscribeSubject()
    {
        return _ncore('subscribe');
    }

    private function inputExampleUnubscribeSubject()
    {
        return _ncore('unsubscribe');
    }

    private function inputLabelArEmail()
    {
        return _ncore('Autoresponder email address' );
    }

    private function inputLabelArSubject()
    {
        return _ncore('Email subject' );
    }

    private function _useEmailFieldLabel()
    {
        return _digi3( 'Use email field' );
    }
}