<?php

class ncore_FormRenderer_InputRadiobuttons extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $postname = $this->postname();
        $value = $this->value();

        $css = $this->form_visibility->select_css()
             . ' '
             . $this->meta( 'class' );

        $attributes = array(
            'class' => $css,
        );

        $options = $this->options();

        return ncore_htmlRadioButtonList( $postname, $options, $value, $attributes );
    }

    protected function renderInnerReadonly()
    {
        $value = $this->value();
        $options = $this->options();

        return ncore_retrieve( $options, $value, $value );
    }

    protected function options()
    {
        return $this->meta( 'options', array() );
    }

    protected function requiredMarker()
    {
        return '';
    }

    public function setValue($value)
    {
        $default = $this->meta('default');
        if ($default && !$value) {
            $value = $default;
        }
        parent::setValue($value);
    }

}


