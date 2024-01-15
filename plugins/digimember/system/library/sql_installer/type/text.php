<?php

class ncore_SqlInstaller_TypeText extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        $collate = $this->collate();

        $sql = "text COLLATE $collate";

        $default_value = ncore_washText( ncore_retrieve( $meta, 'default', '' ) );
        if ($default_value) {
            $sql .= " NOT NULL DEFAULT '$default_value'";
        }
        return $sql;
    }
}