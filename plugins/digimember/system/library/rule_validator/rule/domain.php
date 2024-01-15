<?php

$load->loadPluginClass( 'base_url' );

class ncore_RuleValidator_RuleDomain extends ncore_RuleValidator_RuleBaseUrl
{
    public function validate( $domain, $arg1='', $arg2='', $arg3='' )
    {
        return $this->validateDomain( $domain );
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a valid domain name.' );
    }
}