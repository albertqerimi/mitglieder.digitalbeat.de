<?php

class  ncore_SqlInstaller_TypeId extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        $sql = "int(10) unsigned NOT NULL";

        $is_primary_key = ncore_retrieve( $meta, 'is_primary_key', false );

        if ($is_primary_key)
        {
            $sql .= " AUTO_INCREMENT PRIMARY KEY";
        }
        else
        {
            $sql .= " DEFAULT '0'";
        }

        return $sql;
    }
}