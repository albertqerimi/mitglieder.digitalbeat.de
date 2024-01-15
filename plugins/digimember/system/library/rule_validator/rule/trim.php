<?php

class ncore_RuleValidator_RuleTrim extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        if (!is_string($string)) {
            return $string;
        }

        return trim( $string );
    }
}