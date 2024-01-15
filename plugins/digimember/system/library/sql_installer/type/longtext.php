<?php

class ncore_SqlInstaller_TypeLongText extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        $collate = $this->collate();

        $sql = "longtext COLLATE $collate";

        $default_value = ncore_washText( ncore_retrieve( $meta, 'default', '' ) );
        if ($default_value) {
            $sql .= " NOT NULL DEFAULT '$default_value'";
        }
        return $sql;
    }
}