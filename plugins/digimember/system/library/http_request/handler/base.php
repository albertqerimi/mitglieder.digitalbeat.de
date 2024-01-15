<?php

abstract class ncore_HttpRequest_MethodBase extends ncore_Plugin
{
    public function __construct( $parent, $type )
    {
        $meta = array();

        parent::__construct( $parent, $type, $meta );
    }

    abstract public function isAvailable();

    abstract public function postRequest($url, $params, $settings = array() );

    abstract public function getRequest($url, $params, $settings = array() );

}