<?php

class ncore_RuleValidator_RuleAlphaDash extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        return (!preg_match("/^([-a-z0-9_-])+$/i", $string)) ? FALSE : TRUE;
    }

    public function hintPriority()
    {
        return 10;
    }

    public function hintText( $arg1='', $arg2='', $arg3='' )
    {
        return  _ncore( 'Only letters, digits, dashes (-) and underscores (_)' );
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME], please do not enter a pure number. Add e.g. a letter.');
    }

}