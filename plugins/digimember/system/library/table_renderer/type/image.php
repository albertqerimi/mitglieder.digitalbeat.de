<?php

class ncore_TableRenderer_TypeImage extends ncore_TableRenderer_TypeBase
{

    protected function metaDefaults()
    {
        $defaults = parent::metaDefaults();

        $defaults[ 'sortable' ] = false;

        return $defaults;
    }


    protected function renderInner( $row )
    {
        $image_id = $this->value( $row );
        if (!$image_id) {
            return '';
        }

        $url = wp_get_attachment_url( $image_id );

        return "<img src=\"$url\" alt=\"\" />";
    }
}
