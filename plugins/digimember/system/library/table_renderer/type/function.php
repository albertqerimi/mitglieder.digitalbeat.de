<?php

class ncore_TableRenderer_TypeFunction extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $callable = $this->meta( 'function' );

        $metas = $this->metas();

        $args = array( $row, $metas );

        return call_user_func_array( $callable, $args );

    }

}