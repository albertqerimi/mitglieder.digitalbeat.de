<?php
/** @var ncore_LoaderCore $load */
$load->controllerBaseClass('admin/base');

/**
 * Class digimember_AdminShortcodeDesignerController
 */
class digimember_AdminShortcodeDesignerController extends ncore_AdminBaseController
{
    /**
     * @param $event
     *
     * @return bool
     */
    public function mustVerifyXssPassword($event)
    {
        return false;
    }

    /**
     * @return bool|mixed
     */
    protected function readAccessGranted()
    {
        return true;
    }

    /**
     * @return string
     */
    protected function pageHeadline()
    {
        return _digi('Shortcode Designer');
    }

    /**
     * @return string
     */
    protected function viewName()
    {
        return 'admin/shortcode_designer';
    }

    /**
     * @return array
     */
    protected function viewData()
    {
        $data = parent::viewData();

        /** @var ncore_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');

        $data['locale'] = substr(get_locale(), 0, 2);
        $data['ajax_url'] = $linkLogic->ajaxUrl('admin/shortcode_designer', 'ajaxRequest');
        $data['avatar_html_code'] = str_replace('\'', '"', ncore_userImage(ncore_userId(), 64));
        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model('logic/html');
        $htmlLogic->loadPackage('shortcode-designer.js');

        return $data;
    }

    /**
     * @param $event
     * @param $response
     */
    protected function handleAjaxEvent($event, $response)
    {
        if ($event === 'ajaxRequest') {
            $json = json_decode(file_get_contents('php://input'), true);
            if (!$json) {
                $this->responseError('Malformed request');
            }

            $action = ncore_retrieve($json, 'action');
            if ($action === 'getShortcodes') {
                $this->action_getShortcodes();
            } else if ($action === 'getPages') {
                $this->action_getPages();
            } else if ($action === 'getProducts') {
                $this->action_getProducts();
            } else if ($action === 'deleteShortcode') {
                $this->action_deleteShortcode(ncore_retrieve($json, 'shortcodeId'));
            } else if ($action === 'saveShortcode') {
                $this->action_saveShortcode(
                    ncore_retrieve($json, 'shortcode'),
                    ncore_retrieve($json, 'html')
                );
            } else {
                $this->responseError('Unknown action');
            }
        } else {
            $this->responseError('Unknown event');
        }
    }

    /**
     * @param array  $shortcode
     * @param string $html
     */
    private function action_saveShortcode($shortcode, $html)
    {
        if (!is_array($shortcode)) {
            $this->responseError('Malformed request');
        }
        $id = ncore_retrieve($shortcode, 'id');
        $name = ncore_retrieve($shortcode, 'name');
        $version = ncore_retrieve($shortcode, 'version');
        $tag = ncore_retrieve($shortcode, 'tag');
        $values = ncore_retrieve($shortcode, 'values');
        $template = ncore_retrieve($shortcode, 'template', '');
        if (
            !is_numeric($id) ||
            !is_numeric($version) ||
            !is_string($name) ||
            !is_string($tag) ||
            strpos($tag, 'ds_') !== 0 ||
            !is_array($values) ||
            !is_string($html)
        ) {
            $this->responseError('Malformed request');
        }

        if ($tag == 'ds_signup') {
            if (!is_array($values['global']['products']) || (is_array($values['global']['products']) && count($values['global']['products']) < 1 )) {
                $this->responseError(_ncore('You have to select at last one product.'));
            }
        }


        $data = [
            'name' => $name,
            'tag' => $tag,
            'version' => $version,
            'values' => json_encode($values),
            'template' => $template,
            'html' => $html,
        ];
        // Is new
        if ($id == -1) {
            $id = $this->model()->create($data);
        } else {
            if (!$this->model()->update($id, $data)) {
                $this->responseError(_digi('Could not find shortcode for id: ' . $id));
            }
        }

        $this->responseSuccess([
            'id' => (int)$id,
            'tag' => '[' . $tag . '_styled id="' . $id . '"]',
        ]);
    }

    private function action_deleteShortcode($shortcodeId)
    {
        if (!is_numeric($shortcodeId)) {
            $this->responseError('Malformed request');
        }

        try {
            $this->responseSuccess([
                'deleted' => $this->model()->delete($shortcodeId),
            ]);
        } catch (Exception $e) {
            $this->api->logError('plugin', $e->getMessage());
            $this->responseError($e->getMessage());
        }
    }

    private function action_getPages()
    {
        $this->api->load->helper('html_input');
        $pages = ncore_resolveOptions('page');

        /** @var ncore_LinkLogic $model */
        $model = ncore_api()->load->model('logic/link');
        $options = [];
        foreach ($pages as $page_id => $label) {
            $url = $model->readPost('page', $page_id);
            $options[$url] = $label;
        }

        $this->responseSuccess($options);
    }

    private function action_getProducts()
    {
        $this->api->load->helper('html_input');
        $products = ncore_resolveOptions('product');

        $this->responseSuccess($products);
    }

    private function action_getShortcodes()
    {
        $this->api->load->helper('date');
        $shortcodes = $this->model()->getAll();
        if (!is_array($shortcodes)) {
            $this->responseError('Database error');
        }
        $this->responseSuccess(array_map(function ($obj) {
            return [
                'id' => (int)$obj->id,
                'name' => $obj->name,
                'tag' => $obj->tag,
                'template' => $obj->template,
                'version' => $obj->version,
                'values' => json_decode($obj->values, true, JSON_NUMERIC_CHECK),
                'created' => ncore_formatDateTime($obj->created),
            ];
        }, $shortcodes));
    }

    private function responseHeader()
    {
        header('Content-Type: application/json');
    }

    /**
     * @param string $error
     */
    private function responseError($error)
    {
        $this->responseHeader();
        echo json_encode([
            'state' => 'error',
            'error' => $error,
        ]);
        die();
    }

    /**
     * @param array $data
     */
    private function responseSuccess($data)
    {
        $this->responseHeader();
        echo json_encode([
            'state' => 'success',
            'data' => $data,
        ]);
        die();
    }

    /**
     * @return digimember_ShortcodeDesignData
     */
    private function model()
    {
        /** @var digimember_ShortcodeDesignData $model */
        $model = $this->api->load->model('data/shortcode_design');
        return $model;
    }
}