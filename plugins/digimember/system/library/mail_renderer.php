<?php

class ncore_MailRendererLib extends ncore_Library
{
    public function variableName( $basename )
    {
        return '%%' . $basename . '%%';
    }

    public function renderMail( $template, $params )
    {
        $this->initialize();

        $template_body_html = $this->filterMailBody( $template->body_html );

        list( $params_text, $params_html ) = $this->splitParams( $params );

        $subject   = $this->render( $template->subject, $params_text );
        $body_html = $this->render( $template_body_html, $params_html );

        return array(
             $subject,
            $body_html
        );
    }


    private static $is_initialized = false;

    private function render( $template, $params )
    {
        list( $find, $repl ) = $this->prepareParams( $params );

        $text = str_replace( $find, $repl, $template );

        return $text;
    }

    private function prepareParams( $params )
    {
        $find = array();
        $repl = array();

        foreach ( $params as $key => $value )
        {
            $find[] = $this->variableName( $key );
            $repl[] = $value;
        }

        return array(
             $find,
            $repl
        );
    }

    private function initialize()
    {
        if ( self::$is_initialized )
        {
            return;
        }

        self::$is_initialized = true;
    }

    private function splitParams( $params )
    {
        $params_text = array();
        $params_html = array();

        foreach ( $params as $key => $value )
        {
            if ( is_array( $value ) )
            {
                $text = $value[ 0 ];
                $html = $value[ 1 ];
            }
            else
            {
                $text = $value;
                $html = $value;
            }

            $params_text[ $key ] = $text;
            $params_html[ $key ] = $html;
        }
        return array(
             $params_text,
            $params_html
        );
    }

    private function filterMailBody( $body )
    {
        $body = wptexturize(       $body );
        $body = convert_smilies(   $body );
        $body = convert_chars(     $body );
        $body = wpautop(           $body );
        $body = shortcode_unautop( $body );

        return $body;
    }
}
