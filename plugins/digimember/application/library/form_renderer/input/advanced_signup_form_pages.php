<?php

class digimember_FormRenderer_InputAdvancedSignupFormPages extends ncore_FormRenderer_InputBase
{
    public function __construct( $parent, $meta )
    {
        parent::__construct( $parent, $meta );

        $this->min_page_count = $this->meta( 'min_page_count', 1 );
        $this->min_element_count   = $this->meta( 'min_element_count',   3 );
    }

    protected function onPostedValue( $field_name, &$value )
    {
        if ($field_name)
        {
            return;
        }

        $value = array();

        $basename = $this->postname();

        $sort      = ncore_retrieve( $_POST, $basename.'_sort' );
        $pages = ncore_retrieve( $_POST, $basename.'_pages' );

        $indexes = explode( ',', $sort );
        foreach ($indexes as $i)
        {
            if (empty($pages[$i])) {
                continue;
            }
            $raw_page = $pages[ $i ];

            $is_open = (empty( $raw_page['is_open'] ) ? 0 : 1);

            $page_id = ncore_washText( ncore_retrieve( $raw_page, 'id' ) );


            $raw_elements = ncore_retrieve( $raw_page, 'elements', array() );
            $elements     = array();

            $is_valid = !empty($raw_elements) && is_array($raw_elements);
            if ($is_valid) {
                foreach ($raw_elements as $raw_element)
                {
                    $element_id  = ncore_washText( ncore_retrieve( $raw_element, 'id', '' ) );
                    $name       = ncore_retrieve( $raw_element, 'name', '' );
                    $label      = ncore_retrieve( $raw_element, 'label', '' );
                    $cfname = ncore_retrieve( $raw_element, 'cfname', 'none' );

                    $element = array(
                        'id'         => $element_id,
                        'name'       => $name,
                        'label'      => $label,
                        'cfname'     => $cfname,
                    );

                    $elements[] = $element;
                }
            }

            $value[] = array(
                'is_open' => $is_open,
                'id'      => $page_id,
                'elements' => $elements,
            );
        }
    }

    protected function renderInnerWritable()
    {
        //TODO localisations
        $customFieldsModel = $this->api->load->model('data/custom_fields');
        $customFields = $customFieldsModel->getAllActive();


        $cfData = array();
        foreach ($customFields as $cf) {
            $cfData[] = array(
                'name' => $cf->name,
                'label' => $cf->label.' ('.$cf->section.')',
            );
        }

        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model ('logic/html' );
        $htmlLogic->loadPackage('advanced-signup-forms.js');
        //$htmlLogic->loadPackage('test-app.js');

        $asfTranslations = [
            'labelAddPage' => 'Seite hinzufügen',
            'labelRemPage' => 'Seite entfernen',
            'labelAddElement' => 'Element hinzufügen',
            'labelRemElement' => _digi('remove'),
            'labelPage' => 'Seite',
	        'labelExplanation' => _digi('Explanation'),
            'labelElementNumber' => 'Element',
        ];
        $asfData = [
            'minPageCount' => $this->meta('min_page_count', 1),
            'minElementCount' => $this->meta('min_element_count', 3),
            'baseName' => $this->postname()
        ];

        $asfTranslationsJson = htmlentities(json_encode($asfTranslations));
        $asfDataJson = htmlentities(json_encode($asfData));
        $asfPagesJson = htmlentities(json_encode($this->value()));
        $asfEditorCode = htmlentities(ncore_htmleditor('__exam_page_dummy__', '', [
            'editor_id' => '__exam_page_dummy__',
            'rows' => 5,
        ]));
        $cfDataJson = htmlentities(json_encode($cfData));
//        $html = '<div
//            class="dm-asf-pages"
//            data-asf-editor-template="' . $asfEditorCode . '"
//            data-asf-translations="' . $asfTranslationsJson . '"
//            data-asf-data="' . $asfDataJson . '"
//            data-asf-pages="' . $asfPagesJson . '"
//            data-asf-cfdata="'.$cfDataJson.'"
//        ></div>';
        $html = '<div
            id="dm-advanced-signup-forms"
            class="dm-advanced-signup-forms"
            data-asf-cfdata="'.$cfDataJson.'"
        ></div>';
        return $html;
    }

    protected function defaultRules()
    {
        return 'trim';
    }

    protected function requiredMarker()
    {
        return '';
    }

    public function fullWidth()
    {
        return true;
    }

    //
    // private section
    //
    private $min_page_count = 1;
    private $min_element_count   = 1;
}


