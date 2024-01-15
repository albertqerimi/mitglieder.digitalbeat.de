<?php

class ncore_TableRenderer_TypeTextEscaped extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $value = $this->value( $row );

        if (!$value)
        {
            $void_text = $this->meta( 'void_text' );
            if ($void_text)
            {
                return $void_text;
            }
        }

        return htmlentities($value);
    }
}