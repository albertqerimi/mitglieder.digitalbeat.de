<?php

class ncore_FormRenderer_InputButton extends ncore_FormRenderer_InputBase {
	protected function renderInnerWritable() {
		$html = ncore_htmlButton($this->postname(),$this->meta('button_label'),array('style'=>'width: 100%;'));

		return $html;
	}
}