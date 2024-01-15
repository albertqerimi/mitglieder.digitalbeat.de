<?php

class digimember_AdvancedFormsData extends ncore_BaseData
{
    const ADVANCED_FORM_ID_LENGTH = 15;

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_ADVANCED_FORM;
    }

    public function options( $where=array())
    {
        return $this->asArray( 'name', 'id', $where );
    }

    protected function sqlBaseTableName()
    {
        return 'advanced_forms';
    }

    protected function serializedColumns()
    {
        return array(
            'pages',
            'products',
            'formDimensions',
            'requiredElements',
        );
    }

    protected function onBeforeCopy( &$data )
    {
        parent::onBeforeSave( $data );

        $have_pages = !empty( $data[ 'pages' ] );
        if ($have_pages)
        {
            foreach ($data[ 'pages' ] as $index => $one)
            {
                $data[ 'pages' ][ $index ]['id'] = $this->_generateId();

                foreach ($one[ 'elements' ] as $i => $answer)
                {
                    $data[ 'pages' ][ $index ]['elements'][$i]['id'] = $this->_generateId();
                }
            }
        }
    }


    protected function onBeforeSave( &$data )
    {
        //$advancedFormsElementsModel = $this->api->load->model('data/advanced_forms_elements');
        parent::onBeforeSave( $data );

    }

    protected function cleanNotSubmittedButSavedPagesElements($formElementId, $submittedPages) {
        $advancedFormsModel = $this->api->load->model('data/advanced_forms');
        $advancedFormsElementsModel = $this->api->load->model('data/advanced_forms_elements');

        $whereForFormsWithId = array(
            'elementId'    => $formElementId,
        );
        $savedForms = (array) $advancedFormsModel->getAll($whereForFormsWithId);
        foreach ($savedForms as $savedForm) {
            $savedForm = (array) $savedForm;
            $savedFormPages = $savedForm['pages'];
            foreach ($savedFormPages as $savedPage) {
                $pageFound = false;
                foreach ($submittedPages as $submittedPage) {
                    if ($savedPage['id'] === $submittedPage['id']) {
                        $pageFound = true;
                    }
                }
                if (!$pageFound) {
                    //delete pageElements
                    $whereElementsToDelete = array(
                        'formElementId'    => $formElementId,
                        'pageId' => $savedPage['id'],
                    );
                    $elementsToDelete = $advancedFormsElementsModel->getAll($whereElementsToDelete);
                    foreach ($elementsToDelete as $elementToDelete) {
                        $advancedFormsElementsModel->delete($elementToDelete->id);
                    }
                }
            }
        }
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name'                   => 'string[127]',
            'elementId'                   => 'string[127]',
            'page_count'         => 'int',
            'element_count'           => 'int',
            'product_count' => 'int',
            'hasErrors' => ['type' => 'yes_no_bit', 'default' => 'Y'],
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
        return ncore_randomString( 'alnum_upper', self::ADVANCED_FORM_ID_LENGTH );
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

    public function createSampleDataIfNeeded() {
        $forms = $this->getAll();
        if (is_array($forms) && count($forms) < 1) {
            $config = $this->api->load->config('advanced_forms');
            $sampleData = $config->get('forms_sampledata');
            $formsController = $this->api()->load->controller("admin/advanced_forms_edit");

            $lang = substr(get_locale(), 0, 2);
            if (array_key_exists($lang, $sampleData)) {
                $sampleData = $sampleData[$lang];
            }
            else {
                $sampleData = $sampleData["en"];
            }
            foreach ($sampleData as $sample) {
                $id = $this->create($sample['form']);
                $formsController->saveFormElements($id, $sample['elements']);
            }
        }
    }
}
