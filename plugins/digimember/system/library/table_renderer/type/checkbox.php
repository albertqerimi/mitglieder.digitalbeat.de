<?php

class ncore_TableRenderer_TypeCheckbox extends ncore_TableRenderer_TypeBase
{
    public function label()
    {
        return "<input class='dm-checkbox dm-checkbox-tbl-setter' type='checkbox' value='0' />";
    }
    protected function renderInner( $row )
    {
        $id = $this->value( $row );

        $postname = 'ncore_table_checkbox_id[]';
        $css = $this->meta( 'css' );

        return "<input class='dm-checkbox dm-checkbox-tbl-item $css' type='checkbox' name='$postname' value='$id' />";
    }

}