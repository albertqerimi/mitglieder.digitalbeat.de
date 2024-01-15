<?php

class ncore_RuleValidator_RuleTime extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' ) {
        return (preg_match('/([0-9]{1,2}):([0-9]{1,2})/',$string) === 1);
    }

    public function errorMessageTemplate() {
        return _ncore('For [NAME] please enter a valid time.', $this->type() );
    }
}