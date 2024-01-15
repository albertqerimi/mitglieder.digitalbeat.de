<?php

class ncore_FormRenderer_InputSelect extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();
        $postname = $this->postname();
        $selected_value = $this->value();

        $options = $this->options();

        if ($this->do_show_no_options_text) {
            return $this->renderNoOptionsText()
                 . ncore_htmlHiddenInput( $postname,  $selected_value );
        }

        $select_css = $this->form_visibility->select_css();

        $attributes = array(
            'class'      => $select_css,
            'id'         => $html_id,
            'onchange'   => $this->meta( 'onchange' ),
            'null_value' => $this->meta( 'null_value' ),
        );

        return ncore_htmlSelect( $postname, $options, $selected_value, $attributes );
    }

    protected function renderInnerReadonly()
    {
        $value   = $this->value();
        $options = $this->options();

        if ($this->do_show_no_options_text) {
            return $this->renderNoOptionsText();
        }

        if (isset( $options[ $value ] ))
        {
            return $options[ $value ];
        }

        return $this->meta( 'display_void_as', '' );
    }

    protected function defaultValue()
    {
        $value = parent::defaultValue();
        if (empty($value))
        {
            $allow_null = $this->meta( 'allow_null', false );
            if ($allow_null) {
                $value = $this->meta( 'null_value', '' );
            }
            else
            {
                $options = $this->_rawOptions();
                if (is_array($options) && $options)
                {
                    $keys = array_keys($options);
                    $value = $keys[0];
                }
            }
        }
        return $value;
    }

    protected function options()
    {
        $options = $this->_rawOptions();

        if (is_string($options)) {
            $this->no_options_text = $options;
            $options = array();
            $this->do_show_no_options_text = true;
        }
        elseif (!$options) {
            $this->do_show_no_options_text = (bool) $this->meta( 'no_options_text' );
        }

        $allow_null = $this->meta( 'allow_null', false );

        // array_merge() and array addition (+) seem to handle associative and numeric array inconsistently - thus this work around:
        $final_options = array();

        $must_add_void_entry = $allow_null && !isset( $options[''] )  && !isset( $options[0] ) && !isset( $options['NULL'] );
        if ($must_add_void_entry)
        {
            $final_options[ '' ] = $this->meta( 'null_label', '&nbsp;' );
        }

        $value = $this->value();

        $is_current_value_legal = !$value || isset($options[$value]);
        if (!$is_current_value_legal) {
            $invalid_label = $this->meta( 'invalid_label', false );
            if ($invalid_label)
            {
                $label = str_replace( '[VALUE]', $value, $invalid_label );
                $final_options[ $value ] = $label;
            }
        }

        if (!$final_options)
        {
            return $options;

        }

        foreach ($options as $key => $value)
        {
            $final_options[ $key ] = $value;
        }

        return $final_options;
    }

    protected function requiredMarker()
    {
        return '';
    }

    protected function resolveOptions( $options )
    {
        return ncore_resolveOptions( $options );;
    }

    private $do_show_no_options_text = false;
    private $no_options_text = '';

    private $raw_options;
    private function _rawOptions()
    {
        if (!isset($this->raw_options))
        {
            $options = $this->meta( 'options', array() );

            $this->raw_options = $this->resolveOptions( $options );
        }

        return $this->raw_options;
    }

    private function renderNoOptionsText()
    {
        $text = $this->meta( 'no_options_text' );
        if (!$text)
        {
            $text = $this->no_options_text;
        }

        return '<span class="ncore_no_options">' . $text . '</span>';
    }


}


