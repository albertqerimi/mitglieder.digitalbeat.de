<?php

class ncore_FormRenderer_InputFunction extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $callable = $this->meta( 'function' );
        $args = array($this->meta( 'params' ));
        return call_user_func_array( $callable, $args );
    }
}