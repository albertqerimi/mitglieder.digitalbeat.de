<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminNewsletterEditController extends ncore_AdminFormController
{
    protected function pageHeadline()
    {
        return _ncore( 'Autoresponder' );
    }

    protected function inputMetas()
    {
        $api = $this->api;

        $api->load->model( 'data/autoresponder' );

        /** @var digimember_AutoresponderHandlerLib $lib */
        $lib = $api->load->library( 'autoresponder_handler' );

        $id = $this->getElementId();
       // all available autoresponder engines except for Zapier
        $engine_options = array_diff($lib->getProviders(), ['zapier'=>'Zapier']);

        $ar_with_gdrp    = $lib->unsubscribeAutoresponderTypes();
        $ar_without_gdrp = array_diff( array_keys( $engine_options  ), $ar_with_gdrp );
        
        $have_digimember = $this->api->havePlugin( 'digimember' );

        $product_options = array();
        if ($have_digimember) {
            dm_api()->load->model( 'data/product' );
            $product_options = dm_api()->product_data->options( $product_type='all' );
        }

        $metas = array();

        $metas[] = array(
                'name' => 'engine',
                'section' => 'general',
                'type' => 'select',
                'label' => _ncore('Autoresponder' ),
                'rules' => 'defaults',
                'element_id' => $id,
                'options' => $engine_options,
            );

        $metas[] = array(
                'name' => 'id',
                'section' => 'general',
                'type' => 'int',
                'label' => _ncore('Id' ),
                'element_id' => $id,
                'rules' => 'readonly',
            );

        $metas[] = array(
                'name' => 'is_active',
                'section' => 'general',
                'type' => 'yes_no_bit',
                'label' => _ncore('Active' ),
                'element_id' => $id,
            );
            
        $metas[] = array(
                'name' => 'product_ids_comma_seperated',
                'section' => 'general',
                'type' => 'checkbox_list',
                'options' => $product_options,
                'label' => _ncore('Products' ),
                'element_id' => $id,
                'have_all' => true,
                'row_size' => 1,
                'hide' => !$have_digimember,
            );            
            
        $metas[] = array(
                'name' => 'is_user_opted_out_if_deleted',
                'section' => 'gdpr',
                'type' => 'yes_no_bit',
                'label' => _ncore('Optout on account deletion' ),
                'hint' => _ncore( 'If yes, the user will be deleted from the auto responder, if his is account is deleted in WordPress.'),
                'element_id' => $id,
                'depends_on' => array( 'engine' => $ar_with_gdrp ),
            ); 
            
        $metas[] = array(
                'name' => 'is_personal_ar_data_exported',
                'section' => 'gdpr',
                'type' => 'yes_no_bit',
                'label' => _ncore('Allow export of personal data' ),
                'hint' => _ncore( 'If yes, and if the user exports his data via the %s shortcode, his data stored in the autoresponder\'s database will be exported.', '[ds_account data_export_button]'),
                'element_id' => $id,
                'depends_on' => array( 'engine' => $ar_with_gdrp ),
            );        
            
        $metas[] = array(
                'name' => 'gdpr_note',
                'section' => 'gdpr',
                'type' => 'html',
                'label' => 'none',
                'html' => '<i>' . _ncore( 'For this autoresponder no data privacy settings are available.' ) . '</i>',
                'element_id' => $id,
                'depends_on' => array( 'engine' => $ar_without_gdrp ),
            );                      
            
        foreach ($engine_options as $engine => $label)
        {
            $engine_metas = $lib->engineInputMetas( $engine, $id );
            foreach ($engine_metas as $one)
            {
                $one['section'] = 'autoresponder';
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

        return $metas;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');
        $link = $linkLogic->adminPage( 'newsletter' );

        $form_id = $this->formId();

        list( $email, $first_name, $last_name ) = $this->_getTestDefaults();

        $metas[] = array(
                'type' => 'ajax',
                'label' => _ncore('Test settings'),
                'ajax_meta' => array(
                            'type' => 'form',
                            'cb_form_id' => $form_id,
                            'message' => _digi('Add this subscription to the autoresponder:'),
                            'title' => _ncore( 'Test autoresponder' ),
                            'modal' => false,
                            'width' => '600px',
                            'form_sections' => array(
                            ),
                            'form_inputs' => array(
                                array(
                                    'name' => 'test_email',
                                    'type' => 'text',
                                    'label' => _ncore('Email' ),
                                    'rules' => 'defaults|email',
                                    'default' => $email,
                                ),
                                array(
                                    'name' => 'test_first_name',
                                    'type' => 'text',
                                    'label' => _ncore('First name' ),
                                    'rules' => 'defaults',
                                    'default' => $first_name,
                                ),
                                array(
                                    'name' => 'test_last_name',
                                    'type' => 'text',
                                    'label' => _ncore('Last name' ),
                                    'rules' => 'defaults',
                                    'default' => $last_name,
                                ),
                         ),
                    ),
                );

        $metas[] = array(
                'type' => 'link',
                'label' => _ncore('Back'),
                'url' => $link,
                );

        return $metas;
    }

    protected function sectionMetas()
    {
        return array(
            'general' =>  array(
                            'headline' => _ncore('Settings'),
                            'instructions' => '',
            ),
            'gdpr' =>  array(
                            'headline' => _ncore('GDPR'),
                            'instructions' => '',
            ),
            'autoresponder' =>  array(
                            'headline' => _ncore('Autoresponder'),
                            'instructions' => '',
            )            
        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        $model = $this->api->load->model( 'data/autoresponder' );

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
        $model = $this->api->load->model( 'data/autoresponder' );

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

    protected function handleRequest()
    {
        parent::handleRequest();

        $test_email      = ncore_retrieve( $_POST, 'ncore_test_email' );
        $test_first_name = ncore_retrieve( $_POST, 'ncore_test_first_name' );
        $test_last_name  = ncore_retrieve( $_POST, 'ncore_test_last_name' );

        $do_test = (bool) $test_email;
        if ($do_test)
        {
            $this->performTest( $test_email, $test_first_name, $test_last_name );
        }

        //Grapping data for autoreponder links
        if ($this->form()->isPosted()) {
            $customFieldModel = $this->api->load->model('data/custom_fields');
            $customFields = $customFieldModel->getAllActive();
            if ($ar_id = ncore_retrieve($_GET,'id',false)) {
                if ($engine = ncore_retrieve($_POST, 'ncore_engine'.$ar_id, false)) {
                    $fieldValues = array();
                    foreach ($customFields as $customField) {
                        if ($value = ncore_retrieve($_POST, 'ncore_sub_data_'.$engine.'_custom_field_link_select_'.$customField->name.$ar_id, false)) {
                            $fieldValues[$customField->id] = $value;
                        }
                        else {
                            $fieldValues[$customField->id] = false;
                        }
                    }
                    $arcfModel = $this->api->load->model('data/arcf_links');
                    $arcfModel->createTableIfNeeded();
                    foreach ($fieldValues as $fieldId => $fieldMapping) {
                        if ($fieldMapping) {
                            $arcfModel->set(array(
                                'autoresponder' => $ar_id,
                                'customfield' => $fieldId,
                                'mapping' => $fieldMapping
                            ));
                        }
                        else {
                            $arcfModel->deleteByArAndCf($ar_id, $fieldId);
                        }
                    }
                }
            }
        }
    }



    private function performTest( $test_email, $test_first_name, $test_last_name )
    {
        $this->_setTestDefaults( $test_email, $test_first_name, $test_last_name );

        $rules = $this->api->load->library( 'rule_validator' );

        $error_msg = $rules->validate( _ncore('Email'), $test_email, 'email' );

        if (is_string( $error_msg ))
        {
            $this->formError( $error_msg );
            return;
        }

        if ($this->haveFormErrors())
        {
            return;
        }

        $lib = $this->api->load->library( 'autoresponder_handler' );
        $model = $this->api->load->model( 'data/autoresponder' );

        $id =  $this->getElementId();
        $autoresponder = $model->get( $id );

        $data = new StdClass();
        $data->email = $test_email;
        $data->first_name = $test_first_name;
        $data->last_name = $test_last_name;

        try
        {
            $lib->testSubscribe( $autoresponder, $data );

            $this->formSuccess( ncore_paragraphs( _ncore ('The email %s was added to the autoresponder.', $test_email )
                        . '|' . '<strong>' . _ncore( 'Please check your mailbox for a subscription confirmation email (if applicable).' ) . '</strong>'
                        . '|' . _ncore( 'You also might want to check your autoresponder\'s website and validate that %s is subscribed.', $test_email )
                        . '|' . '<strong>' . _ncore( 'If the subscription is NOT successful, please try a different email address, because the used email address may already be subscribed.' ) . '</strong>'));
        }

        catch (Exception $e)
        {
            $this->formError( _ncore('Error when adding %s to the autoresponder: %s', $test_email, $e->getMessage()  ));
        }

    }

    private function _getTestDefaults()
    {
        $model = $this->api->load->model( 'data/user_settings' );

        $email = $model->get( 'test_ar_email' );
        $first_name = $model->get( 'test_ar_first_name', 'FIRSTNAME' );
        $last_name = $model->get( 'test_ar_last_name', 'LASTNAME' );

        if (!$email)
        {
            $lib = $this->api->load->library( 'mailer' );
            $email = $lib->defaultTestEmailAddress();
        }

        return array( $email, $first_name, $last_name );
    }

    private function _setTestDefaults( $email, $first_name, $last_name )
    {
        $model = $this->api->load->model( 'data/user_settings' );

        $model->set( 'test_ar_email', $email );
        $model->set( 'test_ar_first_name', $first_name );
        $model->set( 'test_ar_last_name', $last_name );
    }
}