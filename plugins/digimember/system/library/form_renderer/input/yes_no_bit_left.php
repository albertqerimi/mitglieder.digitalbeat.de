<?php

$load->loadPluginClass( 'radiobuttons' );

class ncore_FormRenderer_InputYesNoBitLeft extends ncore_FormRenderer_InputRadiobuttons
{
    protected function options()
    {
        $options = parent::options();

        if (!$options)
        {
            $options = array(
                'Y' => _ncore('yes'),
                'N' => _ncore('no'),
            );
        }
        return $options;
    }

    public function setValue($value)
    {
        $default = $this->meta('default');
        if ($default && !$value) {
            $value = $default;
        }
        parent::setValue($value);
    }
    public function rowLayout()
    {
        return 'left';
    }
}


