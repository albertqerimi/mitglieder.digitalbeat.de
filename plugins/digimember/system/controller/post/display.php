<?php

class ncore_PostDisplayController extends ncore_Controller {

	// Variable Section
	private $has_wp_design = true;
	private $post_id = null;

	// Public Section

	public function init($settings = array()) {
		parent::init($settings);

		$this->post_id = ncore_retrieve($settings,'post_id',null);
	}

	public function dispatch() {
		if ($this->has_wp_design) {
			ncore_getWPHeader();
		}

		$this->view();

		if ($this->has_wp_design) {
			ncore_getWPFooter();
		}
	}

	// Protected Section

	protected function hasWPDesign($has = null) {
		if (is_bool($has)) {
			$this->has_wp_design = $has;
		}
		return $this->has_wp_design;
	}

	protected function getPostId() {
		return $this->post_id;
	}

	// Private Section
}