<?php

require_once 'response.php';

class ncore_AjaxLib extends ncore_Library
{
    public function dialog( $meta )
    {
        $dialog = $this->create( $meta );

        return $dialog;
    }


    protected function create( $meta )
    {
        $type = $meta['type'];

        $class = $this->loadPluginClass( $type );

        /** @var ncore_Ajax_DialogBase $dialog */
        $dialog = new $class( $this, $type, $meta );

        return $dialog;
    }
    protected function pluginDir()
    {
        return 'dialog';
    }

}
