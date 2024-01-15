<?php

$load->loadPluginClass('select');

class ncore_FormRenderer_InputTimezone extends ncore_FormRenderer_InputSelect {
	protected function options() {
		$options = parent::options();

        if (!$options)
        {
            $options = ncore_getTimezones();
        }
        return $options;
	}
}