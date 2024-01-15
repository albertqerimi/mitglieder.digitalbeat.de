<?php

abstract class digimember_BlockRenderer_PluginBase extends ncore_Plugin
{
    public $block_name = false;
    public function __construct( digimember_BlockRendererLib $parent, $meta )
    {
        $this->block_name = ncore_retrieve( $meta, 'name', false );
        $type = $meta['type'];
        parent::__construct( $parent, $type, $meta );
    }
}
