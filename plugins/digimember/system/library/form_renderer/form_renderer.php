<?php

class ncore_FormRendererLib extends ncore_Library
{
	public function createForm( $sections, $input_metas, $button_metas, $settings=array() )
	{
		$this->api->load->helper( 'html_input' );

		require_once 'form.php';
		require_once 'element.php';

		$this->api->load->library( 'form_visibility' );

		$form = new ncore_FormRendererForm( $this->api, $sections, $settings );

        $is_form_readonly = ncore_retrieve( $settings, 'is_form_readonly', false );

		foreach ($input_metas as $one)
		{
			$type = $one['type'];

            if ($is_form_readonly) {
                $one['rules'] = 'readonly';
            }

			$class = $this->loadPluginClass( $type );

            if (empty($class)) {
                trigger_error( "Invalid input type: $type" );
                continue;
            }

			$input =  new $class( $form, $one );

			$form->addInput( $input );
		}

		foreach($button_metas as $one)
		{
			$type = $one['type'];

			$class = $this->loadPluginClass( $type, 'button' );

			$button =  new $class( $form, $one );

			$form->addButton( $button );
		}

		return $form;
	}

	protected function pluginDir()
	{
		return 'input';
	}


}