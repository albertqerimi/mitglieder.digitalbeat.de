<?php

class ncore_TableRenderer_TypePage extends ncore_TableRenderer_TypeBase
{
	protected function renderInner( $row )
	{
		$page_id = $this->value( $row );

		$options = $this->options();

		$innerHtml = ncore_retrieve( $options, $page_id );

		$link_type = $this->meta( 'link_type', 'none' );

		$url = false;

		if ($page_id>0)
		{
			$model = $this->api->load->model( 'logic/link' );

			switch ($link_type)
			{
				case 'edit': $url = $model->editPageUrl( $page_id );      break;
				case 'view': $url = $model->readPost( 'page', $page_id ); break;
				case 'none':
				default:     // empty
						;
			}
		}

		return $url
			   ? ncore_htmlLink( $url, $innerHtml )
			   : $innerHtml;
	}

	private static $options = false;
	private function options()
	{
		if (self::$options === false)
		{
			$this->api->load->helper( 'array' );
			$this->api->load->model('data/page_product');

			$pages = $this->api->page_product_data->getAllPages();
			self::$options = ncore_listToArraySorted( $pages, 'ID', 'post_title' );
		}

		return self::$options;
	}
}