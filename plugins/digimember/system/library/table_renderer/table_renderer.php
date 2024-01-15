<?php

class ncore_TableRendererLib extends ncore_Library
{
    public function createModelTable( $model, $columns, $settings )
    {
        $this->api->load->helper( 'html_input' );

        require_once 'base_table.php';
        require_once 'model_table.php';

        $table = new ncore_TableRendererModelTable( $this, $model, $settings );

        $this->initTable( $table, $columns, $settings );

        return $table;
    }

    public function createArrayTable( $rows, $columns, $settings )
    {
        $this->api->load->helper( 'html_input' );

        require_once 'base_table.php';
        require_once 'array_table.php';

        $table = new ncore_TableRendererArrayTable( $this, $rows, $settings );

        $this->initTable( $table, $columns, $settings );

        return $table;
    }

    protected function pluginDir()
    {
        return 'type';
    }

    private function initTable( ncore_TableRendererBaseTable $table, $columns, $settings )
    {
        $have_bulk_actions = (bool) ncore_retrieve( $settings, 'bulk_actions' );
        if ($have_bulk_actions)
        {
            $meta = array(
                'column' => 'id',
                'type'   => 'checkbox',
                'css'    => 'ncore_bulk_action_id',
            );

            $class = $this->loadPluginClass( 'checkbox' );

            $input =  new $class( $table, $meta );

            $table->add( $input );
        }

        foreach ($columns as $one)
        {
            $type = $one['type'];
            if (!empty($one['hide'])) {
                continue;
            }

            $class = $this->loadPluginClass( $type );

            if (!$class || !is_string($class) || !class_exists( $class)) {
                trigger_error( "Invalid type: '$type'" );
            }

            $input =  new $class( $table, $one );

            $table->add( $input );
        }
    }
}