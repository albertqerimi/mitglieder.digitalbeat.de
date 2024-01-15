<?php

class ncore_FormRenderer_InputButtonList extends ncore_FormRenderer_InputBase
{
    public function rowLayout()
    {
        return 'block';
    }

    protected function renderInnerWritable()
    {
        $rows = $this->options();

        $select_css = $this->form_visibility->select_css();

        $attributes = array(
            'class' => $select_css,
            'button_text' => $this->meta( 'button_text', false ),
            'show_product_name' => $this->meta( 'show_product_name', false ),
            'show_order_id' => $this->meta( 'show_order_id', false ),
            'seperator' => $this->rowSeperator(),
        );

        return ncore_htmlButtonList($rows, $attributes );
    }

    protected function rowSeperator()
    {
        return $this->meta( 'seperator', '' );
    }

    protected function options()
    {
        return ncore_resolveOptions( $this->meta( 'rows', array() ) );
    }

    protected function requiredMarker()
    {
        return '';
    }
}


