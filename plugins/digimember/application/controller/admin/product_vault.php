<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminVaultEditController extends ncore_AdminFormController
{
    protected function pageHeadline()
    {
        return _digi( 'File vault' );
    }

    protected function inputMetas()
    {
        $api = $this->api;
        $api->load->model( 'data/product' );
        $api->load->model( 'data/file_vault' );
        $api->load->model( 'data/autoresponder' );

        $id = $this->getElementId();

        $type_options = $api->file_vault_data->typeOptions();

        $metas = array();

        $metas[] = array(
            'name' => 'name',
            'section' => 'general',
            'type' => 'text',
            'label' => _digi('Name' ),
            'rules' => 'defaults|trim|required',
            'element_id' => $id,
        );

        $metas[] = array(
                'name' => 'id',
                'section' => 'general',
                'type' => 'int',
                'label' => _digi('Id' ),
                'element_id' => $id,
                'rules' => 'readonly',
            );

        $metas[] = array(
                'name' => 'is_active',
                'section' => 'general',
                'type' => 'yes_no_bit',
                'label' => _digi('Active' ),
                'element_id' => $id,
            );


        $metas[] = array(
                'name' => 'type',
                'section' => 'general',
                'type' => 'select',
                'options' => $type_options,
                'label' => _digi('File type' ),
                'element_id' => $id,
            );


        $metas[] = array(
                'depends_on' => array( 'type' => 'url' ),
                'name' => 'source_url',
                'section' => 'general',
                'type' => 'url',
                'label' => _digi('File URL' ),
                'hint' => _digi( 'The file URL is kept secret from the user.' ),
                'element_id' => $id,
            );

        $metas[] = array(
                'depends_on' => array( 'type' => 'upload' ),
                'name' => 'source_file',
                'section' => 'general',
                'type' => 'upload',
                'label' => _digi('Upload file' ),
                'element_id' => $id,
            );

        $metas[] = array(
                'name' => 'available_days',
                'section' => 'general',
                'type' => 'int',
                'label' => _digi('Download limit' ),
                'unit' => _digi('days') . ' ' . _digi( 'or' ) . ' ',
                'in_row_with_next' => true,
                'display_zero_as' => '',
                'hint' => _digi( 'Leave blank for no limit.' ),
                'element_id' => $id,
            );

        $metas[] = array(
                'name' => 'available_times',
                'section' => 'general',
                'type' => 'int',
                'label' => 'none',
                'unit' => _digi('times'),
                'display_zero_as' => '',
                'element_id' => $id,
            );



        $metas[] = array(
                'name' => 'filename',
                'section' => 'general',
                'type' => 'text',
                'label' => _digi('File name'),
                'element_id' => $id,
            );


        return $metas;
    }

    protected function buttonMetas()
    {
        $id = $this->getElementId();

        $metas = parent::buttonMetas();

        $link = $this->api->link_logic->adminPage( 'actions' );

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

        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        $model = $this->api->load->model( 'data/file_vault' );

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
        $model = $this->api->load->model( 'data/file_vault' );

        $have_id = is_numeric( $id ) && $id > 0;

        $subdata = array();

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