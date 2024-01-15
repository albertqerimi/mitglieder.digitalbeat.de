<?php

class ncore_SqlInstaller_TypeDate extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        return "date NOT NULL DEFAULT '2010-01-01'";
    }
}