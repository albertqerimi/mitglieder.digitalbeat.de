<?php

$load->controllerBaseClass('post/meta');

abstract class ncore_PostCustomController extends ncore_PostMetaController {

    // Public Section

    public function init($settings = array()) {

        parent::init($settings);

        add_action( 'admin_notices', array($this, 'cbAdminBodyClassPrepare') );

    }

    public function cbAdminBodyClassPrepare() {

        global $post;

        $post_type = ncore_retrieve( $post, 'post_type' );

        if (!$post_type) {
            return;
        }

        $config = $this->api->load->config( 'menu' );
        $our_post_types = $config->get( 'post_types' );

        $is_our_post_type = is_array($our_post_types)
                            && !empty( $our_post_types[ $post_type ] );

        if ($is_our_post_type) {
            add_filter('admin_body_class',array($this,'cbAdminBodyClass'));
        }
    }

    public function cbAdminBodyClass() {
        return 'ncore';
    }

    public function cbMetaBoxSave($post_id) {
        if (!$this->form()->isPosted()) {
            return;
        }
        $this->validate();
        $this->handleRequest();
    }

    public function renderFormInner() {
        $this->form()->render();
    }

    public function renderFormErrors() {
        $this->initErrors();

        $html = array();
        foreach ($this->form_errors as $field=>$error) {
            // Don't display array errors (dynamic objects have to take care of themselves)
            if (!is_array($error['errors'])) {
                $html[] = '<div class="ncore_error error"><p>'.$error['errors'].'</p></div>';
            }
        }
        echo join('',$html);
    }

    // Protected Section

    protected function getPostId() {
        $post_id = parent::getPostId();
        if (!is_numeric($post_id)) {
            $post_id = (int) filter_input(INPUT_POST,'post_ID',FILTER_SANITIZE_NUMBER_INT);
        }
        return $post_id;
    }


    protected function getPostType() {

        static $cache;

        $post_id = $this->getPostId();

        $post_type =& $cache[ $post_id ];

        if (!isset($post_type)) {
            $post_type = get_post_type($post_id);
        }

        return $post_type;
    }

    protected function viewData() {
        $data = parent::viewData();

        $data['post_type'] = $this->getPostType();
        $data['post_id'] = $this->getPostId();

        if ($this->displayMessages()) {
            $this->evaluateErrors();
        }

        return $data;
    }

    protected function isFieldErrorFree($field_name,$sub_field_name = '') {
        $this->initErrors();
        return (!array_key_exists($field_name,$this->form_errors) || (is_array($this->form_errors[$field_name]['errors']) && !array_key_exists($sub_field_name,$this->form_errors[$field_name]['errors'])));
    }

    protected function initErrors($empty = false) {
        if (!property_exists($this,'form_errors')) {
            if ($this->getPostId() > 0) {
                $model = $this->api->load->model('logic/session');
                $this->form_errors = (!$empty) ? $model->get($this->getErrorIdentifier(),array()) : array();
            }
            else {
                $this->form_errors = array();
            }
        }
    }

    protected function form() {
        if (property_exists($this,'form_obj')) {
            return $this->form_obj;
        }

        $this->initMetas();

        $lib = $this->api->load->library('form_renderer');

        $settings = array();

        $this->form_obj = $lib->createForm($this->input_sections, $this->input_metas, $this->button_metas, $settings);

        return $this->form_obj;
    }

    protected function handleRequest() {

    }

    // Abstract Section

    abstract protected function inputMetas();
    abstract protected function buttonMetas();
    abstract protected function inputSections();

    // Private Section

    private function displayMessages() {
        return (filter_input(INPUT_GET,'message') == 1) ? true : false;
    }

    //TODO PHPStan check if this is still used
    private function getInputMeta() {
        if (!property_exists($this,'input_metas')) {
            $this->input_metas = $this->inputMetas();
        }

        foreach ($this->input_metas as $input) {
            if ($input['name'] == $name) {
                return $input;
            }
        }

        return null;
    }

    private function validate() {
        $this->initMetas();
        $this->initErrors();
        $this->resetErrors();
        $form = $this->form();

        foreach ($this->input_metas as $meta_input) {
            $form_input = $form->getInput($meta_input['name'],'');
            $errors = $form_input->validate();
            if ($errors) {
                $postValue = $form_input->postedValue();
                if (is_array($postValue)) {
                    $data_array = array_intersect_key($postValue,$errors);
                    $this->addError($meta_input['name'],$errors,$data_array);
                }
                else {
                    $this->addError($meta_input['name'],$errors,$postValue);
                }

            }
        }

        $this->writeErrors();
    }

    private function evaluateErrors() {
        $this->initErrors();
        $form = $this->form();
        foreach ($this->form_errors as $field_name=>$errors_and_data) {
            $form_input = $form->getInput($field_name,'');
            if ($form_input !== false) {
                $form_input->hasError(true,$errors_and_data['errors']);
                $form_input->setValue($errors_and_data['data']);

                // TODO
                // Add the error Messages throgh the form for now
                // Change this to individual message later
                // Used for now: $this->renderFormErrors()
            }
        }
    }

    private function writeErrors() {
        $this->initErrors(true);
        $model = $this->api->load->model('logic/session');
        $model->set($this->getErrorIdentifier(),$this->form_errors);
    }

    private function getError($field_name) {
        $this->initErrors();
        return (isset($this->form_errors[$field_name])) ? $this->form_errors[$field_name] : null;
    }

    private function addError($field_name,$errors,$data) {
        $this->initErrors();
        if (is_array($data) && isset($this->form_errors[$field_name])) {
            $this->form_errors[$field_name]['errors'] = array_merge($this->form_errors[$field_name]['errors'],$errors);
            $this->form_errors[$field_name]['data'] = array_merge($this->form_errors[$field_name]['data'],$data);
        }
        else {
            $this->form_errors[$field_name] = array(
                'errors' => $errors,
                'data' => $data
            );
        }
    }

    private function resetErrors() {
        // TODO
        // Might wanna call that after evaluation

        $model = $this->api->load->model('logic/session');
        $model->set($this->getErrorIdentifier(),array());
        $this->form_errors = array();
    }

    private function getErrorIdentifier() {
        return $this->baseId().'_'.$this->getPostId();
    }

    private function initMetas() {
        if (!property_exists($this,'input_metas')) {
            $this->input_metas = $this->inputMetas();
        }
        if (!property_exists($this,'input_sections')) {
            $this->input_sections = $this->inputSections();
        }
        if (!property_exists($this,'button_metas')) {
            $this->button_metas = $this->buttonMetas();
        }
    }
}