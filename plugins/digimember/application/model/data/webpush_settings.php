<?php

class digimember_WebpushSettingsData extends ncore_BaseData
{
    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }

    public function optinMethodOptions()
    {
        return array(
            'button' => _digi( 'Display optin text button' ),
            'image'  => _digi( 'Display optin image button' ),
            'direct' => _digi( 'Request without prior information' ),
        );
    }

    public function optoutMethodOptions()
    {
        return array(
            'button' => _digi( 'Display optout text button' ),
            'image'  => _digi( 'Display optout image button' ),
            'none'   => _digi( 'None - the user cannot optout' ),
        );
    }

    public function getDefaultSettings()
    {
        $configs = $this->getAll( $where=array(), $limit="0,1", $order_by='id ASC' );

        if ($configs) {
            return $configs[0];
        }
        else
        {
            $id = $this->create( array() );
            return $this->get( $id );
        }
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'webpush_settings';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name'                    => 'string[127]',

            'optin_method'            => 'string[15]',
            'optin_button_label'      => 'text',
            'optin_button_image_id'   => 'id',
            'optin_button_bg_color'   => 'string[15]',
            'optin_button_fg_color'   => 'string[15]',
            'optin_button_radius'     => 'int',

            'optout_method'           => 'string[15]',
            'optout_button_label'     => 'text',
            'optout_button_image_id'  => 'id',
            'optout_button_bg_color'  => 'string[15]',
            'optout_button_fg_color'  => 'string[15]',
            'optout_button_radius'    => 'int',


//FEATURE: LIMIT PUSH OPTIN TRIES//            'direct_show_count'            => 'int',
//FEATURE: LIMIT PUSH OPTIN TRIES//            'direct_show_interval_seconds' => 'int',

            'use_confirm_dialog'      => 'yes_no_bit',
            'use_exit_popup_dialog'   => 'yes_no_bit',

            'max_exit_popup_count'    => 'int',
            'max_exit_popup_days'     => 'int',

            'confirm_title'           => 'string[127]',
            'confirm_msg'             => 'text',
            'confirm_button_ok'       => 'string[127]',
            'confirm_button_cancel'   => 'string[127]',

            'exit_popup_title'         => 'string[127]',
            'exit_popup_msg'           => 'text',
            'exit_popup_button_ok'     => 'string[127]',
            'exit_popup_button_cancel' => 'string[127]',

       );

       $indexes = array( /*'order_id', 'product_id', 'email'*/ );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function buildObject( $obj )
    {
        parent::buildObject( $obj );
    }


    protected function hasTrash()
    {
        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values[ 'name' ]                     = _digi( 'Default settings' );
        $values[ 'optin_method' ]             = 'button';
        $values[ 'optin_button_label' ]       = _ncore( 'Optin now' );
        $values[ 'optout_button_label' ]      = _ncore( 'Optout now' );


        $values[ 'optin_button_fg_color' ]      = '#FFFFFF';
        $values[ 'optin_button_bg_color' ]      = '#2196F3';
        $values[ 'optout_button_fg_color' ]     = '#FFFFFF';
        $values[ 'optout_button_bg_color' ]     = '#707070';

        $values[ 'optin_button_radius' ]      = '100';
        $values[ 'optout_button_radius' ]     = '100';

        $values[ 'use_confirm_dialog' ]       = 'N';
        $values[ 'use_exit_popup_dialog' ]    = 'N';

        $values[ 'confirm_button_ok' ]        = _ncore( 'Ok' );
        $values[ 'confirm_button_cancel' ]    = _ncore( 'Cancel' );

        $values[ 'exit_popup_button_ok' ]     = _ncore( 'Ok' );
        $values[ 'exit_popup_button_cancel' ] = _ncore( 'Cancel' );

        $values[ 'max_exit_popup_count' ]     = 3;
        $values[ 'max_exit_popup_days' ]      = 7;

//FEATURE: LIMIT PUSH OPTIN TRIES//        $values[ 'direct_show_count' ]            = 1;
//FEATURE: LIMIT PUSH OPTIN TRIES//        $values[ 'direct_show_interval_seconds' ] = 1;

        return $values;
    }

    protected function hasModified()
    {
        return true;
    }


}
