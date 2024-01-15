<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminOrderListController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _digi('Orders');
    }

    protected function modelPath()
    {
        return 'data/user_product';
    }

    protected function columnDefinitions()
    {
        /** @var digimember_ProductData $product_model */
        $product_model = $this->api->load->model( 'data/product' );
        /** @var digimember_LinkLogic $link_model */
        $link_model = $this->api->load->model( 'logic/link' );
        /** @var digimember_UserProductData $user_product_model */
        $user_product_model = $this->api->load->model( 'data/user_product' );

        $product_options = $product_model->options();

        $edit_url = $link_model->adminPage( 'orders', array( 'id' => '_ID_' ) );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $activate_url = $this->actionUrl( 'activate', '_ID_' );
        $deactivate_url = $this->actionUrl( 'deactivate', '_ID_' );

        $welcome_url = $this->actionUrl( 'welcome', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        return array(
            array(
                'column' => 'user_id',
                'type' => 'user',
                'label' => _digi('Buyer'),
                'sortable' => false,
                'search' => 'generic',
                'compare' => 'like',

              'actions' => array(
                   array(
                     'label' => _ncore('Edit'),
                     'action' => 'edit',
                     'url' => $edit_url,
                    ),
                    array(
                        'action' => 'activate',
                        'url' => $activate_url,
                        'label' => _ncore('Activate'),
                        'depends_on' => array(
                            'is_active' => array(
                                'N',
                            )
                        )
                    ),
                   array(
                        'action' => 'deactivate',
                        'url' => $deactivate_url,
                        'label' => _ncore('Deactivate'),
                        'depends_on' => array(
                           'is_active' => array(
                                'Y',
                            ),
                            'status' => array(
                                'created',
                                'active',
                            )
                        )
                    ),

                   array(
                        'action' => 'welcome',
                        'url' => $welcome_url,
                        'label' => _digi('Resend welcome email'),
                        'depends_on' => array(
                           'is_active' => array(
                                'Y',
                            ),
                            'status' => array(
                                'created',
                                'active',
                            )
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
                ),
            ),

            array(
                'column' => 'product_id',
                'type' => 'array',
                'label' => _digi('Product'),
                'sortable' => true,
                'search' => 'generic',
                'compare' => 'like',
                'array' => $product_options,
            ),


            array(
                'column' => 'quantity',
                'type' => 'int',
                'label' => _digi('Quantity'),
                'sortable' => true,
                'hide' => !ncore_hasProductQuantities(),
            ),

            array(
                'column' => 'order_id',
                'type' => 'text',
                'label' => _digi('Order id'),
                'sortable' => true,
                'search' => 'generic',
                'compare' => 'like',
            ),

            array(
                'column' => 'is_active',
                'type' => 'upgrade_info',
                'label' => _ncore('Active'),
                'sortable' => true,
            ),

            array(
                'column' => 'order_date',
                'type' => 'age_date',
                'label' => _ncore('Order date'),
                'sortable' => true,
                'format' => 'days',
            ),

            array(
                'column' => 'last_pay_date',
                'type' => 'age_date',
                'label' => _digi('Last payment was on'),
                'sortable' => true,
                'format' => 'days',
            ),


            array(
                'column' => 'is_right_of_rescission_waived',
                'type' => 'yes_no_bit',
                'label' => _digi('Right of rescission waived'),
                'sortable' => true,
                'text_active'   => _digi( 'Yes, on [DATE]: [REASON]' ),
                'text_inactive' => _ncore( 'No' ),
                'text_placeholders' => array(
                    'DATE'   => array( 'column' => 'right_of_rescission_waived_at', 'function' => 'ncore_formatDateTime' ),
                    'REASON' => array( 'column' => 'right_of_rescission_waived_by', 'function' => array( $user_product_model, 'rightOfRecissionWaiverReasons' ) ),
                ),
            ),





       );
    }

    protected function bulkActionDefinitions()
    {
        $model = $this->api->load->model( 'logic/link' );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $activate_url = $this->actionUrl( 'activate', '_ID_' );
        $deactivate_url = $this->actionUrl( 'deactivate', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        $welcome_url = $this->actionUrl( 'welcome', '_ID_' );

        return array(
                 array(
                        'action' => 'activate',
                        'url' => $activate_url,
                        'label' => _ncore('Activate'),
                        'views' => array( 'all', 'inactive' ),
                    ),

                    array(
                        'action' => 'deactivate',
                        'url' => $deactivate_url,
                        'label' => _ncore('Deactivate'),
                        'views' => array( 'all', 'active' ),
                    ),

                    array(
                        'action' => 'welcome',
                        'url' => $welcome_url,
                        'label' => _digi('Resend welcome email'),
                        'views' => array( 'all', 'active' ),
                    ),

                    array(
                        'action' => 'trash',
                        'url' => $trash_url,
                        'label' => _ncore('Move to trash'),
                        'views' => array( 'all', 'active', 'inactive' ),
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

    protected function viewDefinitions()
    {
        return array(
            array(
                'view' => 'all',
                'where' => array(),
                'label' => _ncore('All')
            ),
            array(
                'view' => 'active',
                'where' => array(
                    'is_active' => 'Y'
                ),
                'label' => _ncore('Active')
            ),
            array(
                'view' => 'inactive',
                'where' => array(
                    'is_active' => 'N'
                ),
                'label' => _ncore('Inactive'),
                'no_items_msg' => _digi('No inactive memberships found.'),
            ),
            array(
                'view' => 'trash',
                'where' => array(
                    'deleted !=' => null
                ),
                'label' => _ncore('Trash'),
                'no_items_msg' => _digi('The trash is empty.'),
            )
        );
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings[ 'default_sorting'] = array( 'created', 'desc' );

        $settings[ 'no_items_msg'] = _digi('You currently have no orders. It\'s time to start selling!');

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        $actions = array();

        $model = $this->api->load->model( 'logic/link' );
        $new_order_url = $model->adminPage( 'orders', array( 'id' => 'new' ) );

        $actions[] = $this->pageHeadlineActionRec( 'create', $new_order_url );

        $mass_create_url = $model->adminPage( 'orders', array( 'masscreate' => '1' ) );

        $actions[] = array(
                'url' => $mass_create_url,
                'label' => _digi('Give to all')
            );

        return $actions;
    }

    protected function handleDeactivate( $elements )
    {
        $model = $this->model();

        $data = array(
            'is_active' => 'N',
        );

        foreach ($elements as $id)
        {
            $model->update( $id, $data );
        }

        $this->actionSuccess( 'deactivate', $elements );
    }

    protected function handleActivate( $elements )
    {
        $model = $this->model();

        $data = array(
            'is_active' => 'Y',
        );

        $success = array();
        $failure = array();
        foreach ($elements as $id)
        {
            $obj = $model->get( $id );
            if (!$obj) {
                continue;
            }

            $can_update = !$obj->is_access_too_late;
            if ($can_update) {
                $model->update( $id, $data );
                $success[] = $id;
            }
            else
            {
                $failure[] = $id;
            }
        }

        if ($success) {
            $this->actionSuccess( 'activate', $success );
        }

        if ($failure) {
            $this->actionFailure( 'activate', $failure, _digi( 'The order cannot be activated, because it has been replaced by an upgrade or package change order.' ) );
        }
    }

    protected function handleWelcome( $elements )
    {
        $lib = $this->api->load->library( 'payment_handler' );

        foreach ($elements as $index => $one)
        {
            try
            {
                $lib->resendWelcomeMail( $one );
            }
            catch (Exception $e)
            {
                $this->actionFailure( 'welcome', $one, $e->getMessage() );
                unset( $elements[$index] );
            }
        }

        if ($elements)
        {
            $this->actionSuccess( 'welcome', $elements );
        }
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
            $userProduct = $model->getDeletedById($id);
            $productModel = $this->api()->load->model('data/product');
            $user = ncore_getUserById($userProduct->user_id);
            $product = $productModel->get($userProduct->product_id);
            $user_email = $user->user_email;
            $model->delete( $id );
            $this->api->log('orders', _digi('Deleted Order %s with Product %s for user %s.', $userProduct->order_id, $product->name, $user_email));
        }

        $this->actionSuccess( 'delete', $elements );
    }

    protected function undoAction( $action )
    {
        switch ($action)
        {
            case 'delete': return false;
            case 'trash': return 'restore';
            case 'restore': return 'trash';
            case 'activate': return 'deactivate';
            case 'deactivate': return 'activate';
        }

        return parent::undoAction( $action );
    }

    protected function actionSuccessMessage( $action, $count )
    {
        switch ($action)
        {
            case 'delete':
                if ($count==1)
                    return _digi( 'Deleted one membership irrevocably.' );
                else
                    return _digi( 'Deleted %s memberships irrevocably.', $count );

            case 'trash':
                if ($count==1)
                    return _digi( 'Moved one membership to trash.' );
                else
                    return _digi( 'Moved %s memberships to trash.', $count );

            case 'restore':
                if ($count==1)
                    return _digi( 'Restored one membership from trash.' );
                else
                    return _digi( 'Restored %s memberships from trash.', $count );

            case 'activate':
                if ($count==1)
                    return _digi( 'Activated one membership.' );
                else
                    return _digi( 'Activated %s memberships.', $count );

            case 'deactivate':
                if ($count==1)
                    return _digi( 'Deactivated one membership.' );
                else
                    return _digi( 'Deactivated %s memberships.', $count );

            case 'welcome':
                if ($count==1)
                    return _digi( 'Welcome email sent.' );
                else
                    return _digi( '%s welcome emails sent.', $count );
        }

        return parent::actionSuccessMessage( $action, $count );
    }

}

