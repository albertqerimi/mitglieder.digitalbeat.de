<?php

class digimember_TableRenderer_TypeUpgradeInfo extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $start_col = $this->meta( 'access_starts_col', 'access_starts_on' );
        $stop_col  = $this->meta( 'access_stop_col',   'access_stops_on' );

        $hints = array();

        $start_at = ncore_retrieve( $row, $start_col );

        if ($start_at)
        {
            $start_at_msg =ncore_formatDateTime( $start_at );
            $hints[] = _digi( 'Access granted after: %s', $start_at_msg );
        }

        $stop_at = ncore_retrieve( $row, $stop_col );
        if ($stop_at)
        {
            $stop_at_msg =ncore_formatDateTime( $stop_at );

            $hints[] = _digi( 'Access only until: %s', $stop_at_msg );
        }

        if ($stop_col)
        {
            $stop_at = ncore_retrieve( $row, $stop_col );
        }

        $value = $this->value( $row );

        $icon = $value === 'Y'
              ? 'ok-circled'
              : 'cancel-circled';

        $tooltip = $hints
                 ? implode( " / ", $hints )
                 : ($value === 'Y'
                     ? _ncore( 'active' )
                     : _ncore( 'not active' ) );

        return ncore_icon( $icon, $tooltip, null, [], $value === 'Y' ? 'success' : 'error' );
    }
}