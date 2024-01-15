<?php

class ncore_FormRenderer_InputPlaceholder extends ncore_FormRenderer_InputBase
{
	protected function renderInnerWritable()
	{
	    /** @var ncore_MailRendererLib $lib */
		$lib = $this->api->load->library( 'mail_renderer' );

		$placeholder = $this->meta('placeholder' );

		$html = '<div>';

		foreach ($placeholder as $name => $description)
		{
			$varname = esc_html( $lib->variableName( $name ) );
			$html .= '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-md-3 dm-col-xs-12 dm-placeholder-name dm-color-coral">' . $varname . '</div>
    <div class="dm-col-md-9 dm-col-md-offset-0 dm-col-xs-offset-1 dm-col-xs-11">' . $description . '</div>
</div>
';
		}
		$html .= '</div>';

		return $html;
	}
}