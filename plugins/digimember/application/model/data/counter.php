<?php

class digimember_CounterData extends ncore_BaseData
{

    public function getUserIdsNotLoggedIn( $min_age_in_days=0, $max_age_in_days=false )
    {
        $sql_where = '';

        if ($min_age_in_days>=1)
        {
            $date = ncore_dbDate( time()-86400*$min_age_in_days );
            $sql_where .= " AND u.user_registered <= '$date'";
        }
        if ($max_age_in_days>=1)
        {
            $date = ncore_dbDate( time()-86400*$max_age_in_days );
            $sql_where .= " AND u.user_registered >= '$date'";
        }

        global $wpdb;
        $user_table = $wpdb->users;
        $counter_table = $this->sqlTableName();

        $sql = "SELECT u.ID,
                       u.user_registered
                FROM $user_table u
                LEFT JOIN $counter_table c
                    ON u.ID = c.user_id
                WHERE c.id IS NULL
                      $sql_where";

        $user_id_age_map = array();

        $now = ncore_unixDate();
        $rows = $wpdb->get_results( $sql, OBJECT );
        foreach ($rows as $row)
        {
            $age_in_days = round( ($now - ncore_unixDate($row->user_registered)) / 86400 );

            $user_id_age_map[ $row->ID ] = $age_in_days;
        }

        return $user_id_age_map;
    }

    public function countLogin( $user_id )
    {
        static $cache;

        $last_login   =& $cache[$user_id];

        if (isset($last_login)) {
            return $last_login;
        }

        $where = array(
            'user_id' => $user_id,
            'name' => 'login',
        );

        $rows = $this->getAll( $where, $limit='0,1', $order_by='id desc' );

        if (!$rows)
        {
            $data = $where;
            $data['count'] = 1;
            $this->create( $data );

            $model = $this->api->load->model( 'logic/event_subscriber' );
            $model->call( 'dm_on_login', $user_id, 1, $last_login=false );

            return $last_login=false;
        }

        $row = $rows[0];

        $last_login = $row->modified;

        $can_count = !$last_login
                     || abs( time() - ncore_unixDate( $last_login )) >= 60000;

        if ($can_count)
        {
            $data = array(
                'count' => $row->count + 1,
            );
            $this->update( $row->id, $data );

            $model = $this->api->load->model( 'logic/event_subscriber' );
            $model->call( 'dm_on_login', $user_id,  $row->count+1, $last_login );
        }

        return $last_login;
    }

    public function getLoginCounter( $user_id )
    {
        return $this->getCounter( 'login', $user_id );
    }

    public function sqlTableName()
    {
        return parent::sqlTableName();
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'counter';
    }

    protected function defaultOrder()
    {
        return 'id ASC';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'name' => 'string[15]',
        'user_id' => 'id',
        'count' => 'int',
       );

       $indexes = array( 'user_id', 'name' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function hasModified()
    {
        return true;
    }

    //
    // private section
    //
    private function getCounter( $counter_name, $user_id )
    {
         $where = array(
            'user_id' => $user_id,
            'name'    => $counter_name,
        );
        $row = $this->getWhere( $where );

        return $row
               ? array( $row->count, $row->modified )
               : array( 0, null );
    }


}