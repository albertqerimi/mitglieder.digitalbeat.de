<?php

class ncore_SqlInstaller_TypeYesNoBit extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        $default_value = ncore_toYesNoBit( ncore_retrieve( $meta, 'default', 'Y' ) );

        $collate = $this->collate();
        return "enum('Y','N') COLLATE $collate NOT NULL DEFAULT '$default_value'";
    }
}