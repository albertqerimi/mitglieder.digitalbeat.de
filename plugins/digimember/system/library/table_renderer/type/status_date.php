<?php

$load->loadPluginClass( 'date' );

class ncore_TableRenderer_TypeStatusDate extends ncore_TableRenderer_TypeDate
{
    protected function renderInner( $row )
    {
        $date = parent::renderInner( $row );

        $status_column = $this->meta( 'status_column', 'status' );

        $labels = $this->meta( 'status_labels' );
        $status = ncore_retrieve( $row, $status_column );

        $label = ncore_retrieve( $labels, $status );

        return "<span class='ncore_date'>$date</span><br /><span class='ncore_status'>$label</span>";


    }
}
