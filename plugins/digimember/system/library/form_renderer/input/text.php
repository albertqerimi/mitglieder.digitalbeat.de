<?php

class ncore_FormRenderer_InputText extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $value = $this->value();

        $attributes = array( 'id' => $html_id );


        $this->meta2attributes( $attributes, array( 'size', 'class', 'maxlength', 'placeholder', 'readonly') );
        
        if ($this->hasRule( 'lower_case' )){
            $js = "this.value=this.value.toLowerCase()";
            $jsAttr=array(
                'onchange' => $js,
                'onkeyup' => $js,
            );
            $attributes = ncore_mergeAttributes( $attributes, $jsAttr );
        } 
        
        $length = $this->meta( 'length', 10 );
        
        $is_long = $length >= 50;
        if ($is_long) {
            ncore_addCssClass( $attributes, 'dm_wide_input' );
        }

        return ncore_htmlTextInput( $postname, $value, $attributes );
    }

    protected function defaultRules()
    {
        return 'trim';
    }
}



