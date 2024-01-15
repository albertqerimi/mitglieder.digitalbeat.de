<?php

class digimember_ExamAnswerData extends ncore_BaseData
{
    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }

    public function getStatusMapForUser( $user_obj_or_id, $exam_obj_or_id_list )
    {
        $this->api->load->model( 'data/exam' );

        $exam_ids    = array();
        $exams_by_id = array();

        if (!empty($exam_obj_or_id_list)) {
            foreach ($exam_obj_or_id_list as $one)
            {
                if (is_object($one))
                {
                    $exam_ids[] = $one->id;
                    $exams_by_id[ $one->id ] = $one;
                }
                else
                {
                    $exam_ids[] = $one;
                }
            }
        }

        $user_id = ncore_userId( $user_obj_or_id );

        $where = array(
            'user_id'    => $user_id,
            'exam_id IN' => $exam_ids,
        );

        $answers = $this->getAll( $where );

        $status_list = array();

        foreach ($answers as $one)
        {
            $is_passed = ncore_isTrue( $one->is_passed );
            $can_retry = false;

            if (!$is_passed)
            {
                $exam     = empty( $exams_by_id[ $one->exam_id ] )
                          ? $this->api->exam_data->get( $one->exam_id )
                          : $exams_by_id[ $one->exam_id ];

                $can_retry = ($exam->repeat_count === 999
                             ? true
                             : $one->try_count < $exam->repeat_count);
            }

            $rec = array(
                'started_at' => $one->created,
                'passed_at'  => ($is_passed                 ? $one->modified : false ),
                'failed_at'  => (!$is_passed && !$can_retry ? $one->modified : false ),
            );

            $status_list[ $one->exam_id ] = $rec;
        }

        foreach ($exam_ids as $id)
        {
            $is_missing = empty( $status_list[ $id ] );
            if ($is_missing)
            {
                $exam = empty( $exams_by_id[ $one->exam_id ] )
                          ? $this->api->exam_data->get( $one->exam_id )
                          : $exams_by_id[ $one->exam_id ];

                $is_exam_valid = (bool) $exam;

                if ($is_exam_valid) {
                    $status_list[$id] = array(
                        'started_at' => false,
                        'passed_at'  => false,
                        'failed_at'  => false,
                    );
                }
            }
        }

        return $status_list;
    }

    public function getAnswers( $exam, $user_id )
    {
        if (!$exam) {
            return false;
        }

        $answers = $this->_getForUser( $user_id, $exam->id, $do_create=false );

        if (ncore_isTrue( $exam->is_answer_sort_random))
        {
            $this->_sortRandom( $exam, $user_id );
        }

        if ($answers)
        {
            $this->_markAnswers( $exam, $answers->answers );

            $exam->test_correct_count         = $answers->correct_count;
            $exam->test_correct_rate          = $answers->correct_rate;
            $exam->test_question_count        = $answers->question_count;
            $exam->test_correct_required_perc = $answers->correct_required_perc;
            $exam->test_is_passed             = $answers->is_passed;
            $exam->test_try_count             = $answers->try_count;
            $exam->test_tries_left            = ncore_isTrue( $answers->is_passed )
                                                ? 0
                                                : ($exam->repeat_count === 999
                                                   ? 999
                                                   : max( 0, $exam->repeat_count - $answers->try_count ));

            $exam->test_can_repeat            = ncore_isFalse( $answers->is_passed ) && $answers->try_count < $exam->repeat_count;
        }

        $have_answers = (bool) $answers;

        return $have_answers;

    }

    public function saveAnswers( $exam, $user_id, $posted_answers )
    {
        if (!$exam) {
            return false;
        }

        $correct_count= 0;

        $sanitized_answers = $this->_markAnswers( $exam, $posted_answers );

        if (!$user_id) {
            return false;
        }

        $answers = $this->_getForUser( $user_id, $exam->id, $do_create=true );

        $can_retry = !$answers || $answers->try_count < $exam->repeat_count;
        if (!$can_retry) {
            return true;
        }

        $data = array(
            'correct_count'         => $exam->test_correct_count,
            'question_count'        => $exam->test_question_count,
            'correct_rate'          => $exam->test_correct_rate,
            'correct_required_perc' => $exam->test_correct_required_perc,
            'is_passed'             => ncore_toYesNoBit( $exam->test_is_passed ),

            'answers'               => $sanitized_answers,
        );

        $modified = $this->update( $answers, $data );
        if ($modified)
        {
            $data = array( 'try_count' => ncore_retrieve( $answers, 'try_count', 0 ) + 1 );
            $modified = $this->update( $answers, $data );
        }

        $exam->test_correct_count         = $answers->correct_count;
        $exam->test_correct_rate          = $answers->correct_rate;
        $exam->test_question_count        = $answers->question_count;
        $exam->test_correct_required_perc = $answers->correct_required_perc;
        $exam->test_is_passed             = $answers->is_passed;
        $exam->test_try_count             = $answers->try_count;
        $exam->test_tries_left            = ncore_isTrue( $answers->is_passed )
                                                ? 0
                                                : ($exam->repeat_count === 999
                                                   ? 999
                                                   : max( 0, $exam->repeat_count - $answers->try_count ));

        $exam->test_can_repeat            = ncore_isFalse( $answers->is_passed ) && $answers->try_count < $exam->repeat_count;

        return true;
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'exam_answer';
    }

    protected function serializedColumns()
    {
        return array(
            'answers',
        );
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'user_id'               => 'id',
            'exam_id'               => 'id',
            'try_count'             => 'int',
            'question_count'        => 'int',
            'correct_count'         => 'int',
            'correct_rate'          => 'int',
            'correct_required_perc' => 'int',
            'is_passed'             => 'yes_no_bit'
       );

       $indexes = array( 'user_id', 'exam_id'  );

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
        return false;
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

    private function _getForUser( $user_id, $exam_obj_or_id, $do_create )
    {
        $this->api->load->model( 'data/exam' );
        $exam_id = $this->api->exam_data->resolveToId( $exam_obj_or_id );
        if (!$exam_id) {
            return array();
        }

        $where = array(
            'user_id' => $user_id,
            'exam_id' => $exam_id,
        );

        $all    = $this->getAll( $where );
        $answer = false;
        foreach ($all as $one)
        {
            if ($answer) {
                $this->delete( $one );
            }
            else
            {
                $answer = $one;
            }
        }

        if (!$answer && $do_create) {
            $answer_id = $this->create( $where );
            $answer    = $this->get( $answer_id );
        }

        if ($answer)
        {
            $answer->correct_count = min( $answer->correct_count, $answer->question_count );
            $answer->correct_rate  = min( $answer->correct_rate, 100 );
        }

        return $answer;
    }

    private function _markAnswers( $exam, $posted_answers )
    {
        $sanitized_answers = array();

        $correct_count = 0;

        foreach ($exam->questions as $index => $question)
        {
            $question_id = ncore_retrieve( $question, 'id' );
            if (!$question_id) {
                continue;
            }

            $answers = ncore_retrieve( $posted_answers, $question_id );

            if (!is_array($answers)) {
                $answers = $answers
                         ? array( $answers )
                         : array();
            }

            $is_correct = true;
            foreach ($question[ 'answers' ] as $i => $answer)
            {
                if (empty($answer['text'])) {
                    continue;
                }

                if (empty($sanitized_answers[ $question_id ])) {
                    $sanitized_answers[ $question_id ] = array();
                }

                $is_selected = !empty( $answer['id'] ) && in_array( $answer['id'], $answers );
                if ($is_selected)
                {
                    $exam->questions[$index][ 'answers' ][ $i ]['is_selected'] = true;
                    $sanitized_answers[ $question_id ][] = $answer['id'];
                }

                $is_one_correct = $is_selected
                                ? ncore_isTrue( $answer['is_correct'] )
                                : ncore_isFalse( $answer['is_correct'] );

                if (!$is_one_correct) {
                    $is_correct = false;
                }
            }
            if ($is_correct)
            {
                $correct_count++;
            }
        }

        $exam->test_correct_count         = $correct_count;
        $exam->test_correct_rate          = round( 100*$correct_count / $exam->question_count, 1 );
        $exam->test_question_count        = $exam->question_count;
        $exam->test_correct_required_perc = $exam->required_correct_perc;
        $exam->test_is_passed             = ncore_toYesNoBit( $exam->test_correct_rate >= $exam->required_correct_perc );

        return $sanitized_answers;
    }

    private function _sortRandom( $exam, $user_id )
    {
        foreach ($exam->questions as $index => $question)
        {
            $answers =& $exam->questions[$index]['answers'];

            $sort = array();

            while (count($sort) < count($answers))
            {
                $sort[] = mt_rand ( 0, 10000 );
            }

            array_multisort( $sort, $answers );
        }
    }

}
