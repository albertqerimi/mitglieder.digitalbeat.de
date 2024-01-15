<?php

class ncore_FormRenderer_InputFlags extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();
        $postname = $this->postname();
        $value = $this->value();

        $flag_options = $this->meta('flags', array());
        $flag_tooltips = $this->meta('tooltips', array());

        $css = $this->form_visibility->select_css();

        $attributes = array(
            'class' => $css,
            'id' => $html_id,
        );

        return ncore_htmlFlags( $postname, $value, $flag_options, $flag_tooltips, $attributes );
    }

    protected function requiredMarker()
    {
        return '';
    }

}


