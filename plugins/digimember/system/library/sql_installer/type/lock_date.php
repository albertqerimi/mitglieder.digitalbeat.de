<?php

class ncore_SqlInstaller_TypeLockDate extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        return "datetime DEFAULT NULL";
    }
}