<?php

$load->loadPluginClass( 'cleverreach' );

class digimember_AutoresponderHandler_PluginLeadmotor extends digimember_AutoresponderHandler_PluginCleverreach
{
    protected function arName() {
        return 'LeadMotor';
    }
}
