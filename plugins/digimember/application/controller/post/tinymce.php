<?php

$load->controllerBaseClass( 'post/meta' );

class digimember_PostTinymceController extends ncore_Controller
{

	protected function ajaxEventHandlers()
	{
		$handlers = parent::ajaxEventHandlers();

		$handlers['add_shortcode'] = 'handleAddShortcode';
        $handlers['get_products'] = 'handleGetProducts';
        $handlers['get_block_config'] = 'handleGetBlockConfig';

		return $handlers;
	}

	protected function handleAddShortcode( $response )
	{
		$ajax_meta = $this->ajaxDialogMeta();

		/** @var ncore_AjaxLib $ajax */
		$ajax = $this->api->load->library( 'ajax' );

		$dialog = $ajax->dialog( $ajax_meta );

		$dialog->setAjaxResponse( $response );



		// $shortcode = "[digimember_shortcode]";
		// $response->js(  "digimember_tinymce_callbackShortcode( '$shortcode' )" );
	}

    protected function handleGetProducts( $response )
    {
        $products = array();
        $model = $this->api->load->model('data/product');
        $product_options = $model->options();
        foreach ($product_options as $id => $name) {
            $products[] = array(
                'id' => $id,
                'name' => $name
            );
        }
        $jsonFormat = json_encode($products);
        echo $jsonFormat;
        die();
    }

    protected function handleGetBlockConfig( $response ) {
	    if ($blockname = $this->ajaxArg('block',false)) {
            $configLib = $this->api->load->config('html_include');
            $config = $configLib->get('dm-blocks');
            if ($ifcontentConfig = ncore_retrieve($config, $blockname,false)) {
                $block_renderer = ncore_api()->load->library('block_renderer');
                $config = $block_renderer->getBlockConfig($ifcontentConfig);
                $jsonFormat = json_encode($config);
                echo $jsonFormat;
                die();
            }
        }

        echo '{"error":true}';
        die();
    }

	private function ajaxDialogMeta($jsCallback = false)
	{
		$this->api->load->helper( 'array' );

		/** @var digimember_ShortCodeController $controller */
		$controller = $this->api->load->controller( 'shortcode' );

		$shortcode_metas = $controller->getShortcodeMetas();

        foreach ($shortcode_metas as $index => $one)
        {
            if (!empty($one['hide'])) {
                unset($shortcode_metas[$index]);
            }
        }

		$shortcode_options = ncore_listToArray( $shortcode_metas, 'tag', 'rendered', 'section' );

        $cb_js_code = $jsCallback ? $jsCallback : "digimember_tinymce_callbackShortcode(form_id)";


		$form_metas = array();

		$form_metas[] = array(
							'name' => 'shortcode',
							'type' => 'select',
							'label' => _ncore('Shortcode' ),
							'options' => $shortcode_options,
					   );

		foreach ($shortcode_metas as $one)
		{
            $tag = $one['tag'];
			$description = $one['description'];

			$form_metas[] = array(
				'label' => _ncore('Description'),
				'type' => 'html',
				'html' => $description,
				'depends_on' => array( 'shortcode' => $tag ),
			);

			$arg_metas = ncore_retrieve( $one, 'args' );
			foreach ($arg_metas as $arg)
			{
                $is_hidden = ncore_retrieve( $arg, 'hide', false );
                if ($is_hidden) {
                    continue;
                }

                $is_hidden = !empty( $arg[ 'is_only_for' ] )
                          && str_replace( '_', '', $arg[ 'is_only_for' ] ) !== 'shortcode';
                if ($is_hidden) {
                    continue;
                }

                $depends_on = ncore_retrieve( $arg, 'depends_on', array() );
                $depends_on[ 'shortcode' ] = $tag;
				$arg['depends_on'] = $depends_on;

                $arg['css'] = 'ncore_shortcode_'.$tag;

				if ($arg['type']  == 'url')
				{
					$arg['size'] = 40;
				}

				$form_metas[] = $arg;
			}
		}

		/** @var digimember_LinkLogic $model */
		$model = $this->api->load->model( 'logic/link' );
		$url = $model->adminPage( 'shortcode' );
		$form_metas[] = array(
			'type' => 'html',
			'html' => ncore_linkReplace( _digi( 'For more infos <a>click here</a>.' ), $url, $asPopup=true ),
		);



		return array(   'type'          => 'form',
						'cb_js_code'    => $cb_js_code,
                        'close_on_ok'   => true,
						'title'         => _digi( 'DigiMember Shortcode' ),
						'form_sections' => array(),
						'form_inputs'   => $form_metas,
						'width'         => '800px',
						'height'        => '600px',
						'dialogClass'   => 'dm-shortcode-dialog',
				 );
	}



}