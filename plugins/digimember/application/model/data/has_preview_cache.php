<?php

class digimember_HasPreviewCacheData extends ncore_BaseData
{
    const expires_after_hours = 24;

    public function hasPreview( $post_id )
    {
        $has_preview =& $this->cache[ $post_id ];

        if (!isset($has_preview))
        {
            $where = array( 'page_id' => $post_id );

            $obj = $this->getWhere( $where, 'created DESC' );

            if ($obj)
            {
                $has_preview = ncore_retrieve( $obj, 'has_preview', 'N' ) === 'Y';
            }
            else
            {
                $has_preview = $this->_PostHasPreview( $post_id );
                $data = array(
                    'page_id' => $post_id,
                    'has_preview' => ($has_preview ? 'Y' : 'N' ),
                );
                $this->create( $data );
            }
        }

        return $has_preview;
    }

    public function invalidate( $post_id )
    {
        $post_id = ncore_washInt( $post_id);

        unset( $this->cache[ $post_id ] );

        $db = $this->db();

        $table = $this->sqlTableName();

        $db->query("DELETE FROM `$table`
                    WHERE page_id = $post_id");
    }

    public function cronDaily()
    {
        $db = $this->db();

        $table = $this->sqlTableName();

        $hours = self::expires_after_hours;

        $now = ncore_dbDate();

        $db->query("DELETE FROM `$table`
                    WHERE created < '$now' - INTERVAL $hours HOUR");
    }


    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'has_preview_cache';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'page_id' => 'int',
        'has_preview' => 'yes_no_bit',
       );

       $indexes = array( 'page_id' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    //
    // private section
    //
    private $cache=array();


    private function _PostHasPreview( $post_id )
    {
        $post = get_post( $post_id, false );

        $content = $post->post_content;

        if (strpos( $content, 'preview]' ) === false) {
            return false;
        }

        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->shortCode( 'preview' );

        $shortcode = "[$shortcode]";
        $has_preview = strpos( $content, $shortcode ) !== false;
        if ($has_preview) {
            return true;
        }

        $shortcode_legacy = "[digimember_preview]";
        $has_preview_legacy = strpos( $content, $shortcode_legacy ) !== false;

        return $has_preview_legacy;
    }
}