<?php

class ncore_RuleValidator_RuleIntList extends ncore_RuleValidator_RuleBase
{
    public function validate( $int_list_comma_seperated, $arg1='', $arg2='', $arg3='' )
    {
        $tokens = explode( ',', str_replace( array( ';',',',' '), ',', $int_list_comma_seperated ) );

        $ints = array();

        foreach ($tokens as $one) {

            $int = intval( $one );
            if ($int>0) {
                $ints[] = $int;
            }
        }

        sort( $ints, SORT_NUMERIC );

        return implode( ', ', $ints );
    }

    public function errorMessageTemplate()
    {
        return _ncore('For [NAME] please enter a valid email address.' );
    }
}
