<?php

$load->model( 'logic/blog_config' );

class ncore_NetworkConfigLogic extends ncore_BlogConfigLogic
{
    public function getNetworkNonce( $get_previous=false ) {

        $ttl = 45000;

        $model = ncore_api()->load->model( 'logic/network_config' );

        $this->model()->pushNetworkMode();

        if ($get_previous)
        {
            $nonce = $model->get( 'network_nonce_previous' );
        }
        else
        {
            $nonce   = $model->get( 'network_nonce_value'   );
            $created = $model->get( 'network_nonce_created' );

            $invalid = !$nonce || $created + $ttl < time();

            if ($invalid)
            {
                $model->set( 'network_nonce_previous', $nonce );

                $this->api->load->helper( 'string' );
                $nonce = ncore_randomString( 'alnum', 32 );

                $model->set( 'network_nonce_value',   $nonce );
                $model->set( 'network_nonce_created', time() );
            }
        }

        $this->model()->popNetworkMode();

        return $nonce;
    }

    public function validateNetworkNonce( $nonce ) {

        $nonce = trim( $nonce );

        if (!$nonce) {
            return false;
        }

        $model = ncore_api()->load->model( 'logic/network_config' );


        if ($nonce == $model->getNetworkNonce())
        {
            return true;
        }

        if ($nonce == $model->getNetworkNonce( $get_previous=true ))
        {
            return true;
        }

        return false;

    }

    //
    // protected
    //

    protected function defaultValues()
    {
        return array();
    }

    protected function namePrefix() {
        return 'net_';
    }

    protected function loadSettings()
    {
        $this->model()->pushNetworkMode();
        parent::loadSettings();
        $this->model()->popNetworkMode();
    }

}