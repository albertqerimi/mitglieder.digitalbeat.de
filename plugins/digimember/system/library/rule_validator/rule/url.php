<?php

$load->loadPluginClass( 'base_url' );

class ncore_RuleValidator_RuleUrl extends ncore_RuleValidator_RuleBaseUrl
{
    public function validate( $url, $arg1='', $arg2='', $arg3='' )
    {
        $url = trim( $url );

        $is_relative_url = $url && $url[0] == '/';

        if ($is_relative_url)
        {
            return $this->validateRelativeUrl( $url );
        }
        else
        {
            return $this->validateAbsoluteUrl( $url );
        }
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a valid URL.' );
    }
}