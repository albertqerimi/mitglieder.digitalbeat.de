<?php

class ncore_TableRenderer_TypeId extends ncore_TableRenderer_TypeBase
{
    public function renderWhere( $search_for )
    {
        if (!is_numeric($search_for)) {
            return array( 'id' => -1 );
        }

        $seperators = array( ',', ';', ' ' );

        $search_for = str_replace( $seperators, ' ', $search_for );

        $tokens = explode( ' ', $search_for );

        $ids = array();

        foreach ($tokens as $one)
        {
            $is_id = is_numeric( $one );
            if ($is_id)
            {
                $ids[] = $one;
            }
        }

        $where = array();

        $column = $this->column;
        $where["$column IN"] = $ids;

        return $where;
    }

    protected function renderInner( $row )
    {
        $id = $this->value( $row );

        return $id;
    }

}