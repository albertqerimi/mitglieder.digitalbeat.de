<?php

class digimember_ShortcodesData extends ncore_BaseData
{
    const ADVANCED_FORM_ELEMENT_ID_LENGTH = 15;

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_ADVANCED_FORM_ELEMENT;
    }

    public function options( $where=array())
    {
        return $this->asArray( 'title', 'id', $where );
    }

    protected function sqlBaseTableName()
    {
        return 'shortcodes';
    }

    protected function serializedColumns()
    {
        return array();
    }

    protected function onBeforeCopy( &$data )
    {
        parent::onBeforeSave( $data );
    }


    protected function onBeforeSave( &$data )
    {
        parent::onBeforeSave( $data );
    }

    protected function sqlTableMeta()
    {
       $columns = array(
           'shortcode'        => 'string[255]',
           'description'      => 'string[255]',
       );
       $indexes = array( /*'order_id', 'product_id', 'email'*/ );
       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );
       return $meta;
    }

    protected function buildObject( $obj )
    {
        parent::buildObject( $obj );
    }


    protected function hasTrash()
    {
        return false;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();
        return $values;
    }

    protected function hasModified()
    {
        return false;
    }


    /**
     * createTableIfNeeded
     * creates table of the model when called and the table doesnt exist.
     * @return bool
     */
    public function createTableIfNeeded() {
        global $wpdb;
        $table_name = $this->sqlTableName();
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
        if ( ! $wpdb->get_var( $query ) == $table_name ) {
            $initCore = $this->api->init();
            $initCore->forceUpgrade();
            return true;
        }
        return false;
    }
}
