<?php

class ncore_FormRenderer_InputPage extends ncore_FormRenderer_InputBase
{
	protected function renderInnerWritable()
	{
		$html_id = $this->htmlId();
		$postname = $this->postname();
		$selected_value = $this->value();
		if ($selected_value == 0) {
		    $selected_value = $this->defaultValue();
        }
		$options = $this->options();

		$allow_null = $this->meta( 'allow_null', false );

		if ($allow_null)
		{
			$null_option = array( '' => '&nbsp;' );
			$options = $null_option + $options;
		}

		if (!$options)
		{
			return _ncore( 'No pages! Please create a page first.' );
		}

		$select_css = $this->form_visibility->select_css() . ' dm-select-page';

		$attributes = array(
			'class' => $select_css,
			'id' => $html_id,
		);

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model( 'logic/link' );
        $url = $linkLogic->readPost( 'page', $selected_value );
        $icon = ncore_icon( 'search', _ncore('View page') );

        $link = ncore_htmlLink( $url, $icon, array( 'as_popup' => true, 'class' => 'dm-btn dm-btn-primary dm-input-button' ) );
        $attributes['data-base-url'] = $linkLogic->readPost( 'page', '' );

		return '<div class="dm-input-group">'.ncore_htmlSelect( $postname, $options, $selected_value, $attributes ).$link.'</div>';
	}

   protected function requiredMarker()
	{
		return '';
	}


	private static $options = false;
	private function options()
	{
		if (self::$options === false)
		{
            self::$options = ncore_resolveOptions( 'page' );
		}

		return self::$options;
	}
}


