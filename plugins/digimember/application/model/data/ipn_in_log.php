<?php

class digimember_IpnInLogData extends ncore_BaseData
{
    const store_log_days = 5;

    public function storeIpnCall( $payment_id )
    {
        $request = array(
            'post' => $_POST,
            'get'  => $_GET,
        );

        $request_serialized = serialize( $request );

        $data = array(
            'payment_id' => $payment_id,
            'request_serialized' => $request_serialized,
        );

        $this->create( $data );
    }

    public function cronDaily()
    {
        $db = $this->db();

        $table = $this->sqlTableName();

        $days = self::store_log_days;

        $now = ncore_dbDate();

        $query = $db->query("DELETE FROM `$table`
                             WHERE created < '$now' - INTERVAL $days DAY");
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'ipn_in_log';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'payment_id' => 'int',
        'request_serialized' => 'text',
       );

       $indexes = array();

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

}