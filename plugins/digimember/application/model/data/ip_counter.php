<?php

class digimember_IpCounterData extends ncore_BaseData
{
    const store_log_days = 90;

    function getForUser( $user_id )
    {
        $user_id = ncore_washInt( $user_id );

        $table = $this->sqlTableName();

        $date = ncore_dbDate( 'now', 'date' );

        $sql = "SELECT COUNT(DISTINCT ip) AS count
                FROM `$table`
                WHERE user_id = $user_id
                  AND DATE(created) = '$date'";

        $rows = $this->db()->query( $sql );
        $row = $rows[0];
        $count = $row->count;

        return intval($count);
    }

    function count( $user_id )
    {
        $ip = ncore_clientIp();

        $table = $this->sqlTableName();

        $user_id = ncore_washInt( $user_id );
        $ip = ncore_washText( $ip, '.' );

        if (!$user_id || !$ip)
        {
            return;
        }

        $date = ncore_dbDate( 'now', 'date' );

        $sql = "UPDATE `$table`
                SET count = count + 1
                WHERE ip = '$ip'
                  AND user_id = $user_id
                  AND created LIKE '$date %'";

        $this->db()->query( $sql );

        if (!$this->db()->modified())
        {
            $data = array(
                'count' => 1,
                'ip' => $ip,
                'user_id' => $user_id,
            );

            $this->create( $data );
        }
    }

    public function cronDaily()
    {
        $db = $this->db();

        $table = $this->sqlTableName();

        $days = self::store_log_days;

        $now = ncore_dbDate();

        $db->query("DELETE FROM `$table`
                    WHERE created < '$now' - INTERVAL $days DAY");

        // LEGACY CODE - REMOVE in 2019
        // remove old clear text ip
        $db->query("DELETE FROM `$table`
                    WHERE ip LIKE '%.%.%' OR ip LIKE '%::%'");
    }

    public function deleteForUserId($userId)
    {
        $db = $this->db();

        $table = $this->sqlTableName();

        $db->query("DELETE FROM `$table`
                    WHERE user_id = '$userId'");
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'ip_counter';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'ip' => 'string[63]',
        'user_id' => 'int',
        'count' => 'int',
       );

       $indexes = array( 'user_id', 'ip' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

}