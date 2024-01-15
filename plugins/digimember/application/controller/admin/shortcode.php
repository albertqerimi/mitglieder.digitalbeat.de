<?php

$load->controllerBaseClass( 'admin/base' );

class digimember_AdminShortcodeController extends ncore_AdminBaseController
{
	protected function pageHeadline()
	{
		return _digi( 'Shortcodes' );
	}

	protected function viewName()
	{
		return 'admin/shortcode';
	}

	protected function viewData()
	{
	    $shortcodesModel = $this->api->load->model('data/shortcodes');
	    $shortcodesModel->createTableIfNeeded();
		$data = parent::viewData();

		/** @var digimember_ShortCodeController $controller */
		$controller = $this->api->load->controller( 'shortcode' );

		$data['renderer'] = $controller;

		$data['demo_slug'] = '/some_url';
		$data['plugin'] = $this->api->pluginDisplayName();

		$data['descriptions'] = array();

        $metas = $controller->getShortcodeMetas();
		foreach ($metas as $one)
		{
            if (!empty($one['hide'])) {
                continue;
            }

            $code = $one['code'];
			$descr = $one['description'];
			$tag = $controller->shortCode( $code );

			$data[ "tag_$code"] = $tag;

			$data['descriptions'][$tag] = $descr;
		}

		$example_product_id = $this->getExampleProductId();
		$example_offset = 51;

		$data['example_product_id'] = $example_product_id;
		$data['example_offset'] = $example_offset;

		$data['html_counter_1'] = $controller->renderShortcode( 'counter' );
		$data['html_counter_2'] = $controller->renderShortcode( 'counter', array( 'start' => $example_offset ) );
		$data['html_counter_3'] = $controller->renderShortcode( 'counter', array( 'product' => $example_product_id) );
		$data['html_counter_4'] = $controller->renderShortcode( 'counter', array( 'start' => $example_offset, 'product' => $example_product_id ) );

		$data['html_login_info'] = $controller->shortcodeLoginInfo();
		$data['html_login_box'] = $controller->shortcodeLogin( array( 'logged_in' => false ) );
		$data['html_account'] = $controller->renderShortcode( 'account' );

		$data['html_signup_1'] = $controller->renderShortcode( 'signup', array( 'product' => 'demo'  ) );
		$data['html_signup_2'] = $controller->renderShortcode( 'signup', array( 'product' => 'demo', 'name' => true ) );

		$data[ 'html_products'] = $controller->shortcodeExampleProduct();

		/** @var digimember_AutoresponderHandlerLib $lib */
        $lib = $this->api->load->library( 'autoresponder_handler' );
        $data[ 'autojoin_autoresponders' ] = $lib->renderAutojoinTypeList( 'or' );

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $data[ 'newsletter_menu' ] = $model->adminMenuLink( 'newsletter' );


		if (ncore_isLicenseServer())
		{
			$data['tag_licenses'] = $controller->shortCode( 'licenses' );

			$data['tag_download_url'] = $controller->shortCode( 'download_url' );
			$data['html_download_url_1'] = $controller->renderShortcode( 'download_url' );
			$data['html_download_url_2'] = $controller->renderShortcode( 'download_url', array( 'env' => 'test' ) );

			$data['tag_download_version'] = $controller->shortCode( 'download_version' );
			$data['html_download_version'] = $controller->renderShortcode( 'download_version' );

			$data['tag_download_changelog'] = $controller->shortCode( 'download_changelog' );
		}

		if (ncore_haveExtendedShortcodes())
		{
			$data['tag_open'] = $controller->shortCode( 'open' );
			$data['tag_close'] = $controller->shortCode( 'close' );

			$data['html_open'] = $controller->renderShortcode( 'open' );
			$data['html_close'] = $controller->renderShortcode( 'close' );
		}
		else
		{
			$data['tag_licenses'] = '';

			$data['tag_download_url'] = '';
			$data['html_download_url'] = '';

			$data['tag_open'] = '';
			$data['tag_close'] = '';
		}

        $link = $this->api->load->model( 'logic/link' );
        $ajax_dialog_url = $link->ajaxUrl( 'post/tinymce', 'add_shortcode' );
		$data['ajaxDialogUrl'] = $ajax_dialog_url;
		return $data;
	}

    /**
     * @return array
     */
	public function getViewData()
    {
	    return $this->viewData();
    }

	private function getExampleProductId()
	{
		return 172;
	}



}