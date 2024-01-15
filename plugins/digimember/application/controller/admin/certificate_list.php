<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdmincertificateListController extends ncore_AdminTableController
{
    public function renderImageColumn( $row, $metas )
    {
        static $metas;
        static $edit_url;

        if (!isset($metas))
        {
            $this->api->load->model( 'data/exam_certificate' );
            $metas = $this->api->exam_certificate_data->getTemplateMetas();

            $this->api->load->model( 'logic/link' );
            $edit_url = $this->api->link_logic->adminPage( 'certificates', array( 'id' => '_ID_' ) );
        }

        $meta = ncore_retrieve( $metas, $row->type );

        if (!$meta)
        {
            return '';
        }

        $url = $meta[ 'preview_image_url' ];

        $one_edit_url = str_replace( '_ID_', $row->id, $edit_url );

        return "<a href='$one_edit_url'><img src=\"$url\" style='max-width: 150px; max-height: 100px;' alt=\"$row->type\" /></a>";
    }

    protected function pageHeadline()
    {
         return _digi('Exam certificates');
    }

    protected function isTableHidden()
    {
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseExams();
        return !$can_use;
    }

    protected function pageInstructions()
    {
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseExams();

        $instructions = array();

        if (!$can_use) {
            $model = $this->api->load->model( 'logic/link' );
            $msg = _digi( 'Exams are NOT included in your subscription.' );
            $instructions[] = $model->upgradeHint( $msg, $label='', $tag='p' );
        }
        return $instructions;
    }

    protected function modelPath()
    {
        return 'data/exam_certificate';
    }

    protected function columnDefinitions()
    {
        $this->api->load->model( 'data/exam_certificate' );

        $model = $this->api->load->model( 'logic/link' );
        $edit_url = $model->adminPage( 'certificates', array( 'id' => '_ID_' ) );

        $preview_url = $this->api->link_logic->certificateDownload( '_ID_' );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );
        $copy_url = $this->actionUrl( 'copy', '_ID_' );

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
                    'label' => _ncore('Preview'),
                    'action' => 'do_preview',
                    'url' => $preview_url,
                    'as_popup' => true,
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
                'column'  => 'type',
                'type'     => 'function',
                'function' => array( $this, 'renderImageColumn' ),
                'label'    => _digi( 'Template' ),
            ),


            array(
                'column' => 'for_exam_ids',
                'type' => 'id_list',
                'label' => _digi('Passed exams'),
                'sortable' => false,
                'model' => 'data/exam',
            ),

            array(
                'column' => 'is_active',
                'type' => 'yes_no_bit',
                'label' => _ncore('Active'),
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
        $settings[ 'no_items_msg'] = _digi('Please add an exam certificate first.');

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUseExams();
        if (!$can_use) {
            return array();
        }

        $model = $this->api->load->model( 'logic/link' );

        $new_url = $model->adminPage( 'certificates', array( 'select' => 'type' ) );

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
        $model = $this->api->load->model( 'logic/link' );

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

