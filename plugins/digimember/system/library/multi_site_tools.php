<?php

class ncore_MultiSiteToolsLib extends ncore_Library
{
    public function getBlogUrl( $blog_id, $file='/' )
    {
        ncore_wpSwitchToBlog( $blog_id );
        $site_url = ncore_siteUrl();
        ncore_wpRestoreCurrentBlog();

        $site_url = rtrim( $site_url, '/' );
        $file    = ltrim( $file, '/' );

        $url = "$site_url/$file";

        return $url;
    }

    public function getBlogIds() {

        static $cache;

        global $wpdb;
        $table = $wpdb->blogs;

        $blog_ids =& $cache[ $table ];

        if (!isset( $blog_ids )) {

            $blog_ids = array();

            $sql = "SELECT blog_id
                    FROM `$table`
                    WHERE archived = 0
                      AND spam     = 0
                      AND deleted  = 0
                    ORDER BY blog_id";

            $rows = $wpdb->get_results( $sql );

            foreach ($rows as $row) {
                $blog_ids[] = $row->blog_id;
            }

        }

        return $blog_ids;

    }

    public function getTables( $blog_id ) {

        ncore_wpSwitchToBlog( $blog_id );
        global $wpdb;
        $prefix      = $wpdb->prefix;
        $base_prefix = $wpdb->base_prefix;
        ncore_wpRestoreCurrentBlog();

        $is_main_blog = $prefix == $base_prefix;

        $base_prefix_regex = "/^${base_prefix}[0-9]{1,}_/";


        $db = $this->api->load->library( 'db' );

        $prefix_esc      = str_replace( "_", "\\_", $prefix );
        $sql = "SHOW TABLES LIKE '${prefix_esc}%'";
        $rows = $db->query( $sql );

        $tables = array();
        foreach ($rows as $row) {

            $row_array = array_values( (array) $row );

            $table_name = end( $row_array );

            if ($is_main_blog) {
                $is_sub_blog_table = preg_match( $base_prefix_regex, $table_name );

            }
            else {
                $is_sub_blog_table = false;
            }

            if (!$is_sub_blog_table) {
                $tables[] =  $table_name;
            }
        }

        sort( $tables );

        return $tables;

    }

    public function getModelTablesDatatypeMap()
    {
        $apis = ncore_api( 'all' );

        $table_data_type_map = array();

        foreach ($apis as $api)
        {
            $models = $api->load->allModels( array( 'system', 'application' ), array( 'data', 'queue' ) );

            foreach ($models as $model)
            {
                $table = $model->unprefixedTableName();
                $data_type = $model->dataType();

                $table_data_type_map[ $table ] = $data_type;
            }
        }

        return $table_data_type_map;
    }

    public function getTableDatatype( $table )
    {
        if (!isset( $this->table_data_type_map ))
        {
            $this->table_data_type_map = $this->getModelTablesDatatypeMap();
        }

        return ncore_retrieve( $this->table_data_type_map, $table );
    }

    public function getBlogIdsAdministratedByUser( $user_id='current' )
    {
        return array_keys( $this->blogOptions( $user_id ) );
    }

    public function blogOptions( $user_id='current', $label_template = '[DOMAIN] (#[BLOG_ID])' )
    {
        if ($user_id==='current') {
            $user_id = ncore_userId();
        }
        if (!$user_id) {
            return array();
        }

        $user_id = ncore_washInt( $user_id );

        global $wpdb;

        $sql = "SELECT meta_key, meta_value
                FROM $wpdb->usermeta
                WHERE user_id = $user_id
                  AND meta_key like '%\_capabilities'
                  AND meta_value like '%administrator%'";
        $rows = $wpdb->get_results( $sql );

        $ids   = array();
        $names = array();
        $sort  = array();

        foreach ($rows as $row)
        {
            $caps = @unserialize( $row->meta_value );

            $is_admin = ncore_retrieve( $caps, 'administrator' );

            if (!$is_admin) {
                continue;
            }

            $prefix = $wpdb->base_prefix;
            $key = $row->meta_key;

            if ($key == $prefix . 'capabilities')
            {
                $blog_id = 1;
            }
            elseif (preg_match( "|^${prefix}_{0,1}([0-9]*)_capabilities\$|", $key, $matches )) {
                $blog_id = $matches[1];
            }
            else {
                continue;
            }

            if (!ncore_blogExists( $blog_id )) {
                continue;
            }

            $blog_name = ncore_getBlogDomain( $blog_id );

            $find = array( '[BLOG_ID]', '[DOMAIN]' );
            $repl = array( $blog_id, $blog_name );

            $ids[]   = $blog_id;
            $names[] = str_replace( $find, $repl, $label_template );
            $sort[]  = mb_strtolower( $blog_name );
            $options[ $blog_id ] = ncore_getBlogDomain( $blog_id );
        }

        array_multisort( $sort, $names, $ids );

        $options = array_combine( $ids, $names );

        return $options;
    }

    public function getUsers( $blog_id = 'current' )
    {
        if ($blog_id==='current') {
            $blog_id = ncore_blogId();
        }

        $blog_id = ncore_washInt( $blog_id );

        global $wpdb, $table_prefix;

        $prefix = ncore_washText( $table_prefix );

        $sql = "SELECT u.*
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} m
                    ON u.ID = m.user_id
                       AND meta_key='${prefix}capabilities'

                WHERE length( meta_value ) > 10

                ORDER BY user_login ASC";

        return $wpdb->get_results( $sql );
    }

    private $table_data_type_map;

}

