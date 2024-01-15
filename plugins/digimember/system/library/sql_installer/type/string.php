<?php

class ncore_SqlInstaller_TypeString extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        $length = $this->getArg( $meta, 0, 255 );

        $collate = $this->collate();

        $default_value = ncore_washText( ncore_retrieve( $meta, 'default', '' ) );

        return "varchar($length) COLLATE $collate NOT NULL DEFAULT '$default_value'";
    }
}