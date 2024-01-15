<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminMailTextController extends ncore_AdminFormController
{
    protected function elementSelectorLevelCount()
    {
        switch ($this->currentTab())
        {
            case 'membership_product':
            case 'download_product':
                return 1;

            case 'account':
            default:
                return 0;
        }
    }

    protected function elementOptions( $level=1 )
    {
        assert( $level == 1);

        switch ($this->currentTab())
        {
            case 'membership_product':
                /** @var digimember_ProductData $model */
                $model = $this->api->load->model('data/product');
                $product_options = $model->options( 'membership' );
                return $product_options;

            case 'download_product':
                /** @var digimember_ProductData $model */
                $model = $this->api->load->model('data/product');
                $product_options = $model->options( 'download' );
                return $product_options;


            case 'account':
            default:
                return array();
        }
    }

    protected function elementSelectionMandatory()
    {
        switch ($this->currentTab())
        {
            case 'membership_product':
            case 'download_product':
                return true;

            case 'account':
            default:
                return false;
        }
    }

    protected function noElementsMessage()
    {
        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model( 'logic/link' );
        $url = $linkLogic->adminPage( 'digimember_products' );

        $msg = $this->currentTab() == 'membership_product'
             ? _digi('Please <a>add a membership product</a> first.')
             : _digi('Please <a>add a download product</a> first.');

        $msg = ncore_linkReplace( $msg, $url );

        return ncore_renderMessage( NCORE_NOTIFY_ERROR, $msg, 'span' );
    }

    protected function pageHeadline()
    {
        switch ($this->currentTab())
        {
            case 'membership_product':
                return _digi('Email Texts for membership product:');

            case 'download_product':
                return _digi('Email Texts for download product:');

            case 'account':
                return _digi('New Password Email');

            default:
                return '';
        }
    }

    protected function inputMetas()
    {
        $api = $this->api;
        $api->load->model('data/product');
        /** @var digimember_MailTextData $mailTextData */
        $mailTextData = $api->load->model('data/mail_text');
        /** @var digimember_MailHookLogic $mailHookLogic */
        $mailHookLogic = $api->load->model('logic/mail_hook');
        $api->load->library('payment_handler');

        $hookMetas = $this->hookMetas();



        $product_options = $this->elementOptions();

        $product_id = (int) $this->selectedElement();

        $inputs = array();

        foreach ($hookMetas as $hook => $meta)
        {
            extract( $meta );
            /** @var string $context */
            /** @var string $label */
            /** @var string $description */
            /** @var array $placeholder */
            /** @var array $demo_values */

            $mail_text = $mailTextData->getForHook( $hook, $product_id );
            $mail_id = $mail_text->id;

            $section = $this->_sectionId( $hook, $product_id );

            $inputs[] = array(
                            'name' => 'subject',
                            'element_id' => $mail_id,
                            'section' => $section,
                            'type' => 'text',
                            'label' => _digi('Subject'),
                            'rules' => 'required|defaults',
                        );

           $inputs[] = array(
                            'name' => 'body_html',
                            'element_id' => $mail_id,
                            'section' => $section,
                            'type' => 'htmleditor',
                            'label' => _digi('Message'),
                            'rules' => 'defaults',
                            'simple_buttons' => true,
                        );

           $inputs[] = array(
                            'name' => 'placeholder',
                            'section' => $section,
                            'type' => 'placeholder',
                            'label' => _digi('Placeholder'),
                            'model' => 'data/mail_text',
                            'element_id' => $mail_id,
                            'placeholder' => $placeholder,
                        );

            $inputs[] = array(
                            'name' => 'attachment',
                            'section' => 'email',
                            'type' => 'file',
                            'label' => _digi('Mail attachment'),
                            'tooltip' => _digi( 'The selected file will be send as mail attachment. This is optional.' ),
                            'element_id' => $mail_id,
                        );

            if ($product_options)
            {
                $inputs[] = array(
                            'name' => 'send_policy',
                            'element_id' => $mail_id,
                            'section' => $section,
                            'type' => 'select',
                            'label' => _digi('Send this email ...'),
                            'rules' => 'required|defaults',
                            'options' => $mailHookLogic->sendPolicyOptions( $hook ),
                        );
            }
        }

        return $inputs;
    }

    protected function sectionMetas()
    {
        $api = $this->api;

        $api->load->model('data/product');
        $api->load->model('logic/mail_hook');

        $product_id = $this->selectedElement();
        $product = $api->product_data->get( $product_id );

        $hookMetas = $this->hookMetas();
        $sectionMetas = array();

        if (!$product)
        {
            foreach ($hookMetas as $hook => $meta)
            {
                $section = $this->_sectionId( $hook, 0 );

                $instructions =ncore_retrieve( $meta, 'description' );

                $headline = ncore_retrieve( $meta, 'label' );

                $sectionMetas[ $section ] =array(
                    'headline' => $headline,
                    'instructions' => $instructions,
                );
            }
        }
        else {
            foreach ($hookMetas as $hook => $meta)
            {
                $section = $this->_sectionId( $hook, $product_id );

                $instructions =ncore_retrieve( $meta, 'description' );

                $headline =ncore_retrieve( $meta, 'label' )
                    . ' ' . _digi('for product %s', $product->name);

                $sectionMetas[ $section ] =array(
                    'headline' => $headline,
                    'instructions' => $instructions,
                );
            }
        }

        return $sectionMetas;
    }


    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        $form_id = $this->formId();

        $metas[] = array(
                'type' => 'ajax',
                'label' => _ncore('Send test email'),
                'ajax_meta' => array(
                            'type' => 'form',
                            'cb_form_id' => $form_id,
                            'message' => _ncore( 'Enter an email address:' ),
                            'title' => _digi( 'Test email settings' ),
                            'modal' => false,
                            'width' => '600px',
                            'form_sections' => array(
                            ),
                            'form_inputs' => array(
                                array(
                                    'name' => 'test_email',
                                    'type' => 'text',
                                    'label' => _digi('Email' ),
                                    'rules' => 'defaults|email',
                                    'default' => $this->_getTestDefaults(),
                                ),
                         ),
                    ),
                );


        $product_options = $this->elementOptions();

        if ($product_options)
        {
            $this->api->load->helper( 'array' );
            $hook_metas   = $this->hookMetas();
            $hook_options = ncore_listToArray(  $hook_metas, 'use_index', 'label' );

            $metas[] = array(
                    'type' => 'ajax',
                    'label' => _digi('Copy this text'),
                    'ajax_meta' => array(
                                'type' => 'form',
                                'cb_form_id' => $form_id,
                                'message' => _digi('Copy this text to:'),
                                'title' => _digi( 'Copy email text' ),
                                'modal' => false,
                                'width' => '600px',

                                'confirm_ok' => _digi( 'The email texts of the chosen products will be overwritten by this email text.|Continue?' ),

                                'form_sections' => array(
                                ),
                                'form_inputs' => array(
                                   array(
                                        'name' => 'copy_for_hook',
                                        'type' => 'select',
                                        'label' => 'none',
                                        'options' => $hook_options,
                                        'hide' => count($hook_options) == 1,
                                    ),
                                    array(
                                        'name' => 'copy_to_element_ids',
                                        'type' => 'checkbox_list',
                                        'label' => 'none',
                                        'options' => $product_options,
                                        'seperator' => '<br />',
                                        'have_all'  => true,
                                    ),
                                ),
                        ),
                    );
        }

        return $metas;
    }

    protected function tabs()
    {
        return array(
            'membership_product' => _digi( 'Membership product' ),
            'download_product'   => _digi( 'Download product' ),
            'account'            => _digi( 'New Password Mail' ),
            'cancel'            => _digi( 'Cancel emails' ),
        );
    }


    protected function editedElementIds()
    {
        $hookMetas = $this->hookMetas();

        $product_id = $this->selectedElement();

        $element_ids = array();

        foreach ($hookMetas as $hook => $meta)
        {
            extract( $meta );

            $mail_text = $this->api->mail_text_data->getForHook( $hook, $product_id );
            $element_ids[] = $mail_text->id;
        }

        return $element_ids;
    }

    protected function handleRequest()
    {
        parent::handleRequest();

        $test_email = ncore_retrieve( $_POST, 'ncore_test_email' );

        $do_test = (bool) $test_email;
        if ($do_test)
        {
            $this->performTest( $test_email );
        }

        $product_ids = ncore_retrieve( $_POST, 'ncore_copy_to_element_ids' );
        $hook        = ncore_retrieve( $_POST, 'ncore_copy_for_hook' );

        $do_copy = (bool) $product_ids;
        if ($do_copy)
        {
            $this->copyMailTexts( $product_ids, $hook );
        }

    }



    protected function getData( $element_id )
    {
        $model = $this->api->load->model( 'data/mail_text' );

        return $model->get( $element_id );
    }

    protected function setData( $element_id, $data )
    {
        $model = $this->api->load->model( 'data/mail_text' );

        return $model->update( $element_id, $data );
    }


    //
    // private
    //
    private function _sectionId( $hook, $product_id )
    {
        return "$hook/$product_id";
    }

    private function _getTestDefaults()
    {
        $model = $this->api->load->model( 'data/user_settings' );

        $email = $model->get( 'test_settings_email' );

        if (!$email)
        {
            $lib = $this->api->load->library( 'mailer' );
            $email = $lib->defaultTestEmailAddress();
        }

        return $email;
    }

    private function _setTestDefaults( $email )
    {
        $model = $this->api->load->model( 'data/user_settings' );

        $model->set( 'test_settings_email', $email );
    }

   private function performTest( $test_email )
    {
        $api = $this->api;
        $api->load->model('data/product');

        $api->load->model('logic/mail_hook');

        $api->load->library( 'rule_validator' );
        $api->load->library( 'mail_renderer' );
        $api->load->library( 'mailer' );

        $this->_setTestDefaults( $test_email );

        $error_msg = $api->rule_validator_lib->validate( _ncore('Email'), $test_email, 'email' );

        if (is_string( $error_msg ))
        {
            $this->formError( $error_msg );
            return;
        }

        $hookMetas = $this->hookMetas();

        $product_id = $this->selectedElement();
        $product = $api->product_data->get( $product_id );

        $success_count = 0;
        $error_count = 0;
        $skip_count = 0;

        $mailer = $this->api->load->library( 'mailer' );
        $renderer = $this->api->load->library( 'mail_renderer' );

        foreach ($hookMetas as $hook => $meta)
        {
            $meta[ 'product_id' ] = $product_id;
            $meta[ 'test_email' ] = $test_email;

            $mail_text = $api->mail_text_data->getForHook( $hook, $product_id );

            $demo_params = $api->mail_hook_logic->demoParams( $meta );

            $demo_params['product_id'] = $product_id;
            $demo_params['product_name'] = empty($product) ? '' : $product->name;
            $demo_params['product_url'] = empty($product) ? '' : $product->shortcode_url;
            if (!empty($product) && property_exists($product, "shortcode_url")) {
                $demo_params['product_url'] = is_numeric($product->shortcode_url) ? '<a href="'.get_permalink($product->shortcode_url).'">'.get_permalink($product->shortcode_url).'</a>' : '<a href="'.$product->shortcode_url.'">'.$product->shortcode_url.'</a>';
            }
            list( $subject, $body_html ) = $renderer->renderMail( $mail_text, $demo_params );

            $have_mail = trim( $body_html ) != '';

            if ($have_mail)
            {
                $mailer->to( $test_email );
                $mailer->subject( $subject );
                $mailer->html( $body_html );
                if (isset($mail_text->attachment) && is_numeric(trim($mail_text->attachment))) {
                    if ($attachment = get_attached_file($mail_text->attachment)) {
                        $mailer->attachments(array($attachment));
                    }
                }

                $one_success = $mailer->send();
            }
            else
            {
                $skip_count++;
                continue;
            }

            if ($one_success)
            {
                $success_count++;
            }
            else
            {
                $error_count++;
            }
        }

        $all_success = $success_count && !$error_count && !$skip_count;

        if ($all_success)
        {
            $this->formSuccess( _ncore('A test email has been sent to %s.', $test_email ));
        }
        else
        {
            if ($error_count)
            {
                $this->formError( _ncore('The email to %s could not be send. Please validate the email address and your email settings.', $test_email ));
            }

            if ($skip_count)
            {
                $this->formError( _ncore('The email to %s has not been send, because the message is left blank.', $test_email ));
            }
        }
    }

    private function hookMetas()
    {
        /** @var digimember_MailHookLogic $model */
        $model = $this->api->load->model( 'logic/mail_hook' );
        switch ($this->currentTab())
        {
            case 'membership_product':
                $hookMetas = $model->membershipProductHookMetas();
                break;

            case 'download_product':
                $hookMetas = $model->downloadProductHookMetas();
                break;

            case 'account':
                $hookMetas = $model->accountHookMetas();
                break;

            case 'cancel':
                $hookMetas = $model->cancelHookMetas();
                break;

            default:
                $hookMetas = array();
        }

        return $hookMetas;
    }


    private function copyMailTexts( $product_ids, $hook=false )
    {
        $product_ids = explode( ',', $product_ids );
        $have_all = in_array( 'all', $product_ids );
        if ($have_all)
        {
            $where = array();
            $where['type'] = $this->currentTab() == 'membership_product'
                           ? 'membership'
                           : 'download';

            $this->api->load->model( 'data/product' );
            $all = $this->api->product_data->getAll( $where );
            $product_ids = ncore_listToArray( $all, 'id', 'id' );
        }

        $my_product_id = $this->selectedElement();

        if (!$hook)
        {
            $hook_metas = $this->hookMetas();
            $hook_list = array_keys( $hook_metas );
            $hook = $hook_list[0];
        }


        $count = 0;

        $this->api->load->model('data/mail_text');

        $mail_text = $this->api->mail_text_data->getForHook( $hook, $my_product_id );

        foreach ($product_ids as $product_id)
        {
            $product_id = ncore_washInt( $product_id );
            if (!$product_id) {
                continue;
            }

            if ($product_id == $my_product_id)
            {
                continue;
            }

            $count++;
            $this->api->mail_text_data->setForHook( $hook, $product_id, $mail_text->subject, $mail_text->body_html );
        }

        switch ($count)
        {
            case 0:
                $this->formError( _digi( 'No products selected to copy the email text to.' ) );
                break;
            case 1:
                $this->formSuccess( _digi( 'Copied the email text to one product.' ) );
                break;
            default:
                $this->formSuccess( _digi( 'Copied the email text to %s products.', $count ) );
        }
    }



}
