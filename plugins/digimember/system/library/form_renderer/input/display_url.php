<?php

class ncore_FormRenderer_InputDisplayUrl extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        return $this->renderInnerReadonly();
    }

    protected function renderInnerReadonly()
    {
        $html_id = $this->htmlId();

        $url = $this->value();

        $size = max( 40, strlen( $url ) + 25 );

        $attributes = array(
            'size' => $size,
            'id'   => $html_id,
        );

        return ncore_htmlTextInputCode( $url, $attributes );
    }

    public function isReadonly()
    {
        return true;
    }
}
