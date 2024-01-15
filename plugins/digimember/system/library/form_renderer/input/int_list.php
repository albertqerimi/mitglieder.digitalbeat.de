<?php

$load->loadPluginClass( 'text' );

class ncore_FormRenderer_InputIntList extends ncore_FormRenderer_InputText
{          
    public function __construct( $parent, $meta )
    {
        if (!isset($meta['hint']))
        {
            $meta['hint'] = _ncore( 'Seperate multiple values by commas.' );
        }

        parent::__construct( $parent, $meta );
    }
        
    public function value()
    {
        $value = parent::value();
            
        $this->api->load->helper( 'string' );
        
        $zero = $this->meta( 'display_zero_as', '0' );
        $allow_duplicates = $this->meta( 'allow_duplicates', true );
        
        return ncore_santizeIntList( $value, $zero, $allow_duplicates );
    }
       
    
}


