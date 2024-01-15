<?php

class ncore_RuleValidator_RuleLowerEqual extends ncore_RuleValidator_RuleBase
{
    public function validate( $str, $arg1='', $arg2='', $arg3='' )
    {
        $str = trim( $str );

        if (!is_numeric($str))
        {
            return false;
        }

        $is_valid = $str <= $arg1;

        return $is_valid
               ? $str
               : false;


    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a value of [ARG] or lower.' );
    }
}