<?php

class ncore_RuleValidator_RuleCurrentPassword extends ncore_RuleValidator_RuleBase
{
    public function validate( $password, $arg1='', $arg2='', $arg3='' )
    {
        if (!$password) {
            return false;
        }
        
        try {
        
            $username          = ncore_userName();    
            $is_password_valid = (bool) ncore_wp_authenticate( $username, $password );
        
            return $is_password_valid;
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter your current password.' );
    }
}