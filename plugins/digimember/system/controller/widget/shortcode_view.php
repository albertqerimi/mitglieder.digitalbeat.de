<?php

$load->controllerBaseClass( 'widget/base_view' );

abstract class ncore_WidgetShortcodeViewController extends ncore_WidgetBaseViewController
{
	abstract protected function shortcode();

	protected function userDescription()
	{
		return $this->shortcodeMeta( 'description' );
	}

	protected function loadView()
	{
		$shortcode = $this->shortcode();
		$settings = $this->setting( 'all' );

		echo $this->shortcodeController()->renderShortcode( $shortcode, $settings );
	}


	private $shortcode_meta=false;
	private function shortcodeMeta( $key='all', $default='' )
	{
		if ($this->shortcode_meta === false)
		{
			$shortcode = $this->shortCode();

			$this->shortcode_meta = $this->shortcodeController()->getShortcodeMetas( $shortcode );
		}

		return $key==='all'
			   ? $this->shortcode_meta
			   : ncore_retrieve( $this->shortcode_meta, $key, $default );
	}

    /**
     * @return ncore_ShortCodeController
     */
	private function shortcodeController()
	{
		return ncore_api()->load->controller( 'shortcode' );
	}

}

