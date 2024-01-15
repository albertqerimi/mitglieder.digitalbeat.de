<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminExamEditController extends ncore_AdminFormController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }

    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUseExams();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUseExams();
    }

    protected function tabs()
    {
        $tabs = array();

        $tabs[ 'questions' ] = _digi( 'Questions' );

        $tabs[ 'texts' ]     = _digi( 'Texts' );

        $tabs[ 'settings' ]  = _digi( 'Properties' );

        return $tabs;
    }

    protected function isPageReloadAfterSubmit() {
        return true;
    }


    protected function pageHeadline()
    {
        return _digi( 'Exam' );
    }

    protected function inputMetas()
    {
        switch ($this->currentTab())
        {
            case 'questions':
                return $this->_inputMetasQuestions();

            case 'settings':
                return $this->_inputMetasSettings();

            case 'texts':
                return $this->_inputMetasTexts();
        }
        return [];
    }

    private function _inputMetasTexts()
    {
        /** @var digimember_ExamData $examData */
        $examData = $this->api->load->model( 'data/exam' );
        $this->api->load->model( 'data/exam_answer' );
        $this->api->load->helper( 'html_input' );

        $placeholders = $examData->placeHolders( 'example' );

        $placeholder_hl   = _digi( 'Placeholder' );
        $example_value_hl = _digi( 'Example value' );
        $cols             = 2;

        $placeholder_tt = '<table class="dm-placeholder"><tbody><tr>';
        for ($i=1; $i<=$cols; $i++)
        {
            $placeholder_tt .= "<th class='dm-placeholder-find'>$placeholder_hl</th><th class='dm-placeholder-repl'>$example_value_hl</th>";
        }
        $placeholder_tt .= '</tr><tr>';

        $keys  = array_keys( $placeholders );
        $count = ceil( count($keys) / 2 );

        for ($i=0; $i<$count; $i++)
        {
            $placeholder_1 = &$keys[$i];
            $placeholder_2 = &$keys[$i+$count];

            $placeholder_1_code = ncore_htmlTextInputCode( $placeholder_1 );
            $example_value_1    = $placeholders[ $placeholder_1 ];

            $placeholder_tt .= "<tr><td class='dm-placeholder-find'>$placeholder_1_code</td><td class='dm-placeholder-repl'>$example_value_1</td>";

            if (!empty($placeholder_2))
            {
                $placeholder_2_code = ncore_htmlTextInputCode( $placeholder_2 );
                $example_value_2    = $placeholders[ $placeholder_2 ];

                $placeholder_tt .= "<td class='dm-placeholder-find'>$placeholder_2_code</td><td class='dm-placeholder-repl'>$example_value_2</td>";
            }

            $placeholder_tt .= '</tr>';
        }

        $placeholder_tt .= '</tr></tbody></table>';

        $id = $this->getElementId();

        $html_shortcode = '<div class="dm-row">
    <div class="dm-col-md-8">
        ' . $this->_renderShortcode() . '
    </div>
</div>';
        $html_placeholder = '<div class="dm-row">
    <div class="dm-col-md-8">
        ' . $placeholder_tt . '
    </div>
</div>';

        $metas = array();

        $metas[] = array(
            'name'              => 'name',
            'section'           => 'texts',
            'type'              => 'text',
            'label'             => _ncore('Name' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );

        $metas[] = array(
            'section'           => 'texts',
            'type'              => 'html',
            'label'             => _ncore('Shortcode' ),
            'html'              => $html_shortcode,
        );

        $metas[] = array(
            'name'              => 'text_intro',
            'section'           => 'texts',
            'type'              => 'htmleditor',
            'label'             => _digi('Introduction'),
            'element_id'        => $id,
            'rows'              => 5,
            'hint'              => _digi( 'This text is shown when taking the exam right above the questions.' ),
        );
        $metas[] = array(
            'name'              => 'text_successs',
            'section'           => 'texts',
            'type'              => 'htmleditor',
            'label'             => _digi('Exam passed'),
            'element_id'        => $id,
            'rows'              => 5,
            'hint'              => _digi( 'This text is shown after the exam, if the user passes the exam.' ),
        );
        $metas[] = array(
            'name'              => 'text_failure_retry',
            'section'           => 'texts',
            'type'              => 'htmleditor',
            'label'             => _digi('Exam failed (may retry)'),
            'element_id'        => $id,
            'rows'              => 5,
            'hint'              => _digi( 'This text is shown after the exam, if the user fails the exam and may retry.' ),
        );
        $metas[] = array(
            'name'              => 'text_failure',
            'section'           => 'texts',
            'type'              => 'htmleditor',
            'label'             => _digi('Exam failed (finally)'),
            'element_id'        => $id,
            'rows'              => 5,
            'hint'              => _digi( 'This text is shown after the exam, if the user fails the exam and may <strong>NOT</strong> retry.' ),
        );
        $metas[] = array(
            'section'           => 'texts',
            'type'              => 'html',
            'label'             => _ncore('Placeholders' ),
            'html'              => $html_placeholder,
            'tooltip'           => _digi('In the texts above you may use these placeholders:'),
        );

        return $metas;
    }

    private function _inputMetasSettings()
    {
        /** @var digimember_ExamData $examData */
        $examData = $this->api->load->model( 'data/exam' );
        $this->api->load->model( 'data/exam_answer' );
        $this->api->load->helper( 'html_input' );

        $id = $this->getElementId();

        $exam = $examData->get( $id );
        $total_questions = $exam
                         ? max( 1, $exam->question_count )
                         : 1;
        $required_correct_perc = $exam
                               ? $exam->required_correct_perc
                               : 80;

        $html_shortcode = '<div class="dm-row">
    <div class="dm-col-md-8">
        ' . $this->_renderShortcode() . '
    </div>
</div>';

        $metas = array();

        $metas[] = array(
            'name'              => 'name',
            'section'           => 'general',
            'type'              => 'text',
            'label'             => _ncore('Name' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );

        $metas[] = array(
            'section'           => 'general',
            'type'              => 'html',
            'label'             => _ncore('Shortcode' ),
            'html'              => $html_shortcode,
        );



        $metas[] = array(
            'name'              => 'is_answer_sort_random',
            'section'           => 'general',
            'type'              => 'yes_no_bit',
            'label'             => _digi('Arange answers randomly' ),
            'element_id'        => $id,
        );


        $score = max( 0, min( $total_questions, ceil( $required_correct_perc * $total_questions / 100 ) ) );
        $min = "<span id='correct_count'>$score</span>";

        $metas[] = array(
            'name'              => 'required_correct_perc',
            'section'           => 'general',
            'type'              => 'int',
            'label'             => _digi('Required score' ),
            'rules'             => 'defaults|trim',
            'element_id'        => $id,
            'unit'              => '% ' . _digi( '= %s of %s questions answered correctly', $min, ($exam ? $exam->question_count : 1) ),
        );

        $metas[] = array(
            'name'              => 'repeat_count',
            'section'           => 'general',
            'type'              => 'select',
            'label'             => _digi('May be repeated'),
            'element_id'        => $id,
            'options'           => $examData->repeatCountOptions(),
        );

        $metas[] = array(
            'name'              => 'is_multiple_answer',
            'section'           => 'general',
            'type'              => 'select',
            'label'             => _digi('Exam type' ),
            'rules'             => 'readonly',
            'element_id'        => $id,
            'options'           => $examData->isMultipleAnswerOptions(),
        );


        $metas[] = array(
            'name' => 'submit_button_bg_color',
            'section' => 'style',
            'type' => 'color',
            'label' => _digi('Button background color' ),
            'element_id'        => $id,
        );

        $metas[] = array(
            'name' => 'submit_button_fg_color',
            'section' => 'style',
            'type' => 'color',
            'label' => _digi('Button text color' ),
            'element_id'        => $id,
        );

        $metas[] = array(
            'name' => 'submit_button_radius',
            'section' => 'style',
            'type' => 'select',
            'options' => 'border_radius',
            'label' => _digi('Button corner radius' ),
            'element_id'        => $id,
            'depends_on' => array( 'submit_button_type' => array( 'button' ) ),
        );

        $metas[] = array(
            'name'              => 'reset_what',
            'section'           => 'reset',
            'type'              => 'select',
            'label'             => _digi('Reset exams' ),
            'options'           => array(
                'one'             => _digi( 'for single user' ),
                'all'             => _digi( 'for all users' ),
            ),
            'element_id'        => $id,
        );

        $metas[] = array(
            'name'              => 'reset_user_id',
            'section'           => 'reset',
            'type'              => 'user_id',
            'label'             => _digi('User name' ),
            'element_id'        => $id,
            'depends_on'        => array( 'reset_what' => 'one' ),
            'default'           => ncore_userId(),
        );



        return $metas;
    }

    private function _inputMetasQuestions()
    {
        $this->api->load->helper( 'html_input' );

        $id = $this->getElementId();

        $html_shortcode = '<div class="dm-row">
    <div class="dm-col-md-8 dm-col-xs-12">
        ' . $this->_renderShortcode() . '
    </div>
</div>';

        $metas = array();

        $metas[] = array(
            'name'              => 'name',
            'section'           => 'questions',
            'type'              => 'text',
            'label'             => _ncore('Name' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );

        $metas[] = array(
            'section'           => 'questions',
            'type'              => 'html',
            'label'             => _ncore('Shortcode' ),
            'html'              => $html_shortcode,
        );



        $metas[] = array(
            'name'               => 'questions',
            'section'            => 'questions',
            'type'               => 'exam_questions',
            'label'              => _digi( 'Questions' ),
            'element_id'         => $id,
            'add_button_name'    => 'add',
            'min_question_count' => 3,
        );

        return $metas;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        $have_add_answer_button = $this->currentTab() == 'questions';

        if ($have_add_answer_button) {
            $metas[] = array(
                    'type'    => 'submit',
                    'label'   => '+ ' . _digi('Add question'),
                    'name'    => 'add',
                    'primary' => true,
                    'class'   => 'dm-exam-questions-add-question'
            );
        }

        $have_reset_button = $this->currentTab() == 'settings';

        if ($have_reset_button) {
            $metas[] = array(
                    'type'    => 'submit',
                    'label'   => _digi('Reset answers'),
                    'name'    => 'reset',
                    'primary' => true,
                    'confirm' => _digi( 'The answers of the selected users will now be reset.|The user(s) then can take the exam again.|Continue?' ),
            );
        }

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');
        $link = $linkLogic->adminPage( 'exam' );

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
            'questions' =>  array(
                            'headline'     => '',
                            'instructions' => '',
            ),
            'general' =>  array(
                            'headline'     => _ncore( 'Settings' ),
                            'instructions' => '',
            ),
            'texts' =>  array(
                            'headline'     => '', //_digi( 'Texts' ),
                            'instructions' => '',
            ),
            'style' =>  array(
                            'headline'     => _digi( 'Style' ),
                            'instructions' => '',
            ),
            'reset' =>  array(
                            'headline'     => _digi( 'Reset answers' ),
                            'instructions' => '',
            ),
        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        /** @var digimember_ExamData $model */
        $model = $this->api->load->model( 'data/exam' );

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            $obj = $model->get( $id );
        }
        else
        {
            $obj = $model->emptyObject();
        }

        if (!$obj)
        {
            $this->formDisable( _ncore( 'The element has been deleted.' ) );
            return false;
        }

        return $obj;
    }

    protected function setData( $id, $data )
    {
        $must_reset = false;
        $data = stripslashes_deep( $data );
        if (is_numeric($id) && $id >= 1 && $this->form()->isPosted( 'reset' )) {
            $must_reset = true;
            $reset_user_id = ncore_retrieveAndSet($data,'reset_user_id',0);
        }

        /** @var digimember_ExamData $model */
        $model = $this->api->load->model( 'data/exam' );

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            $is_modified  = $model->update( $id, $data );
        }
        else
        {
            $id = $model->create( $data );

            $this->setElementId( $id );

            $is_modified = (bool) $id;
        }

        if ($must_reset)
        {
            $count = $model->resetAnswers( $id , $reset_user_id);

            $msg = $count
                 ? _digi( 'The exams for the selected user(s) have been resetted.' )
                 : _digi( 'The selected user(s) did not take the exam.' );

            $type = $count
                   ? NCORE_NOTIFY_SUCCESS
                   : NCORE_NOTIFY_WARNING;

            ncore_flashMessage( $type, $msg );
        }

        return $is_modified;
    }

    protected function formActionUrl()
    {
        $this->api->load->helper( 'url' );

        $action_url = parent::formActionUrl();

        $id =  $this->getElementId();

        if ($id)
        {

            $args = array( 'id' => $id );

            return ncore_addArgs( $action_url, $args );
        }
        else
        {
            return $action_url;
        }
    }

    private function _renderShortcode()
    {

        $id = $this->getElementId();
        $have_shortcode = is_numeric( $id ) && $id >= 1;
        if ($have_shortcode)
        {
            /** @var digimember_ShortCodeController $controller */
            $controller = $this->api->load->controller( 'shortcode' );
            $shortcode = $controller->shortcode('exam' );

            $html_shortcode = ncore_htmlTextInputCode( "[$shortcode id=$id]" );
        }
        else
        {
            $html_shortcode = '<i>' . _digi( 'Please save to show the shortcode for this exam.' ) . '</i>';
        }

        return $html_shortcode;
    }




}