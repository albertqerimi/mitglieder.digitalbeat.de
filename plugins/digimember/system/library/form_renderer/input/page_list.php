<?php

$load->loadPluginClass( 'checkbox_list' );

class ncore_FormRenderer_InputPageList extends ncore_FormRenderer_InputCheckboxList
{

    protected function options()
    {
        return ncore_resolveOptions( 'page' );
    }

    protected function checkboxSeperator()
    {
        return $this->meta( 'seperator', '<br />' );
    }

}