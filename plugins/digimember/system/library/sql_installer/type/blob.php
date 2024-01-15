<?php

class ncore_SqlInstaller_TypeBlob extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        return "longblob";
    }
}