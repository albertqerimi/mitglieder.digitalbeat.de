<?php

class ncore_TableRenderer_TypeFlags extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $flag_labels = $this->meta( 'flag_labels', array() );
        $flag_tooltips = $this->meta( 'flag_tooltips', array() );

        $flags = $this->value( $row );
        $set_flags = array();

        foreach ($flag_labels as $flag => $label)
        {
            $is_set = $flag & $flags;
            if ($is_set)
            {
                $tooltip = ncore_retrieve( $flag_tooltips, $flag );
                $html = "<span class='ncore_texttoken'>$label</span>";
                $set_flags[] = $html . ncore_tooltip( $tooltip );
            }
        }

        $seperator = $this->meta( 'seperator', '<br />' );

        return implode( $seperator, $set_flags );
    }

    public function renderWhere( $search_for )
    {
        $array = $this->meta( 'array', array() );

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
}