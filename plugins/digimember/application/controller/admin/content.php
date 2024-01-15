<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass( 'admin/form' );

class digimember_AdminContentController extends ncore_AdminFormController
{
	public function init( $settings=array() )
	{
		parent::init( $settings );
	}

	protected function elementSelectorLevelCount()
	{
		return 1;
	}

	protected function elementOptions( $level=1 )
	{
		switch ($level)
		{
			case 1:
			{
			    /** @var digimember_ProductData $model */
				$model = $this->api->load->model('data/product');
				return $model->options();
			}

			default:
				return array();
		}
	}

	protected function elementSelectionMandatory()
	{
		return true;
	}


	protected function noElementsMessage()
	{
		$api = $this->api;
		/** @var digimember_LinkLogic $link_model */
		$link_model = $api->load->model( 'logic/link' );
		$url = $link_model->adminPage( 'products' );

        $msg = ncore_linkReplace(_digi( 'Please <a>add a product</a> first.'), $url );

		return ncore_renderMessage( NCORE_NOTIFY_ERROR, $msg, 'span' );
	}

	protected function pageHeadline()
	{
		 return _digi('Content for Product:');
	}

	protected function pageInstructions()
	{
        $instructions = array();

        $api = ncore_api();
        /** @var digimember_FeaturesLogic $model */
        $model = $api->load->model( 'logic/features' );
        $can_unlock = $model->canContentsBeUnlockedPeriodically();

        if (!$can_unlock) {
            /** @var digimember_LinkLogic $model */
            $model = $api->load->model( 'logic/link' );

            $instructions[] = $model->upgradeHint( _digi( 'Unlocking content periodically is NOT included in your subscription.' ), $label='', $tag='p' );
        }

        $instructions[] = _digi( 'Here you can add content to a product. Just use your mouse to drag the content from the left column to the right column. All content in the right column belongs to the products. This means, that every buyer of the product gets access to this content.' );
        $instructions[] = _digi( 'If you want to unlock content step by step, use the text input fields in the right column. Enter the number of days the user has to wait to view this content. E.g. enter 14 to make the content available after two weeks. Leave it blank, if the buyer should gain access immediately after buying the product.' );
        $instructions[] = _digi( 'When you edit a page or a post, you also can assign content (page or post) to a product to protect the content.' );

        return $instructions;
	}

	protected function editedElementIds()
	{
		$ids = array();

		foreach ($this->elementSelectorLevels() as $level)
		{
			$ids[] = $this->selectedElement( $level );
		}

		$id = implode( '_', $ids );

		return array( $id );
	}

	protected function getData( $product_id )
	{
	    /** @var digimember_PageProductData $model */
		$model = $this->api->load->model( 'data/page_product' );

		$post_type = $this->postType();

		$pages = $model->getPostsForProduct( $product_id, $post_type );

		return [ 'pages' => $pages ];
	}

	protected function setData( $product_id, $data )
	{
        /** @var digimember_PageProductData $model */
		$model = $this->api->load->model( 'data/page_product' );

		$pages_json = ncore_retrieve( $data, 'pages' );

        $pages = $pages_json
               ? json_decode( $pages_json )
               : array();

		$post_type = $this->postType();

		$result = $model->storePostsForProduct( $product_id, $post_type, $pages );

        do_action( 'digimember_invalidate_cache' );

        return $result;
	}


    protected function formSettings() {

        $settings = parent::formSettings();

        $settings[ 'container_css']     = 'digimember_content_editor';
        $settings[ 'omit_default_css' ] = true;

        return $settings;

    }


	protected function inputMetas()
	{
		$api = $this->api;
		/** @var digimember_ProductData $data_product */
		$data_product = $api->load->model('data/product');
		/** @var digimember_PageProductData $page_product */
		$page_product = $api->load->model('data/page_product');
		/** @var digimember_MailHookLogic $mail_hook */
		$mail_hook = $api->load->model('logic/mail_hook');
		/** @var digimember_PaymentHandlerLib $payment_handler */
		$payment_handler = $api->load->library('payment_handler');
		$api->load->helper( 'array' );

		$pay_plugin_options = $payment_handler->getProviders();

		$hookMetas = $mail_hook->hookMeta();

		list( $element_id ) = $this->editedElementIds();

		$product_id = $this->selectedElement( 1 );

		$product = $data_product->get( $product_id );

		$post_type = $this->postType();

		$inputs = array();

		$pages = $page_product->getAllPosts( $post_type );

        $options = ncore_listToArraySorted( $pages, 'ID', 'post_title' );

		$details_url   = $this->api->link_logic->readPost( $post_type, '__ID__' );
		$details_label = ncore_icon( 'view_page', _ncore('View page') );

		/** @var digimember_FeaturesLogic $model */
        $model = $api->load->model( 'logic/features' );
        $have_wait_days = $model->canContentsBeUnlockedPeriodically();

		$inputs[] = array(
						'name'    => 'pages',
						'section' => 'general',
						'type'    => 'product_content',
						'options' => $options,
                        'label'   => 'omit',

						'headline_available' => _digi( 'Available content' ),
						'headline_selected'  => _digi( 'Content selected for this product' ),

						'details_url'   => $details_url,
						'details_label' => $details_label,

						'element_id'    => $element_id,

						'id_name'             => 'post_id',
						'label_name'          => 'title',
                        'lecture_number_name' => 'number',

						'is_active_key' => 'is_active',
						'is_active_val' => 'Y',

                        'level_name' => 'level',
                        'level_max'  => 2,

						'inputs' => array(
							array(
								'type' => $have_wait_days
                                          ? 'int'
                                          : 'hidden',
								'name' => 'unlock_day',
								'label' => _digi( 'Unlock day' ),
								'tooltip' => _digi( 'Enter the number of days after which this page will be visible to the buyer. E.g. enter 7 to make it visible a week after the sale. Leave blank to make it visible immediately after buying.' ),
                                'display_zero_as' => '',
							),
						)
					);


		return $inputs;
	}

	protected function sectionMetas()
	{
		return [
			'general' => [
                'headline' => '',
                'instructions' => '',
			]
        ];
	}

	protected function tabs()
 	{
 	    /** @var digimember_PageProductData $model */
        $model = $this->api->load->model( 'data/page_product' );
        return $model->postTypeOptions();
	}

	private function postType()
	{
		return $this->currentTab();
	}
}
