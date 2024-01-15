<?php

class digimember_FormRenderer_InputSelect extends ncore_FormRenderer_InputSelect
{
    protected function resolveOptions( $options )
    {
        switch ($options)
        {
            case 'exam':
                $this->api->load->model( 'data/exam' );
                $options = $this->api->exam_data->options();

                return $options;

            case 'exam_certificate':
                $this->api->load->model( 'data/exam_certificate' );
                $options = $this->api->exam_certificate_data->options();

                return $options;

            case 'subscriptions_show':
                return array(
                    'all'     => _digi( '... web push and email subscription' ),
                    'email'   => _digi( '... email subscription only' ),
                    'webpush' => _digi( '... web push subscription only' ),
                );

            case 'lecture_or_menu':
                $this->api->load->model( 'data/product' );
                $product_options = $this->api->product_data->options();
                $menu_options    = ncore_resolveOptions( 'menu' );

                $menu = array();

                $menu[ 'optgroup_products' ] = _digi( 'Lectures of product' );


                $menu[ 'product_current' ] = _digi( 'Product of current course' );

                foreach ($product_options as $id => $label)
                {
                    $menu[ "product_$id" ] = $label;
                }

                $menu[ 'optgroup_menus' ] = _digi( 'Wordpress menu' );

                foreach ($menu_options as $id => $label)
                {
                    $menu[ "menu_$id" ] = $label;
                }

                return $menu;

            case 'lecture_button_styles':
                return array(
                            'white'      => _digi( 'white thin' ),
                            'white_bold' => _digi( 'white bold' ),
                            'grey'       => _digi( 'grey thin' ),
                            'grey_bold'  => _digi( 'grey bold' ),
                            'black'      => _digi( 'black thin' ),
                            'black_bold' => _digi( 'black bold' ),
                            'blue'       => _digi( 'blue thin' ),
                            'blue_bold'  => _digi( 'blue bold' ),
                            'red'        => _digi( 'red thin' ),
                            'red_bold'   => _digi( 'red bold' ),
                );

            default:
                return parent::resolveOptions( $options );
        }
    }
}
