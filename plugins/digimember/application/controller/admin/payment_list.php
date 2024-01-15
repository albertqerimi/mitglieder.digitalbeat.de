<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminPaymentListController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _digi('Payment Provider');
    }

    protected function pageInstructions()
    {
        /** @var digimember_DigistoreConnectorLogic $ds24 */
        $ds24 = $this->api->load->model( 'logic/digistore_connector' );

        list( $type, $message ) = $ds24->renderStatusNotice( 'mixed', 'default' );

        $as_link = $type == NCORE_NOTIFY_SUCCESS;

        $plugin_name = $this->api->pluginDisplayName();
        $digistore24 = $this->api->Digistore24DisplayName( $as_link );

        if ($type == NCORE_NOTIFY_SUCCESS)
        {
            $message = ncore_renderMessage( $type, $message, 'span' );
        }
        else
        {
            $message = ncore_htmlAlert('info', _digi( '%s recommends to connect with %s.', $plugin_name,  $digistore24), 'info', '', $message);
        }

        //Look for old stripe paymentprovider
        $paymentModel = $this->api->load->model('data/payment');
        $stripeSearchResult = $paymentModel->search('engine','stripe','equal');
        if (count($stripeSearchResult) > 0) {
            foreach ($stripeSearchResult as $stripeEntry){
                if ($stripeEntry->is_active === 'Y') {
                    $message .= '<br>'.ncore_renderMessage( 'info', _digi('You currently have one outdated Stripe-DigiMember connection called “stripe” active, which is not supported by Stripe anymore. To connect DigiMember to Stripe please add a new Payment Provider using the “Stripe Pricing API” instead. We advise you to deactivate or delete the old “stripe” connection additionally.'), 'span' );
                }
            }
        }

        return array(
            $message
        );
    }

    protected function renderInstructions()
    {
        echo '<br />' . join('', $this->pageInstructions()) . '<br />';
    }

    protected function isTableHidden()
    {
        $have_entries = (bool) $this->model()->getAll( array( 'is_visible' => 'Y' ) );
        if ($have_entries) {
            return false;
        }

        $have_deleted = (bool) $this->model()->getAll( array( 'deleted !=' => null, 'is_visible' => 'Y' ) );
        return !$have_deleted;
    }

    protected function modelPath()
    {
        return 'data/payment';
    }

    protected function columnDefinitions()
    {
        /** @var digimember_PaymentHandlerLib $lib */
        $lib = $this->api->load->library( 'payment_handler' );
        $engine_options = $lib->getProviders();

        $model = $this->api->load->model( 'logic/link' );
        $edit_url = $model->adminPage( 'payment', array( 'id' => '_ID_' ) );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $activate_url = $this->actionUrl( 'activate', '_ID_' );
        $deactivate_url = $this->actionUrl( 'deactivate', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        return array(

             array(
                'column' => 'engine',
                'type' => 'array',
                'array' => $engine_options,
                'label' => _digi('Payment Provider Type'),
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
                            )
                        )
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
                           'is_active' => array(
                                'status' =>  'deleted',
                            )
                        )
                    ),
                )
            ),
            array(
                'column' => 'id',
                'type' => 'id',
                'label' => _ncore('Id'),
                'sortable' => true,
            ),

            array(
                'column' => 'product_code_map',
                'type' => 'mapped_ids',
                'label' => _digi('Products'),
                'sortable' => false,
                'model' => 'data/product',
                'name_column' => 'name',
                'search' => 'generic',
                'void_value' => '<em>(' . _digi('none') . ')</em>',
                'override' => array(
                    'engine' => array(
                        'paypal'    => '<em>(' . _digi('as set in PayPal') . ')</em>',
                        '2checkout' => '<em>(' . _digi('as set in 2CheckOut') . ')</em>',
                     )
                ),
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

    protected function bulkActionDefinitions()
    {
        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $activate_url = $this->actionUrl( 'activate', '_ID_' );
        $deactivate_url = $this->actionUrl( 'deactivate', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

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
                'where' => array(
                    'is_visible'  => 'Y',
                ),
                'label' => _ncore('All'),
            ),
            array(
                'view' => 'active',
                'where' => array(
                    'is_active'  => 'Y',
                    'is_visible' => 'Y',
                ),
                'label' => _ncore('Active')
            ),
            array(
                'view' => 'inactive',
                'where' => array(
                    'is_active'  => 'N',
                    'is_visible' => 'Y',
                ),
                'label' => _ncore('Inactive'),
                'no_items_msg' => _digi('No inactive payment providers found.'),
            ),
            array(
                'view' => 'trash',
                'where' => array(
                    'deleted !=' => null,
                    'is_visible' => 'Y',
                ),
                'label' => _ncore('Trash'),
                'no_items_msg' => _digi('No payment providers found in trash.'),
            )
        );
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings[ 'row_css_column' ] = 'status';
        $settings[ 'default_sorting'] = array( 'engine', 'asc' );
        $settings[ 'no_items_msg'] = _digi('Please add a new payment provider first.');

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $new_url = $model->adminPage( 'payment', array( 'id' => 'new' ) );

        return array(
                $this->pageHeadlineActionRec( 'create', $new_url, $label=false),
        );
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

        foreach ($elements as $id)
        {
            $model->update( $id, $data );
        }

        $this->actionSuccess( 'activate', $elements );
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
                    return _digi( 'Deleted one payment provider irrevocably.' );
                else
                    return _digi( 'Deleted %s payment providers irrevocably.', $count );

            case 'trash':
                if ($count==1)
                    return _digi( 'Moved one payment provider to trash.' );
                else
                    return _digi( 'Moved %s payment providers to trash.', $count );

            case 'restore':
                if ($count==1)
                    return _digi( 'Restored one payment provider from trash.' );
                else
                    return _digi( 'Restored %s payment providers from trash.', $count );

            case 'activate':
                if ($count==1)
                    return _digi( 'Activated one payment provider.' );
                else
                    return _digi( 'Activated %s payment providers.', $count );

            case 'deactivate':
                if ($count==1)
                    return _digi( 'Deactivated one payment provider.' );
                else
                    return _digi( 'Deactivated %s payment providers.', $count );
        }

        return parent::actionSuccessMessage( $action, $count );
    }
}

