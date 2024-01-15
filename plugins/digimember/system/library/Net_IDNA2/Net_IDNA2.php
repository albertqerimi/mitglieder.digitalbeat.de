<?php

class ncore_NetIDNA2Lib
{
    public function decode( $encoded )
    {
        try
        {
            return self::getInstance()->decode( $encoded );
        }
        catch (Exception $e)
        {
            return $encoded;
        }
    }

    public function encode( $decoded )
    {
        try
        {
            return self::getInstance()->encode( $decoded );
        }
        catch (Exception $e)
        {
            return $decoded;
        }
    }

    private static $instance = false;

    private static function getInstance()
    {
        if (self::$instance === false)
        {
            require_once dirname(__FILE__) . '/IDNA2.php';

            self::$instance = new ncore_Net_IDNA2();
        }

        return self::$instance;
    }
}