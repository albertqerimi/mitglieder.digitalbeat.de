<?php

class ncore_FormRenderer_InputInt extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $value = $this->value();

        $attributes = array( 'id' => $html_id );

        $zero = $this->meta( 'display_zero_as', '0' );
        $size = $this->meta( 'size', false );
        $maxlength = $this->meta( 'maxlength', 0 );
        
        if ($value === '0' || intval($value) === 0)
        {
            $value = $zero;
        }

        if ($size)
        {
              $attributes['size'] = $size;

              if ($maxlength < $size)
              {
                  $maxlength = $size;
              }
        }
        if ($maxlength)
        {
            $attributes['maxlength'] = $maxlength;
        }

        return ncore_htmlIntInput( $postname, $value, $attributes );
    }

    protected function defaultRules()
    {
        return 'trim|numeric';
    }
}


