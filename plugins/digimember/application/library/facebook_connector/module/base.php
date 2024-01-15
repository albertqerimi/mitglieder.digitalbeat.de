<?php

class digimember_FacebookBaseModule extends ncore_Class
{
    public function __construct( $api, $parent, $app_id, $app_secret ) //, $use_extended_permissions=false )
    {
        parent::__construct( $api );

        $this->parent = $parent;
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        // $this->use_extended_permissions = (bool) $use_extended_permissions;
    }


    protected $parent;
    protected $app_id;
    protected $app_secret;
    // protected $use_extended_permissions;

    protected function requestedPremissions()
    {
        $permissions = digimember_FacebookConnectorLib::fb_permissions;
        // if ($this->use_extended_permissions)
        // {
        //     if ($permissions && digimember_FacebookConnectorLib::fb_permissions_extended) {
        //         $permissions .= ',';
        //     }
        //     $permissions .= digimember_FacebookConnectorLib::fb_permissions_extended;
        // }

        return $permissions;
    }



}
