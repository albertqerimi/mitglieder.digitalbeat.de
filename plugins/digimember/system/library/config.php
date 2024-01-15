<?php

// stores data from files in system/config and application/config

class ncore_ConfigLib extends ncore_Library
{
    public function setConfig( $domain, $items )
    {
        if (isset($items))
        {
            $this->config[ $domain ] = $items;
        }
        else
        {
            $this->config[ $domain ] = array();
        }


        $this->setDomain( $domain );
    }

    public function isLoaded( $domain )
    {
        $is_loaded = isset( $this->config[ $domain ] );

        return $is_loaded;
    }

    public function setDomain( $domain )
    {
        $this->current_domain = $domain;

        return $this;
    }

    public function get( $key, $default='' )
    {
        $storedItems =& $this->config[ $this->current_domain ];

        $have_domain = isset( $storedItems );

        if ($have_domain)
        {
            $have_value = isset( $storedItems[ $key ] );

            return $have_value ? $storedItems[ $key ] : $default;
        }

        trigger_error( "Must set ncore_Initlib::domain before getting values (key: '$key')" );

    }

    private $config = array();
    private $current_domain = false;
}


