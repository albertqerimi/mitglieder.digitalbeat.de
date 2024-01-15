<?php

class ncore_FormRenderer_InputCheckboxList extends ncore_FormRenderer_InputBase
{
    public function rowLayout()
    {
        return 'block';
    }

    protected function renderInnerWritable()
    {
        $postname = $this->postname();
        $selected_values = $this->value();
        $options = $this->options();

        $select_css = $this->form_visibility->select_css();

        $attributes = array(
            'class' => $select_css,
            'have_all' => $this->meta( 'have_all', false ),
            'row_size' => $this->meta( 'row_size', '' ),
            'seperator' => $this->checkboxSeperator(),
            'all_label' => $this->meta( 'all_label', '' ),
            'no_options_text' => $this->meta( 'no_options_text', '' ),
        );

        return ncore_htmlCheckboxList($postname, $options, $selected_values, $attributes );
    }

    protected function checkboxSeperator()
    {
        return $this->meta( 'seperator', '' );
    }

    protected function renderInnerReadonly()
    {
        $sep = ', ';
        $options = $this->options();
        $ids = explode( ',', $this->value() );

        $all_selected = in_array( 'all', $ids );
        if ($all_selected)
        {
            return ncore_retrieve( $options, 'all', _ncore( 'all' ) );
        }

        $html = '';
        foreach ($options as $id => $label)
        {
            $checked = in_array( $id, $ids );

            if (!$checked)
            {
                continue;
            }

            if ($html)
            {
                $html .= $sep;
            }

            $html .= $label;

        }

        return $html;
    }

    protected function options()
    {
        return ncore_resolveOptions( $this->meta( 'options', array() ) );
    }

    protected function requiredMarker()
    {
        return '';
    }
}


