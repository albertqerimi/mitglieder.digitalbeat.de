<?php

class ncore_TableRenderer_TypeIdList extends ncore_TableRenderer_TypeBase
{
    public function renderWhere( $search_for )
    {
        $model = $this->model();
        $name_column = $this->meta( 'name_column', 'name' );
        $compare = $this->meta( 'compare', 'equal' );

        $matches = $model->search( $name_column, $search_for, $compare );

        $conditions = array();

        $col = $this->column();

        $conditions[] = "$col LIKE 'all' OR $col LIKE '%,all' OR $col LIKE 'all,%' OR $col LIKE '%,all,%'";

        foreach ($matches as $one)
        {
            $id = $one->id;

            $conditions[] = "$col LIKE '$id' OR $col LIKE '%,$id' OR $col LIKE '$id,%' OR $col LIKE '%,$id,%'";
        }

        $where = array();

        $sql = '(' . implode( ' OR ', $conditions ) . ')';

        $column = $this->column();
        $where["$column sql"] = $sql;

        return $where;
    }

    protected function renderInner( $row )
    {
        $seperator = $this->meta( 'seperator', ', ' );
        $ids_comma_seperated = $this->value( $row );
        $ids = $ids_comma_seperated
             ? explode( ',', $ids_comma_seperated )
             : array();

        $model = $this->model();
        $name_column = $this->meta( 'name_column', 'name' );

        $names = array();

        foreach ($ids as $one)
        {
            if ($one === 'all')
            {
                return _ncore('all' );
            }

            $obj = $model->getCached( $one );

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
            $model_path   = $this->meta( 'model');
            $api_function = $this->meta( 'api');

            if (!$model_path)
            {
                trigger_error( 'Meta "model" required.' );
            }

            $api = $api_function
                 ? $api_function()
                 : $this->api;

            $this->model = $api->load->model( $model_path );
        }

        return $this->model;
    }

}