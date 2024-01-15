<?php

$load->controllerBaseClass('admin/form');

class digimember_AdminCertificateEditController extends ncore_AdminFormController
{
    public function init($settings=array())
    {
        parent::init($settings);
    }

    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted()) {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model('logic/features');
        return $model->canUseExams();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted()) {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model('logic/features');
        return $model->canUseExams();
    }

    protected function pageHeadline()
    {
        return _digi('Exam certificate');
    }

    protected function inputMetas()
    {
        /** @var digimember_ExamData $examData */
        $examData = $this->api->load->model('data/exam');
        /** @var digimember_ExamCertificateData $examCertificateData */
        $examCertificateData = $this->api->load->model('data/exam_certificate');

        $this->api->load->helper('html_input');

        $id = $this->getElementId();

        $obj    = $examCertificateData->get($id);
        $is_new = !$obj;

        $type = $is_new
              ? ncore_retrieveGET('type')
              : $obj->type;

        $type_meta = $type
                   ? $examCertificateData->getTemplateMetas($type)
                   : false;

        $is_valid = (bool) $type_meta;
        if (!$is_valid) {
            /** @var digimember_LinkLogic $linkLogic */
            $linkLogic = $this->api->load->model('logic/link');
            $new_url = $linkLogic->adminPage('certificates', array( 'select' => 'type' ), $sep='&');
            ncore_redirect($new_url);
        }

        $metas = array();

        $metas[] = array(
            'name'              => 'name',
            'section'           => 'general',
            'type'              => 'text',
            'label'             => _ncore('Name'),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );

        $metas[] = array(
                'name' => 'is_active',
                'section' => 'general',
                'type' => 'yes_no_bit',
                'label' => _ncore('Active'),
                'element_id' => $id,
        );


        $metas[] = array(
            'section' => 'general',
            'name' => 'for_exam_ids',
            'type' => 'checkbox_list',
            'seperator' => '<br />',
            'label' => _digi('Passed exams'),
            'hint'  => _digi('The certificate is granted, if all of the selected exams are passed. If no exam is checked, the user can download it when he has access to the page containing the shortcode for this certificate.'),
            'options' => $examData->options(),
            'element_id' => $id,
        );


        $html = // '<strong>' . $type_meta['name'] . '</strong><br />' .
                "<img style='max-height: 150px; max-width: 150px' src=\"${type_meta['preview_image_url']}\" />";


        $metas[] = array(
            'name'              => 'type',
            'section'           => 'template',
            'type'              => 'html',
            'label'             => _digi('Template'),
            'html'              => $html,
        );


        $find = [ '[FULL_ADMIN_NAME]' ];
        $repl = $this->_full_admin_name();

        foreach ($type_meta[ 'settings_metas' ] as $one) {
            $meta = $one;

            $meta[ 'section' ]    = 'template';
            $meta[ 'name' ]       = 'template_' . $one['name'];
            $meta[ 'element_id' ] = $id;

            $is_image = $one['type'] == 'image';
            if ($is_image) {
                $url = $meta[ 'sample_url' ];
                $msg = _digi('Download a template image');
                $meta[ 'hint' ] = ncore_linkReplace($msg, $url, $as_popup=true);
            }

            $have_depends_on = !empty($meta[ 'depends_on' ]);
            if ($have_depends_on) {
                $meta[ 'depends_on' ] = array();
                foreach ($one[ 'depends_on' ] as $name => $value) {
                    $meta[ 'depends_on' ][ "template_$name" ] = $value;
                }
            }

            $must_add_default_hint = empty($meta['hint']) && !empty($meta['default']) && $meta['type']=='text';
            if ($must_add_default_hint) {
                $meta['hint'] = _ncore('Default is: %s', $meta['default']);
            }

            if (!empty($meta['default'])) {
                $meta['default'] = str_replace($find, $repl, $meta['default']);
            }

            $metas[] = $meta;
        }

        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller('shortcode');
        $shortcode = $controller->shortcode('exam_certificate');
        $html = '
<div class="dm-row">
    <div class="dm-col-md-8 dm-col-xs-12">
        ' . ncore_htmlTextInputCode("[$shortcode id=$id]") . '    
    </div>
</div>        
';

        $metas[] = array(
            'type'              => 'html',
            'section'           => 'download',
            'label'             => _digi('Shortcode'),
            'html'              => $html,
            'hint'              => _digi('Use this shortcode to display the certificate download button in your page.'),
        );

        $metas[] = array(
            'name' => 'download_button_type',
            'section' => 'download',
            'type' => 'select',
            'label' => _digi('Button type'),
            'options' => array(
                            'button' => _digi('Display text button'),
                            'image'  => _digi('Display image button'),
                         ),
            'element_id'        => $id,
        );

        $metas[] = array(
            'name' => 'download_button_label',
            'section' => 'download',
            'type' => 'text',
            'label' => '&bull; '._digi('Text of download button'),
            'element_id'        => $id,
            'depends_on' => array( 'download_button_type' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'download_button_bg_color',
            'section' => 'download',
            'type' => 'color',
            'label' => '&bull; '._digi('Background color'),
            'element_id'        => $id,
            'depends_on' => array( 'download_button_type' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'download_button_fg_color',
            'section' => 'download',
            'type' => 'color',
            'label' => '&bull; '._digi('Text color'),
            'element_id'        => $id,
            'depends_on' => array( 'download_button_type' => array( 'button' ) ),
        );

        $metas[] = array(
            'name' => 'download_button_radius',
            'section' => 'download',
            'type' => 'select',
            'options' => 'border_radius',
            'label' => '&bull; '._digi('Corner radius'),
            'element_id'        => $id,
            'depends_on' => array( 'download_button_type' => array( 'button' ) ),
        );


        $metas[] = array(
            'name' => 'download_button_image_id',
            'section' => 'download',
            'type' => 'image',
            'label' => '&bull; '._digi('Button image'),
            'element_id'        => $id,
            'depends_on' => array( 'download_button_type' => array( 'image' ) ),
        );






        $metas[] = array(
            'type'              => 'htmleditor',
            'name'              => 'text_passed',
            'section'           => 'download',
            'label'             => _digi('Text after user passed all exams'),
            'hint'              => _digi('Is shown, after the user has passed all exams (if any selected) and can download the certificate.'),
            'rows'              => 4,
            'element_id'        => $id,
        );

        $metas[] = array(
            'type'              => 'htmleditor',
            'name'              => 'text_pending',
            'section'           => 'download',
            'label'             => _digi('Text while user is taking the exams'),
            'hint'              => _digi('Is shown, while the user has not yes taken all of the exams. If no exams are selected, this text is never shown.'),
            'rows'              => 4,
            'element_id'        => $id,
        );

        $metas[] = array(
            'type'              => 'htmleditor',
            'name'              => 'text_failed',
            'section'           => 'download',
            'label'             => _digi('Text if user fails one or more exams permanently'),
            'hint'              => _digi('Is shown, if the user has failed at least one exam permanently. If no exams are selected, this text is never shown.'),
            'rows'              => 4,
            'element_id'        => $id,
        );

        $html = '<div class="dm-row">';
        $placeholders = $examCertificateData->getPlaceholders();
        foreach ($placeholders as $placeholder) {
            $html .= '
<div class="dm-col-md-4 dm-col-sm-6 dm-col-xs-12">
    ' . ncore_htmlTextInputCode($placeholder) . '
</div>
';
        }
        $html .= '</div>';

        $metas[] = array(
            'type'              => 'html',
            'section'           => 'download',
            'label'             => _digi('Placeholders'),
            'html'              => $html,
        );
        return $metas;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

//        $metas[] = array(
//                'type'    => 'submit',
//                'name'    => 'preview',
//                'label'   => _digi( 'Preview' ),
//                'primary' => true,
//        );


        $form_id = $this->formId();

        $metas[] = array(
                'type' => 'ajax',
                'label' => _digi('Create a certificate'),
                'primary' => true,
                'ajax_meta' => array(
                            'type' => 'form',
                            'cb_form_id' => $form_id,
                            'message' => '',
                            'title' => _digi('Preview / Create certificate'),
                            'width' => '600px',
                            'modal' => false,
                            'form_sections' => array(
                            ),
                            'form_inputs' => array(
                                array(
                                    'name' => 'create_for_name',
                                    'type' => 'text',
                                    'label' => _digi('Full name of recipient'),
                                    'rules' => 'defaults|trim|required',
                                    'default' => $this->_full_admin_name(),
                                ),
                         ),
                    ),
                );

        $have_obj = $this->getElementId() > 0;

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');

        $link = $have_obj
              ? $linkLogic->adminPage('certificates')
              : $linkLogic->adminPage('certificates', array( 'select' => 'type' ));
        $metas[] = array(
                'type' => 'link',
                'label' => _ncore('Back'),
                'url' => $link,
        );

        return $metas;
    }

    protected function sectionMetas()
    {
        return array(
            'general' =>  array(
                            'headline'     => _ncore('Settings'),
                            'instructions' => '',
            ),
            'template' =>  array(
                            'headline'     => _digi('Template'),
                            'instructions' => '',
            ),
            'download' =>  array(
                            'headline'     => _digi('Download'),
                            'instructions' => '',
            ),
        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData($id)
    {
        /** @var digimember_ExamCertificateData $model */
        $model = $this->api->load->model('data/exam_certificate');

        $have_id = is_numeric($id) && $id > 0;

        if ($have_id) {
            $obj = $model->get($id);
        } else {
            $obj = $model->emptyObject();
            foreach ($this->inputMetas() as $meta) {
                $have_default = !empty($meta['default']);
                if ($have_default) {
                    $key = $meta[ 'name' ];
                    $val = $meta[ 'default' ];
                    $obj->$key = $val;
                }
            }
        }

        if (!$obj) {
            $this->formDisable(_ncore('The element has been deleted.'));
            return false;
        }

        return $obj;
    }

    protected function setData($id, $data)
    {
        /** @var digimember_ExamCertificateData $model */
        $model = $this->api->load->model('data/exam_certificate');

        $have_id = is_numeric($id) && $id > 0;

        $data[ 'template_serialized' ] = '';

        if ($have_id) {
            $modified = $model->update($id, $data);
        } else {
            $type = ncore_retrieveGET('type');

            $is_valid = (bool) $model->getTemplateMetas($type);

            if (!$is_valid) {
                $type_metas = $model->getTemplateMetas();

                $type = $type_metas
                      ? $type_metas[0][ 'type' ]
                      : false;
            }

            if (!$type) {
                return false;
            }

            $data[ 'type' ] = $type;

            $id = $model->create($data);

            $this->setElementId($id);

            $modified = (bool) $id;
        }

        $postname = "ncore_create_for_name";
        $is_preview = $id >0 && !empty($_POST[ $postname ]);
        if ($is_preview) {
            $fullname = $_POST[ $postname ];

            /** @var digimember_LinkLogic $linkLogic */
            $linkLogic = $this->api->load->model('logic/link');

            $preview_url = $linkLogic->certificateDownload($id, $fullname);

            // $js = "window.open(\"$preview_url\");";
            // $this->api->load->model( 'logic/html' );
            // $this->api->html_logic->jsOnLoad( $js );

            $msg = _digi('The certificate has been created. <a>Click here to download it.</a>');

            $msg = ncore_linkReplace($msg, $preview_url);

            ncore_flashMessage(NCORE_NOTIFY_SUCCESS, $msg);
        }

        return $modified;
    }

    protected function formActionUrl()
    {
        $this->api->load->helper('url');

        $action_url = parent::formActionUrl();

        $id =  $this->getElementId();

        if ($id) {
            $args = array( 'id' => $id );

            return ncore_addArgs($action_url, $args);
        } else {
            return $action_url;
        }
    }

    private function _full_admin_name()
    {
        $full_admin_name = '';
        $user = get_userdata(ncore_userId());
        if ($user) {
            $full_admin_name = trim($user->first_name . ' ' . $user->last_name);
            if (!$full_admin_name) {
                $full_admin_name = trim($user->display_name);
            }
        }
        return $full_admin_name;
    }
}
