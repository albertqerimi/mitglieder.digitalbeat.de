<?php

class ncore_FormRenderer_InputHidden extends ncore_FormRenderer_InputBase
{
    public function isHiddenInput()
    {
        $hint = $this->meta( 'hint' );
        return empty( $hint );
    }

    public function value()
    {
        $value = $this->meta( 'value' );

        return $value
               ? $value
               : parent::value();
    }
    protected function hint()
    {
        return '';
    }

    protected function renderInnerWritable()
    {
        $postname = $this->postname();
        $value = $this->value();

        $hint = $this->meta( 'hint' );

        $html = ncore_htmlHiddenInput( $postname, $value );

        if ($hint)
        {
            $css = $this->meta( 'must_save_css', 'ncore_must_save_hint' );
            $html .= "<span class='$css'>$hint</span>";
        }

        return $html;
    }

    protected function defaultRules()
    {
        return 'trim';
    }
}



