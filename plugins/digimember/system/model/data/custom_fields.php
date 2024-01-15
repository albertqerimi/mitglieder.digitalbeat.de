<?php

class ncore_CustomFieldsData extends ncore_BaseData
{
    public function getDefaultFields() {
        return $this->getWhere(array('default' => 'Y'));
    }

    public function getCustomFields() {
        return $this->getWhere(array('default' => 'N'));
    }

    public function getCustomFieldsBySection($type = false, $visibleOnly = false) {
        if($type) {
            $where = array(
                'section'    => $type,
                'is_active' => 'Y',
            );
            if ($visibleOnly) {
                $where['visible'] = 'Y';
            }
            return $this->getAll( $where, false, 'position ASC' );
        }
        return array();
    }

    public function getCustomFieldsBySectionList($typeList = array(), $visibleOnly = false) {
        $fields = array();
        foreach ($typeList as $type) {
            $where = array(
                'section'    => $type,
                'is_active' => 'Y',
            );
            if ($visibleOnly) {
                $where['visible'] = 'Y';
            }
            $customfields = $this->getAll( $where, false, 'position ASC' );
            foreach ($customfields as $customfield) {
                $fields[] = $customfield;
            }
        }
        return $fields;
    }

    public function get( $id, $default='' )
    {
        $where = array(
            'id'    => $id,
        );
        $row = $this->getWhere( $where, 'id DESC' );
        if ($row)
        {
            return $row;
        }
        return $default;
    }

    public function getIfActive($id) {
        if($field = $this->get($id,false)) {
            return $field->is_active == 'Y' ? $field : false;
        }
    }

    public function set(  $fieldData )
    {
        $where = array(
            'name'    => $fieldData['name'],
            'section'    => $fieldData['section'],
        );

        ncore_serializeField($fieldData,'autoresponder');

        $row = $this->getWhere( $where );
        if ($row)
        {
            if ($fieldData && is_array($fieldData))
            {
                $data = array();
                foreach ($fieldData as $fieldKey => $fieldValue) {
                    $data[$fieldKey] = $fieldValue;
                }
                $this->update( $row->id, $data );
            }
            else
            {
                $this->delete( $row->id );
            }
        }
        elseif ($fieldData && is_array($fieldData))
        {
            $data = array();
            foreach ($fieldData as $fieldKey => $fieldValue) {
                $data[$fieldKey] = $fieldValue;
            }
            $this->setNameField($data);
            $new_id = $this->create( $data );
            $data['id'] = $new_id;
            $this->setNameField($data, false);
            $this->setPosition($data);
            $this->update($new_id, $data);
        }
    }

    public function getAllActive() {
        return $this->getAll(array(
            'is_active' => 'Y'
        ), false, 'section ASC');
    }

    public function getMeta($userId, $customField) {
        $output = array(
            'name' => $customField->name,
            'section' => $customField->section,
            'type' => $customField->type,
            'label' => $customField->label,
            'rules' => $customField->rules,
            'element_id' => $userId,
            'css' => 'digimember_row_custom_fields',
            'tooltip' => $customField->hinttext,
        );
        if ($customField->type === 'select') {
            $output['options'] = $this->resolveSelectOptions($customField->content_type, true, $customField->content);
            //$output['label'] = '';
        }
        return $output;
    }

    public function getAutoresponderLinkSwitchMeta ($autoresponderId, $customField) {

        $output = array(
            'name' => 'custom_field_link_switch_'.$customField->name,
            'type' => 'radiobuttons',
            'label' => $customField->label,
            'element_id' => $autoresponderId,
            'options' => array(
                'enter' => 'Eingeben',
                'select' => 'Auswählen'
            ),
            'default' => 'enter'
        );
        return $output;
    }

    public function getArLinkSelectMeta($autoresponderId, $customField, $autoresponderAttributes) {
            $options = array();
            if (array_key_exists(0, $autoresponderAttributes) && $autoresponderAttributes[0] == '') {
                array_shift($autoresponderAttributes);
            }
            $options = array(0 => _ncore('No linking'));
            foreach ($autoresponderAttributes as $attribute) {
                $options[$attribute['tag']] = $attribute['label'] != '' ? $attribute['label'].' - '.$attribute['tag'] : $attribute['tag'];
            }
            $output = array(
                'name' => 'custom_field_link_select_'.$customField->name,
                'type' => 'select',
                'label' => $customField->label,
                'element_id' => $autoresponderId,
                'options' => $options,
                'depends_on' => array('custom_field_link_switch_'.$customField->name => 'select')
            );
        return $output;
    }

    public function getArLinkEnterMeta($autoresponderId, $customField , $depending = false) {
        $output = array(
            'name' => 'custom_field_link_enter_'.$customField->name,
            'type' => 'text',
            'label' => $customField->label,
            'element_id' => $autoresponderId,
        );
        if ($depending) {
            $output['label'] = 'none';
            $output['depends_on'] = array('custom_field_link_switch_'.$customField->name => 'enter');
        }
        return $output;
    }

    public function resolveSelectOptions($optionsType, $null_entry_label=false, $rawContent = false)
    {
        if ($optionsType === 'country') {
            return array_merge($this->resolveNullEntryLabel($null_entry_label, $optionsType),$this->resolveCountrySelectContent());
        }
        elseif ($optionsType === 'fieldtypes') {
            return array(
                'text' => _ncore('Text field'),
                'select' => _ncore('Selection'),
            );
        }
        elseif ($optionsType === 'selectoptions') {
            return array_merge($this->resolveNullEntryLabel($null_entry_label, $optionsType),array(
                'country' => _ncore('Country selection'),
                'custom' => _ncore('Own select list'),
            ));
        }
        elseif ($optionsType === 'custom') {
            if ($rawContent) {
                return array_merge($this->resolveNullEntryLabel($null_entry_label, $optionsType),$this->resolveCustomSelectContent($rawContent));
            }
            return array();
        }
        return array();
    }

    public function resolveSelection($optionsType, $selectedEntry, $content = false) {
        if ($optionsType === 'custom') {
            $content = $this->resolveSelectOptions('custom', true, $content);
            if (array_key_exists($selectedEntry, $content)) {
                return $content[$selectedEntry];
            }
        }
        else if ($optionsType === 'country'){
            $content = $this->resolveSelectOptions('country', true);
            if (array_key_exists($selectedEntry, $content)) {
                return $content[$selectedEntry];
            }
        }
        else {
            if (array_key_exists($selectedEntry, $content)) {
                return $content[$selectedEntry];
            }
        }
        return false;
    }

    public function mapFieldTypeLabel($type_obj_or_string, $args = false) {
        if (is_object($type_obj_or_string))
        {
            $type = ncore_retrieve( $type_obj_or_string, array( 'type', 'type' ) ) ;
        }
        if (is_string( $type_obj_or_string ))
        {
            $type = ncore_washText( $type_obj_or_string );
        }
        $types = $this->resolveSelectOptions('fieldtypes', false);
        if (array_key_exists($type, $types)) {
            return $types[$type];
        }
        return $type;
    }

    public function resolveNullEntryLabel($null_entry_label, $optionsType = false) {
        if ($null_entry_label)
        {
            $this->api->load->helper( 'html_input' );
            if (!is_string($null_entry_label))
            {
                switch ($optionsType) {
                    case 'country':
                        $null_entry_label = _ncore('Select country');
                        break;
                    default;
                        $null_entry_label = _ncore('Please select');
                        break;
                }
            }
            return array( 0 => ncore_htmlSelectNullEntryLabel( $null_entry_label ) );
        }
        return array();
    }

    public function resolveCustomSelectContent($rawContent)
    {
        $output = array();
        if (strpos($rawContent,',') !== false) {
            $rawOptions = explode(',',$rawContent);
            foreach ($rawOptions as $rawOption) {
                if (strpos($rawOption,'#') !== false) {
                    list($name,$value) = explode('#',$rawOption);
                    $output[$name] = $value;
                }
                else {
                    $output[] = $rawOption;
                }
            }
        }
        return $output;
    }

    public function resolveCountrySelectContent() {
        $this->api->load->helper( 'select' );
        list($lang,$country) = explode('_',get_locale());
        return ncore_getCountryListLocalized($lang);
    }

    public function getCountrySelectOptionsAsJavascriptArray() {
        $output = [];
        $options = $this->resolveCountrySelectContent();
        foreach ($options as $key => $value) {
            $output[] = array("label" => $value, "value" => $key);
        }
        return $output;
    }

    public function getCustomSelectOptionsAsJavascriptArray($rawContent) {
        $output = [];
        //$options = $this->resolveCustomSelectContent($rawContent);
        $options = $this->resolveSelectOptions('custom', true, $rawContent);
        foreach ($options as $key => $value) {
            if ($key !== 0) {
                $output[] = array("label" => $value, "value" => $key);
            }
        }
        return $output;
    }

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_CUSTOMFIELD;
    }

    protected function sqlBaseTableName()
    {
        return 'custom_fields';
    }

    public function setNameField(&$data, $is_new = true) {
        if ($is_new) {
            $label = trim($data['label']) !== '' ? md5(trim($data['label']).'_new') : md5('generic_new');
        }
        else {
            $label = trim($data['label']) !== '' ? md5(trim($data['label'])).'_'.$data['id'] : md5('generic').'_'.$data['id'];
        }
        $data['name'] = strtolower(preg_replace("/\s+/", "", $label));
    }

    public function setPosition(&$data) {
        if (trim($data['position']) === "") {
            $data['position'] = array_key_exists('id', $data) ? $data['id'] : 1;
        }
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name' => 'string[255]',
            'label' => 'string[255]',
            'hinttext' => 'text',
            'type'    => 'string[63]',
            'rules' => "string[63]",
            'section'    => 'string[63]',
            'content_type' => 'string[63]',
            'content' => 'text',
            'default'   => array( 'type' => 'yes_no_bit', 'default' => 'N' ),
            'visible'   => array( 'type' => 'yes_no_bit', 'default' => 'Y' ),
            'is_active'   => array( 'type' => 'yes_no_bit', 'default' => 'Y' ),
            'position' => array( 'type' => 'int', 'default' => 1 ),
            'link_autoresponder' => array( 'type' => 'yes_no_bit', 'default' => 'N' ),
            'autoresponders' => 'text',
       );

       $indexes = array( 'name' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    public function getModelProperties() {
        $sqlMeta = $this->sqlTableMeta();
        return $sqlMeta['columns'];
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

    protected function hasFields() {
        return count($this->getAll()) > 0 ? true : false;
    }

    protected function onSetup()
    {
        if (!$this->hasFields()) {
            $this->setDefaultFields();
        }
    }

    protected function hasTrash()
    {
        return true;
    }

    protected function hasModified()
    {
        return true;
    }

    protected function isUniqueInBlog() {
        return true;
    }

    public function isLinkedToAutoresponder($customField) {
        return ncore_retrieve($customField,'link_autoresponder','N') == 'Y' ? true : false;
    }

    public function customFieldSections()
    {
        $sections = array(
            'account' => _ncore('User account'),
        );
        return $sections;
    }

    public function hasField($fieldData) {
        $row = $this->getWhere( $fieldData );
        if ($row) {
            return true;
        }
        return false;
    }



    public function setDefaultFields() {
        $this->set(array(
            'name' => 'generic_new',
            'label' => _ncore('street'),
            'hinttext' => _ncore('Please enter a street.'),
            'section' => 'account',
            'type' => 'text',
            'rules' => "defaults|min_length[3]",
            'default' => 'Y',
            'is_active' => 'N',
            'position' => 1,
        ));

        $this->set(array(
            'name' => 'generic_new',
            'label' => _ncore('street number'),
            'hinttext' => _ncore('Please enter a street number.'),
            'section' => 'account',
            'type' => 'text',
            'rules' => "defaults|min_length[1]",
            'default' => 'Y',
            'is_active' => 'N',
            'position' => 2,
        ));

        $this->set(array(
            'name' => 'generic_new',
            'label' => _ncore('zip'),
            'hinttext' => _ncore('Please enter a zip.'),
            'section' => 'account',
            'type' => 'text',
            'rules' => "defaults|min_length[3]",
            'default' => 'Y',
            'is_active' => 'N',
            'position' => 3,
        ));

        $this->set(array(
            'name' => 'generic_new',
            'label' => _ncore('city'),
            'hinttext' => _ncore('Please enter a city.'),
            'section' => 'account',
            'type' => 'text',
            'rules' => "defaults|min_length[3]",
            'default' => 'Y',
            'is_active' => 'N',
            'position' => 4,
        ));

        $this->set(array(
            'name' => 'generic_new',
            'label' => _ncore('country'),
            'hinttext' => _ncore('Please select a country.'),
            'section' => 'account',
            'type' => 'select',
            'content_type' => 'country',
            'default' => 'Y',
            'is_active' => 'N',
            'position' => 5,
        ));
    }

}