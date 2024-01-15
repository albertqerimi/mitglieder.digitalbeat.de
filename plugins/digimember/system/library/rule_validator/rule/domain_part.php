<?php

$load->loadPluginClass( 'base_url' );

class ncore_RuleValidator_RuleDomainPart extends ncore_RuleValidator_RuleBaseUrl
{
    public function validate( $domain_part, $arg1='', $arg2='', $arg3='' )
    {
        return $this->validateDomainPart( $domain_part );
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a valid name consisting of letters, digits and dashes only. The name must start with a letter and end with a letter or a digit.' );
    }
}