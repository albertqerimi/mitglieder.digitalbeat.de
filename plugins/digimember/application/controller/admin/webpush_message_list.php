<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminWebpushMessageListController extends ncore_AdminTableController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );

        /** @var digimember_WebpushLogic $webpushLogic */
        $webpushLogic = $this->api->load->model( 'logic/webpush' );
        $webpushLogic->notifySetupErrors();
    }


    protected function pageHeadline()
    {
         return _digi('Web push notification messages');
    }

    protected function isTableHidden()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUsePushNotifications();
        return !$can_use;
    }

    protected function pageInstructions()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use = $model->canUsePushNotifications();

        $instructions = array();

        if (!$can_use) {
            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model( 'logic/link' );
            $msg = _digi( 'Web push notifications are NOT included in your subscription.' );
            $instructions[] = $model->upgradeHint( $msg, $label='', $tag='p' );
        }

        return $instructions;
    }

    protected function renderPageFootnotes()
    {
        return _digi( 'Web push notifications work with Firefox and Chrome.' );

        // return '<strong>' . _digi( 'Web push notifications work with Firefox and Chrome.' ) . '</strong><br />'
        //   . _digi( 'Internet Explorer, Edge and Opera do not support them.' ) . '<br />'
        //   . _digi( 'Safari would require you to setup a apple developer account and pay an annual fee to apple - so we did not include Safari support into DigiMember.' );
    }

    protected function modelPath()
    {
        return 'data/webpush_message';
    }

    protected function columnDefinitions()
    {
        $this->api->load->model( 'data/webpush_message' );

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $edit_url = $model->adminPage( 'webpush', array( 'id' => '_ID_' ) );

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
                'column' => 'title',
                'type' => 'text',
                'label' => _digi('Title'),
                'search' => 'generic',
            ),

//            array(
//                'column' => 'message',
//                'type' => 'html',
//                'label' => _digi('Message'),
//            ),

            array(
                'column' => 'icon_image_id',
                'type' => 'image',
                'label' => _digi('Icon'),
            ),


//            array(
//                'column' => 'is_active',
//                'type' => 'yes_no_bit',
//                'label' => _ncore('Active'),
//            ),


            array(
                'column' => 'count_sent',
                'type' => 'int',
                'label' => _digi('Messages sent'),
                'sortable' => true,
            ),

            array(
                'column' => 'quota_shown',
                'type' => 'rate',
                'label' => _digi('Show quota'),
                'sortable' => true,
                'decimals' => 0,
            ),

            array(
                'column' => 'quota_clicked',
                'type' => 'rate',
                'label' => _digi('Click quota'),
                'sortable' => true,
                'decimals' => 0,
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

