<?php

class ncore_TableRenderer_TypeIntList extends ncore_TableRenderer_TypeBase
{
    public function renderWhere( $search_for )
    {
        if (!is_numeric($search_for)) {
            return array( 'id' => -1 );
        }

        return parent::renderWhere( $search_for );
    }


    protected function renderInner( $row )
    {
        $this->api->load->helper( 'string' );

        $values = $this->value( $row );

        $zero = $this->meta( 'display_zero_as', '0' );
        $allow_duplicates = $this->meta( 'allow_duplicates', true );

        return ncore_santizeIntList( $values, $zero, $allow_duplicates );

    }

}