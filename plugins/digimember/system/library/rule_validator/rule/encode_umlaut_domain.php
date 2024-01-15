<?php

$load->loadPluginClass( 'umlaut_domain' );

class ncore_RuleValidator_RuleEncodeUmlautDomain extends ncore_RuleValidator_RuleDomain
{
    public function validate( $domain, $arg1='', $arg2='', $arg3='' )
    {
        $domain = parent::validate( $domain, $arg1, $arg2, $arg3 );

        $lib = ncore_api()->load->library( 'Net_IDNA2' );
        $domain = $lib->encode( $domain );

        return $domain;
    }
}