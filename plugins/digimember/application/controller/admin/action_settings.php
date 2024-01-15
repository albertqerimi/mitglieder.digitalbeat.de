<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminActionSettingsController extends ncore_AdminFormController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }


    protected function tabs()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $tabs = array();

        $tabs[ 'list' ] = array(
            'url' => $model->adminPage( 'actions'  ),
            'label' => _ncore( 'Edit' ),
        );

        $tabs[ 'log' ] = array(
            'url' => $model->adminPage( 'actions', array( 'log' => 'all' ) ),
            'label' => _ncore( 'Log' ),
        );

        $tabs[ 'settings' ] = array(
             'url'  => $model->adminPage( 'actions', array( 'cfg' => 'all' ) ),
            'label' => _ncore( 'Settings' ),
        );

        return $tabs;
    }


    protected function getElementId()
    {
        return 0;
    }

     protected function pageHeadline()
    {
        return _digi( 'Action settings' );
    }

    protected function inputMetas()
    {
        $this->api->load->helper( 'html_input' );

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
            'name'        => 'email_imprint',
            'section'     => 'email',
            'type'        => 'htmleditor',
            'label'       => _digi('Imprint' ),
            'element_id'  => $id,
            'rows'        => 5,
            'hint'        => _digi( 'This text will be appended to each action email send by %s.', $this->api->pluginDisplayName() )
                          . '<br />'
                          . _digi( 'For legal reasons add your business name and address and a phone number.' ),
        );

        $find = array( '<a>', '</a>' );
        $repl = array( '[UNSUBSCRIBE]', '[/UNSUBSCRIBE]' );
        $shortcode = str_replace( $find, $repl, _dgyou( '<a>Click here</a> to unsubscribe.' ) );
        $placeholder_html = '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-md-12 dm-col-xs-12 dm-placeholder-name dm-color-coral">' . $shortcode . '</div>
</div>
';

        $metas[] = array(
            'section'           => 'email',
            'type'              => 'html',
            'label'             => _digi('Placeholder' ),
            'html'              => $placeholder_html,
            'element_id'        => $id,
        );

        $metas[] = array(
            'section'           => 'email',
            'name'              => 'unsubscribe_ty_page',
            'type'              => 'page_or_url',
            'label'             => _digi('Unsubscribe thankyou page' ),
            'element_id'        => $id,
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
            'email' => array(
                            'headline' => _digi( 'Sending email' ),
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
        /** @var digimember_BlogConfigLogic $blogConfig */
        $blogConfig = $this->api->load->model( 'logic/blog_config' );
        /** @var digimember_ActionLogic $actionLogic */
        $actionLogic = $this->api->load->model( 'logic/action' );

        $default_email_imprint = $actionLogic->defaultEmailImprint();

        $data = array(
            'email_imprint'       => $blogConfig->get( 'action_email_imprint', $default_email_imprint ),
            'unsubscribe_ty_page' => $blogConfig->get( 'action_unsubscribe_ty_page', site_url() ),
        );

        return (object) $data;
    }

    protected function setData( $id, $data )
    {
        /** @var digimember_BlogConfigLogic $blogConfig */
        $blogConfig = $this->api->load->model( 'logic/blog_config' );

        $keys_to_store = array(
            'email_imprint'       => 'action_email_imprint',
            'unsubscribe_ty_page' => 'action_unsubscribe_ty_page',
        );

        $is_modified = false;
        foreach ($keys_to_store as $form_key => $config_key)
        {
            if (isset( $data[$form_key ] ))
            {
                $is_one_modified = $blogConfig->set( $config_key, $data[$form_key ] );
                if ($is_one_modified)
                {
                    $is_modified = true;
                }
            }
        }

        return $is_modified;
    }
}