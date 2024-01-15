<?php

class ncore_RuleValidator_RuleTimezone extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        try {
            new DateTimeZone($string);
        }
        catch(Exception $e){
            return false;
        }
        return true;
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a valid timezone.', $this->type() );
    }
}