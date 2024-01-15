<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminExamListController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _digi('Exam');
    }

    protected function isTableHidden()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseExams();
        return !$can_use;
    }

    protected function pageInstructions()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseExams();

        $instructions = array();

        if (!$can_use) {
            $this->api->load->helper('html_input');
            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model( 'logic/link' );
            $msg = _digi( 'Exams are not included in your subscription.' );
            $instructions[] = ncore_htmlAlert('info', $msg, 'info', '', $model->upgradeHint('', $label='', $tag='p' ));
        }

        return $instructions;
    }

    protected function modelPath()
    {
        return 'data/exam';
    }

    protected function columnDefinitions()
    {
        /** @var digimember_ExamData $examData */
        $examData = $this->api->load->model( 'data/exam' );

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $edit_url = $model->adminPage( 'exam', array( 'id' => '_ID_' ) );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );
        $copy_url = $this->actionUrl( 'copy', '_ID_' );

        $this->api->load->model( 'logic/action' );

        return array(

             array(
                'column' => 'name',
                'type' => 'text',
                'label' => _ncore('Name'),
                'search' => 'generic',
                'compare' => 'like',
                'sortable' => true,
                'actions' => array(
                    array(
                    'label' => _ncore('Edit'),
                    'action' => 'edit',
                    'url' => $edit_url,
                    'depends_on' => array(
                        'status' => array(
                            'created',
                            'active',
                            'inactive',
                            'published',
                        ),
                    )
                   ),
                   array(
                    'label' => _ncore('Copy'),
                    'action' => 'copy',
                    'url' => $copy_url,
                    'depends_on' => array(
                        'status' => array(
                            'created',
                            'active',
                            'inactive',
                            'published',
                        ),
                    )
                    ),

                    array(
                        'action' => 'trash',
                        'url' => $trash_url,
                        'label' => _ncore('Move to trash'),
                        'depends_on' => array(
                            'status' => array(
                                'created',
                                'active',
                            )
                        )
                    ),
                   array(
                        'action' => 'restore',
                        'url' => $restore_url,
                        'label' => _ncore('Restore'),
                        'depends_on' => array(
                            'status' =>  'deleted',
                        )
                    ),

                    array(
                        'action' => 'delete',
                        'url' => $delete_url,
                        'label' => _ncore('Delete irrevocably'),
                        'depends_on' => array(
                               'status' =>  'deleted',
                        )
                    ),
                )
            ),

            array(
                'column' => 'question_count',
                'type' => 'int',
                'label' => _digi('Questions'),
                'sortable' => true,
            ),



            array(
                'column' => 'repeat_count',
                'type' => 'select',
                'label' => _digi('May be repeated'),
                'sortable' => true,
                'options' => $examData->repeatCountOptions(),
            ),


            array(
                'column' => 'is_multiple_answer',
                'type' => 'select',
                'label' => _digi('Correct answers'),
                'sortable' => true,
                'options' => $examData->isMultipleAnswerOptions( 'short' ),
            ),


            array(
                'column' => 'id',
                'type' => 'id',
                'label' => _ncore('Id'),
                'sortable' => true,
            ),

            array(
                'column' => 'modified',
                'type' => 'status_date',
                'label' => _ncore('Date'),
                'sortable' => true,
                'status_labels' => $this->model()->statusLabels(),
            )
        );
    }



    protected function viewDefinitions()
    {
        return array(
            array(
                'view' => 'all',
                'where' => array(),
                'label' => _ncore('All')
            ),
            array(
                'view' => 'trash',
                'where' => array(
                    'deleted !=' => null
                ),
                'label' => _ncore('Trash'),
                'no_items_msg' => _ncore('The trash is empty.'),
            )
        );
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings[ 'row_css_column' ] = 'status';
        $settings[ 'default_sorting'] = array( 'name', 'asc' );
        $settings[ 'no_items_msg'] = _digi('Please add an exam first.');

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseExams();
        if (!$can_use) {
            return array();
        }

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $new_url = $model->adminPage( 'exam', array( 'id' => 'new' ) );

        return array(
                $this->pageHeadlineActionRec( 'create', $new_url ),
        );
    }

    protected function handleTrash( $elements )
    {
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->moveToTrash( $id );
        }

        $this->actionSuccess( 'trash', $elements );
    }

    protected function handleRestore( $elements )
    {
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->retoreFromTrash( $id );
        }

        $this->actionSuccess( 'restore', $elements );
    }

    protected function handleDelete( $elements )
    {
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->delete( $id );
        }

        $this->actionSuccess( 'delete', $elements );
    }

    protected function handleCopy( $elements )
    {
        $model = $this->model();

        $created_elements = array();

        foreach ($elements as $id)
        {
            $created_elements[] = $model->copy( $id );
        }

        $this->actionSuccess( 'copy', $created_elements );
    }

    protected function undoAction( $action )
    {
        switch ($action)
        {
            case 'delete':     return false;
            case 'trash':      return 'restore';
            case 'restore':    return 'trash';
            case 'activate':   return 'deactivate';
            case 'deactivate': return 'activate';
            case 'copy':       return 'delete';
        }

        return parent::undoAction( $action );
    }

    protected function bulkActionDefinitions()
    {
        $this->api->load->model( 'logic/link' );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        return array(

                    array(
                        'action' => 'trash',
                        'url' => $trash_url,
                        'label' => _ncore('Move to trash'),
                        'views' => array( 'all', 'published', 'drafts' ),
                    ),
                   array(
                        'action' => 'restore',
                        'url' => $restore_url,
                        'label' => _ncore('Restore'),
                        'views' => array( 'trash' ),
                    ),

                    array(
                        'action' => 'delete',
                        'url' => $delete_url,
                        'label' => _ncore('Delete irrevocably'),
                        'views' => array( 'trash' ),
                    ),
        );
    }

}

