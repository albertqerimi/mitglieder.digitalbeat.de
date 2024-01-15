<?php

$load->controllerBaseClass( 'widget/base_edit' );

abstract class ncore_WidgetShortcodeEditController extends ncore_WidgetBaseEditController
{
    abstract protected function shortcode();

    protected function userDescription()
    {
        return $this->shortcodeMeta( 'description' );
    }

    protected function inputMetas()
    {
        return $this->shortcodeMeta( 'args', array() );
    }


    private $shortcode_meta=false;
    private function shortcodeMeta( $key='all', $default='' )
    {
        if ($this->shortcode_meta === false)
        {
            $shortcode = $this->shortCode();

            $this->shortcode_meta = $this->shortcodeController()->getShortcodeMetas( $shortcode );
        }

        return $key==='all'
               ? $this->shortcode_meta
               : ncore_retrieve( $this->shortcode_meta, $key, $default );
    }

    /**
     * @return ncore_ShortCodeController
     */
    private function shortcodeController()
    {
        return $this->api->load->controller( 'shortcode' );
    }

}

