<?php

class ncore_TableRenderer_TypeMappedIds extends ncore_TableRenderer_TypeBase
{
    public function renderWhere( $search_for )
    {
        $model = $this->model();
        $name_column = $this->meta( 'name_column', 'name' );
        $compare = $this->meta( 'compare', 'equal' );

        $matches = $model->search( $name_column, $search_for, $compare );

        $conditions = array();

        $col = $this->column();

        foreach ($matches as $one)
        {
            $id = $one->id;

            $conditions[] = "($col LIKE   '$id:\"%' AND $col NOT LIKE   '$id:\"\"%')";
            $conditions[] = "($col LIKE '%,$id:\"%' AND $col NOT LIKE '%,$id:\"\"%')";
        }

        $where = array();

        $sql = '(' . implode( ' OR ', $conditions ) . ')';

        $column = $this->column();
        $where["$column sql"] = $sql;

        return $where;
    }

    protected function init()
    {
        parent::init();

        $this->api->load->helper( 'array' );
    }


    protected function renderInner( $row )
    {
        $overrides = $this->meta( 'override', array() );
        foreach ($overrides as $col => $value_label)
        {
            $value = ncore_retrieve( $row, $col );
            foreach ($value_label as $v => $l)
            {
                if ($value == $v)
                {
                    return $l;
                }
            }
        }

        $seperator = $this->meta( 'seperator', ', ' );
        $map_imploded = $this->value( $row );
        $map = ncore_simpleMapExplode( $map_imploded );

        $model = $this->model();
        $name_column = $this->meta( 'name_column', 'name' );

        $names = array();

        foreach ($map as $id => $value)
        {
            if (!trim($value))
            {
                continue;
            }

            $obj = $model->getCached( $id );

            if (!$obj)
            {
                continue;
            }

            $names[] = $obj->$name_column;
        }

        sort( $names, SORT_LOCALE_STRING );

        $html = implode( $seperator, $names );

        return $html
               ? $html
               : $this->meta( 'void_value' );

    }

    private $model;

    private function model()
    {
        if (!isset($this->model))
        {
            $model_path = $this->meta( 'model');
            if (!$model_path)
            {
                trigger_error( 'Meta "model" required.' );
            }
            $this->model = $this->api->load->model( $model_path);
        }

        return $this->model;
    }

}