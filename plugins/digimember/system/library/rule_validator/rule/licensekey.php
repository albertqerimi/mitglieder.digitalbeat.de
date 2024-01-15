<?php

class ncore_RuleValidator_RuleLicensekey extends ncore_RuleValidator_RuleBase
{
    public function validate( $str, $arg1='', $arg2='', $arg3='' )
    {
        $this->api->load->helper( 'license' );

        $str = trim( $str );

        $is_valid = ncore_validateLicensekeyChecksum( $str );

        return $is_valid
               ? $str
               : false;
    }

    public function errorMessageTemplate()
    {
        return _ncore('Please enter a valid license key for [NAME].' );
    }
}