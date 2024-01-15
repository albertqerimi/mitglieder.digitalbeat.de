<?php

class ncore_FormRenderer_InputHtmleditor extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $postname = $this->postname();
        $html_id = $this->htmlId();
        $value = $this->value();

        $rows = $this->meta( 'rows', 20 );
        $simple_buttons = $this->meta( 'simple_buttons', false );
        $hide_images    = $this->meta( 'hide_images',    false );
        $editor_id = $this->meta('editor_id', false);
        $settings = array(
            'rows'           => $rows,
            'simple_buttons' => $simple_buttons,
            'hide_images'    => $hide_images,
            'editor_id'      => $editor_id,
            'tinymce'        => array(
                'relative_urls' => false,
                'convert_urls' => true,
                'remove_script_host' => false
            )
        );

        $this->api->load->helper( 'html_input' );

        $html = ncore_htmleditor( $postname, $value, $settings );

        return $html;
    }

    protected function defaultRules()
    {
        return 'trim';
    }
}


