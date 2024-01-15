<?php

class ncore_SqlInstaller_TypeDateTime extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        return "datetime NOT NULL DEFAULT '2010-01-01 00:00:00'";
    }
}