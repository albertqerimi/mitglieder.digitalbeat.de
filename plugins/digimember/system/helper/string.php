<?php
/**
 * Delivers a random generated string of a specified length (by $len) and containing only dedicated sorts of characters (by $type)
 * @param string $type
 * possible types are alpha, id_for_humans, id_for_files_, alnum, alnum_lower, alnum_upper, numeric, nozero and password
 * @param int    $len
 * @return string
 */
function ncore_randomString( $type = 'alnum', $len = 8 )
{
    switch ( $type )
    {
        case 'alpha':
            $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'id_for_humans':
            $pool = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // no I,1, O 0 - they could be mistaken!
            break;
        case 'id_for_files': //Note: currently the cases 'id_for_files' and 'alnum_lower' use basically the same pool
            $pool = '1234567890abcdefghijklmnopqrstuvwxyz'; // only lower
            break;
        case 'alnum':
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'alnum_lower':
            $pool = '0123456789abcdefghijklmnopqrstuvwxyz';
            break;
        case 'alnum_upper':
            $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'numeric':
            $pool = '0123456789';
            break;
        case 'nozero':
            $pool = '123456789';
            break;
        case 'password':
            $pool = '23456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
            break;
        default:
            trigger_error( 'Invalid $type' );
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }

    $str = '';
    $max = strlen( $pool ) - 1;
    for ( $i = 0; $i < $len; $i++ )
    {
        $str .= substr( $pool, mt_rand(0, $max), 1 );
    }
    return $str;
}

/**
 * Shortens a text ($str) to a specified length.
 * @param string $str
 * @param int $length
 * @param int $grace
 * @param string $end_msg
 * @param string $text_filter this should be, if used, the name of a (global) function
 * @return string
 */
function ncore_shortenText( $str, $length, $grace = 50, $end_msg = '&#8230;', $text_filter = '' )
{
    $have_mb = function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' );

    $cur_length = $have_mb ? mb_strlen( $str ) : strlen( $str );

    if ( $cur_length > $length + $grace )
    {
        $shortened_str = $have_mb ? mb_substr( $str, 0, $length ) : substr( $str, 0, $length );

        $chars_cutted = $cur_length - $length;

        $msg = sprintf( $end_msg, $chars_cutted );

        if ( $text_filter && function_exists($text_filter))
        {
            $shortened_str = $text_filter( $shortened_str );
        }

        $str = "$shortened_str $msg";
    }
    elseif ( $text_filter && function_exists($text_filter))
    {
        $str = $text_filter( $str );
    }

    return $str;
}

/**
 * Orders integer values in a list seperated by comma in ascending order.
 * @param string $values_comma_seperated
 * @param string $zero
 * @param bool   $allow_duplicates
 * @return string
 */
function ncore_santizeIntList( $values_comma_seperated, $zero='0', $allow_duplicates=true )
{
    $values_comma_seperated = str_replace(' ', '', trim( $values_comma_seperated, ',' ) );

    //@fixme I think here are && meant instead of || , since right now there is no given way that all three conditions are false at the same time. Therefore the if-statement is "useless" since it will never be !false. But because this has been here for a long time, I don't want to change it without further discussion

    $have_values = $values_comma_seperated && $values_comma_seperated !== '0' && $values_comma_seperated !== 0;

    if (!$have_values)
    {
        return $zero;
    }

    $values = explode( ',', $values_comma_seperated  );
    $used_values = array();
    $display_values = array();
    foreach ($values as $value)
    {
        $value  = ncore_washInt($value);

        $is_used = in_array( $value, $used_values, $strict=true );
        if ($is_used && !$allow_duplicates)
        {
            continue;
        }

        $used_values[] = $value;

        if (!$value)
        {
            $value = $zero;
        }

        if ($value === '')
        {
            continue;
        }

        $display_values[] = $value;
    }

    sort($display_values);

    return implode( ',', $display_values );
}

function ncore_appendUnbrokenSuffixToLabel($label, $suffix)
{
    if (!$suffix) {
        return $label;
    }

    $label = trim($label);
    $split = preg_split('/(<.*>)|(\(.*\))| /', $label, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $lastPart = array_pop($split);
    return join(' ', $split) . ' <span class="ncore_nowrap">' . $lastPart . $suffix . '</span>';
}

/**
 * Appends the $suffix at the end of a $text.
 *
 * @param string $text
 * @param string $suffix
 * @param string $quote
 * @return string
 */
function ncore_appendUnbrokenSuffix( $text, $suffix, $quote="'" )
{
    if (!$suffix) {
        return $text;
    }

    $text = trim($text);

    $pos_a_end = strrpos( $text, '</a>' );

    $have_anchor = $pos_a_end !== false;

    $is_a_at_end = $have_anchor && $pos_a_end == strlen($text)-4;
    if ($is_a_at_end)
    {
        return $text.$suffix;
    }

    $pos_blank = strrpos( $text, ' ' );
    $pos_shy   = strrpos( $text, '&shy;' );
    $pos_wbr   = strrpos( $text, '<wbr>' );
    $pos_dash  = strrpos( $text, '-' );

    $max_pos = 0;
    $len = 0;
    if ($pos_blank!==false && $pos_blank>$max_pos) {
        $max_pos = $pos_blank;
        $len = 1;
    }
    if ($pos_shy!==false && $pos_shy>$max_pos) {
        $max_pos = $pos_shy;
        $len = 5;
    }
    if ($pos_wbr!==false && $pos_wbr>$max_pos) {
        $max_pos = $pos_wbr;
        $len = 5;
    }
    if ($pos_dash!==false && $pos_dash>$max_pos) {
        $max_pos = $pos_dash;
        $len = 1;
    }
    if ($pos_a_end!==false && $pos_a_end>$max_pos) {
        $max_pos = $pos_a_end;
        $len = 4;
    }

    if (!$max_pos) {
        return $text.$suffix;
    }

    $first_part  = substr( $text, 0, $max_pos+$len );
    $second_part = substr( $text, $max_pos+$len );

    $css = 'ncore_nowrap';

    $must_not_break_1st_part = strlen($first_part) <= 9;


    return ($must_not_break_1st_part
           ? "<span class=$quote$css$quote>$first_part</span>"
           : "<span>$first_part</span>")
           . "<span class=$quote$css$quote>$second_part$suffix</span>";
}

/**
 * Turns a list that is seperated by $seperator (can be multiple ones) into an array.
 * @param string|array $comma_seperated_list
 * @param string|array $seperator
 * @return array|mixed
 */
function ncore_explodeAndTrim( $comma_seperated_list, $seperator=',' ) {

    if (is_array($comma_seperated_list)) {
        return $comma_seperated_list;
    }

    $result = array();
    
    if (is_array($seperator))
    {
        $comma_seperated_list = str_replace( $seperator, '<DMdh373DM>', $comma_seperated_list );
        $seperator = '<DMdh373DM>';
    }  

    $exploded = explode($seperator,$comma_seperated_list);
    foreach ($exploded as $value) {
        $trimmed = trim( $value );
        if ($trimmed) {
            $result[] = $trimmed;
        }
    }

    return $result;
}