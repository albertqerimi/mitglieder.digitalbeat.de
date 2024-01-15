<?php

$load->loadPluginClass( 'domain' );

class ncore_RuleValidator_RuleUmlautDomain extends ncore_RuleValidator_RuleDomain
{
    protected function validateDomainPart( $part )
    {
        return preg_match( '/^[äöüÄÖÜßa-z\d][äöüÄÖÜßa-z\d-]{0,62}$/i', $part ) && !preg_match( '/-$/', $part );
    }

}