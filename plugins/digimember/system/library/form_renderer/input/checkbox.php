<?php

class ncore_FormRenderer_InputCheckbox extends ncore_FormRenderer_InputBase
{
    public function label()
    {
        return $this->singleCell()
               ? 'none'
               : parent::label();
    }

    public function isSingleRow()
    {
        return true;
    }

    protected function renderInnerWritable()
    {
        $single_cell = $this->singleCell();

        $html_id = $this->htmlId();
        $postname = $this->postname();
        $checked = (bool) $this->value();

        $css = $this->form_visibility->select_css();

        $attributes = array(
            'id' => $html_id,
        );

        $hidden_input_attributes = array(
            'class' => $css,
        );

        $label = $single_cell
               ? $this->meta('checkbox_label', parent::label() )
               : $this->meta('checkbox_label');

        $checked_value = $this->meta( 'checked_value' );
        if ($checked_value) {
            $attributes[ 'checked_value' ] = $checked_value;
        }

//DM-275 one attempt to fix initial checked unchecked status for checkboxes. but it doesnt work on javascript side.
//        $unchecked_value = $this->meta( 'unchecked_value' );
//        if ($unchecked_value) {
//            $attributes[ 'unchecked_value' ] = $unchecked_value;
//            if ($this->defaultValue() == $unchecked_value) {
//                $checked = false;
//            }
//        }

        return ncore_htmlCheckbox( $postname, $checked, $label, $attributes, $hidden_input_attributes );
    }

    protected function requiredMarker()
    {
        return '';
    }

    private function singleCell()
    {
        $single_cell = $this->parent()->layout() == 'narrow';
        return $single_cell;
    }

}


