<?php

$load->controllerBaseClass( 'user/base' );

class digimember_UserExamController extends ncore_UserBaseController
{
    public function init( $settings=array() )
    {
        /** @var digimember_ExamData $examData */
        $examData = $this->api->load->model( 'data/exam' );
        /** @var digimember_ExamAnswerData $examAnswerData */
        $examAnswerData = $this->api->load->model( 'data/exam_answer' );

        $exam_id = ncore_retrieve( $settings, 'id' );

        $this->is_retrying = !empty( $_POST[ 'digimember_retry_exam_'.$exam_id ]);

        $posted_answers = isset( $_POST[ 'exam_'.$exam_id ] ) && !$this->is_retrying
                        ? $_POST[ 'exam_'.$exam_id ]
                        : false;

        $this->exam = $examData->get( $exam_id );

        $user_id = ncore_userId();

        $this->have_answers = $examAnswerData->getAnswers( $this->exam, $user_id );

        if ($posted_answers)
        {
            $this->have_answers = $examAnswerData->saveAnswers( $this->exam, $user_id, $posted_answers );
        }
    }

    protected function handleRequest()
    {
        parent::handleRequest();
    }

    protected function viewName()
    {
        if (!$this->exam)
        {
            return 'user/exam_not_found';
        }

        if ($this->have_answers && !$this->is_retrying)
        {
            return 'user/exam_completed';
        }

        return 'user/exam_questions';
    }

    protected function viewData()
    {
        $exam = $this->exam;

        /** @var digimember_ExamData $examData */
        $examData = $this->api->load->model( 'data/exam' );
        $p = $examData->placeHolders( $exam );

        $button_style = ncore_renderButtonStyle( $exam, 'submit_button_' );

        $data = array(
            'exam' => $exam,
            'button_style' => $button_style,
        );

        $p[ '[RETRY_BUTTON]' ] = '';

        if ($this->have_answers && !$this->is_retrying)
        {
            $must_add_button = false;

            $is_passed  = ncore_isTrue( $exam->test_is_passed );
            $can_repeat = ncore_isTrue( $exam->test_can_repeat );

            if ($exam->repeat_count>0 && !$is_passed)
            {
                $disabled = $can_repeat
                          ? ''
                          : 'disabled="disabled"';

                $css = $can_repeat
                          ? 'digimember_button_enabled'
                          : 'digimember_button_disabled';

                $tt = $exam->test_tries_left === 999
                      ? _dgyou( 'You may retry this exam as often as you like.' )
                      : ($exam->test_tries_left > 0
                         ? _dgyou( 'You may retry this exam %s more times.', $exam->test_tries_left )
                         : _dgyou( 'You have used all your tries for this exam. You cannot retry it.', $exam->test_tries_left ));

                $this->api->load->helper( 'html' );



                $retry_label = _digi( 'Retry exam' );
                $button_html = "<form method='post'><input title=\"$tt\" class='button button-primary digimember_exam_button digimember_exam_retry_button $css' style='$button_style' $disabled name='digimember_retry_exam_$exam->id' value=\"$retry_label\" type=\"submit\"></form>";

                $p[ '[RETRY_BUTTON]' ] = $button_html;

                $must_add_button = stripos( $exam->text_failure_retry, '[RETRY_BUTTON]' ) === false;

            }

            $text = $is_passed
                  ? $exam->text_successs
                  : ($can_repeat
                     ? $exam->text_failure_retry
                     : $exam->text_failure);

            if ($must_add_button)
            {
                $text .= "<p>[RETRY_BUTTON]</p>";
            }

            $data[ 'text' ] = wpautop( str_replace( array_keys($p), array_values($p), $text ) );
        }
        elseif ($exam)
        {
            $data[ 'text' ] = wpautop( str_replace( array_keys($p), array_values($p), $exam->text_intro ) );
        }


        return $data;
    }


    private $have_answers = false;
    private $is_retrying  = false;
    /** @var bool | stdClass */
    private $exam         = false;

}
