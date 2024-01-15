<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminAdvancedFormsListController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
        return _ncore('Forms');
    }

    protected function isTableHidden()
    {
        //TODO pro feature?
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseExams();
        return !$can_use;
    }

    protected function pageInstructions()
    {
        $advancedFormsModel = $this->api->load->model('data/advanced_forms');
        $advancedFormsElementsModel = $this->api->load->model('data/advanced_forms_elements');
        $advancedFormsModel->createTableIfNeeded();
        $advancedFormsElementsModel->createTableIfNeeded();
        $advancedFormsModel->createSampleDataIfNeeded();
        //TODO pro feature?
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features');
        $can_use = $model->canUseForms();
        $instructions = array();
        if (!$can_use) {
            $this->api->load->helper('html_input');
            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model( 'logic/link' );
            $msg = _digi( 'Forms are NOT included in your subscription.' );
            $instructions[] = ncore_htmlAlert('info', $msg, 'info', '', $model->upgradeHint('', $label='', $tag='p' ));
        }

        $linkModel = $this->api->load->model('logic/link');
        $customfields_link = $linkModel->adminMenuLink('customfields');

        $instructions[] = _ncore('Here you manage the DigiMember forms. You can use these forms to collect data from your (potential) customers in a highly individual style. Edit a form to simply add questions about e-mail address, your DigiMember %s or display further information.', $customfields_link).' ';
        $instructions[] = _ncore('For an easy start, we provided you two exemplary DigiMember forms to give you an inspiration on how to use them. You can delete or edit these predefined forms anytime.').' ';

        return $instructions;
    }

    protected function modelPath()
    {
        return 'data/advanced_forms';
    }

    protected function columnDefinitions()
    {

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $edit_url = $model->adminPage( 'advancedforms', array( 'id' => '_ID_' ) );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );
        //$copy_url = $this->actionUrl( 'copy', '_ID_' );

        $this->api->load->model( 'logic/action' );

        return array(

            array(
                'column' => 'name',
                'type' => 'text',
                'label' => _ncore('Name'),
                'search' => 'generic',
                'compare' => 'like',
                'sortable' => true,
                'actions' => array(
                    array(
                        'label' => _ncore('Edit'),
                        'action' => 'edit',
                        'url' => $edit_url,
                        'depends_on' => array(
                            'status' => array(
                                'created',
                                'active',
                                'inactive',
                                'published',
                            ),
                        )
                    ),
                    array(
                        'action' => 'trash',
                        'url' => $trash_url,
                        'label' => _ncore('Move to trash'),
                        'depends_on' => array(
                            'status' => array(
                                'created',
                                'active',
                            )
                        )
                    ),
                    array(
                        'action' => 'restore',
                        'url' => $restore_url,
                        'label' => _ncore('Restore'),
                        'depends_on' => array(
                            'status' =>  'deleted',
                        )
                    ),

                    array(
                        'action' => 'delete',
                        'url' => $delete_url,
                        'label' => _ncore('Delete irrevocably'),
                        'depends_on' => array(
                            'status' =>  'deleted',
                        )
                    ),
                )
            ),
            array(
                'column' => 'id',
                'type' => 'id',
                'label' => _ncore('Id'),
                'sortable' => true,
            ),
            array(
                'column' => 'id',
                'type' => 'shortcode_copy',
                'label' => _digi('Shortcode'),
            ),
            array(
                'column' => 'modified',
                'type' => 'status_date',
                'label' => _ncore('Date'),
                'sortable' => true,
                'status_labels' => $this->model()->statusLabels(),
            )
        );
    }



    protected function viewDefinitions()
    {
        return array(
            array(
                'view' => 'all',
                'where' => array(),
                'label' => _ncore('All')
            ),
            array(
                'view' => 'trash',
                'where' => array(
                    'deleted !=' => null
                ),
                'label' => _ncore('Trash'),
                'no_items_msg' => _ncore('The trash is empty.'),
            )
        );
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings[ 'row_css_column' ] = 'status';
        $settings[ 'default_sorting'] = array( 'name', 'asc' );
        $settings[ 'no_items_msg'] = _ncore('Please add a form first');

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseExams();
        if (!$can_use) {
            return array();
        }

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $new_url = $model->adminPage( 'advancedforms', array( 'id' => 'new' ) );

        return array(
            $this->pageHeadlineActionRec( 'create', $new_url ),
        );
    }

    protected function handleTrash( $elements )
    {
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->moveToTrash( $id );
        }

        $this->actionSuccess( 'trash', $elements );
    }

    protected function handleRestore( $elements )
    {
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->retoreFromTrash( $id );
        }

        $this->actionSuccess( 'restore', $elements );
    }

    protected function handleDelete( $elements )
    {
        $advancedFormsModel = $this->api->load->model('data/advanced_forms');
        $advancedFormsElementsModel = $this->api->load->model('data/advanced_forms_elements');
        $model = $this->model();

        foreach ($elements as $id)
        {
            $where = array(
                "formId" => $id,
            );
            $formElements = $advancedFormsElementsModel->getAll($where);
            foreach ($formElements as $formElement) {
                $advancedFormsElementsModel->delete($formElement->id);
            }
            $model->delete( $id );
        }

        $this->actionSuccess( 'delete', $elements );
    }

    protected function handleCopy( $elements )
    {
        $model = $this->model();

        $created_elements = array();

        foreach ($elements as $id)
        {
            $created_elements[] = $model->copy( $id );
        }

        $this->actionSuccess( 'copy', $created_elements );
    }

    protected function undoAction( $action )
    {
        switch ($action)
        {
            case 'delete':     return false;
            case 'trash':      return 'restore';
            case 'restore':    return 'trash';
            case 'activate':   return 'deactivate';
            case 'deactivate': return 'activate';
            //case 'copy':       return 'delete';
        }

        return parent::undoAction( $action );
    }

    protected function bulkActionDefinitions()
    {
        $this->api->load->model( 'logic/link' );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        return array(

            array(
                'action' => 'trash',
                'url' => $trash_url,
                'label' => _ncore('Move to trash'),
                'views' => array( 'all', 'published', 'drafts' ),
            ),
            array(
                'action' => 'restore',
                'url' => $restore_url,
                'label' => _ncore('Restore'),
                'views' => array( 'trash' ),
            ),

            array(
                'action' => 'delete',
                'url' => $delete_url,
                'label' => _ncore('Delete irrevocably'),
                'views' => array( 'trash' ),
            ),
        );
    }

}

