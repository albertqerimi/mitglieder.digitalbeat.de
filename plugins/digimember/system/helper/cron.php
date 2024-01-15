<?php

function ncore_isCronjob() {

    $is_web_call = isset( $_SERVER[ 'HTTP_HOST'] ) && $_SERVER[ 'HTTP_HOST'];
    if (!$is_web_call) {
        return true;
    }

    if (defined('DOING_CRON') && DOING_CRON)
    {
        return true;
    }

    return false;
}

function ncore_cronStart()
{
    global $ncore_cron_end_time;

    if (isset($ncore_cron_end_time)) {
        return $ncore_cron_end_time;
    }

    ignore_user_abort( true );

    $is_cron_run = ncore_isCronjob();

    if ($is_cron_run) {
        $old_level = error_reporting(0);
        $old_value = @ini_set( 'display_errors', 0);
        @ini_set( 'max_execution_time', 3600 );
        if (!empty($old_value)) {
            @ini_set( 'display_errors', $old_value );
        }
        error_reporting( $old_level );
    }

     $max_execution_time = ini_get( 'max_execution_time' );
     $max_run_time = round( 0.5 * $max_execution_time );

    if (!$is_cron_run && !NCORE_DEBUG) {
        $max_run_time = 5;
    }

    $ncore_cron_end_time = time() + $max_run_time;

    return $ncore_cron_end_time;
}

function ncore_cronMayContinue() {

    $stop_running_at = ncore_cronStart();

    $time_left = $stop_running_at - time();

    if ($time_left <= 0) {
        return false;
    }

    return $time_left;
}


