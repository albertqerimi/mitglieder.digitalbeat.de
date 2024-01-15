<?php

class ncore_BlogHandlerLib extends ncore_Library
{
    function onBeforeCopy( $from_blog_id, $to_blog_id )
    {
        $settings = new stdClass();

        $settings->from_blog_id      = $from_blog_id;
        $settings->to_blog_id        = $to_blog_id;

        $settings->preserved_options = $this->saveOptions( $to_blog_id );

        return $settings;
    }

    function onAfterCopy( $settings )
    {
        $from_blog_id      = $settings->from_blog_id;
        $to_blog_id        = $settings->to_blog_id;
        $preserved_options = $settings->preserved_options;

        $this->restoreOptions( $to_blog_id, $preserved_options );

        $this->fixOptionPrefixes( $from_blog_id, $to_blog_id );

        $this->fixGuidUrls( $from_blog_id, $to_blog_id );
    }


    private function saveOptions( $to_blog_id )
    {
        $preserved_options = array();
        foreach ($this->preserved_options($to_blog_id) as $key)
        {
            $preserved_options[$key] = get_blog_option( $to_blog_id, $key );
        }
        return $preserved_options;
    }

    private function restoreOptions( $to_blog_id, $preserved_options )
    {
        ncore_wpSwitchToBlog( $to_blog_id );
        wp_cache_flush();

        foreach( $preserved_options as $option_name => $option_value ) {
            update_option( $option_name, $option_value );
        }
        ncore_wpRestoreCurrentBlog();
    }

    private function fixOptionPrefixes( $from_blog_id, $to_blog_id )
    {
        global $wpdb;

        $from_blog_prefix = $wpdb->get_blog_prefix( $from_blog_id );
        $to_blog_prefix   = $wpdb->get_blog_prefix( $to_blog_id );

        $table = $to_blog_prefix . 'options';

        $from_blog_prefix_length = strlen( $from_blog_prefix );

        $from_blog_escaped_prefix = str_replace( '_', '\_', $from_blog_prefix );
        $sql = "SELECT * FROM `$table` WHERE option_name LIKE '${from_blog_escaped_prefix}%'";
        $rows = $wpdb->get_results( $sql );

        foreach ($rows as $row)
        {
            $raw_option_name = substr($row->option_name,$from_blog_prefix_length);
            $new_option_name = $to_blog_prefix . $raw_option_name;

            $wpdb->delete( $table, array( 'option_name' => $new_option_name ) );

            $wpdb->update( $table, array( 'option_name' => $new_option_name ), array( 'option_id' => $row->option_id ) );
        }
        wp_cache_flush();

    }

    private function fixGuidUrls( $from_blog_id, $to_blog_id )
    {
        global $wpdb;

        $to_blog_prefix = $wpdb->get_blog_prefix( $to_blog_id );

        $from_blog_url  = get_blog_option( $from_blog_id, 'siteurl' );
        $to_blog_url    = get_blog_option( $to_blog_id, 'siteurl' );
        $query = $wpdb->prepare( "UPDATE {$to_blog_prefix}posts SET guid = REPLACE(guid, '%s', '%s')", $from_blog_url, $to_blog_url );
        $wpdb->query( $query );
    }


     private $basic_preserved_options = array(
        'siteurl',
        'home',
        'upload_path',
        'fileupload_url',
        'upload_url_path',
        'admin_email',
        'blogname',
        'admin_email',
        'new_admin_email',
        'adminhash',
    );

    private function preserved_options( $blog_id )
    {
        $blog_id = intval( $blog_id );

        $keys = $this->basic_preserved_options;

        global $wpdb;

        $base_prefix = $wpdb->get_blog_prefix();
        $blog_prefix = $wpdb->get_blog_prefix( $blog_id );

        $base_prefix_esc = str_replace( "_", "\\_", $base_prefix );

        $sql = "SELECT option_name FROM {$blog_prefix}options WHERE option_name like '${base_prefix_esc}${blog_id}\_%'";
        $rows = $wpdb->get_results( $sql );

        foreach ($rows as $row)
        {
            $keys[] = $row->option_name;
        }

        return $keys;
    }



}