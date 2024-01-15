<?php

class ncore_RuleValidator_RuleMinLength extends ncore_RuleValidator_RuleBase
{
    public function validate( $str, $val='', $arg2='', $arg3='' )
    {
        if (preg_match("/[^0-9]/", $val))
        {
            trigger_error( "Expected number as argument, not '$val'" );
            return FALSE;
        }

        if (function_exists('mb_strlen'))
        {
            return (mb_strlen($str) < $val) ? FALSE : TRUE;
        }

        return (strlen($str) < $val) ? FALSE : TRUE;
    }

    public function hintPriority()
    {
        return 30;
    }

    public function hintText( $arg1='', $arg2='', $arg3='' )
    {
        $msg = $arg1 == 1
            ? _ncore( 'Minimun one character'  )
            : _ncore( 'Minimum %s characters', $arg1 );
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] enter at least [ARG] characters.');
    }

}