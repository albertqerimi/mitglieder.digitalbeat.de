<?php

class ncore_RuleValidator_RuleUnixLinebreaks extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        return str_replace( array( "\r\n", "\r" ), "\n" , $string );
    }
}