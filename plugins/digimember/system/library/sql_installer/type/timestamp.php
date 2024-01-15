<?php

class ncore_SqlInstaller_TypeTimestamp extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        return "timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP";
    }
}