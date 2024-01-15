<?php

class ncore_RuleValidator_RuleNotNumeric extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        $is_numeric = preg_match('/^[0-9]{1,}$/', trim($string));
        return !$is_numeric;
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME], please do not enter a pure number. Add e.g. a letter.');
    }
}