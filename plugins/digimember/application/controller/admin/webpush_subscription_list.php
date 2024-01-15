<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminWebpushSubscriptionListController extends ncore_AdminTableController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );

        $this->api->load->model( 'logic/webpush' );
        $this->api->load->model( 'data/webpush_subscription' );
        $this->api->webpush_logic->notifySetupErrors();
    }


    protected function pageHeadline()
    {
         return _digi('Web push subscriptions');
    }

    protected function isTableHidden()
    {
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUsePushNotifications();
        return !$can_use;
    }

    protected function pageInstructions()
    {
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUsePushNotifications();

        $instructions = array();

        if (!$can_use) {
            $model = $this->api->load->model( 'logic/link' );
            $msg = _digi( 'Web push notifications are NOT included in your subscription.' );
            $instructions[] = $model->upgradeHint( $msg, $label='', $tag='p' );
        }

        return $instructions;
    }

    protected function modelPath()
    {
        return 'data/webpush_subscription';
    }

    protected function columnDefinitions()
    {
        $this->api->load->model( 'data/webpush_message' );

        $model = $this->api->load->model( 'logic/link' );

        $trash_url   = $this->actionUrl( 'trash', '_ID_'   );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );
        $delete_url  = $this->actionUrl( 'delete', '_ID_'  );

        return array(

            array(
                'column' => 'subscription_id',
                'type' => 'text',
                'label' => _digi('Subscription id'),
                'search' => 'generic',
                'compare' => 'like',
                'sortable' => true,
                'actions' => array(
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

//            array(
//                'column' => 'message',
//                'type' => 'html',
//                'label' => _digi('Message'),
//            ),

            array(
                'column' => 'user_id',
                'type' => 'user',
                'label' => _ncore('User'),
                'search' => 'generic',
            ),

            array(
                'column' => 'last_result',
                'type' => 'array',
                'label' => _digi('Last result'),
                'sortable' => true,
                'array' => $this->api->webpush_subscription_data->lastResultOptions(),
            ),

            array(
                'column' => 'success_count',
                'type' => 'int',
                'label' => _digi('Deliveries'),
                'sortable' => true,
            ),
            array(
                'column' => 'error_count',
                'type' => 'int',
                'label' => _digi('Errors'),
                'sortable' => true,
            ),
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
        $settings[ 'default_sorting'] = array( 'created', 'desc' );

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUsePushNotifications();
        if (!$can_use) {
            return array();
        }

        $model = $this->api->load->model( 'logic/link' );

        $new_url      = $model->adminPage( 'webpush', array( 'id'       => 'new' ) );

        return array(
                $this->pageHeadlineActionRec( 'create',   $new_url      ),
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

    protected function actionUrlExtraArgs()
    {
        return array( 'subscriptions' => 'show' );
    }


    protected function tabs()
    {
        $model = $this->api->load->model( 'logic/link' );

        $tabs = array();

        $tabs[ 'edit' ] = array(
            'url'   => $model->adminPage( 'webpush'),
            'label' => _ncore( 'Messages' ),
        );

        $tabs[ 'subscriptions' ] = array(
            'url'   => $model->adminPage( 'webpush', array( 'subscriptions' => 'show' ) ),
            'label' => _digi( 'Subscriptions' ),
        );

        $tabs[ 'settings' ] = array(
            'url'   => $model->adminPage( 'webpush', array( 'settings' => 'edit' ) ),
            'label' => _ncore( 'Settings' ),
        );

        return $tabs;
    }

    protected function bulkActionDefinitions()
    {
        $model = $this->api->load->model( 'logic/link' );

        $trash_url   = $this->actionUrl( 'trash',   '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );
        $delete_url  = $this->actionUrl( 'delete',  '_ID_' );

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

