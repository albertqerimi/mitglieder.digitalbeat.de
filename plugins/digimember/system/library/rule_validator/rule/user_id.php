<?php

class ncore_RuleValidator_RuleUserId extends ncore_RuleValidator_RuleBase
{
    public function validate( $value, $arg1='', $arg2='', $arg3='' )
    {
        if (is_numeric( $value )) {
            $user = ncore_getUserById( $value );
            return $user
                   ? $user->ID
                   : false;
        }

        $user_id = ncore_getUserIdByName( $value );
        return $user_id
               ? $user_id
               : false;
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a valid user id or name.' );
    }
}