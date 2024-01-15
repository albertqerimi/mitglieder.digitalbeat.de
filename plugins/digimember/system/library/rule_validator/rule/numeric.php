<?php

class ncore_RuleValidator_RuleNumeric extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        $str = trim( $string );
        $is_integer = preg_match('/^[0-9]*$/', $str);
        return $is_integer
               ? $str
               : false;
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a number.', $this->type() );
    }
}