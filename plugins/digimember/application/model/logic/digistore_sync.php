<?php

class digimember_DigistoreSyncLogic extends ncore_BaseLogic
{
    private $ds24_product_key_map_push = array(
            'name'                      => 'name',
            'ds24_description'          => 'description',
            'ds24_first_amount'         => 'amount',
            'ds24_salespage'            => 'salespage_url',
            'ds24_thankyoupage'         => 'thankyou_url',
            'ds24_approval_status'      => 'approval_status',
            'ds24_affiliate_commission' => 'affiliate_commission',
    );

    private $ds24_product_key_dont_push_if_void = array
    (
        'ds24_approval_status',
        'ds24_first_amount',
        'ds24_currency',
    );

    private $ds24_product_key_map_pull = array(
            'name'                      => 'name',
            'ds24_description'          => 'description',
            'ds24_salespage'            => 'salespage_url',
            'ds24_thankyoupage'         => 'thankyou_url',
            'ds24_affiliate_commission' => 'affiliate_commission',
            'ds24_approval_status'      => 'approval_status',
            'ds24_approval_status_msg'  => 'approval_status_msg',
    );

    public function pullProduct( $product_obj_or_id )
    {
        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );
        $product = $productData->resolveToObj( $product_obj_or_id );
        if (!$product || ncore_isFalse( $product->is_ds24_sync_enabled )) {
            return;
        }

        try
        {
            /** @var digimember_DigistoreConnectorLogic $ds24lib */
            $ds24lib = $this->api->load->model( 'logic/digistore_connector' );
            $ds24    = $ds24lib->ds24();

            $info = $ds24lib->connectionInfo();

            $ds24_user_id   = $info['userid'];

            $can_synchronize = $ds24_user_id > 0;
            if (!$can_synchronize) {
                return;
            }

            $ds24_product_id = $product->ds24_sync_product_id;

            $ds24_product = $ds24_user_id && $ds24_product_id
                          ? $ds24->getProduct( $ds24_product_id )
                          : false;

            if (!$ds24_product)
            {
                return;
            }

            $is_synchronized = $ds24_user_id
                            && $ds24_product->user_id == $ds24_user_id;
            if (!$is_synchronized)
            {
                return;
            }

            $data = array();

            $result = $ds24->listPaymentplans( $ds24_product_id );
            $plans = $result->paymentplans;
            $plan  = ncore_retrieve( $plans, 0, false );
            $can_sync_pplan = count($plans) == 1
                          && (  ($plan->number_of_installments  == 0
                                 && $plan->other_amounts >= 0.01
                                 && $plan->first_billing_interval  == '1_month'
                                 && $plan->other_billing_intervals == '1_month')

                             || ($plan->number_of_installments  == 1 && $plan->other_amounts == 0) );

            $can_create_pplan = count($plans) == 0;
            if ($can_create_pplan)
            {
                $currency = 'EUR';

                $plan = array();
                $plan[ 'first_amount' ]            = 27;
                $plan[ 'first_billing_interval' ]  = '1_month';
                $plan[ 'other_billing_intervals' ] = '1_month';
                $plan[ 'number_of_installments' ]  = 1;
                $plan[ 'currency' ]                = $currency;

                $result = $ds24->createPaymentplan( $ds24_product_id, $plan );

                $data[ 'ds24_sync_payplan_id' ] = $result->paymentplan_id;
                $data[ 'ds24_first_amount' ]    = $ds24_product->amount;
                $data[ 'ds24_other_amounts' ]   = 0;
                $data[ 'ds24_currency' ]        = $currency;
            }
            else
            if ($can_sync_pplan)
            {
                $data[ 'ds24_sync_payplan_id' ] = $plan->id;
                $data[ 'ds24_first_amount' ]    = $plan->first_amount;
                $data[ 'ds24_other_amounts' ]   = $plan->other_amounts;
                $data[ 'ds24_currency' ]        = $plan->currency;
            }
            else
            {
                $data[ 'ds24_sync_payplan_id' ] = null;
            }

            foreach ($this->ds24_product_key_map_pull as $dmkey => $ds24key)
            {
                $data[ $dmkey ] = $ds24_product->$ds24key;
            }

            $productData->update( $product, $data );

        }
        catch (Exception $e)
        {
            $must_disable_sync = $e->getCode() == DS_ERR_NOT_FOUND;
            if ($must_disable_sync)
            {
                $digistore24 = $this->api->Digistore24DisplayName( $as_link=false );
                $msg = _digi( 'The product has been deleted in %s.', $digistore24 )
                     . ' ' . _digi( 'This %s product cannot be synchronized with %s any more.', $this->api->pluginDisplayName(), $digistore24 );

                $data = array( 'is_ds24_sync_enabled' => 'N' );
                $productData->update( $product, $data );

                ncore_flashMessage( NCORE_NOTIFY_ERROR, $msg );
                ncore_redirect( ncore_currentUrl() );
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            throw $ds24lib->sanitizeException( $e, 'product', 'sync' );
        }

    }

    public function pushProduct( $product_obj_or_id )
    {
        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );
        $product = $productData->resolveToObj( $product_obj_or_id );
        if (!$product || ncore_isFalse( $product->is_ds24_sync_enabled )) {
            return;
        }

        try
        {
            /** @var digimember_DigistoreConnectorLogic $ds24lib */
            $ds24lib = $this->api->load->model( 'logic/digistore_connector' );
            $ds24    = $ds24lib->ds24();

            $info = $ds24lib->connectionInfo();

            $ds24_user_id   = $info['userid'];
            $ds24_user_name = $info['username'];

            $can_synchronize = $ds24_user_id > 0;
            if (!$can_synchronize) {
                return;
            }

            $ds24_product_id = $product->ds24_sync_product_id;

            $ds24_product = $ds24_user_id && $ds24_product_id
                          ? $ds24->getProduct( $ds24_product_id )
                          : false;

            $is_synchronized = $ds24_user_id
                            && $ds24_product
                            && $ds24_product->user_id == $ds24_user_id;

            $data = array();
            foreach ($this->ds24_product_key_map_push as $dmkey => $ds24key)
            {
                $is_void = empty( $product->$dmkey );
                if ($is_void && in_array( $dmkey, $this->ds24_product_key_dont_push_if_void ))
                {
                    continue;
                }
                $data[ $ds24key ] = $product->$dmkey;
            }

            $data[ 'name' ] = str_replace( '&amp;', '&',     $data[ 'name' ] );
            $data[ 'name' ] = str_replace( '&',     '&amp;', $data[ 'name' ] );

            if (empty($data['approval_status']))
            {
                unset( $data['approval_status'] );
            }

            if ($is_synchronized)
            {
                $ds24->updateProduct( $ds24_product_id, $data );
                $ds24_product_ids = false;
            }
            else
            {
                $result = $ds24->createProduct( $data );
                $ds24_product_id = $result->product_id;

                $ds24_product_ids = $product->ds24_product_ids
                                    ? trim( $product->ds24_product_ids, ',' ) . ',' . $ds24_product_id
                                    : $ds24_product_id;
            }

            $data = array();
            $data[ 'ds24_sync_product_id' ] = $ds24_product_id;
            $data[ 'ds24_userid' ]          = $ds24_user_id;
            $data[ 'ds24_username' ]        = $ds24_user_name;
            $data[ 'ds24_last_sync_at' ]    = ncore_dbDate();
            if ($ds24_product_ids)
            {
                $data[ 'ds24_product_ids' ] = $ds24_product_ids;
            }

            $productData->update( $product, $data );

            $must_sync_image = $product->ds24_image_url != $product->ds24_sync_image_url;

            if ($must_sync_image)
            {
                $have_image_url = strlen( $product->ds24_image_url ) >= 10;
                if ($have_image_url)
                {
                    $response = $ds24->createImage( $product->ds24_image_url, $usage_type='product', $product->name, $alt_tag='' );
                    $image_id  = $response->image_id;
                    $image_url = $response->image_url;
                }
                else
                {
                    $image_id  = '';
                    $image_url = '';
                }

                $data = array();
                $data[ 'ds24_sync_image_id' ]  = $image_id;
                $data[ 'ds24_sync_image_url' ] = $image_url;
                $data[ 'ds24_image_url' ]      = $image_url;
                $productData->update( $product, $data );

                $data = array( 'image_id' => $image_id );
                $ds24->updateProduct( $ds24_product_id, $data );

            }

            $is_subscription = $product->ds24_other_amounts > 0;

            $data = $is_subscription
                          ? array(
                            'first_amount'            => $product->ds24_first_amount,
                            'other_amounts'           => $product->ds24_other_amounts,
                            'currency'                => $product->ds24_currency,
                            'number_of_installments'  => 0,
                            'first_billing_interval'  => '1_month',
                            'other_billing_intervals' => '1_month',
                           )
                          : array(
                            'first_amount'            => $product->ds24_first_amount,
                            'other_amounts'           => 0.00,
                            'currency'                => $product->ds24_currency,
                            'number_of_installments'  => 1,
                            'first_billing_interval'  => '1_month',
                            'other_billing_intervals' => '1_month',
                           );

            $payplan_id = $product->ds24_sync_payplan_id;
            if ($payplan_id>0)
            {
                $ds24->updatePaymentplan( $payplan_id, $data );
            }
            else
            {
                $result = $ds24->listPaymentplans( $ds24_product_id );
                $plans = $result->paymentplans;
                $have_plans = count( $plans ) >= 1;

                if (!$have_plans)
                {
                    $result = $ds24->createPaymentplan( $ds24_product_id, $data );
                    $payplan_id = $result->paymentplan_id;
                    $data = array( 'ds24_sync_payplan_id' => $payplan_id );
                    $productData->update( $product, $data );
                }
            }
        }
        catch (Exception $e)
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $ds24lib->sanitizeException( $e, 'product', 'sync' );
        }
    }

    public function importProducts()
    {
        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );
        $dm_all = $productData->getAll();

        /** @var digimember_DigistoreConnectorLogic $ds24lib */
        $ds24lib = $this->api->load->model( 'logic/digistore_connector' );
        /** @noinspection PhpUnhandledExceptionInspection */
        $ds24    = $ds24lib->ds24();
        $ds_all = $ds24->listProducts()->products;

        $used_ds24_product_ids = array();
        foreach ($dm_all as $one)
        {
            $product_ids = $one->ds24_product_ids
                         ? explode( ',', $one->ds24_product_ids )
                         : array();

            $used_ds24_product_ids = array_merge( $used_ds24_product_ids, $product_ids );

            $used_ds24_product_ids[] = $one->ds24_sync_product_id;
        }

        $new_product_ids = array();
        foreach ($ds_all as $one)
        {
            $have_product = in_array( $one->id, $used_ds24_product_ids );
            if ($have_product) {
                continue;
            }

            $data = array();

            $data[ 'name' ]                 = $one->name;
            $data[ 'ds24_product_ids' ]     = $one->id;
            $data[ 'ds24_sync_product_id' ] = $one->id;
            $data[ 'ds24_last_sync_at' ]    = '2000-01-01 00:00:00';

            $data[ 'is_ds24_sync_enabled' ] = 'Y';

            $new_product_ids[] = $productData->create( $data );

        }

        foreach ($new_product_ids AS $new_product_id)
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->pullProduct( $new_product_id );
        }

        return count( $new_product_ids );
    }



}