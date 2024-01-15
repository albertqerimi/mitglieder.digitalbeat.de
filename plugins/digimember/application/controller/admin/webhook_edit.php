<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminWebhookEditController extends ncore_AdminFormController
{

    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted()) {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model('logic/features');
        return $model->canUseWebhooks();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted()) {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model('logic/features');
        return $model->canUseWebhooks();
    }


    protected function pageHeadline()
    {
        $model = $this->api->load->model( 'data/webhook' );

        $obj_id = $this->getElementId();
        if ($obj_id>0)
        {
            $obj = $model->get( $obj_id );

            $title = ncore_retrieve( $obj, 'name', _digi('new webhook' ) );
        }
        else
        {
            $title = _digi('new webhook');
        }

        return array( _digi( 'Webhooks' ), $title );
    }

    protected function inputMetas()
    {
        $this->api->load->model( 'data/webhook' );
        $this->api->load->model( 'data/product' );
        $this->api->load->model( 'logic/webhook' );

        $id = $this->getElementId();

        $product_options       = $this->api->product_data->options();

        $metas = array();

        $metas[] = array(
            'name'              => 'name',
            'section'           => 'general',
            'type'              => 'text',
            'label'             => _ncore('Name' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );

        $metas[] = array(
            'name'              => 'is_active',
            'section'           => 'general',
            'type'              => 'yes_no_bit',
            'label'             => _digi('Is active' ),
            'element_id'        => $id,
        );

        $metas[] = array(
            'name'              => 'webhook_type',
            'section'           => 'general',
            'type'              => 'select',
            'label'             => _digi('Webhook function'),
            'element_id'        => $id,
            'options'           => array(
                'newOrder'      => _digi('Create order'),
                'cancelOrder'   => _digi('Deactivate order'),
            ),
        );

        $metas[] = array(
            'name'              => 'param_email',
            'section'           => 'params',
            'type'              => 'text',
            'label'             => _digi('Email parameter' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );

        $metas[] = array(
            'name'              => 'param_first_name',
            'section'           => 'params',
            'type'              => 'text',
            'label'             => _digi('First name parameter' ),
            'rules'             => 'defaults|trim',
            'element_id'        => $id,
            'depends_on' => array( 'webhook_type' => 'newOrder' ),
        );

        $metas[] = array(
            'name'              => 'param_last_name',
            'section'           => 'params',
            'type'              => 'text',
            'label'             => _digi('Last name parameter' ),
            'rules'             => 'defaults|trim',
            'element_id'        => $id,
            'depends_on' => array( 'webhook_type' => 'newOrder' ),
        );

        $metas[] = array(
            'section'           => 'params',
            'type'              => 'html',
            'hint'              => _digi( 'The parameters above can be transferred as GET or POST parameters. %s accepts both types.', $this->api->pluginDisplayName() ),
        );

        $metas[] = array(
            'name'              => 'add_product_method',
            'section'           => 'product',
            'type'              => 'select',
            'label'             => _digi('Assign products ....' ),
            'element_id'        => $id,
            'options'           => array(
                'by_hook' => _digi( '... here on this page' ),
                'by_url'  => _digi( '... by GET or POST parameter' ),
            ),
            'depends_on' => array( 'webhook_type' => 'newOrder' ),
        );

        $metas[] = array(
            'name'       => 'product_ids_comma_seperated',
            'section'    => 'product',
            'type'       => 'checkbox_list',
            'options'    => $product_options,
            'label'      => _digi('Products for new members' ),
            'element_id' => $id,
            'row_size'   => 1,
            'hint'       => _digi( 'New users get these products.' ),
            'rules'      => 'int_list|required',
            'depends_on' => array(
                'webhook_type' => 'newOrder',
                'add_product_method' => 'by_hook'
            ),
        );

        $metas[] = array(
            'name'              => 'param_product',
            'section'           => 'product',
            'type'              => 'text',
            'label'             => _digi('Product parameter' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
            'depends_on'        => array( 'add_product_method' => 'by_url' ),
        );

        $metas[] = array(
            'name'              => 'add_order_id_method',
            'section'           => 'order_id',
            'type'              => 'select',
            'label'             => _digi('Assign order id ....' ),
            'element_id'        => $id,
            'options'           => array(
                'by_hook' => _digi( '... here on this page' ),
                'by_url'  => _digi( '... by GET or POST parameter' ),
            ),
        );

        $metas[] = array(
            'name'              => 'order_id',
            'section'           => 'order_id',
            'type'              => 'text',
            'label'             => _digi('Fixed order id' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
            'hint'              => _digi( 'Use this parameter to send an individual order id on the webhook call.' ),
            'depends_on'        => array( 'add_order_id_method' => 'by_hook' ),
        );

        $metas[] = array(
            'name'              => 'param_order_id',
            'section'           => 'order_id',
            'type'              => 'text',
            'label'             => _digi('Order id parameter' ),
            'rules'             => 'defaults|trim',
            'element_id'        => $id,
            'depends_on'        => array( 'add_order_id_method' => 'by_url' ),
        );

        $metas[] = array(
            'name'              => 'access_stops_on_method',
            'section'           => 'access_stops_on',
            'type'              => 'select',
            'label'             => _digi('Revoke access ....'),
            'element_id'        => $id,
            'options'           => array(
                'now'           => _digi('... instant'),
                'delayed'       => _digi('... delayed'),
            ),
            'depends_on' => array( 'webhook_type' => 'cancelOrder' ),
        );

        $metas[] = array(
            'name'              => 'param_access_stops_on',
            'section'           => 'access_stops_on',
            'type'              => 'text',
            'label'             => _digi('Revoke access parameter' ),
            'rules'             => 'defaults|trim',
            'element_id'        => $id,
            'hint'              => _digi('Use this parameter to send an individual date to stop the access on the webhook call. Provide the date in the format YYYY-MM-DD. So use 2022-02-01 for February 01 , 2022.'),
            'depends_on'        => array(
                'webhook_type' => 'cancelOrder',
                'access_stops_on_method' => 'delayed'
            ),
        );

        $metas[] = array(
            'name'              => 'add_password_method',
            'section'           => 'password',
            'type'              => 'select',
            'label'             => _digi('Assign password ....' ),
            'element_id'        => $id,
            'options'           => array(
                'by_hook' => _digi( '... by generating a random password' ),
                'by_url'  => _digi( '... by GET or POST parameter' ),
            ),
            'hint'              => _digi( 'Existing passwords are never changed.'),
            'depends_on' => array( 'webhook_type' => 'newOrder' ),
        );

        $metas[] = array(
            'name'              => 'param_password',
            'section'           => 'password',
            'type'              => 'text',
            'label'             => _digi('Password parameter' ),
            'rules'             => 'defaults|trim',
            'element_id'        => $id,
            'depends_on'        => array( 'add_password_method' => 'by_url' ),
        );




        $url = $id > 0
             ? $this->api->webhook_logic->render_url( $id )
             : false;

        if ($url)
        {
            $this->api->load->helper( 'html_input' );
            $metas[] = array(
                'name'              => 'url',
                'section'           => 'url',
                'type'              => 'html',
                'label'             => _digi('Action URL' ),
                'rules'             => 'readonly',
                'element_id'        => $id,
                'html'              => ncore_htmlTextInputCode( $url ),

            );
        }
        else
        {
            $metas[] = array(
                'name'              => 'action_url',
                'section'           => 'url',
                'type'              => 'html',
                'label'             => _digi('Action URL' ),
                'rules'             => 'defaults|trim',
                'element_id'        => $id,
                'html'              => '<i>' . _digi( 'Click "Save" to display the action URL.' ) .'</i>',
            );
        }



        return $metas;
    }

    protected function buttonMetas()
    {
        $id = $this->getElementId();

        $metas = parent::buttonMetas();

        $link = $this->api->link_logic->adminPage( 'webhooks' );

        $metas[] = array(
                'type'  => 'link',
                'label' => _ncore('Back'),
                'url'   => $link,
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
            'params' =>  array(
                            'headline' => _digi('Parameters'),
                            'instructions' => '',
            ),
            'url' =>  array(
                            'headline' => _digi('Intergration'),
                            'instructions' => '',
            ),
            'product' => array(
            ),
            'order_id' => array(
            ),
            'password' => array(
            ),
        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        $model = $this->api->load->model( 'data/webhook' );

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

        return $obj;
    }

    protected function setData( $id, $data )
    {
        $model = $this->api->load->model( 'data/webhook' );

        $have_id = is_numeric( $id ) && $id > 0;

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



}