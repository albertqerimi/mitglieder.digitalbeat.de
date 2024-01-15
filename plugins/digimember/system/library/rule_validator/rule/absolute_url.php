<?php

$load->loadPluginClass( 'base_url' );

class ncore_RuleValidator_RuleAbsoluteUrl extends ncore_RuleValidator_RuleBaseUrl
{
    public function validate( $url, $arg1='', $arg2='', $arg3='' )
    {
        return $this->validateAbsoluteUrl( $url );
    }


    public function errorMessageTemplate()
    {
        return _ncore('For [NAME], please enter a valid URL incl. domain name.', $this->type() );
    }
}