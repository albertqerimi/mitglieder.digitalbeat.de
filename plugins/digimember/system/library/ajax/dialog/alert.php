<?php

class ncore_Ajax_DialogAlert extends ncore_Ajax_DialogBase
{
    protected function buttons()
    {
        $close_button = $this->closeButton( _ncore( 'Ok' ) );

        return $close_button;
    }

    protected function innerHtml()
    {
        $message = $this->meta( 'message', NCORE_ARG_REQUIRED );

        $icon = $this->meta( 'icon', 'info' );

        return "<div class='instruction $icon'>$message</div>";
    }
}