<?php

/**
 * Class ncore_Class
 * @property bool $is_new_instance
 * @property ncore_ApiCore $api
 */
abstract class ncore_Class
{
    public function __construct( ncore_ApiCore $api, $file='', $dir='' )
    {
        $this->api = $api;
        $this->file = $file;
        $this->dir = $dir;

        static $instance_id;
        $instance_id++;
        $this->instance_id = $instance_id;
    }

    public function api()
    {
        return $this->api;
    }

    public function baseName()
    {
        return $this->file;
    }

    public function baseId()
    {
        return str_replace( '/', '_', $this->baseName() );
    }

    protected $api;

    private $dir='';
    private $file='';

    public $instance_id;
}