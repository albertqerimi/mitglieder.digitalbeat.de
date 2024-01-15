<?php

class ncore_FormRenderer_InputTextarea extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();
        $postname = $this->postname();
        $value = $this->value();

        $cols = $this->meta( 'cols', 40 );
        $rows = $this->meta( 'rows', 5 );
        $css  = $this->meta( 'css', '' );

        $attributes = array(
            'id' => $html_id,
            'rows' => $rows,
            'cols' => $cols,
            'class'  => $css,
        );

        return ncore_htmlTextarea( $postname, $value, $attributes );
    }

    protected function defaultRules()
    {
        return 'trim';
    }
}


