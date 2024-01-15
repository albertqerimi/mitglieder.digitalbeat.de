<?php

class ncore_TableRenderer_TypeRate extends ncore_TableRenderer_TypeBase
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
        $decimals = $this->meta( 'decimals', 1 );
        $percent  = $this->meta( 'percent', '%' );

        $value = $this->value( $row );

        if (!$value)
        {
            $value = $this->meta( 'display_zero_as', 0.0 );
        }

        return number_format_i18n( $value, $decimals ) . $percent;
    }
}