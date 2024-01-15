<?php

class ncore_FormRenderer_InputSelectableImageList extends ncore_FormRenderer_InputBase {
	protected function renderInnerWritable() {
		$css = $this->form_visibility->select_css();
		
        $attributes = array(
            'class' => $css,
        );
        
		$html = ncore_htmlSelectableImageList($this->postname(),$this->options(),$this->value(),$this->settings(),$attributes);

		return $html;
	}

	private function options() {
		// Options need format:
		// label, value, image, link, tooltip
		return $this->meta('options',array());
	}

	private function settings() {
		// Settings can be:
		// max_items_per_row, default_image, links_new_window
		return $this->meta('settings');
	}
}