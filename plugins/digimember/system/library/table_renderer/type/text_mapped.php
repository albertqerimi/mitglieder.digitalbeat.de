<?php

class ncore_TableRenderer_TypeTextMapped extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $value = $this->value( $row );
        $text_mappings = $this->meta( 'text_mappings' );
        if (array_key_exists($value, $text_mappings)) {
            $value = $text_mappings[$value];
        }
        if (!$value)
        {

            $void_text = $this->meta( 'void_text' );
            if ($void_text)
            {
                return $void_text;
            }
        }

        return $value;
    }
}