<?php

class ncore_RuleValidator_RuleTrimlines extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        if (!is_string($string)) {
            return $string;
        }

        $lines = explode( "\n", $string );
        foreach ($lines as $index => $line) {
            $lines[ $index ] = trim( $line );
        }

        return trim( implode( "\n", $lines ) );
    }
}