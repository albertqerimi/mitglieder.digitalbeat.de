<?php

class ncore_TableRenderer_TypeCheckboxList extends ncore_TableRenderer_TypeBase
{
    public function renderWhere( $search_for )
    {
        $options = $this->options();

        $search_keys = array();
        foreach ($options as $key => $label)
        {
            $matches = mb_stripos( $label, $search_for ) !== false;
            if ($matches) {
                $search_keys[] = $key;
            }
        }
        if (!$search_keys) {
            $where = array( 'id' => 0 );
            return $where;
        }

        $col = $this->column();

        $conditions = array();
        foreach ($search_keys as $one)
        {
            $one = ncore_washText($one);

            $conditions[] = "$col='$one'";
            $conditions[] = "$col LIKE '$one,%'";
            $conditions[] = "$col LIKE '%,$one'";
            $conditions[] = "$col LIKE '%,$one,%'";
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
        $values_comma_seperated = $this->value( $row );

        $values = explode( ',', $values_comma_seperated);

        $seperator  = $this->meta( 'seperator', ', ' );
        $options    = $this->options();
        $void_value = $this->meta( 'void_value' );

        $names = array();

        foreach ($values as $one)
        {
            $one = trim( $one );
            if (!$one) {
                continue;
            }

            $label = ncore_retrieve( $options, $one, $one );
            if (!$label) {
                continue;
            }

            $names[] = $label;
        }

        if (!$names)
        {
            return $void_value;
        }


        sort( $names, SORT_LOCALE_STRING );

        $html = implode( $seperator, $names );

        return $html;
    }

    private function options()
    {
        $options = $this->meta( array('array','options'), array() );

        $options = ncore_resolveOptions( $options );

        if (is_array($options)) {
            return $options;
        }

        return array();

    }

}