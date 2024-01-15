<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass('admin/form');

class digimember_AdminApiKeyEditController extends ncore_AdminFormController
{
    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted()) {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model('logic/features');
        return $model->canUseActions();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted()) {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model('logic/features');
        return $model->canUseActions();
    }

    protected function pageHeadline()
    {
        return _ncore('Api Keys');
    }

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
                'label' => _digi('Api Keys'),
                'url' => $linkLogic->adminPage('', ['api_keys' => 'all']),
            ],
        ];

        return $tabs;
    }

    protected function inputMetas()
    {
        $id = $this->getElementId();

        $metas = [];

        $metas[] = [
            'name' => 'id',
            'section' => 'general',
            'type' => 'int',
            'label' => _ncore('Id'),
            'element_id' => $id,
            'rules' => 'readonly',
        ];

        $metas[] = [
            'name' => 'key',
            'section' => 'general',
            'type' => 'text',
            'label' => _digi('Api Key'),
            'element_id' => $id,
            'rules' => 'readonly',
        ];

        $metas[] = [
            'name' => 'is_active',
            'section' => 'general',
            'type' => 'yes_no_bit',
            'label' => _ncore('Active'),
            'element_id' => $id,
        ];

        $metas[] = [
            'name' => 'scope',
            'section' => 'general',
            'type' => 'select',
            'options' => [
                DM_API_KEY_SCOPE_ADMIN => _ncore('Admin'),
                DM_API_KEY_SCOPE_ZAPIER => _ncore('Zapier'),
            ],
            'label' => _digi('Permissions'),
            'element_id' => $id,
        ];

        return $metas;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');

        $metas[] = [
            'type' => 'link',
            'label' => _ncore('Back'),
            'url' => $linkLogic->adminPage('', ['api_keys' => 'all', 'tab' => 'api_keys']),
        ];

        return $metas;
    }

    protected function sectionMetas()
    {
        return [
            'general' => [
                'headline' => _ncore('Settings'),
                'instructions' => '',
            ],
        ];
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return [$id];
    }

    protected function getData($id)
    {
        /** @var digimember_ApiKeyData $apiKeyData */
        $apiKeyData = $this->api->load->model('data/api_key');

        $have_id = is_numeric($id) && $id > 0;

        $obj = $have_id
            ? $apiKeyData->get($id)
            : $apiKeyData->emptyObject();

        if (!$obj) {
            $this->formDisable(_ncore('The element has been deleted.'));
            return false;
        }

        return $obj;
    }

    protected function setData($id, $data)
    {
        /** @var digimember_ApiKeyData $apiKeyData */
        $apiKeyData = $this->api->load->model('data/api_key');

        $have_id = is_numeric($id) && $id > 0;

        if ($have_id) {
            $is_modified = $apiKeyData->update($id, $data);
        } else {
            $id = $apiKeyData->create($data);

            $this->setElementId($id);

            $is_modified = (bool)$id;
        }

        return $is_modified;
    }

    protected function getElementId()
    {
        $have_id = !empty($this->element_id)
            && is_numeric($this->element_id);
        if (!$have_id) {
            $id = ncore_retrieve($_GET, 'api_key_id', 0);
            if (is_numeric($id) && $id > 0) {
                $this->element_id = $id;
                $have_id = true;
            }
        }
        if (!$have_id) {
            $id = ncore_retrieve($_POST, 'ncore_element_id', 0);
            if (is_numeric($id) && $id > 0) {
                $this->element_id = $id;
            }
        }

        return $this->element_id;
    }

    protected function formActionUrl()
    {
        $this->api->load->helper('url');

        $action_url = parent::formActionUrl();

        $id = $this->getElementId();

        if ($id) {

            $args = ['api_key_id' => $id];

            return ncore_addArgs($action_url, $args);
        } else {
            return $action_url;
        }
    }
}