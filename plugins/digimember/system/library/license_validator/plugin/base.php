<?php

abstract class ncore_LicenseValidator_PluginBase extends ncore_Plugin
{
    abstract public function licenseCheckEnabled();

    abstract public function getLicense( $force_reload=false );

    abstract public function fetchLicense( $license_key );

    abstract public function clearLicense();

    abstract public function licenseStatus();

    abstract public function getLicenseErrors( $force_reload = false);

    abstract public function getFeature( $feature );


}
