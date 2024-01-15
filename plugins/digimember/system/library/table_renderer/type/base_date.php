<?php

abstract class ncore_TableRenderer_TypeBaseDate extends ncore_TableRenderer_TypeBase
{
    protected function init()
    {
        $this->api->load->helper( 'date' );
    }

    protected function date( $date_unix )
    {
         return ncore_formatDate( $date_unix );
    }

    protected function time( $date_unix )
    {
         return ncore_formatTime( $date_unix );
    }
}
