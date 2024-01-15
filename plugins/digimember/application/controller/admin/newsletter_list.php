<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminNewsletterListController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _ncore('Autoresponder');
    }

    protected function pageInstructions()
    {
        $message = _ncore( 'Here you can have your customers automatically added to an email software (autoresponder), that will sent follow up emails. This setting is optional.' );
        return array(
            $message
        );
    }

    protected function modelPath()
    {
        return 'data/autoresponder';
    }

    protected function columnDefinitions()
    {
        $lib = $this->api->load->library( 'autoresponder_handler' );
        // all available autoresponder engines except for Zapier
        $engine_options = array_diff($lib->getProviders(), ['zapier'=>'Zapier']);

        $model = $this->api->load->model( 'logic/link' );
        $edit_url = $model->adminPage( 'newsletter', array( 'id' => '_ID_' ) );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $activate_url = $this->actionUrl( 'activate', '_ID_' );
        $deactivate_url = $this->actionUrl( 'deactivate', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        $have_digimember = $this->api->havePlugin( 'digimember' );

        $return_array = [

            [
                'column' => 'engine',
                'type' => 'array',
                'array' => $engine_options,
                'label' => _ncore('Autoresponder Type'),
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
                'column' => 'product_ids_comma_seperated',
                'type' => 'id_list',
                'label' => _ncore('Products'),
                'sortable' => false,
                'model' => 'data/product',
                'api' => 'dm_api',
                'name_column' => 'name',
                'search' => 'generic',
                'void_value' => '<em>(' . _ncore('none') . ')</em>',
                'hide' => !$have_digimember,
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
        $model = $this->api->load->model( 'logic/link' );

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
                'where' => ['engine !=' => 'zapier'],
                'label' => _ncore('All'),
            ],
            [
                'view' => 'active',
                'where' => [
                    'is_active' => 'Y', 'engine !=' => 'zapier',
                ],
                'label' => _ncore('Active'),
            ],
            [
                'view' => 'inactive',
                'where' => [
                    'is_active' => 'N', 'engine !=' => 'zapier',
                ],
                'label' => _ncore('Inactive'),
                'no_items_msg' => _ncore('No inactive autoresponders found.'),
            ],
            [
                'view' => 'trash',
                'where' => [
                    'deleted !=' => null, 'engine !=' => 'zapier',
                ],
                'label' => _ncore('Trash'),
                'no_items_msg' => _ncore('No autoresponders found in trash.'),
            ],
        ];
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings[ 'row_css_column' ] = 'status';
        $settings[ 'default_sorting'] = array( 'engine', 'asc' );
        $settings[ 'no_items_msg'] = _ncore('Please add a new autoresponder first.');

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        $model = $this->api->load->model( 'logic/link' );

        $new_url = $model->adminPage( 'newsletter', array( 'id' => 'new' ) );

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
        $arcfModel = $this->api->load->model('data/arcf_links');
        $arcfModel->createTableIfNeeded();
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->moveToTrash( $id );
            $links = $arcfModel->getAllForAr($id);
            foreach ($links as $link) {
                $arcfModel->delete($link->id);
            }
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
        $arcfModel = $this->api->load->model('data/arcf_links');
        $arcfModel->createTableIfNeeded();
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->delete( $id );
            $links = $arcfModel->getAllForAr($id);
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
                    return _ncore( 'Deleted one autoresponder irrevocably.' );
                else
                    return _ncore( 'Deleted %s autoresponders irrevocably.', $count );

            case 'trash':
                if ($count==1)
                    return _ncore( 'Moved one autoresponder to trash.' );
                else
                    return _ncore( 'Moved %s autoresponders to trash.', $count );

            case 'restore':
                if ($count==1)
                    return _ncore( 'Restored one autoresponder from trash.' );
                else
                    return _ncore( 'Restored %s autoresponders from trash.', $count );

            case 'activate':
                if ($count==1)
                    return _ncore( 'Activated one autoresponder.' );
                else
                    return _ncore( 'Activated %s autoresponders.', $count );

            case 'deactivate':
                if ($count==1)
                    return _ncore( 'Deactivated one autoresponder.' );
                else
                    return _ncore( 'Deactivated %s autoresponders.', $count );
        }

        return parent::actionSuccessMessage( $action, $count );
    }
}

