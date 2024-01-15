<?php

class ncore_FormRenderer_InputTemplate extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        return $this->renderInnerReadonly();
    }
    
    protected function renderInnerReadonly()
    {
        $html_id = ncore_id( 'template' );
        
        $lower_case = $this->meta( 'lower_case' );
        
        $this->api->load->helper( 'html_input' );
        $template = $this->meta( 'template' );
        
        $replace = $this->meta( 'replace', array() );
        
        $id = $this->element_id();
        
        $find = array( '[',     ']', '/',   '\'',   '?', '*' );
        $repl = array( "\\[", "\\]", "\\/", "\\'",  "\\?", "\\*" );
        
        $js_update = "var template=\"$template\"; var text=template";
        foreach ($replace as $key => $value)
        {
            $key_esc = str_replace( $find, $repl, $key );
            $postname = $this->form()->postname( $id, $value );
            $js_update .= ".replace( /$key_esc/, ncoreJQ(this).val() )";
        }
        if ($lower_case)
        {
            $js_update .= '.toLowerCase()';
        }
        
        $js_update.= "; ncoreJQ( '#$html_id' ).html( text );";
        
        $find = array();
        $repl = array();
        foreach ($replace as $key => $value)
        {
            $find[] = $key;
            $repl[] = $this->form()->value( $id, $value );
            
            $postname = $this->form()->postname( $id, $value );
            $js = "ncoreJQ( 'input[name=$postname]'  ).change( function(){ $js_update })";
            $js = "ncoreJQ( 'input[name=$postname]'  ).keyup(  function(){ $js_update })";
            ncore_addJsOnLoad( $js );
            
        }
        
        $html = str_replace( $find, $repl, $template );
        
        $html = "<span id='$html_id'>$html</span>";
        
        
        return $html;
    }
}