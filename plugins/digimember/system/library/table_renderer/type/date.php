<?php

$load->loadPluginClass( 'base_date' );

class ncore_TableRenderer_TypeDate extends ncore_TableRenderer_TypeBaseDate
{
    protected function renderInner( $row )
    {
        $date = $this->value( $row );
        $date_unix = strtotime( $date );

        if (!$date_unix)
        {
            $void_text = $this->meta( 'void_text' );
            return $void_text;
        }

        $past_text = $this->meta( 'past_text' );
        if ($past_text && $date_unix < time()) {
            return $past_text;
        }

        $short_date = ncore_formatDate( $date_unix );

        $long_date =  $this->date( $date_unix )
                    .  ' - '
                    .  $this->time( $date_unix );


        $html = "<abbr title='$long_date'>$short_date</abbr>";

        return $html;
    }
}
