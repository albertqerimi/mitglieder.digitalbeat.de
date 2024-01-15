<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminActionListController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _ncore('Actions');
    }

    protected function pageInstructions()
    {
        $instructions = array();

        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseActions();

        if (!$can_use) {
            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model( 'logic/link' );

            $msg = _digi( 'KlickTipp tagging by user actions is NOT included in your subscription.' );

            $instructions[] = $model->upgradeHint( $msg, $label='', $tag='p' );
        }

        $instructions[] = _digi( 'When your customers perform actions like visiting a page or logging in, you can give Autoresponder-Tags for actions.' );

        return $instructions;
    }

    protected function tabs()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $tabs = array();

        $tabs[ 'list' ] = _ncore( 'Edit' );

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

    protected function modelPath()
    {
        return 'data/action';
    }

    protected function isTableHidden()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseActions();
        return !$can_use;
    }

    protected function columnDefinitions()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $edit_url = $model->adminPage( 'actions', array( 'id' => '_ID_' ) );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $activate_url = $this->actionUrl( 'activate', '_ID_' );
        $deactivate_url = $this->actionUrl( 'deactivate', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );
        $copy_url = $this->actionUrl( 'copy', '_ID_' );

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
                    'label' => _ncore('Copy'),
                    'action' => 'copy',
                    'url' => $copy_url,
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
                        'action' => 'activate',
                        'url' => $activate_url,
                        'label' => _ncore('Activate'),
                        'depends_on' => array(
                            'is_active' => array(
                                'N',
                            ),
                            'status' => array(
                                'created',
                                'active',
                                'inactive',
                                'published',
                            ),
                        )
                    ),
                   array(
                        'action' => 'deactivate',
                        'url' => $deactivate_url,
                        'label' => _ncore('Deactivate'),
                        'depends_on' => array(
                           'is_active' => array(
                                'Y',
                            ),
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
                           'is_active' => array(
                                'status' =>  'deleted',
                            )
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
                'column' => 'condition_type',
                'type' => 'array',
                'array' => $this->api->action_logic->conditionOptions(),
                'label' => _ncore('Action will be triggered' ),
            ),

            array(
                'column' => 'condition_product_ids_comma_seperated',
                'type' => 'id_list',
                'label' => _ncore('Products'),
                'sortable' => false,
                'model' => 'data/product',
                'api' => 'dm_api',
                'name_column' => 'name',
                'void_value' => '<em>(' . _ncore('none') . ')</em>',
            ),

            array(
                'column' => 'is_active',
                'type' => 'yes_no_bit',
                'label' => _ncore('Active'),
                'sortable' => true,
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

    protected function bulkActionDefinitions()
    {
        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $activate_url = $this->actionUrl( 'activate', '_ID_' );
        $deactivate_url = $this->actionUrl( 'deactivate', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        return array(
                 array(
                        'action' => 'activate',
                        'url' => $activate_url,
                        'label' => _ncore('Activate'),
                        'views' => array( 'all', 'inactive' ),
                    ),

                    array(
                        'action' => 'deactivate',
                        'url' => $deactivate_url,
                        'label' => _ncore('Deactivate'),
                        'views' => array( 'all', 'active' ),
                    ),

                    array(
                        'action' => 'trash',
                        'url' => $trash_url,
                        'label' => _ncore('Move to trash'),
                        'views' => array( 'all', 'active', 'inactive' ),
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

    protected function viewDefinitions()
    {
        return array(
            array(
                'view' => 'all',
                'where' => array(),
                'label' => _ncore('All')
            ),
            array(
                'view' => 'active',
                'where' => array(
                    'is_active' => 'Y'
                ),
                'label' => _ncore('Active')
            ),
            array(
                'view' => 'inactive',
                'where' => array(
                    'is_active' => 'N'
                ),
                'label' => _ncore('Inactive'),
                'no_items_msg' => _ncore('No inactive actions found.'),
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
        $settings[ 'no_items_msg'] = _digi('Please add an action first.');

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseActions();
        if (!$can_use) {
            return array();
        }

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $new_url = $model->adminPage( 'actions', array( 'id' => 'new' ) );

        return array(
                $this->pageHeadlineActionRec( 'create', $new_url ),
        );
    }

    protected function handleDeactivate( $elements )
    {
        $model = $this->model();

        $data = array(
            'is_active' => 'N',
        );

        foreach ($elements as $id)
        {
            $model->update( $id, $data );
        }

        $this->actionSuccess( 'deactivate', $elements );
    }

    protected function handleActivate( $elements )
    {
        $model = $this->model();

        $data = array(
            'is_active' => 'Y',
        );

        foreach ($elements as $id)
        {
            $model->update( $id, $data );
        }

        $this->actionSuccess( 'activate', $elements );
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
        $model = $this->model();

        foreach ($elements as $id)
        {
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
            case 'delete': return false;
            case 'trash': return 'restore';
            case 'restore': return 'trash';
            case 'activate': return 'deactivate';
            case 'deactivate': return 'activate';
            case 'copy': return 'delete';
        }

        return parent::undoAction( $action );
    }

}

