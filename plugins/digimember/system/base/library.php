<?php

abstract class ncore_Library extends ncore_Class
{
    public function __construct( ncore_ApiCore $api, $file='', $dir='' )
    {
        parent::__construct( $api, $file, $dir );

        $this->init();
    }

    public function api()
    {
        return $this->api;
    }

    protected function init()
    {
    }

    protected function pluginDir()
    {
        return 'plugin';
    }

    protected function loadPluginClass( $file, $plugin_dir=false )
    {
        if (!$plugin_dir)
        {
            $plugin_dir = $this->pluginDir();
        }

        $class =& $this->loaded_classes[ "$plugin_dir/$file" ];

        $class_loaded = !empty($class);
        if ($class_loaded)
        {
            return $class;
        }

        $base_class_loaded = in_array( $plugin_dir, $this->loaded_base_classes );
        if (!$base_class_loaded)
        {
            $this->loaded_base_classes[] = $plugin_dir;
            $this->loadPluginClass( 'base', $plugin_dir );
        }

        $lib_dir = $this->baseName();

        $sys_dir = $this->api->sysDir();
        $app_dir = $this->api->appDir();

        $load = $this;

        $path = "$sys_dir/library/$lib_dir/$plugin_dir/$file.php";
        if (file_exists( $path ))
        {
            $class = $this->renderClassName( $file, $plugin_dir, 'system' );

            require_once $path;
        }

        $path = "$app_dir/library/$lib_dir/$plugin_dir/$file.php";
        if (file_exists( $path ))
        {
            $application = $this->api->pluginName();
            $class = $this->renderClassName( $file, $plugin_dir, $application );

            require_once $path;
        }

        return $class;
    }

    protected function renderClassName( $file, $plugin_dir, $application='system' )
    {
        $base_name = $this->api->className( $application, $this->baseName(), $dir = '' );

        $file = ucfirst( $file );
        $plugin_dir = ucfirst( $plugin_dir );

        return $base_name . '_' . ncore_camelCase( $plugin_dir . $file );

    }

    private $loaded_base_classes = array();
    private $loaded_classes = array();

}