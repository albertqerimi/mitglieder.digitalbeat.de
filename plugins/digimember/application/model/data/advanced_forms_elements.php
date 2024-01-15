<?php

class digimember_AdvancedFormsElementsData extends ncore_BaseData
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
        return 'advanced_forms_elements';
    }

    protected function serializedColumns()
    {
        return array(
            'attributes',
            'grid',
            'styles',
            'subElements',
            'rules'
        );
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
           'elementId'        => 'string[127]',
           'formId'           => 'string[127]',
           'pageElementId'    => 'string[127]',
           'title'            => 'string[127]',
           'description'      => 'string[255]',
           'elementCategory'  => 'string[127]',
           'elementType'      => 'string[127]',
           'modelId'          => 'string[127]',
           'disabled'         => 'yes_no_bit',
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
        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        return $values;
    }

    protected function hasModified()
    {
        return true;
    }

    protected function sanitizeSerializedData( $column, $array )
    {
        switch ($column)
        {
            case 'pages':
                return $this->_sanitzePages( $array );

            default:
                return $array;
        }
    }

    protected function _sanitzePages( $pages )
    {
        return $pages;
    }


    private function _generateId()
    {
        $this->api->load->helper( 'string' );
        return ncore_randomString( 'alnum_upper', self::ADVANCED_FORM_ELEMENT_ID_LENGTH );
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
