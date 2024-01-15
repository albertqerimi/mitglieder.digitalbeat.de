<?php

function ncore_sortCompareTimes($a,$b) {
    $a = explode(':',$a);
    $b = explode(':',$b);

    if ($a[0] == $b[0]) {
        if ($a[1] == $b[1]) {
            return 0;
        }
        elseif ($a[1] > $b[1]) {
            return 1;
        }
        else {
            return -1;
        }
    }
    elseif ($a[0] > $b[0]) {
        return 1;
    }
    else {
        return -1;
    }
}

/**
 * Takes a given $date_unix (seconds passed since 01-01-1970) and a time zone, returns a readable date with time.
 * @param int $date_unix seconds passed since 01-01-1970
 * @param null | DateTimeZone $tz Timezone in which the date should be interpreted
 * @return string "MM/DD/YYYY HH:II" and ' ' if a moment not older than 1000 seconds from 01.01.1970 00:00 is called
 * @throws Exception
 */
function ncore_formatDateTime( $date_unix , $tz=null )
{
    return ncore_formatDate( $date_unix , $tz ).' '.ncore_formatTime( $date_unix , $tz );
}

/**
 * Formats the given $date_unix_or_readable into "MM/DD/YYYY"
 * @param string|int $date_unix_or_readable either the unix date or something like MM-DD-YYYY or an "English textual datetime" as the PHP manual calls it
 * @param null | DateTimeZone $tz Timezone in which the date should be interpreted
 * @return string '' if a wrong formatted parameter for $date_unix_or_readable was used; String formatted like MM/DD/YYYY; or false if the interpretation of the $date_unix_or_readable failed in another way.
 * @throws Exception
 */
function ncore_formatDate( $date_unix_or_readable='now' , $tz=null )
{
    if ($date_unix_or_readable === 'now') {
        $date_unix = time();
    }
    else
    {
        $date_unix = is_numeric( $date_unix_or_readable )
                   ? $date_unix_or_readable
                   : strtotime( $date_unix_or_readable );
    }

    $date_format = _ncore( 'm/d/Y' );

    if ($date_unix < 10000)
    {
        return '';
    }
    if (empty($tz)) {
        return date( $date_format, $date_unix );
    }
    else {
        $date = new DateTime();
        $date->setTimestamp($date_unix);
        $date->setTimezone($tz);

        return $date->format($date_format);
    }
}

/** @noinspection PhpDocMissingThrowsInspection */
/**
 * Formats a given time $date_unix_or_readable into hours:minutes
 * @param string|int $date_unix_or_readable
 * @param null | DateTimeZone $tz Timezone in which the date should be interpreted
 * @return string if a wrong formated parameter for $date_unix_or_readable was used; String formatted like HH:II; false if the interpretation of the $date_unix_or_readable failed in another way.
 */
function ncore_formatTime( $date_unix_or_readable , $tz=null )
{
    $date_unix = is_numeric( $date_unix_or_readable )
               ? $date_unix_or_readable
               : strtotime( $date_unix_or_readable );

    $time_format = _ncore( 'H:i' );

    if ($date_unix < 10000)
    {
        return '';
    }

    if (empty($tz))
    {
        return date( $time_format, $date_unix );
    }
    elseif (is_numeric($tz))
    {
        $have_hours = $tz <= 20;
        $date_unix += $have_hours
                    ? 3600 * $tz
                    : $tz;

        return date( $time_format, $date_unix );
    }
    elseif (is_string($tz))
    {
        $tz = timezone_open( $tz );
    }

    $date = new DateTime();
    $date->setTimestamp($date_unix);
    $date->setTimezone($tz);

    return $date->format($time_format);
}

function ncore_formatAge( $date )
{
    $server_time =  ncore_serverTime();
    $date_unix   = ncore_unixDate( $date );
    $time_diff = max( 2, $server_time - $date_unix );

    if ( $time_diff < 86400 )
    {
        return ncore_formatTimeSpan( $time_diff, 'ago' );
    }
    else
    {
        return ncore_formatDate( $date_unix );
    }
}


function ncore_formatTimeSpan( $seconds, $message='timespan', $format='auto' )
{
    $is_format_of_days_or_higher = $format == 'days+';

    switch ($message)
    {
        case 'in':
            $msg_second_1 = _ncore( 'in 1 second' );     $msg_second_N = _ncore( 'in %s seconds' );
            $msg_minute_1 = _ncore( 'in 1 minute' );     $msg_minute_N = _ncore( 'in %s minutes' );
            $msg_hour_1   = _ncore( 'in 1 hour' );       $msg_hour_N   = _ncore( 'in %s hours' );
            $msg_day_1    = _ncore( 'in 1 day' );        $msg_day_N    = _ncore( 'in %s days' );
            $msg_week_1   = _ncore( 'in 1 week' );       $msg_week_N   = _ncore( 'in %s weeks' );
            $msg_month_1  = _ncore( 'in 1 month' );      $msg_month_N  = _ncore( 'in %s months' );
            $msg_year_1   = _ncore( 'in 1 year' );       $msg_year_N   = _ncore( 'in %s years' );
            break;

        case 'for':
            $msg_second_1 = _ncore( 'for 1 second' );    $msg_second_N = _ncore( 'for %s seconds' );
            $msg_minute_1 = _ncore( 'for 1 minute' );    $msg_minute_N = _ncore( 'for %s minutes' );
            $msg_hour_1   = _ncore( 'for 1 hour' );      $msg_hour_N   = _ncore( 'for %s hours' );
            $msg_day_1    = _ncore( 'for 1 day' );       $msg_day_N    = _ncore( 'for %s days' );
            $msg_week_1   = _ncore( 'for 1 week' );      $msg_week_N   = _ncore( 'for %s weeks' );
            $msg_month_1  = _ncore( 'for 1 month' );     $msg_month_N  = _ncore( 'for %s months' );
            $msg_year_1   = _ncore( 'for 1 year' );      $msg_year_N   = _ncore( 'for %s years' );
            break;

        case 'after':
            $msg_second_1 = _ncore( 'after 1 second' );  $msg_second_N = _ncore( 'after %s seconds' );
            $msg_minute_1 = _ncore( 'after 1 minute' );  $msg_minute_N = _ncore( 'after %s minutes' );
            $msg_hour_1   = _ncore( 'after 1 hour' );    $msg_hour_N   = _ncore( 'after %s hours' );
            $msg_day_1    = _ncore( 'after 1 day' );     $msg_day_N    = _ncore( 'after %s days' );
            $msg_week_1   = _ncore( 'after 1 week' );    $msg_week_N   = _ncore( 'after %s weeks' );
            $msg_month_1  = _ncore( 'after 1 month' );   $msg_month_N  = _ncore( 'after %s months' );
            $msg_year_1   = _ncore( 'after 1 year' );    $msg_year_N   = _ncore( 'after %s years' );
            break;

        case 'ago':
            $msg_second_1 = _ncore( '1 second ago' );    $msg_second_N = _ncore( '%s seconds ago' );
            $msg_minute_1 = _ncore( '1 minute ago' );    $msg_minute_N = _ncore( '%s minutes ago' );
            $msg_hour_1   = _ncore( '1 hour ago' );      $msg_hour_N   = _ncore( '%s hours ago' );
            $msg_day_1    = _ncore( '1 day ago' );       $msg_day_N    = _ncore( '%s days ago' );
            $msg_week_1   = _ncore( '1 week ago' );      $msg_week_N   = _ncore( '%s weeks ago' );
            $msg_month_1  = _ncore( '1 month ago' );     $msg_month_N  = _ncore( '%s months ago' );
            $msg_year_1   = _ncore( '1 year ago' );      $msg_year_N   = _ncore( '%s years ago' );

            $msg_day_0 = _ncore( 'today' );
            break;

        case 'timespan':
            $msg_second_1 = _ncore( '1 second' );        $msg_second_N = _ncore( '%s seconds' );
            $msg_minute_1 = _ncore( '1 minute' );        $msg_minute_N = _ncore( '%s minutes' );
            $msg_hour_1   = _ncore( '1 hour' );          $msg_hour_N   = _ncore( '%s hours' );
            $msg_day_1    = _ncore( '1 day' );           $msg_day_N    = _ncore( '%s days' );
            $msg_week_1   = _ncore( '1 week' );          $msg_week_N   = _ncore( '%s weeks' );
            $msg_month_1  = _ncore( '1 month' );         $msg_month_N  = _ncore( '%s months' );
            $msg_year_1   = _ncore( '1 year' );          $msg_year_N   = _ncore( '%s years' );
            break;

        default:
            trigger_error( "Invalid \$message: '$message'" );
    }

    $seconds = max( 1, $seconds );

    $force_days = $format == 'days';
    if ($force_days)
    {
        $days = round( $seconds / 86400 );

        return $days == 1
               ? $msg_day_1
               : ($days == 0 && !empty($msg_day_0)
                  ? $msg_day_0
                  : sprintf( $msg_day_N, $days ) );
    }

    $is_year = $seconds >= 0.9 * 365 * 86400;
    if ($is_year)
    {
        $years = round( $seconds / (365 * 86400) );

        return $years == 1
               ? $msg_year_1
               : sprintf( $msg_year_N, $years );
    }

    $is_month = $seconds >= 1.8 * 30 * 86400;
    if ($is_month)
    {
        $months = round( $seconds / (30 * 86400) );

        return $months == 1
               ? $msg_month_1
               : sprintf( $msg_month_N, $months );
    }

    $is_weeks= $seconds >= 1.5 * 7 * 86400 || $seconds == 7 * 86400;
    if ($is_weeks)
    {
        $weeks = round( $seconds / (7 * 86400) );

        return $weeks == 1
               ? $msg_week_1
               : sprintf( $msg_week_N, $weeks );
    }

    $is_days = $seconds >= 0.8 * 86400
            || $is_format_of_days_or_higher;
    if ($is_days)
    {
        $days = round( $seconds / 86400 );

        if ($days == 0)
        {
            return _ncore( 'today' );
        }

        return $days == 1
               ? $msg_day_1
               : sprintf( $msg_day_N, $days );
    }

    $is_hours = $seconds >= 0.9 * 3600;
    if ($is_hours)
    {
        $hours = round( $seconds / 3600 );

        return $hours == 1
               ? $msg_hour_1
               : sprintf( $msg_hour_N, $hours );
    }

    $is_minutes = $seconds >= 50;
    if ($is_minutes)
    {
        $minutes = round( $seconds / 60 );

        return $minutes == 1
               ? $msg_minute_1
               : sprintf( $msg_minute_N, $minutes );
    }

    return $seconds == 1
           ? $msg_second_1
           : sprintf( $msg_second_N, $seconds );
}

/**
 * Takes a $date and returns it in following format = array [$year, $month, day]
 * @param string $date if the order is MM-DD-YYYY use '-', '/', ':' as sperator, for DD.MM.YYYY use '.' If only YY instead of YYYY is used, the current century is assumed (meaning 20XX right now). If no year at all is passed, the current year is assumed.
 * @return array Of the following form [$year, $month, day], for wrong input it is [false, false, false]
 */
function ncore_parseDate( $date )
{
    $have_german_date = strpos( $date, '.' ) !== false;

    $tokens = explode( '-', str_replace( array( '.', '-', '/', ':' ), '-', $date ) );

    $have_date = count( $tokens ) == 2 || count( $tokens ) == 3;

    if (!$have_date) {
        return array( false, false, false );
    }

    $have_year = count( $tokens ) == 3;

    $year = $have_year
          ? $tokens[2]
          : date( 'Y' );

    if ($year < 100) {
        $full = date( 'Y' );
        $year = $full[0] . $full[1] . $year;
    }

    $month = $have_german_date
           ? $tokens[1]
           : $tokens[0];

    $day = $have_german_date
           ? $tokens[0]
           : $tokens[1];



    return array( $year, $month, $day);
}

/**
 * Converts the given $time into an array [$hours, $minutes, $seconds].
 * @param string $time Seperators canm be ' ', '/', '-', ':' and '.'.
 * @return array
 */
//@TODO this function currently does not check if $time uses numbers or anything else. Currently "ab-cd-ef" works just fine, too. Because of the name parseTime, I (Verena) would expect it to only work on numbers. Additional Info: This function is currently nowhere used in our Code (except for the tests).

function ncore_parseTime($time )
{
     $tokens = explode( ':', str_replace( array( ' ', '/', '-', '.' ), ':', $time ) );

    $have_time = count($tokens)==2 || count($tokens)==3;
    if (!$have_time)
    {
        return array( false, false, false );
    }

    $have_seconds = count($tokens) == 3;
    $second = $have_seconds
             ? $tokens[2]
             : 0;
    $minute = $tokens[1];
    $hour   = $tokens[0];

    return array( $hour, $minute, $second );
}

/**
 * Returns the difference between $date_1 and $date_2 in days
 *
 * @param string $date_1 preferred format YYYY-MM-DD or YYYY_MM_DD H:i:s
 * @param string $date_2 preferred format YYYY-MM-DD or YYYY_MM_DD H:i:s
 * @param bool   $abs true -> delivers only positive or results that are zero, false -> may deliver negative results
 *
 * @return float the difference between the two dates in days
 */
function ncore_dateDiffDays( $date_1, $date_2, $abs = true )
{
    $unix_1 = strtotime( ncore_DbDate( $date_1 ) );
    $unix_2 = strtotime( ncore_DbDate( $date_2 ) );

    $seconds = $unix_1 - $unix_2;

    $days = floor( $seconds / 86400 );

    return $abs ? abs($days) : $days;
}

/**
 * Adds a specified number of $days to a current $date_or_timestamp.
 *
 * @param string $date_or_timestamp YYYY-MM-DD or YYYY_MM_DD H:i:s
 * the current date or timestamp to which days should be added. If no parameter is given or the timestamp is younger than 1970-01-01, this date will be used instead
 * @param int $days number of days that should be added
 * @return string returns the new timestamp as 'date'
 */
function ncore_dateAddDays( $date_or_timestamp, $days )
{
    $timestamp = strtotime( ncore_dbDate( $date_or_timestamp, $format='date' ) );

    $timestamp += $days * 86400 + 36000;

    return ncore_dbDate( $timestamp, 'date' );
}


function ncore_unixDiffSeconds($unix_1, $unix_2)
{
    return  $unix_1 == $unix_2 ? 0 : ($unix_1 < $unix_2 ? $unix_2 - $unix_1 : $unix_1 - $unix_2);
}

function ncore_dateDiffDaysCeil( $date_1, $date_2, $abs = true )
{
    $unix_1 = strtotime( ncore_DbDate( $date_1 ) );
    $unix_2 = strtotime( ncore_DbDate( $date_2 ) );

    $seconds = $unix_1 - $unix_2;

    $days = ceil( $seconds / 86400 );

    return $abs ? abs($days) : $days;
}