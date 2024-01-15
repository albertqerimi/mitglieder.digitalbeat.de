<?php

class ncore_FormRenderer_InputTimeSelector extends ncore_FormRenderer_InputBase {
	protected function renderInnerWritable() {
		$css = $this->form_visibility->select_css();

        $attributes = array(
            'class' => $css,
        );

        $settings = array(
            'hours' => $this->meta('hours',true),
            'minutes' => $this->meta('minutes',true),
            'seconds' => $this->meta('seconds',false)
    	);

		$html = ncore_htmlTimeSelector($this->postname(),$this->value(),$attributes,$settings);

		return $html;
	}
}