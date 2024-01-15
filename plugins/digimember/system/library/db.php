<?php

class ncore_DbLib extends ncore_Library
{
    public function tableNamePrefix()
    {
        global $wpdb;

        return $wpdb->prefix;
    }

    public function networkTableNamePrefix()
    {
        global $wpdb;

        return empty( $wpdb->base_prefix )
               ? $wpdb->prefix
               : $wpdb->base_prefix;
    }

    public function query( $sql )
    {
        global $wpdb;
        return $wpdb->get_results( $sql, OBJECT);
    }

    public function modified()
    {
        global $wpdb;
        return (bool) $wpdb->rows_affected;
    }

    public function insertId()
    {
        global $wpdb;
        return $wpdb->insert_id;
    }

    public function escape( $arg )
    {
        global $wpdb;
        return  esc_sql( $arg );
    }

}
