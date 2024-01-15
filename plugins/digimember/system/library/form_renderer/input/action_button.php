<?php

class ncore_FormRenderer_InputActionButton extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        return $this->renderInnerReadonly();
    }

    protected function renderInnerReadonly()
    {
        $hints    = $this->meta( 'instructions' );
        $label    = $this->meta( 'action_label' );
        $confirm  = $this->meta( 'confirm' );
        $class    = $this->meta( 'class', 'dm-btn dm-btn-primary' );
        $disabled = $this->meta( 'disabled', false );

        $name = $this->meta( 'name' );

        if ($confirm)
        {
            $find = array( "'", "<p>", '|' );
            $repl = array( "\\'", "\\n\\n", "\\n\\n" );
            $confirm = str_replace( $find, $repl, $confirm );
            $js_attr = "onclick=\"return confirm('$confirm');\"";
        }
        else
        {
            $js_attr = '';
        }

        $disable_attr = $disabled
                      ? 'disabled="disabled"'
                      : '';

        $button  = "<input $js_attr $disable_attr name='$name' class='$class' type='submit' value='$label' />";

        if ($hints)
        {
            $hints .= "<br />";
        }

        if ($hints) {
            return ncore_htmlAlert('info', '', 'info', $hints, $button);
        }
        else {
            return "<div class='dm-text'>$hints$button</div>";
        }
    }

    public function isReadonly()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function fullWidth()
    {
        return true;
    }
}
