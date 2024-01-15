<?php

class ncore_FormRenderer_InputPassword extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html = '';
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $value = $this->value();

        $attributes = array( 'id' => $html_id, 'type' => 'password' );

        return ncore_htmlTextInput( $postname, $value, $attributes );
    }

    protected function defaultRules()
    {
        return 'trim';
    }


}




