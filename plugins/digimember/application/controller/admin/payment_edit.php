<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass( 'admin/form' );

class digimember_AdminPaymentEditController extends ncore_AdminFormController
{
    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUseOtherPaymentProviders();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUseOtherPaymentProviders();
    }

    protected function isPageReloadAfterSubmit()
    {
        $element_ids = $this->editedElementIds(); 
        if (count($element_ids)) {
            $data = $this->getData($element_ids[0]);
            return $data->engine == 'stripe_pricing_api';
        }
        return false;
    }

    protected function handleRequest()
    {
        parent::handleRequest();

        $do_mail_shareit_config = isset( $_POST['mail_shareit_config'] );
        if ($do_mail_shareit_config)
        {
            $this->mailShareitConfig();
        }

    }

    protected function pageHeadline()
    {
        return _digi( 'Payment Provider' );
    }

    protected function inputMetas()
    {
        $this->api->load->model( 'data/product' );
        /** @var digimember_PaymentHandlerLib $lib */
        $lib = $this->api->load->library( 'payment_handler' );

        $id = $this->getElementId();
        $engine_options = $lib->getProviders();

        $ipn_per_product_engines = $lib->ipnPerProductEngines();
        $ipn_per_provider_engines = $lib->ipnPerProviderEngines();

        $product_options = $this->api->product_data->options('all');

        $shareit_instructions = _digi3( 'To connect ShareIt to DigiMember, please send a mail to the ShareIt support. This is required to use ShareIt with DigiMember.<p> ShareIt will set the correct notification URL for you ShareIt account.<p>Please not, that ShareIt only support one notification URL per ShareIt account.<p>ShareIt will need 2-3 workdays to update your configuration.' );

        $metas = array();

        $have_product = (bool) $product_options;
        if (!$have_product) {
            $warning = _digi3('Please <a>create a product first</a>.' );

            $url = $this->api->link_logic->createProduct();
            $find = array( '<a>' );
            $repl = array( "<a href='$url'>" );
            $warning = str_replace( $find, $repl, $warning );

            $warning = ncore_renderMessage( NCORE_NOTIFY_WARNING, $warning );

            $metas[] = array(
                    'section' => 'payment',
                    'type' => 'html',
                    'label' => 'none',
                    'html' => $warning,
            );
        }


        $metas[] = array(
                'name' => 'engine',
                'section' => 'payment',
                'type' => 'select',
                'label' => _digi('Payment provider' ),
                'rules' => 'defaults',
                'element_id' => $id,
                'options' => $engine_options,
        );

        $metas[] = array(
                'name' => 'id',
                'section' => 'payment',
                'type' => 'int',
                'label' => _digi('Id' ),
                'element_id' => $id,
                'rules' => 'readonly',
        );

        $metas[] = array(
                'name' => 'is_active',
                'section' => 'payment',
                'type' => 'yes_no_bit',
                'label' => _digi('Active' ),
                'element_id' => $id,
        );

        foreach ($engine_options as $engine => $label)
        {
            $one = $lib->instructionMeta( $engine );
            if ($one)
            {
                $one['section'] = 'payment';
                $one['element_id'] = $id;

                $one['name'] = 'sub_data_' . $one['name'];

                $one['depends_on'] = array(
                    'engine' => $engine,

                );

                $metas[] = $one;
            }
        }

        $metas[] = array(
                'name' => 'ipn_url_per_product',
                'section' => 'payment',
                'type' => 'display_ipn_url_per_product',
                'label' => _digi('Notification URL' ),
                'element_id' => $id,
                'options' => $product_options,
                'depends_on' => array(
                    'engine' => $ipn_per_product_engines,
                ),
            );


       $metas[] = array(
                'name' => 'ipn_url',
                'section' => 'payment',
                'type' => 'display_ipn_url',
                'label' => _digi('Notification URL' ),
                'element_id' => $id,
                'depends_on' => array(
                    'engine' => $ipn_per_provider_engines,
                ),

       );


        foreach ($engine_options as $engine => $label)
        {
            $engine_metas = $lib->engineInputMetas( $engine, $id );
            foreach ($engine_metas as $one)
            {
                $one['section'] = 'payment';
                $one['element_id'] = $id;

                $one['name'] = 'sub_data_' . $one['name'];

                $depends_on = ncore_retrieve( $one, 'depends_on', array() );
                unset( $depends_on['engine'] );

                $one['depends_on'] = array(
                    'engine' => $engine,
                );

                foreach ($depends_on as $key => $value)
                {
                    $one['depends_on']['sub_data_'.$key] = $value;
                }

                $metas[] = $one;
            }

        }

        $metas[] =  array(
                'name' => 'mail_shareit_config',
                'section' => 'payment',
                'type' => 'action_button',
                'label' => _digi('Notify ShareIt support' ),
                'action_label' => _digi3( 'Send email to ShareIt' ),
                'instructions' => _digi3( 'Send configuration update email to ShareIt support.' ),
                'tooltip' => $shareit_instructions,
                'depends_on' => array( 'engine' => 'shareit' ),
                'element_id' => $id,
                'confirm' => _digi3( 'The email to ShareIt\'s customer support is sent now.<p>Please note that this changes the notification url for your ShareIt-account, not just for the selected products, since ShareIt does not support different notification URLs for different products.<p>You will receive a copy of the email.<p>Continue?' ),
            );

        return $metas;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        $link = $this->api->link_logic->adminPage( 'payment' );

        $metas[] = array(
                'type' => 'link',
                'label' => _ncore('Back'),
                'url' => $link,
                );

        return $metas;
    }

    protected function sectionMetas()
    {
        return [
            'payment' =>  [
                'headline' => _ncore('Settings'),
                'instructions' => '',
            ]
        ];
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        /** @var digimember_PaymentData $model */
        $model = $this->api->load->model( 'data/payment' );

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            $obj = $model->get( $id );
        }
        else
        {
            $obj = $model->emptyObject();
        }

        if (!$obj)
        {
            $this->formDisable( _ncore( 'The element has been deleted.' ) );
            return false;
        }

        foreach ($obj->data as $key => $value)
        {
            $col = "sub_data_$key";

            $obj->$col = $value;
        }

        return $obj;
    }

    protected function setData( $id, $data )
    {
        /** @var digimember_PaymentData $model */
        $model = $this->api->load->model( 'data/payment' );

        $have_id = is_numeric( $id ) && $id > 0;

        $subdata = array();

        foreach ($data as $col => $value)
        {
            $is_data = ncore_stringStartsWith( $col, 'sub_data_' );
            if ($is_data)
            {
                $key = substr( $col, 9 );
                $subdata[$key] = $value;
            }
        }

        $engine = ncore_retrieve( $data, 'engine' );
        if ($engine)
        {
            $data['product_code_map'] = ncore_retrieve( $subdata, $engine.'_product_code_map' );

        }

        $data[ 'data_serialized' ] = serialize( $subdata );

        if ($have_id)
        {
            return $model->update( $id, $data );
        }
        else
        {
            $id = $model->create( $data );

            $this->setElementId( $id );

            return (bool) $id;
        }
    }

    protected function formActionUrl()
    {
        $this->api->load->helper( 'url' );

        $action_url = parent::formActionUrl();

        $id =  $this->getElementId();

        if ($id)
        {

            $args = array( 'id' => $id );

            return ncore_addArgs( $action_url, $args );
        }
        else
        {
            return $action_url;
        }
    }

    /**
     * @return string|void
     */
    private function mailShareitConfig()
    {
        /** @var digimember_PaymentData $paym_model */
        $paym_model = $this->api->load->model( 'data/payment' );
        /** @var digimember_LinkLogic $link_model */
        $link_model = $this->api->load->model( 'logic/link' );

        $this->api->load->helper( 'array' );

        $payment_id = $this->getElementId();

        $payment = $paym_model->get( $payment_id );
        if (!$payment)
        {
            return $this->formError( 'Internal error' );
        }

        $customer_id = ncore_retrieve( $payment->data, 'shareit_shareit_customer_id' );
        if (!$customer_id)
        {
            return $this->formError( 'Please enter your ShareIt customer id first.' );
        }

        $map = ncore_simpleMapExplode( $payment->product_code_map );

        $shareit_product_ids = array();
        foreach ($map as $digimber_product_id => $shareit_product_id)
        {
            $shareit_product_id = trim( $shareit_product_id );
            if ($shareit_product_id)
            {
                $shareit_product_ids[] = $shareit_product_id;
            }
        }
        if (!$shareit_product_ids)
        {
            return $this->formError( 'Please enter your ShareIt product id(s) first.' );
        }

        $product_ids_text = implode( "\n", $shareit_product_ids );

        $ipn_url = $link_model->ipnCall( $payment_id, $product_id=false, $arg_sep='&' );

        /** @var digimember_BlogConfigLogic $config */
        $config       = $this->api->load->model( 'logic/blog_config' );
        $sender_email = $config->get( 'mail_sender_email' );
        $sender_name  = $config->get( 'mail_sender_name' );
        $reply_email  = $config->get( 'mail_reply_email' );

        if (!$sender_email)
        {
            $sender_email = get_bloginfo('admin_email');
        }

        if (!$sender_name)
        {
            $sender_name = $sender_email;
        }


        $find = array( '[CUSTOMER_ID]', '[PROPDUCT_IDS]', '[IPNURL]', '[SENDER]' );
        $repl = array( $customer_id, $product_ids_text, $ipn_url, $sender_name );

        $subject = sprintf( 'Customer ID %s - XML Notification URL', $customer_id );
        $message = "Hello,

my Customer ID is: [CUSTOMER_ID]

Can you please set the xml notification URL for all notifications types

including
 - Order Notification,
 - Chargeback,
 - Chargeback Reversal,
 - Refund Done,
 - Fraud Refund Done,
 - Rebilling Cancelled,
 - Rebilling Deactivated

for my account to:

[IPNURL]

Thank you very much!

Best regards,

[SENDER]




";

        $message = str_replace( $find, $repl, $message );

        $support_email = 'authors@shareit.com';

        if (NCORE_DEBUG)
        {
            $sender_email  = 'debug@digimember.de';
            $support_email = 'debug@digimember.de';
        }


        /** @var ncore_MailerLib $mailer */
        $mailer = $this->api->load->library( 'mailer' );
        $mailer->subject( $subject );
        $mailer->text( $message );
        $mailer->to( $support_email );
        $mailer->bcc( $reply_email ? $reply_email : $sender_email );

        $success = $mailer->send();

        if ($success)
        {
            $this->formSuccess( _digi3('An email with your request has been sent to the ShareIt support (%s). You will receive a copy of this email to %s. Please give the ShareIt support 2-3 workdays to handle your request.', $support_email, $sender_email) );
        }
        else
        {
            $this->formError( _ncore('Sending email to %s and %s failed.', $support_email, $sender_email) );
        }
    }
}
