<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminActionLogController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _ncore('Actions');
    }

    protected function modelPath()
    {
        return 'queue/action_out';
    }

    protected function tabs()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $tabs = array();

        $tabs[ 'list' ] = array(
            'url'   => $model->adminPage( 'actions' ),
            'label' => _ncore( 'Edit' ),
        );

        $tabs[ 'log' ] = _ncore( 'Log' );

        $tabs[ 'settings' ] = array(
            'url'  => $model->adminPage( 'actions', array( 'cfg' => 'all' ) ),
            'label' => _ncore( 'Settings' ),
        );

        return $tabs;
    }

    protected function columnDefinitions()
    {
        /** @var digimember_ActionLogic $model */
        $model = $this->api->load->model( 'logic/action' );
        $status_options = $model->statusOptions();

        return array(

           array(
                'column' => 'user_id',
                'type' => 'user',
                'label' => _ncore('User'),
                'sortable' => 'desc',
                'search' => 'generic',
            ),

           array(
                'column' => 'action_id',
                'type' => 'int',
                'label' => _ncore('Action id'),
            ),

           array(
                'column' => 'action_id',
                'type' => 'model',
                'label' => _ncore('Action name'),
                'model' => 'data/action',
                'search' => 'generic',
                'compare' => 'like',
            ),

           array(
                'column' => 'created',
                'type' => 'date_time',
                'label' => _ncore('Created'),
                'sortable' => 'desc',
            ),

           array(
                'column' => 'processed_at',
                'type' => 'date_time',
                'label' => _ncore('Processed'),
                'sortable' => 'desc',
            ),

//           array(
//                'column' => 'facebook_status',
//                'type' => 'array',
//                'label' => _ncore('Facebook'),
//                'sortable' => 'desc',
//                'array' => $status_options,
//                'hide' => !ncore_hasFacebookApp(),
//            ),

           array(
                'column' => 'webpush_status',
                'type' => 'array',
                'label' => _digi('Web push notification'),
                'sortable' => 'desc',
                'array' => $status_options,
            ),


           array(
                'column' => 'email_status',
                'type' => 'array',
                'label' => _ncore('Email'),
                'sortable' => 'desc',
                'array' => $status_options,
            ),

           array(
                'column' => 'klicktipp_status',
                'type' => 'array',
                'label' => _ncore('Autoresponder'),
                'sortable' => 'desc',
                'array' => $status_options,
            ),

        );
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings[ 'default_sorting'] = array( 'created', 'desc' );

        return $settings;
    }
}


