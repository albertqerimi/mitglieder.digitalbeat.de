<?php

class ncore_TableRendererArrayTable extends ncore_TableRendererBaseTable
{
    public function __construct( $parent, $rows, $settings ) {

        parent::__construct( $parent, $settings );

        $this->array = $rows;
    }


    //
    // protected
    //


    protected function getViewRowCount( $view_meta )
    {
        // $where = ncore_retrieve( $view_meta, 'where' );

        return count( $this->array );
    }

    protected function rowCount()
    {
        return count( $this->array );
    }

    protected function getRows( $limit, $order_by )
    {
        $array = $this->orderArray( $this->array, $order_by );

        $array = $this->applyLimit( $array, $limit );

        return $array;
    }

    //
    // private
    //
    private $array;

    private function applyLimit( $array, $limit )
    {
        if (!$limit)
        {
            return $array;
        }

        list( $start, $page_size ) = explode( ',', $limit );

        if (!$page_size)
        {
            $page_size = 1000;
        }

        $rows = array();

        while ($page_size-- && isset( $array[ $start] ))
        {
            $rows[] = $array[ $start++ ];
        }

        return $rows;
    }

    private function orderArray( $array, $order_by )
    {
        if (!$order_by)
        {
            return $array();
        }

        list( $column, $dir ) = explode( ' ', $order_by );

        $sort = array();
        foreach ($array as $one)
        {
            $sort[] = mb_strtolower( ncore_retrieve( $one, $column ) );
        }

        $sortdir = $dir == 'asc'
                 ? SORT_ASC
                 : SORT_DESC;

        array_multisort( $sort, $sortdir, $array );

        return $array;
    }



}

