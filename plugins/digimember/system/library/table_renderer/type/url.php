<?php

class ncore_TableRenderer_TypeUrl extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $url = $this->value( $row );

        if (!$url)
        {
            $void_text = $this->meta( 'void_text' );
            if ($void_text)
            {
                return $void_text;
            }
        }


        $attributes['target'] = '_blank';

        return ncore_htmlLink( $url, $url, $attributes );
    }
}