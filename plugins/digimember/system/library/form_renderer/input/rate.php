<?php

$load->loadPluginClass( 'float' );

class ncore_FormRenderer_InputRate extends ncore_FormRenderer_InputFloat
{
    protected function renderInnerWritable()
    {
        $input = parent::renderInnerWritable();

        return '
<div class="dm-input-group">
    ' . $input . '
    <label class="dm-input-icon">%</label>
</div>
';
    }

    protected function renderInnerReadonly()
    {
        $value = parent::renderInnerReadonly();
        return $value.'%';
    }

    protected function defaultRules()
    {
        return 'trim|float|greater_equal[0]|lower_equal[100]';
    }
}


