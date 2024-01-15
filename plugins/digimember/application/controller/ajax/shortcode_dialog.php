<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass('ajax/info');

class digimember_AjaxShortcodeDialogController extends ncore_AjaxInfoController
{
    /**
     * @param array $settings
     */
    public function init($settings = [])
    {
        parent::init($settings);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_page_url(ncore_AjaxResponse $response)
    {
        $responseObj = new stdClass();
        $responseObj->page_url = ncore_siteUrl('index.php?p=');
        $response->setResponseObject($responseObj);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_options(ncore_AjaxResponse $response)
    {
        $options = ncore_retrieveGet('options');
        if (!$options) {
            return [];
        }

        $this->api->load->helper('html_input');
        $options = ncore_resolveOptions($options);
        $responseObj = new stdClass();
        $responseObj->options = $options;
        $response->setResponseObject($responseObj);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_shortcode_metas(ncore_AjaxResponse $response)
    {
        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller('shortcode');
        $metas = $controller->shortcodeMetas();
        $sections = $controller->shortcodeSections();

        $responseObj = new stdClass();
        $responseObj->metas = $metas;
        $responseObj->sections = $sections;
        $response->setResponseObject($responseObj);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_pages(ncore_AjaxResponse $response)
    {

        dm_api()->load->helper('array');
        $pages = ncore_getPages('page');
        $pages = ncore_listToArraySorted($pages, 'ID', 'post_title');
        foreach ($pages as $page_id => $page_label) {
            if (!$page_label) {
                $pages[$page_id] = _ncore('Page %s', '#' . $page_id);
            }
        }

        $responseObj = new stdClass();
        $responseObj->pages = $pages;
        $response->setResponseObject($responseObj);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_products(ncore_AjaxResponse $response)
    {
        return [];
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_menus(ncore_AjaxResponse $response)
    {

        dm_api()->load->helper('array');
        $menus = wp_get_nav_menus();

        if (empty($menus)) {
            $url = ncore_siteUrl('/wp-admin/nav-menus.php');
            $label = _ncore('Design - Menus');
            $link = ncore_htmlLink($url, $label);
            return _ncore('Go to %s and add a menu.', $link);
        }

        $menus = ncore_listToArray($menus, 'slug', 'name');

        $responseObj = new stdClass();
        $responseObj->menus = $menus;
        $response->setResponseObject($responseObj);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_exams(ncore_AjaxResponse $response)
    {
        /** @var digimember_ExamDataModel $model */
        $model = $this->api->load->model('data/exam');
        $options = $model->options();


        $responseObj = new stdClass();
        $responseObj->exams = $options;
        $response->setResponseObject($responseObj);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_exam_certificates(ncore_AjaxResponse $response)
    {
        /** @var digimember_ExamCertificateDataModel $model */
        $model = $this->api->load->model('data/exam_certificate');
        $options = $model->options();


        $responseObj = new stdClass();
        $responseObj->exam_certificates = $options;
        $response->setResponseObject($responseObj);
    }

    /**
     * @param ncore_AjaxResponse $response
     */
    public function handle_get_lecture_or_menus(ncore_AjaxResponse $response)
    {
        /** @var digimember_ProductDataModel $model */
        $model = $this->api->load->model('data/product');
        $product_options = $model->options();
        $menu_options    = ncore_resolveOptions('menu');

        $menu = [];
        $menu['optgroup_products'] = _digi('Lectures of product');
        $menu['product_current'] = _digi('Product of current course');

        foreach ($product_options as $id => $label) {
            $menu["product_$id"] = $label;
        }

        $menu['optgroup_menus'] = _digi('Wordpress menu');

        foreach ($menu_options as $id => $label) {
            $menu["menu_$id"] = $label;
        }

        $responseObj = new stdClass();
        $responseObj->lecture_or_menus = $menu;
        $response->setResponseObject($responseObj);
    }
}
