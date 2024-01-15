<?php

abstract class ncore_RuleValidator_RuleBase extends ncore_Plugin
{
    public function __construct( $parent, $type )
    {
        $meta = array();

        parent::__construct( $parent, $type, $meta );

    }

    public function errorMessageTemplate()
    {
        return _ncore('[NAME] violated rule &quot;%s&quot;!', $this->type() );
    }

    abstract public function validate( $string, $arg1='', $arg2='', $arg3='' );

    public function hintPriority()
    {
        return 100;
    }

    public function hintText( $arg1='', $arg2='', $arg3='' )
    {
        return '';
    }


}