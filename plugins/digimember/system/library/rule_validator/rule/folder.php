<?php

class ncore_RuleValidator_RuleFolder extends ncore_RuleValidator_RuleBase
{
    public function validate( $folder, $arg1='', $arg2='', $arg3='' )
    {
        $is_valid = is_dir( $folder );

        return $is_valid;
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a absolute path to a folder on the server (beginning with: %s)', '/' );
    }
}