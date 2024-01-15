<?php

class ncore_HttpRequestLib extends ncore_Library
{

    public function postRequest( $url, $params, $settings = array() )
    {
        require_once 'http_response.php';

        $settings = $this->_sanitizeSettings( $settings );

        $handler = $this->loadHandler();

        @ignore_user_abort(true);
        @set_time_limit(0);

        return $handler->postRequest( $url, $params, $settings );

    }

    public function getRequest( $url, $params=array(), $settings = array() )
    {
        require_once 'http_response.php';

        $settings = $this->_sanitizeSettings( $settings );

        $handler = $this->loadHandler();

        @ignore_user_abort(true);
        @set_time_limit(0);

        return $handler->getRequest( $url, $params, $settings );

    }

    //
    // protected
    //
    protected function pluginDir()
    {
        return 'handler';
    }

    //
    // private
    //
    private $handler=false;

    private $methods = array( 'curl', 'fsockopen' );

    private function _sanitizeSettings( $settings_array__or__validate_ssl_bool )
    {
        if (is_array($settings_array__or__validate_ssl_bool)) {
            return $settings_array__or__validate_ssl_bool;
        }

        $settings = array();
        $settings[ 'dont_validate_ssl' ] = !$settings_array__or__validate_ssl_bool;
        return $settings;
    }

    private function loadHandler()
    {
        if ($this->handler != false)
        {
            return $this->handler;
        }

        foreach ($this->methods as $type)
        {
            $class_name = $this->loadPluginClass( $type );
            $plugin = new $class_name( $this, $type );

            if ($plugin->isAvailable())
            {
                $this->handler = $plugin;
                return $this->handler;
            }
        }

        trigger_error( 'No availble handler for http requests found.' );
    }
}