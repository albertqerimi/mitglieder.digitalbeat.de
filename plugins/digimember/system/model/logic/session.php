<?php

final class ncore_SessionLogic extends ncore_BaseLogic
{
    const lifetime_hours = 24;
    public function init()
    {
        global $counter;

        if (empty($counter)) $counter=0;

        $this->sessionKey();
    }

    public function set( $name_or_key_value_pairs, $value=false )
    {
        $session_data = $this->sessionData();

        if (is_array($name_or_key_value_pairs))
        {
            $key_value_pairs = $name_or_key_value_pairs;
            foreach ($key_value_pairs as $name => $value)
            {
                $session_data->$name = $value;
            }
        }
        elseif ($value)
        {
            $name = $name_or_key_value_pairs;
            $session_data->$name = $value;
        }
        else
        {
            $name = $name_or_key_value_pairs;
            unset( $session_data->$name );
        }

        self::$session_data = $session_data;

        $key = $this->sessionKey();

        $this->model()->store( $key, $session_data, self::lifetime_hours );
    }

    public function get( $name, $default='' )
    {
        $data = $this->sessionData();

        return ncore_retrieve( $data, $name, $default );
    }

    //
    // protected
    //

    protected function defaultValues()
    {
        return array();
    }

    //
    // private
    //
    private static $session_data   = false;
    private static $session_key    = false;
    private static $model          = false;

    private function model()
    {
        if (self::$model === false)
        {
            self::$model = ncore_api()->load->model( 'data/session_store' );
        }

        return self::$model;
    }

    private function sessionKey()
    {
        if (!self::$session_key)
        {
            self::$session_key = ncore_retrieve( $_COOKIE, 'ncore_session' );
        }

        if (!self::$session_key && !defined( 'DOING_CRON' ))
        {
            $this->api->load->helper( 'string' );
            self::$session_key = ncore_randomString( 'alnum', 30 );

            if (headers_sent( $file, $line ))
            {

			    $current_url = !empty( $_SERVER['HTTP_HOST'] ) && !empty($_SERVER['REQUEST_URI'])
			                 ? "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
				             : 'unknown';

                trigger_error( "DigiMember needs to set a cookie, but output has allready startet in $file:$line - current url: $current_url", E_USER_ERROR );
            }
            else {
                ncore_setcookie('ncore_session', self::$session_key, 0, '/');
            }
        }

        return self::$session_key;
    }

    private function sessionData()
    {
        if (self::$session_data === false)
        {
            $key = $this->sessionKey();

            $model = $this->model();

            self::$session_data = $model->retrieve( $key, self::lifetime_hours );
        }

        if (!self::$session_data)
        {
            self::$session_data   = new stdClass();
        }

        return self::$session_data;
    }
}