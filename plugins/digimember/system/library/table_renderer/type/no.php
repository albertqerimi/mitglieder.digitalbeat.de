<?php

class ncore_TableRenderer_TypeNo extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        return $row->row_no;
    }
}