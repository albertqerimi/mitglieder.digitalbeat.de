<?php

class ncore_RuleValidator_RuleEmail extends ncore_RuleValidator_RuleBase
{
    public function validate( $email, $arg1='', $arg2='', $arg3='' )
    {
        $email = trim( str_replace( ' ', '+', $email ) );

        $filtered_email = filter_var( $email , FILTER_VALIDATE_EMAIL );

        if ($filtered_email)
        {
            return $filtered_email;
        }

        return false;
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a valid email address.' );
    }
}