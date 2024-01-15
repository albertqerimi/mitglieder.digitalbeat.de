<?php

class ncore_TableRendererModelTable extends ncore_TableRendererBaseTable
{
    protected $model;

    public function __construct( $parent, $model, $settings ) {

        parent::__construct( $parent, $settings );

        $this->setModel( $model );

    }


    //
    // protected
    //
    protected function getViewRowCount( $view_meta )
    {
        $view = $view_meta['view'];

        $count =& $this->view_row_count_cache[ $view ];

        if (!isset($count))
        {
            $view_where   = ncore_retrieve( $view_meta, 'where', array() );
            $global_where = $this->setting( 'where' );

            $where = array_merge( $global_where,$view_where );

            $count = $this->model->getCount( $where );
        }

        return $count;
    }

    protected function rowCount()
    {
        $model = $this->model;
        $where = $this->getWhere();
        return $model->getCount($where);
    }

    protected function getRows( $limit, $order_by )
    {
        $model = $this->model;
        $where = $this->getWhere();
        return $model->getAll($where, $limit, $order_by);
    }



    //
    // private
    //
    private $view_row_count_cache = array();

    private function setModel( $model )
    {

        if (is_string($model))
        {
            $this->model = $this->api->load->model( $model );
        }
        elseif (is_object($model))
        {
            $this->model = $model;
        }
        else
        {
            trigger_error( 'Invalid $model' );
        }
    }


    private function getWhere()
    {
        $currentView = $this->currentView();

        $view_where   = ncore_retrieve( $currentView, 'where', array() );
        $global_where = $this->setting( 'where' );

        $where = array_merge( $global_where,$view_where );

        $search_for = $this->searchFor();

        $search_for = trim( strtolower( $search_for ) );

        if (!$search_for)
        {
            return $where;
        }

        foreach ($this->renderers as $one)
        {
            $search_type = $one->searchType();

            $condition = $one->renderWhere( $search_for );

            $is_generic_search = $search_type == 'generic';

            if ($is_generic_search)
            {
                if (!isset($where['or']))
                {
                    $where['or'] = array();
                }

                foreach ($condition as $k => $v)
                {
                    $where['or'][ $k ] = $v;
                }

                continue;
            }
        }

        return $where;
    }




}

