<?php

class digimember_FormRenderer_InputExamQuestions extends ncore_FormRenderer_InputBase
{
    public function __construct( $parent, $meta )
    {
        parent::__construct( $parent, $meta );

        $this->min_question_count = $this->meta( 'min_question_count', 1 );
        $this->min_answer_count   = $this->meta( 'min_answer_count',   3 );
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
        $questions = ncore_retrieve( $_POST, $basename.'_questions' );

        $indexes = explode( ',', $sort );
        foreach ($indexes as $i)
        {
            if (empty($questions[$i])) {
                continue;
            }
            $raw_question = $questions[ $i ];

            $is_open = (empty( $raw_question['is_open'] ) ? 0 : 1);

            $hl          = ncore_retrieve( $raw_question, 'hl',    '' );
            $descr       = ncore_retrieve( $raw_question, 'descr', '' );
            $question_id = ncore_washText( ncore_retrieve( $raw_question, 'id' ) );


            $raw_answers = ncore_retrieve( $raw_question, 'answers', array() );
            $answers     = array();

            $is_valid = !empty($raw_answers) && is_array($raw_answers);
            if ($is_valid) {
                foreach ($raw_answers as $raw_answer)
                {
                    $text       = ncore_retrieve( $raw_answer, 'text', '' );
                    $answer_id  = ncore_washText( ncore_retrieve( $raw_answer, 'id', '' ) );
                    $is_correct = ncore_toYesNoBit( ncore_retrieve( $raw_answer, 'is_correct', 'N' ) );

                    $answer = array(
                        'id'         => $answer_id,
                        'text'       => $text,
                        'is_correct' => $is_correct,
                    );

                    $answers[] = $answer;
                }
            }

            $value[] = array(
                'is_open' => $is_open,
                'hl'      => $hl,
                'id'      => $question_id,
                'descr'   => $descr,
                'answers' => $answers,
            );
        }
    }

    protected function renderInnerWritable()
    {
        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model ('logic/html' );
        $htmlLogic->loadPackage('exam-questions.js');

        $examTranslations = [
            'isCorrectN' => _digi('wrong'),
            'isCorrectY' => _digi('correct'),
            'labelAddQuestion' => _digi('Add question'),
            'labelRemQuestion' => _digi('Remove question'),
            'labelAddAnswer' => _digi('Add answer'),
            'labelRemAnswer' => _digi('remove'),
            'labelQuestion' => _digi('Question'),
	        'labelExplanation' => _digi('Explanation'),
            'labelAnswerN' => _digi('Answer %s', '__num__'),
        ];
        $examData = [
            'minQuestionCount' => $this->meta('min_question_count', 1),
            'minAnswerCount' => $this->meta('min_answer_count', 3),
            'baseName' => $this->postname(),
        ];

        $examTranslationsJson = htmlentities(json_encode($examTranslations));
        $examDataJson = htmlentities(json_encode($examData));
        $examQuestionsJson = htmlentities(json_encode($this->value()));
        $examEditorCode = htmlentities(ncore_htmleditor('__exam_question_dummy__', '', [
            'editor_id' => '__exam_question_dummy__',
            'rows' => 5,
        ]));
        $html = '<div
            class="dm-exam-questions"
            data-exam-editor-template="' . $examEditorCode . '"
            data-exam-translations="' . $examTranslationsJson . '"
            data-exam-data="' . $examDataJson . '"
            data-exam-questions="' . $examQuestionsJson . '"
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
    private $min_question_count = 1;
    private $min_answer_count   = 3;
}


