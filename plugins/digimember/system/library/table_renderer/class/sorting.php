<?php

class ncore_TableRendererSorting
{
    public function __construct( $api, $current_url, $default_order_by, $default_order_dir='asc' )
    {
        $this->api = $api;
        $this->url = $current_url;


        $order_by  = ncore_retrieve( $_REQUEST, 'by' );
        $order_dir = ncore_retrieve( $_REQUEST, 'up', '1' );

        if ( !$order_by )
        {
            $order_by = $default_order_by;
            $order_dir = $default_order_dir;
        }


        $this->order_up = $order_dir == 'up'
                         || $order_dir == 'asc'
                         || $order_dir === '1';

        $this->order_by = ncore_washText( $order_by );
    }

    public function header( ncore_TableRenderer_TypeBase $renderer, &$class, &$url )
    {
        list( $column, $dir ) = $renderer->sorting();

        if (!$column)
        {
            $url = '';
            return;
        }

        $is_sorted = $column == $this->order_by;

        if ($is_sorted)
        {
             $class[] = 'sorted';
             $class[] = $this->order_up ? 'asc' : 'desc';

             $new_up = $this->order_up ? 0 : 1;
        }
        else
        {
            $class[] = 'sortable';
            $class[] = $dir=='asc' ? 'desc' : 'asc';

            $new_up = $dir=='asc' ? 1 : 0;
        }

        $args = array(
            'by' => $column,
            'up' => $new_up,
        );

        $url = ncore_removeArgs($this->url, 'action', '&');
        $url = ncore_addArgs($url, $args );
    }

    function getUrlArgs()
    {
        $args = array();

        if ($this->order_by) {
            $args[ 'by' ] = $this->order_by;

            $args[ 'up' ] =$this->order_up ? '1' : '0';
        }

        return $args;
    }

    public function sqlOrderBy()
    {
        $sql = $this->order_by;
        if (!$sql)
        {
            return '';
        }

        $dir = $this->order_up
             ? 'asc'
             : 'desc';

        $sql .= " $dir";

        return $sql;
    }

    private $api;
    private $url = '';
    private $order_by = '';
    private $order_up = true;
}