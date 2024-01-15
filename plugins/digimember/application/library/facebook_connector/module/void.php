<?php

class digimember_FacebookVoid extends digimember_FacebookBaseModule
{
    public function __construct( $api, $parent, $app_id, $app_secret ) // , $use_extended_permissions=false )
    {
        parent::__construct( $api, $parent, $app_id, $app_secret ); // , $use_extended_permissions );
    }

    public function __call( $method, $args ) {
        return false;
    }
}