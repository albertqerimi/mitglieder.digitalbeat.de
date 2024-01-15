<?php

class ncore_TableRenderer_TypeCode extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $value = $this->value( $row );

        $attributes = array();

        return ncore_htmlTextInputCode( $value, $attributes );
    }
}