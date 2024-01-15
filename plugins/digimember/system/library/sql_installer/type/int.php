<?php

class  ncore_SqlInstaller_TypeInt extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        $default_value = intval( ncore_retrieve( $meta, 'default', 0 ) );

        $sql = "int(10) unsigned NOT NULL DEFAULT '$default_value'";

        return $sql;
    }
}