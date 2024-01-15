<?php

class ncore_RuleValidator_RuleColor extends ncore_RuleValidator_RuleBase {
    public function validate( $string, $arg1='', $arg2='', $arg3='' ) {
        return (preg_match('/^#[a-f0-9]{6}$/i',$string) === 1);
    }

    public function errorMessageTemplate() {
        return _ncore('For [NAME] please enter a valid color.', $this->type() );
    }
}