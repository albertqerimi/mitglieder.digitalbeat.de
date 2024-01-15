<?php

class ncore_BlogConfigLogic extends ncore_BaseLogic
{
    private $serialized_types = array( 'array' );

    public function delete( $name )
    {
        $prefixed_name = $this->applyPrefix( $name );

        $where = array(
            'name' => $prefixed_name,
        );

        $modified = (bool) $this->model()->deleteWhere( $where );

        $cache =& $this->cache;

        if ($cache !== false)
        {
            unset( $cache[ $prefixed_name ] );
        }

        return $modified;
    }

    public function setIfModified( $name, $value )
    {
        if ($this->get( $name ) != $value)
        {
            return $this->set( $name, $value );
        }

        return false;
    }

    public function set( $name, $value, $lifetime_hours=false )
    {
        $prefixed_name = $this->applyPrefix( $name );

        $data = array(
            'name'  => $prefixed_name,
            'value' => $value,
        );

        if (is_array($value))
        {
            $data[ 'type' ]  = 'array';
            $data[ 'value' ] = serialize( $value );
        }

        $lifetime_seonds = $lifetime_hours
                         ? round( $lifetime_hours*3600 )
                         : 0;
        if ($lifetime_seonds>60)
        {
            $data[ 'expire' ] = ncore_dbDate( time() + $lifetime_seonds );
        }

        $where = array(
            'name' => $prefixed_name,
        );

        $all = $this->model()->getAll( $where );

        $modified = false;

        if ($all)
        {
            foreach ($all as $one)
            {
                if ($this->model()->update( $one->id, $data ))
                {
                    $modified = true;
                }
            }
        }
        else
        {
            $this->model()->create( $data );
        }

        $cache =& $this->cache;

        if ($cache !== false)
        {
            $cache[ $prefixed_name ] = $value;
        }

        return $modified;
    }

    public function get( $name, $default=false )
    {
        $this->loadSettings();

        if ($default===false)
        {
            $default = $this->defaultValue( $name );
        }

        $prefixed_name = $this->applyPrefix( $name );

        return ncore_retrieve( $this->cache, $prefixed_name, $default );
    }

    public function setAll( $data )
    {
        $modified = false;

        foreach ($data as $name => $value)
        {
            if ($this->set( $name, $value ))
            {
                $modified = true;
            }
        }

        return $modified;
    }

    public function getAll()
    {

        $prefix_len = strlen( $this->namePrefix() );

        $this->loadSettings();

        $data = array();
        foreach ($this->cache as $key => $value) {

            if ($prefix_len)
            {
                if (substr( $key, 0, $prefix_len) ==  $this->namePrefix())
                {
                    $key = substr( $key, $prefix_len );
                }
                else
                {
                    continue;
                }
            }

            $data[ $key ] = $value;
        }

        return $data;
    }

    public function getAffiliate()
    {
        $affiliate        = $this->get( 'affiliate' );
        $campaignkey      = $this->get( 'campaignkey' );

        return array( $affiliate, $campaignkey );
    }


    //
    // protected
    //

    protected function defaultValues()
    {
        return array();
    }

    protected function namePrefix() {
        return ''; // e.g. 'net_'
    }

    protected function model()
    {
        if ($this->model === false)
        {
            $this->model = $this->api->load->model( 'data/config_store' );
        }

        return $this->model;
    }

    protected function onInit()
    {
        $must_set_affiliate = defined('DIGIMEMBER_AFFILIATE' )
                              && DIGIMEMBER_AFFILIATE
                              && !$this->get( 'affiliate' );

        if ($must_set_affiliate)
        {
            $affiliate = DIGIMEMBER_AFFILIATE;
            if ($affiliate[0] != '{') {
                $this->set( 'affiliate',        DIGIMEMBER_AFFILIATE );
                $this->set( 'campaignkey',      DIGIMEMBER_CAMPAIGNKEY );
            }
        }
    }

    //
    // private
    //
    private $cache = false;

    private $model = false;

    private function applyPrefix( $name ) {
        return $this->namePrefix() . $name;
    }

    private function defaultValue( $name )
    {
        $default_values = $this->defaultValues();
        return ncore_retrieve( $default_values, $name );
    }

    protected function loadSettings()
    {
        $cache =& $this->cache;

        if ($cache !== false)
        {
            return;
        }

        $where = array();
        $limit = '';
        $order_by = 'id ASC';

        $have_table = $this->model()->sqlTableExists();

        $all = $have_table
             ? $this->model()->getAll( $where, $limit, $order_by )
             : array();

        $cache = $this->defaultValues();

        foreach ($all as $one)
        {
            $must_unserialize = !empty( $one->type ) && in_array( $one->type, $this->serialized_types );

            $cache[ $one->name ] = $must_unserialize
                                 ? unserialize( $one->value )
                                 : $one->value;
        }

        $this->onInit();
    }

}