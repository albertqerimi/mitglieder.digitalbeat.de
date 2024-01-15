<?php

//
// Class digiTimerTable
//
// Author: Christian Neise
// Date    04/03/2012
//
// Purpose: Make sure, recurring jobs are executed only after a certain amount
//          of time.
//
// Example: To excute cronjob every hourr, you can call this every minute:
//
//          if ($api->timer->runNow( 'cronjob', 3600) {
//                 run_hourly_cronjob();
//          }
//
class ncore_TimerData extends ncore_BaseData
{
    const night_hours = "01-07"; // 24h format: HH to HH

    public function weekly($label)
    {
        $ageInSecs   = 7 * 86400 - 3 * 3600;

        return $this->runAtNight($label . '_weekly', $ageInSecs );
    }

    public function daily($label)
    {
        $ageInSecs   = 86400 - 3 * 3600;

        return $this->runAtNight($label . '_daily', $ageInSecs );
    }


    public function hourly($label)
    {
        return $this->runNow($label . '_hourly', $ageInSecs = 3300 );
    }

    public function minutely($label)
    {
        return $this->runNow($label . '_minutely', $ageInSecs = 50 );
    }

    public function cronDaily()
    {
        $days = 365;

        $table = $this->sqlTableName();

        $now = ncore_dbDate();

        $sql = "DELETE FROM $table
                WHERE created < '$now' - INTERVAL $days DAY";

        $this->db()->query($sql);
    }

    public function runNow($label, $ageInSecs, $setNotified = true)
    {
        $db = $this->db();

        $ageInSecs = intval(max($ageInSecs, 60));
        $table     = $this->sqlTableName();

        $now = ncore_dbDate();

        $label = $this->_label( $label );

        $sql = "SELECT UNIX_TIMESTAMP(created) AS created_unix,
                       IF(created >= '$now' - INTERVAL $ageInSecs SECOND, 1, 0) AS is_set

                FROM $table
                WHERE name   = '$label'";

        $list = $this->db()->query( $sql );
        $row  = $list ? $list[ 0 ] : false;

        if ($row)
        {

            $created_unix = $row->created_unix;
            $is_set       = $row->is_set;

            if ($is_set)
            {
                return false;

            }
            elseif ($setNotified)
            {

                $table   = $this->sqlTableName();

                $now = ncore_dbDate();

                $sql = "UPDATE $table
                        SET created = '$now'
                        WHERE name = '$label'
                          AND created < '$now' - INTERVAL $ageInSecs SECOND";

                $this->db()->query($sql);

                $have_updated_time_stamp = $this->db()->modified();

                return $have_updated_time_stamp;
            }

        }
        elseif ($setNotified)
        {
            $table = $this->sqlTableName();

            $now = ncore_dbDate();

            $db->query("REPLACE INTO $table
                        (name, created)
                        VALUES
                        ( '$label', '$now')");

        }

        return true;
    }

    public function touch( $label )
    {
        $db = $this->db();

        $label = $this->_label( $label );

        $table = $this->sqlTableName();

        $now = ncore_dbDate();

        $db->query("REPLACE INTO $table
                    (name, created)
                    VALUES
                    ( '$label', '$now')");
    }

    //
    // protected function
    //
    protected function sqlBaseTableName()
    {
        return 'timer';
    }

    protected function sqlTableMeta()
    {
        $columns = array(
            'name'   => 'string[31]',
        );

        $indexes = array();

        $meta = array(
            'columns' => $columns,
            'indexes' => $indexes
        );

        return $meta;
    }

    protected function isUniqueInBlog() {

        return true;
    }

    //
    // private section
    //


    private function is_night()
    {
        return $this->is_in_hour_time_span(self::night_hours);
    }

    private function is_in_hour_time_span($hour_time_span)
    {
        list($from_hour, $to_hour) = explode('-', $hour_time_span);

        $sever_time = ncore_serverTime();

        $hour = (int) date('H', $sever_time );

        $from_hour = ncore_washInt( $from_hour );
        $to_hour   = ncore_washInt( $to_hour );

        if ($from_hour <= $to_hour)
        {
            // e.g.: from 03:00 to 08:00
            $is_night = $from_hour <= $hour && $hour <= $to_hour;
        }
        else
        {
            // e.g.: from 23:00 to 08:00
            $is_day   = $hour < $from_hour && $hour > $to_hour;
            $is_night = !$is_day;
        }

        return $is_night;
    }

    private function runAtNight( $label, $ageInSecs )
    {
        $is_night = $this->is_night();

        $run_daily_after_seconds = round( 1.5* $ageInSecs );

        $label_at_day = $label . '_at_day';

        if (!$this->is_night())
        {
            $ignore_night = $this->runNow( $label_at_day, $run_daily_after_seconds, $setNotified=false );
            if (!$ignore_night)
            {
                return false;
            }
        }

        $run_now = $this->runNow($label, $ageInSecs );
        if ($run_now)
        {
            $this->runNow( $label_at_day, $run_daily_after_seconds );
        }

        return $run_now;
    }

    private function _label( $label )
    {
        $label = ncore_washText($label);

        return substr( $label . '@' . str_replace( 'digi', '', $this->api->pluginName() ), 0, 31 );
    }

}