<?php

class digimember_ExamData extends ncore_BaseData
{
    const QUESTION_ID_LENGTH = 15;

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }

    public function options( $where=array())
    {
        return $this->asArray( 'name', 'id', $where );
    }

    public function isMultipleAnswerOptions( $msg_type='' )
    {
        switch ($msg_type)
        {
            case 'short':
                return array(
                    'N' => _digi( 'One' ),
                    'Y' => _digi( 'Many' )
                );

            case 'long':
            default:
                return array(
                    'N' => _digi( 'One answer is correct' ),
                    'Y' => _digi( 'Many answers may be correct' )
                );
        }
    }

    public function repeatCountOptions()
    {
        return array(
            '0' => _digi( 'no' ),
            '1' => _digi( 'once' ),
            '2' => _digi( 'twice' ),
            '3' => _digi( 'three times' ),
            '4' => _digi( 'four times' ),
            '5' => _digi( 'five times' ),
          '999' => _digi( 'unlimited times' ),
        );
    }

    public function resetAnswers( $exam_obj_or_id, $reset_user_id )
    {
        $exam = $this->resolveToObj( $exam_obj_or_id );
        if (!$exam) {
            return false;
        }

        $have_all = $exam->reset_what === 'all';

        $user_id = $have_all
                 ? false
                 : ncore_userId( $reset_user_id );

        $is_valid = $have_all || $user_id > 0;
        if (!$is_valid) {
            return false;
        }

        $where = array( 'exam_id' => $exam->id );
        if (!$have_all)
        {
            $where[ 'user_id' ] = $user_id;
        }

        $this->api->load->model( 'data/exam_answer' );
        return $this->api->exam_answer_data->deleteWhere( $where );

    }

    public function placeHolders( $exam_obj_or_id='example' )
    {
        $this->api->load->model( 'data/exam' );

        $is_example = $exam_obj_or_id === 'example';

        $exam = $is_example
                ? false
                : $this->resolveToObj( $exam_obj_or_id );

        if ($exam)
        {
            $required_correct_perc = ncore_retrieve( $exam, array( 'test_correct_required_perc', 'required_correct_perc', 0 ) );
            $total_questions       = ncore_retrieve( $exam, array( 'test_question_count',        'question_count', 0 ) );

            $repeat_count          = $exam->repeat_count;

            $correct_count         = ncore_retrieve( $exam, 'test_correct_count', 0 );
            $correct_rate          = ncore_retrieve( $exam, 'test_correct_rate', 0 );

            $tries_used            = ncore_retrieve( $exam, 'test_try_count', 0 );
            $is_passed             = ncore_isTrue( ncore_retrieve( $exam, 'test_is_passed', 0 ) );
        }
        elseif ($is_example)
        {
            $repeat_count          = 3;
            $required_correct_perc = 80;
            $total_questions       = 10;

            $correct_count         = 8;
            $correct_rate          = 80;

            $tries_used            = 2;
            $is_passed             = true;
        }
        else
        {
            $repeat_count          = 999;
            $required_correct_perc = 80;
            $total_questions       = 0;

            $correct_count         = 0;
            $correct_rate          = 0;

            $tries_used            = 0;
            $is_passed             = false;
        }

        $p = array();

        $p[ '[TRIES_TOTAL]' ] = ($repeat_count === 999 ? '&infin;' : $repeat_count);

        $options = $this->api->exam_data->repeatCountOptions();
        $p[ '[TRIES_TOTAL_TEXT]' ] = ncore_retrieve( $options, $repeat_count, $repeat_count );

        $p[ '[TRIES_USED]' ] = $tries_used;

        $tries_left = max( 0, $repeat_count-$tries_used);
        $p[ '[TRIES_LEFT]' ] = ($repeat_count === 999 ? '&infin;' : $tries_left );

        $p[ '[TRIES_LEFT_TEXT]' ] = ($repeat_count === 999 || $tries_used == 0
                                  ? $p[ '[TRIES_TOTAL_TEXT]' ]
                                  : ncore_retrieve( $options, $tries_left, $tries_left ) );


        $p[ '[RETRY_BUTTON]' ] = '(' . _digi( 'Retry exam button' ) . ')';

        $p[ '[IS_PASSED]' ] = $is_passed ? _ncore( 'yes' ) : _ncore( 'no' );

        $p[ '[REQUIRED_RATE]' ] = _digi( '%s%%', $required_correct_perc );

        $p[ '[REQUIRED_COUNT]' ] =  max( 0, min( $total_questions, ceil( $required_correct_perc * $total_questions / 100 ) ) );

        $p[ '[QUESTION_COUNT]' ] = $total_questions;

        $p[ '[CORRECT_COUNT]' ] = $correct_count;

        $p[ '[CORRECT_RATE]' ] = _digi( '%s%%', $correct_rate );

        return $p;
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'exam';
    }

    protected function serializedColumns()
    {
        return array(
            'questions',
        );
    }

    protected function onBeforeCopy( &$data )
    {
        parent::onBeforeSave( $data );

        $have_questions = !empty( $data[ 'questions' ] );
        if ($have_questions)
        {
            $question_count = 0;
            $answer_count   = 0;

            foreach ($data[ 'questions' ] as $index => $one)
            {
                $data[ 'questions' ][ $index ]['id'] = $this->_generateId();

                foreach ($one[ 'answers' ] as $i => $answer)
                {
                    $data[ 'questions' ][ $index ]['answers'][$i]['id'] = $this->_generateId();
                }
            }
        }
    }


    protected function onBeforeSave( &$data )
    {
        parent::onBeforeSave( $data );

        $have_questions = isset( $data[ 'questions_serialized' ] );
        if ($have_questions)
        {
            $questions = empty($data[ 'questions_serialized' ])
                       ? array()
                       : unserialize( $data[ 'questions_serialized' ] );


            $question_count = 0;
            $answer_count   = 0;

            $questions_with_many_correct_answers = 0;

            foreach ($questions as $one)
            {
                $have_question = !empty( $one['hl'] ) && !empty( $one['answer_count'] );
                if (!$have_question) {
                    continue;
                }

                $answer_count += $one['answer_count'];
                $question_count++;

                if ($one['correct_count'] != 1)
                {
                    $questions_with_many_correct_answers++;
                }

            }

            $data[ 'question_count' ]     = $question_count;
            $data[ 'answer_count' ]       = $answer_count;
            $data[ 'is_multiple_answer' ] = ncore_toYesNoBit( $questions_with_many_correct_answers > 0 );
        }
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name'                   => 'string[127]',
            'question_count'         => 'int',
            'answer_count'           => 'int',
            'repeat_count'           => 'int',
            'is_multiple_answer'     => 'yes_no_bit',
            'required_correct_perc'  => 'int',
            'is_answer_sort_random'  => 'yes_no_bit',

            'text_intro'             => 'text',
            'text_successs'          => 'text',
            'text_failure_retry'     => 'text',
            'text_failure'           => 'text',

            'submit_button_bg_color'  => 'string[15]',
            'submit_button_fg_color'  => 'string[15]',
            'submit_button_radius'    => 'int',

            'reset_what'    => 'string[15]',
            'reset_user_id' => 'int',
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

        $values[ 'required_correct_perc' ] = '80';
        $values[ 'repeat_count' ]          = 999;
        $values[ 'is_answer_sort_random' ] = 'Y';

        $values[ 'text_successs' ]      = _digi( 'Congratulations! You passed the exam with [CORRECT_COUNT] corrected answers of [QUESTION_COUNT] questions.' );

        $fail_msg  = _digi( 'I am sorry, but you failed the exam. You had [CORRECT_COUNT] corrected answers of [QUESTION_COUNT], but you needed [REQUIRED_COUNT] (or [REQUIRED_RATE]).' );
        $retry_msg = _digi( 'You may retry the exam [TRIES_LEFT] more times.' );

        $values[ 'text_failure_retry' ] = "<p>$fail_msg</p><p>$retry_msg</p>";
        $values[ 'text_failure_retry' ] = $fail_msg;

        $values[ 'submit_button_fg_color' ]    = '#FFFFFF';
        $values[ 'submit_button_bg_color' ]    = '#2196F3';
        $values[ 'submit_button_radius' ]      = 100;
        $values[ 'reset_what' ] = 'one';

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
            case 'questions':
                return $this->_sanitzeQuestions( $array );

            default:
                return $array;
        }
    }

    protected function _sanitzeQuestions( $questions )
    {
        if (empty($questions) || !is_array($questions))
        {
            return array();
        }

        $this->api->load->helper( 'string' );

        $white_space = array( '<p>', '</p>', ' ', "\n", "\r", "\t" );

        foreach ($questions as $index => $one)
        {
            $questions[$index]['hl'] = trim( $one['hl'] );

            $have_descr = str_replace( $white_space, '', $one['descr'] ) != '';
            if (!$have_descr) {
                $questions[$index]['descr'] = '';
            }

            $have_id = !empty($one['id']);
            if (!$have_id) {
                $questions[$index]['id'] = $this->_generateId();
            }

            $answers = ncore_retrieve( $one, 'answers' );
            if (empty($answers) || !is_array($answers)) {
                $answers = array();
            }

            $answer_count  = 0;
            $correct_count = 0;
            foreach ($answers as $i => $a)
            {
                $answers[$i]['text']       = trim( $a['text']);
                $answers[$i]['is_correct'] = ncore_toYesNoBit( $a['is_correct']);

                if (empty($answers[$i]['text'])) {
                    continue;
                }

                $have_id = !empty($a['id']);
                if (!$have_id) {
                    $answers[$i]['id'] = $this->_generateId();
                }

                if (!empty($answers[$i]['text'])) {
                    $answer_count++;

                    if (ncore_isTrue( $answers[$i]['is_correct'])) {
                        $correct_count++;
                    }
                }
            }

            $questions[$index]['answers']       = $answers;
            $questions[$index]['answer_count']  = $answer_count;
            $questions[$index]['correct_count'] = $correct_count;
        }

        return $questions;
    }


    private function _generateId()
    {
        $this->api->load->helper( 'string' );
        return ncore_randomString( 'alnum_upper', self::QUESTION_ID_LENGTH );
    }
}
