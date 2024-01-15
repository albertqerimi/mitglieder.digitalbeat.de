<?php

class ncore_RuleValidator_RuleRemoveWhitespace extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        $find = array( " ", "\t", "\n", "\r" );
        $repl = '';
        return str_replace( $find, $repl, trim( $string ) );
    }
}