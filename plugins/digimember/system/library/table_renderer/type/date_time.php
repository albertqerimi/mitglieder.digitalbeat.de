<?php

$load->loadPluginClass( 'base_date' );

class ncore_TableRenderer_TypeDateTime extends ncore_TableRenderer_TypeBaseDate
{
    protected function renderInner( $row )
    {
        $date = $this->value( $row );
        $date_unix = strtotime( $date );

        $long_date =  $this->date( $date_unix )
                    .  ' - '
                    .  $this->time( $date_unix );

        return $long_date;
    }



}
