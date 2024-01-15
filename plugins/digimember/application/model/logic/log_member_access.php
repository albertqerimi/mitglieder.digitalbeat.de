<?php

class digimember_LogMemberAccessLogic extends ncore_BaseLogic
{
    public function logMemberAccess( $user_id )
    {
        if ($user_id<=0)
        {
            return false;
        }
        if (ncore_canAdmin()) {
            return false;
        }
        $model = $this->api->load->model( 'logic/blog_config' );
        if (!$model->get('ds24_refund_update', false)) {
            $model->set( 'ds24_refund_update', ncore_serverTime() );
        }
        $ds24RefundUpdateTime = $model->get( 'ds24_refund_update');
        $user = ncore_getUserById( $user_id );
        $userProductModel = $this->api->load->model( 'data/user_product' );
        $userProductModel->updateTableIfNeeded();
        $ds24Model = $this->api->load->model( 'logic/digistore_connector' );
        $orders = $userProductModel->getForUser($user_id);
        $contentAccessData = $this->getContentAccessData($user_id);

        if (is_array($orders) && count($orders) > 0) {
            try {
                $ds24Instance = $ds24Model->ds24();
            }
            catch(Exception $e) {
                return false;
            }
            foreach ($orders as $order) {
                $orderTime = ncore_unixDate($order->order_date);
                if (isset($order->ds24_purchase_key) && $order->ds24_purchase_key != '' && $order->ds24_refund_days == "0" && $orderTime >= $ds24RefundUpdateTime) {
                    $relatedProductId = $order->product_id;
                    $totalContentCount = 0;
                    if (array_key_exists($relatedProductId, $contentAccessData['content'])) {
                        $totalContentCount = count($contentAccessData['content'][$relatedProductId]);
                        $contentAccessibleCount = 0;
                        if (array_key_exists($relatedProductId, $contentAccessData['access'])) {
                            $contentAccessibleCount = count($contentAccessData['access'][$relatedProductId]);
                        }
                        $purchase_id = $order->order_id;
                        $platform_name = get_bloginfo('name');
                        $login_name = $user->user_login;
                        $login_url = get_bloginfo('url');
                        $number_of_unlocked_lectures = $contentAccessibleCount;
                        $total_number_of_lectures = $totalContentCount;
                        if ($number_of_unlocked_lectures < $total_number_of_lectures) {
                            $ds24Instance->logMemberAccess($purchase_id, $platform_name, $login_name, $login_url, $number_of_unlocked_lectures, $total_number_of_lectures);
                        }
                        else {
                            if ($order->ds24_full_access_logged !== "Y"){
                                $ds24Instance->logMemberAccess($purchase_id, $platform_name, $login_name, $login_url, $number_of_unlocked_lectures, $total_number_of_lectures);
                                $userProductModel->update($order->id, array("ds24_full_access_logged" => "Y"));
                            }
                        }

                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * getContentAccessData
     * creates a data array with information about the content user has access to and about user has access to when all content is open.
     * @return array[]
     */
    function getContentAccessData ($user_id = "current") {
        $productContentList = [];
        $productAccessList = [];

        //get all posts that the user may have access to
        $postComplete = $this->listUserContent('post', false, $user_id);
        foreach ($postComplete as $product) {
            if (array_key_exists("posts", $product) && count($product['posts']) > 0) {
                $productContentList[$product['product_id']] = array_key_exists($product['product_id'], $productContentList) ? array_merge($productContentList[$product['product_id']], $product['posts']) : $product['posts'];
            }
        }
        //get all pages that the user may have access to
        $pageComplete = $this->listUserContent('page', false, $user_id);
        foreach ($pageComplete as $product) {
            if (array_key_exists("posts", $product) && count($product['posts']) > 0) {
                $productContentList[$product['product_id']] = array_key_exists($product['product_id'], $productContentList) ? array_merge($productContentList[$product['product_id']], $product['posts']) : $product['posts'];
            }
        }
        //get all posts that the user has access to at this moment
        $postAccessible = $this->listUserContent('post', true, $user_id);
        foreach ($postAccessible as $product) {
            if (array_key_exists("posts", $product) && count($product['posts']) > 0) {
                $productAccessList[$product['product_id']] = array_key_exists($product['product_id'], $productAccessList) ? array_merge($productAccessList[$product['product_id']], $product['posts']) : $product['posts'];
            }
        }
        //get all pages that the user has access to at this moment
        $pageAccessible = $this->listUserContent('page', true, $user_id);
        foreach ($pageAccessible as $product) {
            if (array_key_exists("posts", $product) && count($product['posts']) > 0) {
                $productAccessList[$product['product_id']] = array_key_exists($product['product_id'], $productAccessList) ? array_merge($productAccessList[$product['product_id']], $product['posts']) : $product['posts'];
            }
        }

        return array(
            'content' =>  $productContentList,
            'access' => $productAccessList,
        );
    }

    function listUserContent( $content_type, $hasAccessTo = false, $wordpress_user_id='current')
    {
        $key_map = array(
            'post_id'    => 'content_id',
            'post_type'  => 'content_type',
            'position'   => 'position',
            'unlock_day' => 'unlock_day',
            'title'      => 'title',
        );

        $api = dm_api();

        $api->load->helper( 'array' );

        $api->load->model( 'data/product' );
        $api->load->model( 'data/user_product' );
        $api->load->model( 'data/page_product' );

        $user_id = ncore_userId( $wordpress_user_id );

        $products = digimember_listAccessableProducts( $user_id );

        $product_post_list = array();

        foreach ($products as $product)
        {
            $posts = $api->page_product_data->getPostsForProduct( $product['id'], $content_type, false);

            if ($hasAccessTo) {
                $posts = $this->filterPostsWithoutAccess($posts);
            }
            $product[ 'posts' ] =

            $product_post_list[] = array(
                'product_id'   => $product['id'],
                'product_name' => $product['name'],
                'posts'        => ncore_purgeArray( $posts, $key_map ),
            );
        }

        return $product_post_list;
    }

    private function filterPostsWithoutAccess ($posts) {
        $api = dm_api();
        $accessModel = $api->load->model('logic/access');
        $filteredPostList = [];
        $postObjectList = [];
        foreach ($posts as $post) {
            $wpPostObject = get_post($post['post_id']);
            if ($wpPostObject) {
                $postObjectList[] = $wpPostObject;
            }
        }
        $postObjectList = $accessModel->cbPageListFilter( $postObjectList );
        foreach ($posts as $post) {
            if ($this->findObjectById($postObjectList, $post['post_id'])) {
                $filteredPostList[] = $post;
            }
        }
        return $filteredPostList;
    }

    private function findObjectById($postObjectList, $postId){
        foreach ( $postObjectList as $postObject ) {
            if ( $postId == $postObject->ID ) {
                return true;
            }
        }
        return false;
    }

}

