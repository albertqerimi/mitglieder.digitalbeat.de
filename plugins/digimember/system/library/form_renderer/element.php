<?php

abstract class ncore_Formrenderer_ElementBase extends ncore_Plugin
{
    public function __construct( $parent, $meta )
    {
        $type = $meta['type'];

        parent::__construct( $parent, $type, $meta );
    }

    public function element_id()
    {
        return $this->meta( 'element_id', '' );
    }

    public function htmlId( $name = '' )
    {
        if (!$name)
        {
            $id = $this->meta( 'html_id' );
            if ($id) {
                return $id;
            }
        }

        if (!$name)
        {
            $name = $this->meta( 'name' );
        }

        $element_id = $this->elementId();

        return 'ncore_' . $name . $element_id;
    }

    public function columnName()
    {
        return $this->meta( 'name' );
    }

    public function postname( $field_name='' )
    {
        if (!$field_name)
        {
            $field_name = $this->columnName();
        }

        $element_id = $this->element_id();

        return "ncore_$field_name$element_id";
    }

    public function label()
    {
        return $this->meta( 'label' );
    }

    public function labelCss()
    {
        return $this->meta( 'label_css' );
    }

    public function inputCss()
    {
        return $this->meta( 'input_css' );
    }

    abstract public function render();

    public function isHidden()
    {
        return (bool) $this->meta('hide', false );
    }


    //
    // protected function
    //

    /**
     * @return ncore_FormRendererForm
     */
    protected function form()
    {
        return $this->parent();
    }

    //
    // private section
    //

}




