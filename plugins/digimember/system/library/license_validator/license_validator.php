<?php


class ncore_LicenseValidatorLib extends ncore_Library
{

    public function __call( $method, $args ) {

        $plugin = $this->loadPlugin();

        $callback = array( $plugin, $method );

        return call_user_func_array( $callback , $args );
    }

    //
    // protected
    //
    protected function pluginDir()
    {
        return 'plugin';
    }

    //
    // private
    //

    private $plugin = false;

    private function loadPlugin()
    {
        if (!$this->plugin)
        {
            $type = $this->pluginType();
            $meta = array();
            $class_name = $this->loadPluginClass( $type );
            $this->plugin = new $class_name( $this, $type, $meta );
        }

        return $this->plugin;
    }

    private function pluginType() {

        if (ncore_isLicenseServer()) {
            return 'server';
        }

        return 'download';
    }
}
