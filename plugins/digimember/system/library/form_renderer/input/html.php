<?php

class ncore_FormRenderer_InputHtml extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        return $this->renderInnerReadonly();
    }

    protected function renderInnerReadonly()
    {
        $html = $this->meta( 'html' );
        $cb   = $this->meta( 'callback' );
        $css = $this->meta('css');

        if ($cb) {
            $html = ncore_callUserFunction( $cb, array( $html ) );
        }

        return "<div class=\"dm-text $css\">$html</div>";
    }

    public function isReadonly()
    {
        return true;
    }

    public function fullWidth()
    {
        return true;
    }
}
