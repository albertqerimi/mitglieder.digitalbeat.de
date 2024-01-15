<?php

class ncore_FormRenderer_InputPageOrUrl extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $postname = $this->postname();
        $selected_value = $this->value();

        if ($selected_value && is_numeric($selected_value))
        {
            $selected_value = ncore_resolveUrl( $selected_value );
        }

        return ncore_htmlPageOrUrlInput( $postname, $selected_value );
    }

    protected function defaultRules()
    {
        return 'trim|url';
    }
}


