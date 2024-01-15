<?php

class  ncore_SqlInstaller_TypeBit extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        $default_value = ncore_retrieve( $meta, 'default', '0' ) > 0
                       ? '1'
                       : '0';

        return "tinyint unsigned not null default $default_value";
    }
}