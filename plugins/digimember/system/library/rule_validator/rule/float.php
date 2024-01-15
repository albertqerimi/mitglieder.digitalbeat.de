<?php

class ncore_RuleValidator_RuleFloat extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        $decimal_points = array( ',', '.' );
        $str = trim( str_replace( $decimal_points, '.', $string ) );

        $is_float = preg_match('/^[0-9\.]*$/', $str);
        return $is_float
               ? (float) $str
               : false;
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a number.', $this->type() );
    }
}