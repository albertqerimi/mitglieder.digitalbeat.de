<?php

class ncore_RuleValidator_RuleUserName extends ncore_RuleValidator_RuleBase
{
    public function validate( $value, $arg1='', $arg2='', $arg3='' )
    {
        if (is_numeric( $value )) {
            $user_id = $value;
        }
        else
        {
            $user_id = ncore_getUserIdByName( $value );
        }

        $user = ncore_getUserById( $user_id );
        return $user
               ? $user->user_login
               : false;
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a valid user id or name.' );
    }
}