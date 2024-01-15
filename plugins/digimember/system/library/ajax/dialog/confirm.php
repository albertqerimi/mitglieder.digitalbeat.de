<?php

class ncore_Ajax_DialogConfirm extends ncore_Ajax_DialogBase
{
    protected function buttons()
    {
        $ok_button_label     = $this->meta( 'ok_button_label',     _ncore( 'Ok' )      );
        $cancel_button_label = $this->meta( 'cancel_button_label', _ncore( 'Cancel' ) );

        $ok_button    = $this->okButton( $ok_button_label );
        $close_button = $this->closeButton( $cancel_button_label );

        return array_merge( $ok_button, $close_button );
    }

    protected function innerHtml()
    {
        $message = $this->meta( 'message', NCORE_ARG_REQUIRED );

        $icon = $this->meta( 'icon', 'confirm' );

        return "<div class='instruction $icon'>$message</div>";
    }
}