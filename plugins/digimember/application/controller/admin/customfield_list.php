<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminCustomfieldListController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _ncore('Custom Fields');
    }

    protected function pageInstructions()
    {
        $message = '';
        $customFieldsModel = $this->api->load->model('data/custom_fields');
        $customFieldsModel->createTableIfNeeded();
        $message .= _ncore('Here you can manage your own, custom defined fields to collect additional data from your users.').' ';
        $message .= _ncore('You can use this fields in the shortcodes ds_account (Account management for users) or ds_signup (registration form).').' ';
        $message .= _ncore('You can display all or individual custom fields with the shortcode ds_customfield.');
        $message .= _ncore('The collected data you can find in the %s. <br>For a better start we predefined some fields for you. You can edit or delete them anytime.','<a href="'.ncore_getUserManagementPage().'">'._ncore('Wordpress account management').'</a>').' ';
        $message .= _ncore('You can create new fields over the button ”add new”.');

        return array(
            $message
        );
    }

    protected function modelPath()
    {
        return 'data/custom_fields';
    }

    protected function columnDefinitions()
    {
        $customfieldsModel = $this->api->load->model('data/custom_fields');
        $model = $this->api->load->model( 'logic/link' );
        $edit_url = $model->adminPage( 'customfields', array( 'id' => '_ID_' ) );
        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );
        $activate_url = $this->actionUrl( 'activate', '_ID_' );
        $deactivate_url = $this->actionUrl( 'deactivate', '_ID_' );
        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        $return_array = [

            [
                'column' => 'label',
                'type' => 'text',
                'label' => _ncore('Label'),
                'search' => 'generic',
                'compare' => 'like',
                'sortable' => true,
                'actions' => [
                    [
                        'label' => _ncore('Edit'),
                        'action' => 'edit',
                        'url' => $edit_url,
                    ],
                    [
                        'action' => 'activate',
                        'url' => $activate_url,
                        'label' => _ncore('Activate'),
                        'depends_on' => [
                            'is_active' => [
                                'N',
                            ],
                        ],
                    ],
                    [
                        'action' => 'deactivate',
                        'url' => $deactivate_url,
                        'label' => _ncore('Deactivate'),
                        'depends_on' => [
                            'is_active' => [
                                'Y',
                            ],
                        ],
                    ],

                    [
                        'action' => 'trash',
                        'url' => $trash_url,
                        'label' => _ncore('Move to trash'),
                        'depends_on' => [
                            'status' => [
                                'created',
                                'active',
                            ],
                        ],
                    ],
                    [
                        'action' => 'restore',
                        'url' => $restore_url,
                        'label' => _ncore('Restore'),
                        'depends_on' => [
                            'status' => 'deleted',
                        ],
                    ],

                    [
                        'action' => 'delete',
                        'url' => $delete_url,
                        'label' => _ncore('Delete irrevocably'),
                        'depends_on' => [
                            'status' => 'deleted',
                        ],
                    ],
                ],
            ],
            [
                'column' => 'id',
                'type' => 'id',
                'label' => _ncore('Id'),
                'sortable' => true,
            ],

            [
                'column' => 'position',
                'type' => 'id',
                'label' => 'Position',
                'sortable' => true,
            ],

            [
                'column' => 'type',
                'type' => 'function',
                'label' => _ncore('Field type'),
                'model' => 'data/custom_fields',
                'function' => array($customfieldsModel,'mapFieldTypeLabel'),
                'search' => 'generic',
                'compare' => 'like',
            ],

            [
                'column' => 'is_active',
                'type' => 'upgrade_info',
                'label' => _ncore('Active'),
                'sortable' => true,
            ],

            [
                'column' => 'modified',
                'type' => 'status_date',
                'label' => _ncore('Date'),
                'sortable' => true,
                'status_labels' => $this->model()->statusLabels(),
            ],
        ];

        return $return_array;
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

    /**
     * Returns the content of the Autoresponder table, Zapier is never displayed 
     * @return array
     */
    protected function viewDefinitions()
    {
        return [
            [
                'view' => 'all',
                'label' => _ncore('All'),
            ],
//            [
//                'view' => 'generalsection',
//                'where' => [
//                    'section' => 'general',
//                ],
//                'label' => _ncore('General fields'),
//            ],
            [
                'view' => 'accountsection',
                'where' => [
                    'section' => 'account',
                ],
                'label' => _ncore('Account data'),
            ],
            [
                'view' => 'pollsection',
                'where' => [
                    'section' => 'poll',
                ],
                'label' => _ncore('Form data'),
            ],
            [
                'view' => 'inactive',
                'where' => [
                    'is_active' => 'N',
                ],
                'label' => _ncore('Inactive'),
                'no_items_msg' => _ncore('No inactive fields found.'),
            ],
            [
                'view' => 'trash',
                'where' => [
                    'deleted !=' => null,
                ],
                'label' => _ncore('Trash'),
                'no_items_msg' => _ncore('No deleted fields found.'),
            ],
        ];
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();
        $settings[ 'row_css_column' ] = 'status';
        $settings[ 'default_sorting'] = array( 'position', 'asc' );
        $settings[ 'no_items_msg'] = _ncore('Please create a field first.');
        return $settings;
    }

    protected function pageHeadlineActions()
    {
        $model = $this->api->load->model( 'logic/link' );
        $new_url = $model->adminPage( 'customfields', array( 'id' => 'new' ) );
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
        $userSettingsModel = $this->api->load->model('data/user_settings');
        $arcfModel = $this->api->load->model('data/arcf_links');
        $arcfModel->createTableIfNeeded();
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->delete( $id );
            $userSettingsModel->setForName('customfield_'.$id, '');
            $links = $arcfModel->getAllForCf($id);
            foreach ($links as $link) {
                $arcfModel->delete($link->id);
            }

        }

        $this->actionSuccess( 'delete', $elements );
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
        }

        return parent::undoAction( $action );
    }

    protected function actionSuccessMessage( $action, $count )
    {
        switch ($action)
        {
            case 'delete':
                if ($count==1)
                    return _ncore( 'Deleted one custom field irrevocably.' );
                else
                    return _ncore( 'Deleted %s custom fields irrevocably.', $count );

            case 'trash':
                if ($count==1)
                    return _ncore( 'Moved one custom field to trash.' );
                else
                    return _ncore( 'Moved %s custom fields to trash.', $count );

            case 'restore':
                if ($count==1)
                    return _ncore( 'Restored one custom field from trash.' );
                else
                    return _ncore( 'Restored %s custom fields from trash.', $count );

            case 'activate':
                if ($count==1)
                    return _ncore( 'Activated one custom field.' );
                else
                    return _ncore( 'Activated %s custom fields.', $count );

            case 'deactivate':
                if ($count==1)
                    return _ncore( 'Deactivated one custom field.' );
                else
                    return _ncore( 'Deactivated %s custom fields.', $count );
        }

        return parent::actionSuccessMessage( $action, $count );
    }
}

