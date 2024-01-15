<?php

$load->loadPluginClass( 'date' );

class ncore_TableRenderer_TypeAgeDate extends ncore_TableRenderer_TypeDate
{
    protected function renderInner( $row )
    {
        $date_html = parent::renderInner( $row );

        $date = $this->value( $row );

        $format = $this->meta( 'format', 'auto' );

        $seconds = time() - strtotime( $date );

        $age = ncore_formatTimeSpan( $seconds, 'ago', $format );

        return "<span class='ncore_date'>$date_html</span><br /><span class='ncore_age'>$age</span>";


    }
}
