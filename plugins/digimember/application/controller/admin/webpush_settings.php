<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminWebpushSettingsController extends ncore_AdminFormController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );

        $this->api->load->model( 'data/webpush_settings' );

        /** @var digimember_WebpushLogic $webpushLogic */
        $webpushLogic = $this->api->load->model( 'logic/webpush' );
        $webpushLogic->notifySetupErrors();
    }

    protected function getElementId()
    {
        static $id;

        if (!isset($id))
        {
            /** @var digimember_WebpushSettingsData $webpushSettingsData */
            $webpushSettingsData = $this->api->load->model( 'data/webpush_settings' );
            $config = $webpushSettingsData->getDefaultSettings();
            $id = $config->id;
        }

        return $id;
    }

     protected function pageHeadline()
    {
        return _digi( 'Web push settings' );
    }

    protected function inputMetas()
    {
        /** @var digimember_WebpushSettingsData $webpushSettingsData */
        $webpushSettingsData = $this->api->load->model( 'data/webpush_settings' );
        $id = $this->getElementId();
        $metas = array();

        /*
        $metas[] = array(
            'name'              => 'name',
            'section'           => 'general',
            'type'              => 'text',
            'label'             => _ncore('Name' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );
        */

        $metas[] = array(
            'name' => 'optin_method',
            'section' => 'general',
            'type' => 'select',
            'label' => _digi('Optin method' ),
            'options' => $webpushSettingsData->optinMethodOptions(),
            'element_id'        => $id,
        );

//FEATURE: LIMIT PUSH OPTIN TRIES//        $metas[] = array(
//FEATURE: LIMIT PUSH OPTIN TRIES//            'name' => 'direct_show_count',
//FEATURE: LIMIT PUSH OPTIN TRIES//            'section' => 'general',
//FEATURE: LIMIT PUSH OPTIN TRIES//            'type' => 'select',
//FEATURE: LIMIT PUSH OPTIN TRIES//            'label' => '&bull; '._digi('Request optin' ),
//FEATURE: LIMIT PUSH OPTIN TRIES//            'element_id'        => $id,
//FEATURE: LIMIT PUSH OPTIN TRIES//            'depends_on' => array( 'optin_method' => array( 'direct' ) ),
//FEATURE: LIMIT PUSH OPTIN TRIES//            'options' => array(
//FEATURE: LIMIT PUSH OPTIN TRIES//                '1'   => _digi( 'once' ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                '2'   => _digi( 'twice' ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                '3'   => _digi( '%s times', 3 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                '5'   => _digi( '%s times', 5 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//               '999'  => _digi( 'unlimited times' ),
//FEATURE: LIMIT PUSH OPTIN TRIES//            ),
//FEATURE: LIMIT PUSH OPTIN TRIES//            'in_row_with_next' => true,
//FEATURE: LIMIT PUSH OPTIN TRIES//        );

//FEATURE: LIMIT PUSH OPTIN TRIES//        $metas[] = array(
//FEATURE: LIMIT PUSH OPTIN TRIES//            'name' => 'direct_show_interval_seconds',
//FEATURE: LIMIT PUSH OPTIN TRIES//            'section' => 'general',
//FEATURE: LIMIT PUSH OPTIN TRIES//            'type' => 'select',
//FEATURE: LIMIT PUSH OPTIN TRIES//            'options' => array(
//FEATURE: LIMIT PUSH OPTIN TRIES//                600        => _digi( 'in %s minutes', 10 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                1*3600     => _digi( 'per hour' ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                1*86400    => _digi( 'per day' ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                2*86400    => _digi( 'in %s days', 2 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                3*86400    => _digi( 'in %s days', 3 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                7*86400    => _digi( 'in %s days', 7 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                14*86400   => _digi( 'in %s days', 14 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                30*86400   => _digi( 'in %s month', 1 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//                365*86400  => _digi( 'per %s year', 1 ),
//FEATURE: LIMIT PUSH OPTIN TRIES//            ),
//FEATURE: LIMIT PUSH OPTIN TRIES//            'label' => 'none',
//FEATURE: LIMIT PUSH OPTIN TRIES//            'element_id'        => $id,
//FEATURE: LIMIT PUSH OPTIN TRIES//            'depends_on' => array( 'optin_method' => array( 'direct' ) ),
//FEATURE: LIMIT PUSH OPTIN TRIES//        );

        $metas[] = array(
            'name' => 'optin_button_label',
            'section' => 'general',
            'type' => 'text',
            'label' => '&bull; '._digi('Label' ),
            'element_id'        => $id,
            'depends_on' => array( 'optin_method' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'optin_button_bg_color',
            'section' => 'general',
            'type' => 'color',
            'label' => '&bull; '._digi('Background color' ),
            'element_id'        => $id,
            'depends_on' => array( 'optin_method' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'optin_button_fg_color',
            'section' => 'general',
            'type' => 'color',
            'label' => '&bull; '._digi('Text color' ),
            'element_id'        => $id,
            'depends_on' => array( 'optin_method' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'optin_button_radius',
            'section' => 'general',
            'type' => 'select',
            'options' => 'border_radius',
            'label' => '&bull; '._digi('Corner radius' ),
            'element_id'        => $id,
            'depends_on' => array( 'optin_method' => array( 'button' ) ),
        );


        $metas[] = array(
            'name' => 'optin_button_image_id',
            'section' => 'general',
            'type' => 'image',
            'label' => '&bull; '._digi('Button image' ),
            'element_id'        => $id,
            'depends_on' => array( 'optin_method' => array( 'image' ) ),
        );


        $metas[] = array(
            'name' => 'optout_method',
            'section' => 'general',
            'type' => 'select',
            'label' => _digi('Optout method' ),
            'options' => $webpushSettingsData->optoutMethodOptions(),
            'element_id'        => $id,
        );

        $metas[] = array(
            'name' => 'optout_button_label',
            'section' => 'general',
            'type' => 'text',
            'label' => '&bull; '._digi('Label' ),
            'element_id'        => $id,
            'depends_on' => array( 'optout_method' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'optout_button_bg_color',
            'section' => 'general',
            'type' => 'color',
            'label' => '&bull; '._digi('Background color' ),
            'element_id'        => $id,
            'depends_on' => array( 'optout_method' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'optout_button_fg_color',
            'section' => 'general',
            'type' => 'color',
            'label' => '&bull; '._digi('Text color' ),
            'element_id'        => $id,
            'depends_on' => array( 'optout_method' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'optout_button_radius',
            'section' => 'general',
            'type' => 'select',
            'options' => 'border_radius',
            'label' => '&bull; '._digi('Corner radius' ),
            'element_id'        => $id,
            'depends_on' => array( 'optout_method' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'optout_button_image_id',
            'section' => 'general',
            'type' => 'image',
            'label' => '&bull; '._digi('Optout button image' ),
            'element_id'        => $id,
            'depends_on' => array( 'optout_method' => array( 'image' ) ),
        );

        $html = $this->_renderShortcodeInput();

        $metas[] = array(
            'section' => 'general',
            'type' => 'html',
            'label' => _digi('Shortcode' ),
            'html' => $html,
        );


        $metas[] = array(
            'name' => 'use_confirm_dialog',
            'section' => 'confirm',
            'type' => 'yes_no_bit',
            'label' => _digi('Use confirm dialog' ),
            'default' => 'N',
            'element_id'        => $id,
            'hint' => _digi('Is shown before the user is asked for permission for notifications.' ),
        );

        $metas[] = array(
            'name' => 'confirm_title',
            'section' => 'confirm',
            'type' => 'text',
            'label' => _digi('Dialog Title' ),
            'depends_on' => array(  'use_confirm_dialog' => 'Y' ),
            'element_id'        => $id,
            'rules' => 'trim|required',
        );

        $metas[] = array(
            'name' => 'confirm_msg',
            'section' => 'confirm',
            'type' => 'htmleditor',
            'label' => _digi('Dialog Message' ),
            'depends_on' => array(  'use_confirm_dialog' => 'Y' ),
            'rows' => 6,
            'element_id'        => $id,
            'rules' => 'trim|required',
        );

        $metas[] = array(
            'name' => 'confirm_button_ok',
            'section' => 'confirm',
            'type' => 'text',
            'label' => _digi('Ok button label' ),
            'depends_on' => array(  'use_confirm_dialog' => 'Y' ),
            'element_id'        => $id,
            'rules' => 'trim|required',
        );

        $metas[] = array(
            'name' => 'confirm_button_cancel',
            'section' => 'confirm',
            'type' => 'text',
            'label' => _digi('Cancel button label' ),
            'depends_on' => array(  'use_confirm_dialog' => 'Y' ),
            'element_id'        => $id,
            'rules' => 'trim|required',
        );

        $metas[] = array(
            'name' => 'use_exit_popup_dialog',
            'section' => 'exit_popup',
            'type' => 'yes_no_bit',
            'label' => _digi('Show exit popup' ),
            'default' => 'N',
            'element_id'        => $id,
            'hint' => _digi('Only shown, if the user has <em>not</em> subscribed.' ),
        );

        $metas[] = array(
            'name' => 'exit_popup_title',
            'section' => 'exit_popup',
            'type' => 'text',
            'label' => _digi('Dialog Title' ),
            'depends_on' => array(  'use_exit_popup_dialog' => 'Y' ),
            'element_id'        => $id,
            'rules' => 'trim|required',
        );

        $metas[] = array(
            'name' => 'exit_popup_msg',
            'section' => 'exit_popup',
            'type' => 'htmleditor',
            'label' => _digi('Dialog Message' ),
            'depends_on' => array(  'use_exit_popup_dialog' => 'Y' ),
            'rows' => 6,
            'element_id'        => $id,
            'rules' => 'trim|required',
        );

        $metas[] = array(
            'name' => 'exit_popup_button_ok',
            'section' => 'exit_popup',
            'type' => 'text',
            'label' => _digi('Ok button label' ),
            'depends_on' => array(  'use_exit_popup_dialog' => 'Y' ),
            'element_id'        => $id,
            'rules' => 'trim|required',
        );

        $metas[] = array(
            'name' => 'exit_popup_button_cancel',
            'section' => 'exit_popup',
            'type' => 'text',
            'label' => _digi('Cancel button label' ),
            'depends_on' => array(  'use_exit_popup_dialog' => 'Y' ),
            'element_id'        => $id,
            'rules' => 'trim|required',
        );


        $count_options = array(
            1 => _digi( '1 view' ),
        );

        foreach( array( 2,3,4,5,10 ) as $d)
        {
            $count_options[ $d ] = _digi( '%s views', $d );
        }

        $count_options[ 1000 ] = _digi( 'unlimited views' );

        $day_options = array(
            1 => _digi('per day' ),
        );
        foreach( array( 2,3,4,5,6 ) as $d)
        {
            $day_options[ $d ] = _digi( 'in %s days', $d );
        }
        $day_options[ 7 ] = _digi( 'per week' );
        $day_options[ 14 ] = _digi( 'in 2 weeks' );


        $metas[] = array(
            'name' => 'max_exit_popup_count',
            'section' => 'exit_popup',
            'type' => 'select',
            'label' => _digi('Limit views to' ),
            'depends_on' => array(  'use_exit_popup_dialog' => 'Y' ),
            'element_id'        => $id,
            'options' => $count_options,
            'in_row_with_next' => true,
        );

        $metas[] = array(
            'name' => 'max_exit_popup_days',
            'section' => 'exit_popup',
            'type' => 'select',
            'label' => 'none',
            'depends_on' => array(  'use_exit_popup_dialog' => 'Y' ),
            'element_id'        => $id,
            'options' => $day_options,
        );

        return $metas;
    }


    protected function sectionMetas()
    {
        $metas = array(
            'general' => array(
                            'headline' => _ncore('Settings'),
                            'instructions' => '',
                         ),
            'confirm' => array(
                            'headline' => _digi( 'Confirmation dialog' ),
                            'instructions' => '',
                         ),
            'exit_popup' => array(
                            'headline' => _digi( 'Exit popup' ),
                            'instructions' => '',
                         ),
        );
        return $metas;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        return $metas;
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        /** @var digimember_WebpushSettingsData $model */
        $model = $this->api->load->model( 'data/webpush_settings' );

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
        /** @var digimember_WebpushSettingsData $model */
        $model = $this->api->load->model( 'data/webpush_settings' );

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

    protected function tabs()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $tabs = array();

        $tabs[ 'edit' ] = array(
            'url'   => $model->adminPage( 'webpush'),
            'label' => _ncore( 'Messages' ),
        );

        $tabs[ 'subscriptions' ] = array(
            'url'   => $model->adminPage( 'webpush', array( 'subscriptions' => 'show' ) ),
            'label' => _digi( 'Subscriptions' ),
        );

        $tabs[ 'settings' ] = array(
            'url'   => $model->adminPage( 'webpush', array( 'settings' => 'edit' ) ),
            'label' => _ncore( 'Settings' ),
        );

        return $tabs;
    }

    private function _renderShortcodeInput(){

        $this->api->load->helper( 'html_input' );

        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );
        $tag        = $controller->shortcode('webpush');

        $input_id = ncore_id();

        $code_plain  = '[' .$tag . ']';
        $code_manage = '[' .$tag . ' optout]';

        $input = ncore_htmlTextInputCode( $code_plain, array( 'id' => $input_id ) );

        $options = array(
            'plain'  => _digi( 'Optout button hidden' ),
            'manage' => _digi( 'Optout button shown' ),
        );

        $js = "var val=ncoreJQ(this).val(); var code=(val=='manage'?'$code_manage':'$code_plain'); ncoreJQ('#$input_id').val(code);";

        $select = ncore_htmlSelect( 'dummy', $options, 'plain', array( 'onchange' => $js ) );
        return '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-md-8 dm-col-xs-12">' . $select . '</div>        
    <div class="dm-col-md-4 dm-col-xs-12">' . $input . '</div>        
</div>
';
    }


}