<?php

class digimember_BlockRendererLib extends ncore_Library
{

    public $blocks;

    /**
     * registerBlock
     * loads the plugin for the given block config and calls its register method
     * @param $blockConfig
     */
    public function registerBlock($blockConfig) {
        $blockObject = $this->loadPlugin($blockConfig);
        $blockObject->register();
        $this->addBlock($blockObject);
    }

    /**
     * getBlockConfig
     * loads the plugin for the blockconig and get its config
     * @param $blockConfig
     * @return mixed
     */
    public function getBlockConfig($blockConfig) {
        $blockObject = $this->loadPlugin($blockConfig);
        return $blockObject->getBlockConfig();
    }

    /**
     * loadPlugin
     * get the plugin class for the given blockconfig
     * @param $blockConfig
     * @return false|mixed
     */
    private function loadPlugin( $blockConfig )
    {
        $class_name = $this->loadPluginClass( $blockConfig['type'] );
        if (empty($class_name) || !class_exists( $class_name )) {
            trigger_error( "Could not load class file for type ".$blockConfig['type'] );
            return false;
        }
        $plugin = new $class_name( $this, $blockConfig );
        return $plugin;
    }
    public function addBlock($block) {
        $this->blocks[] = $block;
    }
}