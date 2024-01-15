<?php

$load->controllerBaseClass('admin/form');

class digimember_AdminSignupFormgeneratorController extends ncore_AdminFormController
{
    protected function pageHeadline()
    {
        return _digi( 'Signup Form Generator' );
    }

    protected function viewData()
    {
        $data = parent::viewData();

        $data[ 'form_action_url'  ] = ncore_siteUrl( '?dm_signup=PRODUCT_IDS&dm_redirect=REDIRECT_URL' );

        return $data;
    }

    protected function inputMetas()
    {
        $id = '';

        $meta = array();

        $meta[] = array(
                'section' => 'form',
                'name' => 'product_id',
                'type' => 'checkbox_list',
                'label' => _digi('Product' ),
                'options' => $this->getProductOptions(),
                'element_id' => $id,
                'hint' => _digi( 'New users will get the selected product(s).' ),
                'no_options_text' => _digi( 'No products available' ),
                'row_size' => 1,
            );


        $meta[] = array(
                'section' => 'form',
                'name' => 'do_autologin',
                'type' => 'select',
                'label' => _digi('After sign up ...' ),
                'element_id' => $id,
                'options' => array(
                    'Y' => _digi( '... log in the user and redirect him to the product\'s start page' ),
                    'N' => _digi( '... redirect him to a thank you page' ),
                ),
                'default' => 'Y',
            );

        $meta[] = array(
                'section' => 'form',
                'name' => 'thankyou_page_url',
                'type' => 'page_or_url',
                'label' => _digi('Thankyou page URL' ),
                'element_id' => $id,
                'depends_on' => array( 'do_autologin' => 'N' ),
            );

        return $meta;
    }

    /**
     * @return array
     */
    private function getProductOptions()
    {
        static $productOptions;
        if ($productOptions) {
            return $productOptions;
        }
        /** @var digimember_ProductData $product_model */
        $product_model = $this->api->load->model('data/product');
        $productOptions = $product_model->optionsWithAuthKeys($product_type = 'membership', $public_only = true);
        return $productOptions;
    }

    protected function viewName()
    {
        return $this->baseName();
    }

    protected function formId()
    {
        return 'dm_signup_form_generator';
    }

    protected function buttonMetas()
    {
        $js = "dm_renderCode();";

        return [
            [
                'type' => 'onclick',
                'name' => 'save',
                'label' => $this->saveButtonLabel(),
                'primary' => true,
                'javascript' => $js,
                'disabled' => !count($this->getProductOptions()),
            ],
        ];
    }

    protected function saveButtonLabel()
    {
        return _digi( 'Generate form code' );
    }

    protected function pageInstructions()
    {
        return array(
            _digi( 'On this page you can create a simple sign up form.' ),
            _digi( 'The generated form code can be used as HTML code for page builders, to directly utilize the design settings of the theme.' ),
        );

    }

    protected function sectionMetas()
    {
        return array(
            'form' => array(
                            'headline' => _ncore('Settings'),
                            'instructions' => '',
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
    }

    protected function setData( $id, $data )
    {
    }


}
