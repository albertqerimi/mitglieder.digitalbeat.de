<?php

class digimember_PaymentHandler_PluginGeneric extends digimember_PaymentHandler_PluginBase
{
    protected function parameterNameMap()
    {
        return $this->parseMap( 'parameter_map' );
    }

    protected function eventMap()
    {
        return $this->parseMap( 'event_map' );
    }

    protected function methods()
    {
        return array( METHOD_POST, METHOD_GET );
    }

    public function formMetas()
    {
        $parameter_names = $this->parent()->parameterNames();
        $event_names = $this->parent()->eventNames();

        $parameter_defaults = array();
        foreach ($parameter_names as $key => $value)
        {
            $parameter_defaults[ $key ] = $key;
        }

        $event_defaults = array();
        foreach ($event_names as $key => $value)
        {
            $event_defaults[ $key ] = $key;
        }
        $event_defaults[ EVENT_MISSED_PAYMENT ] = $event_defaults[ EVENT_REFUND ];

        return array(
            array(
                'name' => 'product_code_map',
                'type' => 'map',
                'label' => _digi3('External product ids' ),
                'array' => $this->productOptions(),
                'hint' => _digi('Seperate multiple product ids by commas.'),
            ),
             array(
                'name' => 'parameter_map',
                'type' => 'map',
                'label' => _digi('Parameter names' ),
                'array' => $parameter_names,
                'hint' => _digi('Seperate multiple entries by commas.'),
                'default' => $parameter_defaults,
            ),

            array(
                'name' => 'event_map',
                'type' => 'map',
                'label' => _digi('Event names' ),
                'array' => $event_names,
                'default' => $event_defaults,
                'hint' => _digi('Seperate multiple entries by commas.'),
            ),
        );
    }

    public function instructions()
    {
        $model = $this->api->load->model( 'logic/link' );
        $download_url = $model->downloadExample( 'digimember_ipn_sender' );

        $msg_templ = _digi3('You may <a>download an example php script</a>, which explains how to integrate a payment provider into DigiMember.');

        $find = "<a>";
        $repl = "<a href='$download_url'>";

        $download = str_replace( $find, $repl, $msg_templ );

        $instructions = array();

        $instructions[] = _digi3( 'This payment provider is for advanced users only.' );
        $instructions[] = _digi3( 'It allows you to attach any system to DigiMember. Warning: Programming skills required.' );
        $instructions[] = $download;

        return $instructions;
    }

}