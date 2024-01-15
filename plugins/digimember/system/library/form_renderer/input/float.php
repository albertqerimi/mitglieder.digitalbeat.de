<?php

class ncore_FormRenderer_InputFloat extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $value = $this->value();

        $attributes = array( 'id' => $html_id );

        $decimals = $this->meta( 'decimals', 2 );

        $zero  = sprintf( "%.${decimals}f", 0.00 );

        $zero = $this->meta( 'display_zero_as', $zero );
        $size = $this->meta( 'size', false );
        $maxlength = $this->meta( 'maxlength', 0 );

        $is_zero = str_replace( array( '0',',','.'), '', $value ) === '';
        if ($is_zero)
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

        $attributes[ 'decimals' ] = $decimals;

        return ncore_htmlFloatInput( $postname, $value, $attributes );
    }

    protected function defaultRules()
    {
        return 'trim|float';
    }

    protected function retrieveSubmittedValue( $_POST_or_GET, $field_name )
    {
        $value = parent::retrieveSubmittedValue( $_POST_or_GET, $field_name );

        if (!$field_name)
        {
            $this->api->load->helper( 'format' );
            $value = ncore_parseFloat( $value );
        }

        return $value;
    }
}


