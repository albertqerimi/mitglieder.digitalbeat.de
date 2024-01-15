<?php

abstract class ncore_SqlInstaller_TypeBase
{
    public function render( $meta = array() )
    {
        $definition = $this->sqlDefinition( $meta );

        return $definition;
    }

    public function collate()
    {
//        return 'utf8_unicode_ci';
        return 'utf8mb4_unicode_ci';
    }

    abstract protected function sqlDefinition( $meta );

    protected function getArg( $meta, $index=0, $default=false )
    {
        $args = ncore_retrieve( $meta, 'args' );

        $arg = ncore_retrieve( $args, $index, $default );

        return $arg;
    }


}