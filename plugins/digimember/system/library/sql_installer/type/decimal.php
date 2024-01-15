<?php

class ncore_SqlInstaller_TypeDecimal extends ncore_SqlInstaller_TypeBase
{
    protected function sqlDefinition( $meta )
    {
        $default = ncore_washInt( ncore_retrieve( $meta, 'default', 0 ) );

        $decimals = $this->getArg( $meta, 0, 2 );

        $decimals = intval( $decimals );
        if ($decimals <= 1)  $decimals = 1;
        if ($decimals >= 10) $decimals = 10;

        $digits = $decimals + 8;

        $default = sprintf( "%0.${decimals}f", $default );

        return "decimal($digits,$decimals) signed NOT NULL DEFAULT '$default'";
    }
}