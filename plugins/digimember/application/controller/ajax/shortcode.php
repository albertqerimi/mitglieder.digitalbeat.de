<?php

$load->controllerBaseClass( 'user/base' );

class digimember_AjaxShortcodeController extends ncore_UserBaseController
{
    function ds24smartupgradeJs( $id, $key )
    {
        if (!$id || !$key || !ncore_userId()) {
            return '';
        }

        $event = 'ds24_smartupgrade';

        $params = [
            'id'      => $id,
            'key'     => $key,
            'no_wait' => true,
        ];

        $js = $this->renderAjaxJs( $event, $params, $existing_data_object_name='' );

        return $js;
    }

    /**
     * @param ncore_AjaxResponse $response
     *
     * @return string|void
     */
    function handleAjaxDs24smartUpgradeEvent( $response )
    {
        $smartupgrade_id  = ncore_retrieve( $_POST, 'id' );
        $smartupgrade_key = ncore_retrieve( $_POST, 'key' );

        if (!$smartupgrade_id || !$smartupgrade_key) {
            return;
        }

        $user_id = ncore_userId();
        if (!$user_id) {
            return '';
        }

        $start_time = time();

        $cache_key_local  = 'ds24smup'. $smartupgrade_id.'/'.$user_id.'/local';
        $cache_key_remote = 'ds24smup'. $smartupgrade_id.'/'.$user_id.'/remote';

        $cache_timeout_local  = 600;
        $cache_timeout_remote = 50000;

        $data = ncore_cacheRetrieve( $cache_key_local );

        if (!$data)
        {
            $html   = '';
            $height = 0;

            $purchase_ids = array();
            $vendor_keys  = array();

            /** @var digimember_UserProductData $model */
            $model = $this->api->load->model( 'data/user_product' );
            $orders = $model->getForUser();

            foreach ($orders as $one)
            {
                $is_ds24_order = !empty( $one->ds24_purchase_key );
                if ($is_ds24_order)
                {
                    $purchase_ids[] = $one->order_id;
                    $vendor_keys[]  = $one->ds24_purchase_key;
                }
            }

            if ($purchase_ids) {

                $purchase_ids = implode( ',', $purchase_ids );
                $vendor_keys  = implode( ',', $vendor_keys );

                $data = ncore_cacheRetrieve( $cache_key_remote );

                $must_reload = !$data
                            || empty( $data[ 'purchase_ids' ] )
                            || $purchase_ids != $data[ 'purchase_ids' ];

                if ($must_reload)
                {
                    $base_url = NCORE_DEBUG
                          ? 'http://ds24.de'
                          : 'https://www.digistore24.com';

                    $url = "$base_url/smartupgrade/php/$smartupgrade_id/$smartupgrade_key/$purchase_ids/$vendor_keys";

                    $args = array( 'timeout' => 30 );

                    $result = wp_remote_post( $url, $args );

                    $old_level = error_reporting(0);
                    $data = unserialize( ncore_retrieve( $result, 'body' ) );
                    error_reporting($old_level);

                    if ($data)
                    {
                        $data[ 'purchase_ids' ] = $purchase_ids;
                        ncore_cacheStore( $cache_key_remote, $data, $cache_timeout_remote );
                    }
                    else
                    {
                        $data = array(
                            'info'        => 'could not connect to Digistore24',
                            'purchase_is' => $purchase_ids,
                        );
                    }
                }
            }
            else
            {
                $data = array( 'info' => 'no Digistore24 purchase ids found for the current user' );
            }

            ncore_cacheStore( $cache_key_local, $data, $cache_timeout_local );
        }

        $html   = ncore_retrieve( $data, 'html' );

        $have_content = $html != '';
        if (!$have_content) {
            return '';
        }

        $response->html( 'body_prepend', $html );

        return;
//
//
//        $js = "\$(this).css('height', 40 ); \$(this).show(); \$(body).css('margin-top', 40 );";
//
//        $html = "
//<style>
//iframe.dm_ds24_smartupgrade {
//    position: absolute;
//    top: 0;
//    left: 0;
//    right: 0;
//    height: 1px;
//}
//</style>
//<iframe class='dm_ds24_smartupgrade' src=\"$url\" allowtransparency='true' scrolling='no' frameborder='0'></iframe>";
    }

    protected function ajaxEventHandlers()
    {
        $handlers = parent::ajaxEventHandlers();

        $handlers['ds24_smartupgrade'] = 'handleAjaxDs24smartUpgradeEvent';

        return $handlers;
    }
}