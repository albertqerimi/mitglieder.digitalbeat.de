<?php

function ncore_formatFloat( $float, $decimals='auto' )
{
    $is_auto = $decimals === 'auto';
    
    if (!$is_auto) {
        return number_format_i18n( $float, $decimals );
    }
    
    $text = number_format_i18n( $float, 6 );   
    
    $last_index = strlen($text);
    while ($last_index>=1 && $text[ $last_index-1 ] === '0')
    {
        $last_index--;
    }
    
    return substr( $text, 0, $last_index );
}


function ncore_parseFloat( $text )
{
    if (!$text) {
        return 0;
    }

    $pos_comma = strpos( $text, ',' );
    $pos_dot   = strpos( $text, '.' );

    if ($pos_comma === false && $pos_dot === false) {
        return (float) $text;
    }

    if ($pos_comma !== false && $pos_dot !== false) {

        $is_dot_1000_sep = $pos_dot < $pos_comma;

        $char_to_eliminate = $is_dot_1000_sep
                           ? '.'
                           : ',';
        return (float) str_replace( $char_to_eliminate, '', $text );
    }

    static $decimal_point;
    static $thousands_sep;

    if (!isset($decimal_point)) {

        global $wp_locale;
        if ( isset( $wp_locale ) ) {
            $decimal_point = $wp_locale->number_format['decimal_point'];
            $thousands_sep =  $wp_locale->number_format['thousands_sep'];
        }
        else
        {
            $decimal_point = false;
            $thousands_sep = false;
        }
    }

    if ($thousands_sep) {
        $text_without_thousend_sep = str_replace( $thousands_sep, '', $text );
        if ($text_without_thousend_sep != $text)
        {
            $text_as_float = str_replace( $decimal_point, '.', $text_without_thousend_sep );
            return (float) $text_as_float;
        }
    }

    $char = $pos_comma !== false
          ? ','
          : '.';

    $pos = strrpos( $text, $char );

    $is_thousend_sep = $pos < strlen( $text ) - 3;

    if ($is_thousend_sep) {
        $text = str_replace( $char, '', $text );
    }

    return (float) $text;
}

function ncore_formatDataSize( $bytes, $precision=0 )
{
    $units = array(
        array( _ncore( 'byte' ), _ncore( 'bytes' ) ),
        array( _ncore( 'kb' ),   _ncore( 'kb' ) ),
        array( _ncore( 'mb' ),   _ncore( 'mb' ) ),
        array( _ncore( 'gb' ),   _ncore( 'gb' ) ),
        array( _ncore( 'tb' ),   _ncore( 'tb' ) ),
    );

    $amount_unit_sep = ' ';
    $amounts_sep     = ' ';

    $amount        = $bytes;
    $leftovers     = array();
    $have_leftover = false;

    foreach ($units as $index => $one_many )
    {
        list( $one, $many ) = $one_many;

        $is_finished = $amount < 1024;
        if ($is_finished) {
            break;
        }

        $leftover  = $amount % 1024;
        $amount    = floor( $amount / 1024 );

        if ($leftover) {
            $have_leftover = true;
            $leftovers[$index] = $leftover;
        }



    }

    $unit = $amount >= 2
          ? $many
          : $one;

    $text = $amount . $amount_unit_sep . $unit;

    for ($i=$index-1; $i>=$index-$precision; $i--)
    {
        $leftover = ncore_retrieve( $leftovers, $i, 0 );

        if ($leftover)
        {
            list( $one, $many ) = $units[ $i ];

            $unit = $leftover >= 2
                  ? $many
                  : $one;

            $text .= $amounts_sep . $leftover . $amount_unit_sep . $unit;
        }
    }

    return $text;
}