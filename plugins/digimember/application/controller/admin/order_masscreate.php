<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminOrderMasscreateController extends ncore_AdminFormController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }
    protected function pageHeadline()
    {
        return _digi('Give To All');
    }

    protected function pageInstructions()
    {
        return array(
            _digi( 'Here you can give a single product to <strong>all</strong> registered users.' ),
            _digi( 'For the time based unlocking of content, the date of user registration is considered the start date.' ),
            _digi( 'If a user already has the product, he will not get it again.' ),
            _digi( 'If you have setup autoresponders, they will be ignored when assigning the product.' ),
        );
    }

    protected function inputMetas()
    {
        $product_model = $this->api->load->model( 'data/product' );
        $userpro_model = $this->api->load->model( 'data/user_product' );

        $product_options = $product_model->options();

        $id = $this->getElementId();

        $meta = array();

        $meta[] =
            array(
                'section' => 'order',
                'name' => 'product_id',
                'type' => 'select',
                'label' => _digi('Product' ),
                'options' => $product_options,
                'element_id' => $id,
            );

        return $meta;
    }

    protected function buttonMetas()
    {
        $id = $this->getElementId();

        $metas = array();

        $metas[]= array(
                'type' => 'submit',
                'name' => 'save',
                'label' => _digi('Give product to all users'),
                'primary' => true,
                'confirm' => _digi( 'The selected product will no given to all users.<p>This cannot be undone.<p>Continue?' ),
        );

        $link = $this->api->link_logic->adminPage( 'orders' );

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
        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        global $product_id;
        return array(
            'product_id' => $this->product_id,
        );
    }

    protected function setData( $id, $data )
    {
        $product_model = $this->api->load->model( 'data/product' );
        $user_product_model = $this->api->load->model( 'data/user_product' );

        $product_id = ncore_retrieve( $data, 'product_id' );
        $product = $product_model->get( $product_id );
        $product_name = ncore_retrieve( $product, 'name' );

        $this->product_id = $product_id;

        if (!$product)
        {
            return;
        }

        $count = $user_product_model->giveToAll( $product_id );

        $this->formSuccess( _digi( 'Product "%s" has been given to %s users.', $product_name, $count ) );

        return false;
    }

    protected function formActionUrl()
    {
        $this->api->load->helper( 'url' );

        $action_url = parent::formActionUrl();

        $args = array( 'masscreate' => 1 );

        return ncore_addArgs( $action_url, $args );
    }

    private $product_id=0;
}