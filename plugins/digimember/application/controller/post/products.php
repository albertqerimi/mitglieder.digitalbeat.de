<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass( 'post/meta' );

class digimember_PostProductsController extends ncore_PostMetaController
{
    protected function viewData()
    {
        $api = $this->api;

        $data = parent::viewData();

        /** @var digimember_ProductData $model */
        $model = $api->load->model( 'data/product' );
        $products = $model->getAll( [ 'type' => 'membership' ] );
        $data['products'] = $products;

        $post_id = $this->getPostId();
        $post_type = $this->getPostType();

        /** @var digimember_PageProductData $model */
        $model = $api->load->model( 'data/page_product' );
        $data['post_products'] = $model->getForPage( $post_type, $post_id, true );
        $data['post_type'] = $post_type;
        $data['post_id'] = $post_id;

        /** @var digimember_ProductData $model */
        $model = $api->load->model( 'data/product' );
        $products = $model->getAll( array( 'type' => 'download', 'login_url' => $post_id ));
        $data['download_products'] = $products;

        return $data;
    }

    protected function handleRequest()
    {
        $api = $this->api;
        /** @var digimember_PageProductData $model */
        $model = $api->load->model( 'data/page_product' );

        $data_recs =& $_POST['digi_page_product'];

        $havePost = isset( $data_recs ) && is_array( $data_recs );
        if (!$havePost)
        {
            return;
        }

        foreach ($data_recs as $rec)
        {
            $have_rec = isset($rec) && is_array( $rec );
            if ($have_rec)
            {
                $model->savePageProduct( $rec );
            }
        }

    }

}