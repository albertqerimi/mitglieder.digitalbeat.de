<?php

class ncore_FormRenderer_InputDynamicObjectList extends ncore_FormRenderer_InputBase {

    public function __construct ($parent,$meta) {
        parent::__construct($parent,$meta);

        $this->loadJS();
        $this->setupDialog();
    }

    public function validate() {
        $errors = array();
        $posted_array = $this->postedValue();

        if (is_array($posted_array)) {
            foreach ($posted_array as $ident=>$subgroup) {
                $validation_result = $this->validate_subgroup($subgroup);
                if (is_array($validation_result)) {
                    $errors[$ident] = $validation_result;
                }
            }
        }

        return $errors;
    }

    public function setValue($value) {
        // Must be errors in this case
        if (is_array($value)) {
            foreach ($value as $ident=>$data) {
                $data_obj = array(
                    'fields' => $data,
                    'id' => $ident,
                    'error' => true
                );
                ncore_addJsOnLoad('ncore.dynamicObjectLists[\''.$this->postname().'\'].add_entry('.json_encode($data_obj).')');
            }
        }
    }

    protected function renderInnerWritable() {
        $add_button = ncore_htmlButton($this->postname().'_add',$this->getSetting('button_label'),array('type'=>'button','style'=>'float: right;','onclick'=>$this->dialog->showDialogJs().'ncore.dynamicObjectLists[\''.$this->postname().'\'].edit_reset()'));

        $html = ncore_htmlUnorderedList($this->postname()).'<hr>'.$add_button;

        return $html;
    }

    /** @var ncore_Ajax_DialogForm */
    private $dialog;
    private function setupDialog() {
        /** @var ncore_AjaxLib $lib */
        $lib = $this->api->load->library( 'ajax' );
        $popup_metas = $this->getSetting('popup_metas');


        $meta = array(
            'type' => 'form',
            'ajax_dlg_id' => $this->getPopupID(),
            'cb_js_code'=>'ncore.dynamicObjectLists[\''.$this->postname().'\'].popup_submit_form()',
            'message' => '',
            'close_on_ok' => false,
            'title' => '',
            'width' => '500px',
            'form_sections' => $popup_metas['sections'],
            'form_inputs' => $popup_metas['inputs'],
            'lessz' => true
        );
        $meta = array_merge($meta,$popup_metas['options']);

        $this->dialog = $lib->dialog( $meta );
    }

    private function initValues() {
        $popup_metas = $this->getSetting('popup_metas');
        $ret = array();
        if (isset($popup_metas['values']) && is_array($popup_metas['values'])) {
            foreach ($popup_metas['values'] as $value) {
                $ret[] = $value;
            }
        }
        return $ret;
    }

    private function getPopupID() {
        return $this->postname().'_popup_form';
    }


    private function validate_subgroup($group) {
        if (is_array($group)) {
            /** @var ncore_RuleValidatorLib $validator */
            $validator = $this->api->load->library('rule_validator');

            $errors = array();
            foreach ($group as $post_name=>$post_value) {
                $meta_field = $this->getPopupField($post_name);

                if ($meta_field !== null) {
                    if (isset($meta_field['depends_on']) && is_array($meta_field['depends_on'])) {
                        $form_input = $this->dialog->form()->getInput($meta_field['name'],'');
                        $form_input->form_visibility = $this->api->form_visibility_lib->create(null,$meta_field['name'],'',$meta_field['depends_on']);
                        if (!$form_input->form_visibility->isVisible($group)) {
                            continue;
                        }
                    }

                    $rules = (isset($meta_field['rules'])) ? str_replace('defaults','',$meta_field['rules']) : '';
                    $label = $meta_field['label'];
                    $value = $post_value;

                    $error_msg = $validator->validate( $label, $value, $rules );
                    if ($error_msg) {
                        $this->_has_error = true;
                        $errors[] = $error_msg;
                    }
                }
            }
            if (count($errors) > 0) {
                return $errors;
            }
            else {
                return null;
            }
        }
        else {
            return null;
        }
    }

    private function getPopupField($name) {
        $popup_metas = $this->getSetting('popup_metas');

        foreach ($popup_metas['inputs'] as $input) {
            if ($input['name'] == $name || 'ncore_'.$input['name'] == $name) {
                return $input;
            }
        }

        return null;
    }

    private function getPopupFieldsForJS() {
        $popup_metas = $this->getSetting('popup_metas');

        $fields = array();
        foreach ($popup_metas['inputs'] as $input) {
            $fields[] = array(
                'name' => $input['name'],
                'label' => $input['label'],
                'type' => $input['type'],
                'rules' => isset($input['rules']) ? $input['rules'] : '',
                'depends_on' => isset($input['depends_on']) ? $input['depends_on'] : '',
            );
        }

        return $fields;
    }

    private function loadJS() {
        /** @var ncore_HtmlLogic $model */
        $model = ncore_api()->load->model ('logic/html' );
        $model->includeJs('dynamic_object_list.js');

        $model = $this->api->load->model ('logic/html' );
        $model->jsOnLoad('ncore.helpers._.validation = {};');
        $model->jsOnLoad('ncore.helpers._.validation.numeric = \''._ncore('For [NAME] please enter a number.').'\';');
        $model->jsOnLoad('ncore.helpers._.validation.email = \''._ncore('For [NAME] please enter a valid email address.').'\';');
        $model->jsOnLoad('ncore.helpers._.validation.required = \''._ncore('[NAME] is required. Please enter a value.').'\';');
        $model->jsOnLoad('ncore.helpers._.validation.time = \''._ncore('For [NAME] please enter a valid time.').'\';');
        $model->jsOnLoad('ncore.helpers._.validation.general = \''._ncore('Please enter correct values for the highlighted fields.').'\';');

        // Set up the edit & delete Button codes
        $delete_button_name = $this->postname().'_delete_button';
        $delete_button = ncore_htmlButton($delete_button_name,_ncore('Delete'),array('type'=>'button','data-name'=>'delete_button','style'=>'float: right;'));
        $edit_button_name = $this->postname().'_edit_button';
        $edit_button = ncore_htmlButton($edit_button_name,_ncore('Edit'),array('type'=>'button','data-name'=>'edit_button','style'=>'float: right;'));

        // Initialize Object List
        ncore_addJsOnLoad('ncore.dynamicObjectLists[\''.$this->postname().'\'] = new ncore.dynamicObjectList(\''.$this->postname().'\',\''.$this->getPopupID().'\','.json_encode($this->getPopupFieldsForJS()).',\''.$this->getSetting('line_preview_content').'\',\''.$edit_button.'\',\''.$delete_button.'\','.json_encode($this->initValues()).')');

    }

    private function getSetting($name) {
        $settings = $this->meta('settings');
        if (is_array($settings) && isset($settings[$name])) {
            return $settings[$name];
        }
        else {
            return null;
        }
    }
}