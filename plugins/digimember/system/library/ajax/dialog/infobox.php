<?php

class ncore_Ajax_DialogInfobox extends ncore_Ajax_DialogBase
{
    protected function buttons()
    {
        $links = $this->meta( 'links', array() );

        $buttons = array();

        $have_close_button = false;

        foreach ($links as $label => $url)
        {
            $is_close_button = $url === 'close';

            $button  = $is_close_button
                     ? $this->closeButton( $label )
                     : $this->linkButton( $url, $label, $asPopup=true );

            $buttons = array_merge( $buttons, $button );

            if ($is_close_button) {
                $have_close_button = true;
            }
        }

        if (!$have_close_button)
        {
            $button = $this->closeButton( _ncore( 'Close' ) );
            $buttons = array_merge( $buttons, $button );
        }

        return $buttons;
    }

    protected function innerHtml()
    {
        $message = $this->meta( 'message', NCORE_ARG_REQUIRED );

        $icon = $this->meta( 'icon', 'info' );

        return "<div class='instruction $icon'>$message</div>";
    }
}