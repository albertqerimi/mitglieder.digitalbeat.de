<?php

class ncore_FormRenderer_InputHint extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $hint = $this->meta('text' );
        return "<div class='hint'>$hint</div>";
    }

    public function isReadonly()
    {
        return true;
    }
}