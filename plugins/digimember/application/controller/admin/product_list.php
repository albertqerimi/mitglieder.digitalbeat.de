<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminProductListController extends ncore_AdminTableController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );

        $this->api->load->model( 'logic/features' );

        $this->max_product_count = $this->api->features_logic->maxProductCount();
        $this->cur_product_count = $this->api->features_logic->curProductCount();

        $this->has_too_many_products = $this->max_product_count !== false && $this->cur_product_count > $this->max_product_count;
    }

    protected function pageHeadline()
    {
        $productModel = $this->api->load->model('data/product');
        $productModel->updateTableIfNeeded();
         return _digi('Products');
    }

    protected function pageInstructions()
    {
        return array(
                _digi( 'Create one or more products for your protected membership site.' ),
            );
    }


    protected function modelPath()
    {
        return 'data/product';
    }

    protected function columnDefinitions()
    {
        $model = $this->api->load->model( 'logic/link' );
        $edit_content_url = $model->adminPage( 'content', array( 'element' => '_ID_' ) );

        $edit_url       = $model->adminPage( 'products', array( 'id' => '_ID_' ) );
        $edit_ds24_url  = $model->adminPage( 'products', array( 'id' => '_ID_', 'tab' => 'ds24' ) );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $publish_url = $this->actionUrl( 'publish', '_ID_' );
        $unpublish_url = $this->actionUrl( 'unpublish', '_ID_' );
        $copy_url = $this->actionUrl( 'copy', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        $metas = array(
            array(
                'column' => 'name',
                'type' => 'text',
                'label' => _digi('Product name'),
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
                                'published',
                            )
                        )
                    ),

                    array(
                        'action' => 'edit_ds24',
                        'label' => $this->api->Digistore24DisplayName($as_link=false),
                        'url' => $edit_ds24_url,
                         'depends_on' => array(
                            'status' => array(
                                'created',
                                'published',
                            )
                        )
                    ),

                    array(
                        'action' => 'edit_contents',
                        'label' => _digi('Content'),
                        'url' => $edit_content_url,
                         'depends_on' => array(
                            'status' => array(
                                'created',
                                'published',
                            ),
                            'type' => 'membership',
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
                                'published',
                            )
                        )
                    ),

                 array(
                        'action' => 'publish',
                        'url' => $publish_url,
                        'label' => _ncore('Publish'),
                        'depends_on' => array(
                            'status' =>  'created',
                        )
                    ),

                    array(
                        'action' => 'unpublish',
                        'url' => $unpublish_url,
                        'label' => _ncore('Unpublish'),
                        'depends_on' => array(
                            'status' =>  'published',
                        )
                    ),

                    array(
                        'action' => 'trash',
                        'url' => $trash_url,
                        'label' => _ncore('Move to trash'),
                        'depends_on' => array(
                            'status' => array(
                                'created',
                                'published',
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
            ));

        $metas[] =
            array(
                'column' => 'id',
                'type' => 'id',
                'label' => _digi('Product id'),
                'search' => 'generic',
                'sortable' => true
            );

        $this->api->load->model( 'data/product' );
        $metas[] =
            array(
                'column' => 'type',
                'type' => 'array',
                'label' => _digi('Product type'),
                'sortable' => true,
                'array' => $this->api->product_data->productTypeOptions(),
            );



        $metas[] = array(
            'column' => 'is_right_of_withdrawal_waiver_required',
            'type' => 'yes_no_bit',
            'label' => str_replace( '|', '<br />', _digi('Waiver for|right of rescission' ) ),
            'sortable' => true
        );

        $metas[] = array(
            'column' => 'access_granted_for_days',
            'type' => 'int',
            'label' => str_replace( '|', '<br />', _digi('Days of|access' ) ),
            'display_zero_as' => '',
            'sortable' => true
        );

//        $metas[] =
//            array(
//                'column' => 'flags',
//                'type' => 'flags',
//                'label' => _digi('Options'),
//                'sortable' => false,
//                'flag_labels' => $this->model()->flagsShort(),
//                'flag_tooltips' => $this->model()->flags(),
//                'search' => 'generic',
//                'compare' => 'like',
//                'seperator' => '<br />',
//            );

        $metas[] =
            array(
                'column' => 'modified',
                'type' => 'status_date',
                'label' => _ncore('Date'),
                'sortable' => true,
                'status_labels' => $this->model()->statusLabels(),
            );

        $extra_metas = $this->model()->propertyMetas();

        foreach ($extra_metas as $one)
        {
            $is_hidden = ncore_retrieve( $one, 'is_hidden_from_table', false );
            if (!$is_hidden) {
                $metas[] = $one;
            }
        }

        return $metas;
    }

    protected function bulkActionDefinitions()
    {
        $model = $this->api->load->model( 'logic/link' );

        $trash_url = $this->actionUrl( 'trash', '_ID_' );
        $restore_url = $this->actionUrl( 'restore', '_ID_' );

        $publish_url = $this->actionUrl( 'publish', '_ID_' );
        $unpublish_url = $this->actionUrl( 'unpublish', '_ID_' );
        $copy_url = $this->actionUrl( 'copy', '_ID_' );

        $delete_url = $this->actionUrl( 'delete', '_ID_' );

        return array(
                    array(
                        'action' => 'copy',
                        'url' => $copy_url,
                        'label' => _ncore('Copy'),
                        'views' => array( 'all', 'published', 'membership', 'download' ),
                    ),

                    array(
                        'action' => 'publish',
                        'url' => $publish_url,
                        'label' => _ncore('Publish'),
                        'views' => array( 'all', 'drafts', 'membership', 'download' ),
                    ),

                    array(
                        'action' => 'unpublish',
                        'url' => $unpublish_url,
                        'label' => _ncore('Unpublish'),
                        'views' => array( 'all', 'published', 'membership', 'download' ),
                    ),

                    array(
                        'action' => 'trash',
                        'url' => $trash_url,
                        'label' => _ncore('Move to trash'),
                        'views' => array( 'all', 'published', 'drafts', 'membership', 'download' ),
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
                'label' => _ncore('All'),
                'where' => array(
                    'deleted' => null,
                ),
            ),
            array(
                'view' => 'membership',
                'where' => array(
                    'deleted' => null,
                    'type' => 'membership',
                ),
                'label' => _digi('Membership')
            ),
            array(
                'view' => 'download',
                'where' => array(
                    'deleted' => null,
                    'type' => 'download',
                ),
                'label' => _digi('Download')
            ),
            array(
                'view' => 'published',
                'where' => array(
                    'published !=' => null,
                    'deleted' => null,
                ),
                'label' => _ncore('Published')
            ),
            array(
                'view' => 'drafts',
                'where' => array(
                    'published' => null,
                    'deleted'   => null,
                ),
                'label' => _ncore('Drafts'),
                'no_items_msg' => _digi('No products found.'),
            ),
            array(
                'view' => 'trash',
                'where' => array(
                    'deleted !=' => null
                ),
                'label' => _ncore('Trash'),
                'no_items_msg' => _digi('No products found.'),
            )
        );
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings[ 'row_css_column' ] = 'status';
        $settings[ 'default_sorting'] = array( 'name', 'asc' );


        $model = $this->api->load->model( 'logic/link' );
        $url = $model->adminPage( 'content' );
        $msg = _digi('With %s you may protect different kinds of products, e.g. ebooks, posts, audio books or video lessons. Please create a product first. To do so, click on the button <em>Create</em> above. Later you can <a>add content</a> to the product.', $this->api->pluginDisplayName() );
        $settings[ 'no_items_msg'] = ncore_linkReplace( $msg, $url );

        return $settings;
    }

    protected function pageHeadlineActions()
    {
        $model = $this->api->load->model( 'logic/link' );

        $new_membership_product_url = $model->adminPage( 'products', array( 'id' => 'new', 'type' => 'membership' ) );
        $new_download_product_url   = $model->adminPage( 'products', array( 'id' => 'new', 'type' => 'download'   ) );

        return array(
                $this->pageHeadlineActionRec( 'create', $new_membership_product_url, _digi( 'Add membership product' ) ),
                $this->pageHeadlineActionRec( 'create', $new_download_product_url, _digi( 'Add download product' ) ),
        );
    }

    protected function handleUnpublish( $elements )
    {
        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->unpublish( $id );
        }

        $this->actionSuccess( 'unpublish', $elements );
    }

    protected function handlePublish( $elements )
    {
        if ($this->has_too_many_products) {

            $model = $this->api->load->model( 'data/product' );
            $where = array( 'published !=' => NULL );
            $count = count( $model->getAll( $where ) );

            $can_publish_count = max( 0, $this->max_product_count - $count );

            $not_published_count = max( 0, count($elements) - $can_publish_count );

            if ($not_published_count)
            {
                $this->actionFailure( 'publish', $not_published_count );
            }
            if (!$can_publish_count) {
                return;
            }
        }

        $model = $this->model();

        foreach ($elements as $id)
        {
            $model->publish( $id );
        }

        $this->actionSuccess( 'publish', $elements );
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

        $mailTextModel = $this->api->load->model('data/mail_text');
        $created_elements = array();
        foreach ($elements as $id)
        {
            $copyId = $model->copy( $id );
            $mailTextModel->copyForProduct($id, $copyId);
            $created_elements[] = $copyId;
        }

        $this->actionSuccess( 'copy', $created_elements );
    }

    protected function undoAction( $action )
    {
        switch ($action)
        {
            case 'delete': return false;
            case 'trash': return 'restore';
            case 'restore': return 'trash';
            case 'publish': return 'unpublish';
            case 'unpublish': return 'publish';
            case 'copy': return 'delete';
        }

        return parent::undoAction( $action );
    }

    protected function actionSuccessMessage( $action, $count )
    {
        switch ($action)
        {
            case 'delete':
                if ($count==1)
                    return _digi( 'Deleted one product irrevocably.' );
                else
                    return _digi( 'Deleted %s products irrevocably.', $count );

            case 'trash':
                if ($count==1)
                    return _digi( 'Moved one product to trash.' );
                else
                    return _digi( 'Moved %s products to trash.', $count );

            case 'restore':
                if ($count==1)
                    return _digi( 'Restored one product from trash.' );
                else
                    return _digi( 'Restored %s products from trash.', $count );

            case 'publish':
                if ($count==1)
                    return _digi( 'Published one product.' );
                else
                    return _digi( 'Published %s products.', $count );

            case 'unpublish':
                if ($count==1)
                    return _digi( 'Unpublished one product.' );
                else
                    return _digi( 'Unpublished %s products.', $count );
        }

        return parent::actionSuccessMessage( $action, $count );
    }

    private $max_product_count = false;
    private $cur_product_count = 0;
    private $has_too_many_products = false;


    protected function actionFailureMessage( $action, $count )
    {
        if ($this->has_too_many_products)
        {
            $model = $this->api->load->model( 'logic/link' );

            $dm_free = $this->api->pluginNameFree();
            $dm_pro  = $this->api->pluginNamePro();

            $txt = $this->max_product_count == 1
                 ? _digi( 'In %s one product is included. To publish more than one product, please upgrade.',  $dm_free )
                 : _digi( 'In %s %s products are included. To publish more than %s products, please upgrade..', $dm_free, $this->max_product_count );

            $txt .= ' '
                  . _digi( 'Upgrade to %s for unlimited products.', $dm_pro );

            $msg = $model->upgradeHint( $txt, $label='', $tag='span' );

            return $msg;
        }

        return parent::actionFailureMessage( $action, $count );
    }
}

