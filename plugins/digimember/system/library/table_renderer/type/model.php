<?php

class ncore_TableRenderer_TypeModel extends ncore_TableRenderer_TypeBase
{
    public function renderWhere( $search_for )
    {
        $model = $this->model();
        $name_column = $this->meta( 'name_column', 'name' );
        $compare = $this->meta( 'compare', 'equal' );

        $matches = $model->search( $name_column, $search_for, $compare );

        if (!$matches) {
            return array( 'id' => -1 );
        }

        $ids = array();

        $col = $this->column();

        foreach ($matches as $one)
        {
            $ids[] = $one->id;
        }

        $where = array( "$col IN" => $ids );

        return $where;
    }

    protected function renderInner( $row )
    {
        $id = $this->value( $row );

        $obj = $this->model()->get( $id );

        if (!$obj) {
            return $this->meta( 'void_value' );
        }

        $name_column = $this->meta( 'name_column', 'name' );

        $name = ncore_retrieve( $obj, $name_column );

        $label_template = $this->meta( 'label_template', '' );

        if (!$label_template) {
            return $name;
        }

        $find = array( '[ID]', '[NAME]' );
        $repl = array( $id, $name );

        return str_replace( $find, $repl, $label_template );

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