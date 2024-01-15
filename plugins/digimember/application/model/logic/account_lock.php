<?php

class digimember_AccountLockLogic extends ncore_BaseLogic
{
    public function getAccountLock( $user_login )
    {
        $result =& $this->locks[ $user_login];
        if (isset($result)) {
            return $result;
        }

        global $wpdb;
        $wpdb->hide_errors();
        $lockable_products = $this->getLockableProducts();
        $wpdb->show_errors();

        if (!$lockable_products) {
            $result = array( DIGIMEMBER_AL_NONE, false );
            return $result;
        }

        $this->api->load->helper( 'array' );
        $lockable_product_ids = ncore_retrieveValues( $lockable_products, 'id' );

        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );
        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );

        $user = get_user_by('login', $user_login );
        if (!$user) {
            $user = get_user_by('email', $user_login );
        }
        if (!$user)
        {
            return array( DIGIMEMBER_AL_NONE, false );
        }

        $user_id = ncore_retrieve( $user, 'ID' );

        if (ncore_canAdmin($user->ID)) {
            $result = array( DIGIMEMBER_AL_NONE, false );
            return $result;
        }

        $where = array(
            'user_id' => $user->ID,
        );

        $all = $model->getAll( $where, $limit=false, $order_by='modified ASC, id ASC' );

        $are_products_active = array();

        foreach ($all as $one)
        {
            $product_id = $one->product_id;

            $is_lockable = in_array( $product_id, $lockable_product_ids );
            if (!$is_lockable) {
                continue;
            }

            $is_active = $one->is_active == 'Y';

            if ($is_active) {
                $are_products_active[ $product_id ] = true;
            }
            elseif (!isset($are_products_active[ $product_id ])) {
                $are_products_active[ $product_id ] = false;
            }
        }


        $result = array( DIGIMEMBER_AL_NONE, false );

        foreach ($are_products_active as $product_id => $is_active)
        {
            if ($is_active) {
                continue;
            }

            $product = ncore_findByKey( $lockable_products, 'id', $product_id );

            switch ($product->lock_type)
            {
                case DIGIMEMBER_AL_PAGE:
                    $result = array( DIGIMEMBER_AL_PAGE, $product->lock_page );
                    return $result;

                case DIGIMEMBER_AL_URL:
                    $result =  array( DIGIMEMBER_AL_URL, $product->lock_url );
                    return $result;

                case DIGIMEMBER_AL_TEXT:
                    $msg = htmlspecialchars( $product->lock_text );
                    if (!$msg) {
                        $msg = $productData->accountLockDefaultMessage();
                    }
                    $result =  array( DIGIMEMBER_AL_TEXT, $msg );
                    return $result;

                case DIGIMEMBER_AL_NONE:
                default:
                    break;
            }
        }


        return $result;
    }


    private $products;
    private $locks;

    private function getLockableProducts()
    {
        if (!isset( $this->products ))
        {
            $model = $this->api->load->model( 'data/product' );

            $where = array(
                'lock_type !=' => DIGIMEMBER_AL_NONE,
                'published !=' => null,
            );

            $this->products = $model->getAll( $where );
        }

        return $this->products;
    }
}

