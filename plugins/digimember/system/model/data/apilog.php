<?php

class ncore_ApilogData extends ncore_BaseData
{
    const log_keep_days = 7;

    public function log( $type )
    {
        $post = $_POST;

        $get = $_GET;

        $input = file_get_contents( 'php://input' );

        $this->api->load->helper( 'url' );
        $url = ncore_currentUrl();

        $logdata = array(
            'post'  => $post,
            'get'   => $get,
            'input' => $input,
            'url'   => $url,
        );

        $data = array(
            'type' => $type,
            'data_serialized'   => serialize( $logdata ),
        );

        $this->create( $data );
    }

    public function replay( $type, $api_log_id='last' )
    {
        $ipn_id = ncore_retrieve( $_GET, 'dm_ipn' );
        $ipn_pw = ncore_retrieve( $_GET, 'dm_pw' );

        $where = array(
            'type' => $type,
        );

        if ($api_log_id!=='last')
        {
            $where['id'] = $api_log_id;
        }

        $row = $this->getWhere( $where, 'id DESC' );
        if ($row)
        {
            // ok
        }
        elseif ($api_log_id==='last')
        {
            return;
        }
        else
        {
            trigger_error( "Invalid api_log_id for type '$type'." );
            return;
        }

        $logdata = unserialize( $row->data_serialized );

        $_POST = $logdata['post'];
        $_GET =  $logdata['get'];

        $_GET['dm_ipn'] = $ipn_id;
        $_GET['dm_pw']  = $ipn_pw;

        global $digimember_debug_input;
        $digimember_debug_input = $logdata['input'];
    }

    public function cronDaily()
    {
        $days = self::log_keep_days;

        $db = $this->db();

        $table = $this->sqlTableName();

        $now = ncore_dbDate();

        $query = $db->query("DELETE FROM `$table`
                             WHERE created < '$now' - INTERVAL $days DAY");
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'apilog';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'type' => 'string[15]',
            'data_serialized' => 'text',
       );

       $indexes = array();

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function isUniqueInBlog() {

        return true;
    }

    //
    // private section
    //

}