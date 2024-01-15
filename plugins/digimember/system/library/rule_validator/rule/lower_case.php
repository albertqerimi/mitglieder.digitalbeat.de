<?php

class ncore_RuleValidator_RuleLowerCase extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        return mb_strtolower( $string );
    }
}