<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass('admin/table');

class digimember_AdminApiKeyListController extends ncore_AdminTableController
{
    protected function tabs()
    {
        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');
        $tabs = [
            'basic' => [
                'label' => _digi('Setup'),
                'url' => $linkLogic->adminPage(''),
            ],
            'advanced' => [
                'label' => _digi('Options'),
                'url' => $linkLogic->adminPage('', ['tab' => 'advanced']),
            ],
            'api_keys' => [
                'label' => _ncore('Api Keys'),
                'url' => $linkLogic->adminPage('', ['api_keys' => 'all']),
            ],
        ];

        return $tabs;
    }

    protected function pageHeadline()
    {
        return _digi('Api Keys');
    }

    protected function isTableHidden()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model('logic/features');
        $can_use = $model->canUseExams();
        return !$can_use;
    }

    protected function pageInstructions()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model('logic/features');
        $can_use = $model->canUseExams();

        $instructions = [
            _ncore("<strong> Hint: </strong>"),
            ncore_linkReplace(_ncore("For more information on \"How to integrate DigiMember with Zapier\" or \"How to setup an example Zap\" please visit our <a>help page</a>."), 'https://digimember-hilfe.de/docs/handbuch/hb-zapier', true),
        ];

        if (!$can_use) {
            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model('logic/link');
            $msg = _digi('API usage is NOT included in your subscription.');
            $instructions[] = $model->upgradeHint($msg, $label = '', $tag = 'p');
        }

        return $instructions;
    }

    protected function modelPath()
    {
        return 'data/api_key';
    }

    protected function columnDefinitions()
    {
        $this->api->load->model('data/exam_certificate');

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model('logic/link');
        $edit_url = $model->adminPage('', ['api_key_id' => '_ID_', 'tab' => 'api_keys']);
        $trash_url = $this->actionUrl('trash', '_ID_');
        $restore_url = $this->actionUrl('restore', '_ID_');
        $delete_url = $this->actionUrl('delete', '_ID_');
        $activate_url = $this->actionUrl('activate', '_ID_');
        $deactivate_url = $this->actionUrl('deactivate', '_ID_');

        return [

            [
                'column' => 'key',
                'type' => 'text',
                'label' => _ncore('Name'),
                'actions' => [
                    [
                        'label' => _ncore('Edit'),
                        'action' => 'edit',
                        'url' => $edit_url,
                        'depends_on' => [
                            'status' => [
                                'created',
                                'active',
                                'inactive',
                                'published',
                            ],
                        ],
                    ],
                    [
                        'action' => 'activate',
                        'url' => $activate_url,
                        'label' => _ncore('Activate'),
                        'depends_on' => [
                            'is_active' => 'N',
                        ],
                    ],
                    [
                        'action' => 'deactivate',
                        'url' => $deactivate_url,
                        'label' => _ncore('Deactivate'),
                        'depends_on' => [
                            'is_active' => 'Y',
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
                'column' => 'is_active',
                'type' => 'yes_no_bit',
                'label' => _ncore('Active'),
            ],
            [
                'column' => 'scope',
                'type' => 'select',
                'options' => [
                    DM_API_KEY_SCOPE_ADMIN => _ncore('Admin'),
                    DM_API_KEY_SCOPE_ZAPIER => _ncore('Zapier'),
                ],
                'label' => _ncore('Permissions'),
            ],
            [
                'column' => 'id',
                'type' => 'id',
                'label' => _ncore('Id'),
            ],
            [
                'column' => 'user_id',
                'type' => 'user',
                'label' => _ncore('Created by'),
                'link' => true,
            ],
            [
                'column' => 'created',
                'type' => 'status_date',
                'label' => _ncore('Created'),
                'status_labels' => $this->model()->statusLabels(),
            ],
        ];
    }

    protected function viewDefinitions()
    {
        return [
            [
                'view' => 'all',
                'where' => [],
                'label' => _ncore('All'),
            ],
            [
                'view' => 'trash',
                'where' => [
                    'deleted !=' => null,
                ],
                'label' => _ncore('Trash'),
                'no_items_msg' => _ncore('The trash is empty.'),
            ],
        ];
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings['row_css_column'] = 'status';
        $settings['no_items_msg'] = _digi('Please add an api key first.');

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model('logic/features');
        $can_use = $model->canUseExams();
        if (!$can_use) {
            return [];
        }

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model('logic/link');

        $new_url = $model->adminPage('', ['api_key_id' => 'new', 'tab' => 'api_keys']);

        return [
            $this->pageHeadlineActionRec('create', $new_url),
        ];
    }

    protected function handleTrash($elements)
    {
        $model = $this->model();

        foreach ($elements as $id) {

            // currently the matching zapier autoresponder data set will only be deactivated not moved to trash
            $where = ['id' => $id, 'scope' => 'Zapier'];
            $result = $model->getWhere($where);
            if ($result) {

                $this->trashZapier($result->{'key'});
            }
            $model->moveToTrash($id);

        }

        $this->actionSuccess('trash', $elements);
    }

    protected function handleRestore($elements)
    {
        $model = $this->model();

        foreach ($elements as $id) {
            $model->retoreFromTrash($id);
            $where = ['id' => $id, 'scope' => 'Zapier'];
            $result = $model->getWhere($where);

            if ($result) {
                $this->restoreZapier($result->{'key'});
            }
        }

        $this->actionSuccess('restore', $elements);
    }

    protected function handleDelete($elements)
    {
        $model = $this->model();

        foreach ($elements as $id) {
            $where = ['deleted !=' => null, 'id' => $id, 'scope' => 'Zapier'];
            $result = $model->getWhere($where);
            if ($result) {
                $this->deleteZapier($result->{'key'});
            }
            $model->delete($id);
        }

        $this->actionSuccess('delete', $elements);
    }

    protected function handleActivate($elements)
    {
        $model = $this->model();

        foreach ($elements as $id) {
            $model->update($id, [
                'is_active' => 'Y',
            ]);
            $where = ['id' => $id, 'scope' => 'Zapier'];
            $result = $model->getWhere($where);
            if ($result) {
                $this->reactivateZapier($result->{'key'});
            }
        }

        $this->actionSuccess('activate', $elements);
    }

    protected function handleDeactivate($elements)
    {
        $model = $this->model();

        foreach ($elements as $id) {
            $model->update($id, [
                'is_active' => 'N',
            ]);
            $where = ['id' => $id, 'scope' => 'Zapier'];
            $result = $model->getWhere($where);
            if ($result) {
                $this->deactivateZapier($result->{'key'});
            }
        }

        $this->actionSuccess('deactivate', $elements);
    }

    protected function undoAction($action)
    {
        switch ($action) {
            case 'delete':
                return false;
            case 'trash':
                return 'restore';
            case 'restore':
                return 'trash';
            case 'activate':
                return 'deactivate';
            case 'deactivate':
                return 'activate';
        }

        return parent::undoAction($action);
    }

    protected function bulkActionDefinitions()
    {
        $trash_url = $this->actionUrl('trash', '_ID_');
        $restore_url = $this->actionUrl('restore', '_ID_');

        $delete_url = $this->actionUrl('delete', '_ID_');

        return [

            [
                'action' => 'trash',
                'url' => $trash_url,
                'label' => _ncore('Move to trash'),
                'views' => ['all', 'published', 'drafts'],
            ],
            [
                'action' => 'restore',
                'url' => $restore_url,
                'label' => _ncore('Restore'),
                'views' => ['trash'],
            ],

            [
                'action' => 'delete',
                'url' => $delete_url,
                'label' => _ncore('Delete irrevocably'),
                'views' => ['trash'],
            ],
        ];
    }

    protected function handleRequest()
    {
        $_REQUEST['ids'] = ncore_retrieve($_REQUEST, 'api_key_ids');
        parent::handleRequest();
        unset($_GET['ids']);
    }

    protected function actionUrl($action, $element_ids, $extra_params = [])
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model('logic/link');

        if (is_array($element_ids)) {
            $element_ids = implode(',', $element_ids);
        }

        $page = ncore_retrieve($_REQUEST, 'page');

        $name = ncore_XssVariableName();
        $pw = ncore_XssPassword();

        $action = ncore_washText($action);
        $element_ids = ncore_washText($element_ids, ',');

        $action_params = ['api_key_ids' => $element_ids, 'action' => $action, $name => $pw, 'api_keys' => 'all'];

        $currentUrlArgs = $this->currentUrlArgs();

        $params = array_merge($this->actionUrlExtraArgs(), $extra_params, $action_params, $currentUrlArgs);

        return $this->isNetworkController()
            ? $model->networkPage($page, $params)
            : $model->adminPage($page, $params);
    }

    /**
     * for a given api key, all matching autoresponder setttings get set to "disabled"
     * @param $key
     * @return bool
     */
    protected function deactivateZapier($key)
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model('data/autoresponder');

        // filters all currently available Zapier Autorespondersets
        $results = $model->getAll(['engine' => 'zapier']);

        foreach ($results as $result) {
            $data = unserialize($result->{'data_serialized'});
            // checks if the found data contains settings for the currently deactivated api key
            if ($data['zapier_api_key'] == $key) {
                // the autoresponder gets disabled too
                $model->update($result, [
                    'is_active' => 'N',
                ]);
            }
        }
        return true;
    }

    /**
     * for a given api key, all matching autoresponder setttings get set to "enabled"
     * @param $key
     * @return bool
     */
    protected function reactivateZapier($key)
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model('data/autoresponder');

        // filters all currently available Zapier Autorespondersets
        $results = $model->getAll(['engine' => 'zapier']);

        foreach ($results as $result) {
            $data = unserialize($result->{'data_serialized'});
            // checks if the found data contains settings for the currently activated api key
            if ($data['zapier_api_key'] == $key) {
                // the autoresponder gets enabled too
                $model->update($result, [
                    'is_active' => 'Y',
                ]);
            }
        }
        return true;
    }

    /**
     * for a given api key, all matching autoresponder setttings are moved to trash
     * @param $key
     * @return bool
     */
    protected function deleteZapier($key)
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model('data/autoresponder');

        // filters all currently available Zapier Autorespondersets
        $results = $model->getAll(['deleted !=' => null, 'engine' => 'zapier']);

        foreach ($results as $result) {
            $data = unserialize($result->{'data_serialized'});
            // checks if the found data contains settings for the currently activated api key
            if ($data['zapier_api_key'] == $key) {
                // the autoresponder gets deleted
                $model->delete($result->{'id'});
            }
        }
        return true;
    }

    /**
     * for a given api key, all matching autoresponder setttings are moved to trash
     * @param $key
     * @return bool
     */
    protected function trashZapier($key)
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model('data/autoresponder');

        // filters all currently available Zapier Autorespondersets
        $results = $model->getAll(['engine' => 'zapier']);

        foreach ($results as $result) {
            $data = unserialize($result->{'data_serialized'});
            // checks if the found data contains settings for the currently activated api key
            if ($data['zapier_api_key'] == $key) {
                // the autoresponder gets deleted
                $model->moveToTrash($result->{'id'});
            }
        }
        return true;
    }

    /**
     * for a given api key, all matching autoresponder setttings are restored from trash
     * @param $key
     * @return bool
     */
    protected function restoreZapier($key)
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model('data/autoresponder');

        // filters all currently available Zapier Autorespondersets
        $results = $model->getAll(['deleted !=' => null, 'engine' => 'zapier']);

        foreach ($results as $result) {
            $data = unserialize($result->{'data_serialized'});
            // checks if the found data contains settings for the currently activated api key
            if ($data['zapier_api_key'] == $key) {
                // the autoresponder gets restored
                $model->retoreFromTrash($result->{'id'});
            }
        }
        return true;
    }

}

