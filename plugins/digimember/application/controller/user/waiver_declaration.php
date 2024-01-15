<?php

$load->controllerBaseClass( 'user/base' );

class digimember_UserWaiverDeclarationController extends ncore_UserBaseController
{
    /** @var string */
    private $redirect_to = '';

    public function init( $settings=array() )
    {
        $this->api->load->helper( 'xss_prevention' );
        $this->api->load->helper( 'html' );
        $this->api->load->helper( 'date' );

        $this->user_id      = ncore_userId();
        $this->button_style = ncore_renderButtonStyle( $settings, 'button_' );

        $this->product_id  = ncore_washInt( ncore_retrieveREQUEST( 'dm_product_id' ) );
        $this->post_id     = ncore_washInt( ncore_retrieveREQUEST( 'dm_post_id' ) );
        $this->redirect_to = str_replace( '"', '', ncore_retrieveREQUEST( 'dm_redirect_to' ) );

        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );
        /** @var digimember_UserProductData $userProductData */
        $userProductData = $this->api->load->model( 'data/user_product' );

        $this->product      = $productData->get( $this->product_id );
        $this->user_product = $userProductData->getForUserAndProduct( $this->user_id, $this->product_id );
    }

    protected function handleRequest()
    {
        parent::handleRequest();

        static $have_waived;

        $must_waive = $this->user_product
                    && $this->product
                    && ncore_XssPasswordVerified()
                    && ncore_retrievePOST( 'action' ) === 'waive_right'
                    && empty( $have_waived );

        if ($must_waive)
        {
            $have_waived = true;
            $this->_waiveRight();

            if ($this->redirect_to) {
                ncore_redirect( $this->redirect_to );
            }
        }
    }

    protected function view()
    {
        $is_button_active = $this->user_product && empty($this->user_product->right_of_rescission_waived_at );

        $hint = $is_button_active
              ? ''
              : ($this->user_product && $this->user_product->right_of_rescission_waived_at
                 ? _digi( "You already agreed on %s!", ncore_formatDateTime( $this->user_product->right_of_rescission_waived_at ))
                 : _digi( "You cannot waive your right now - please open this page by accessing protected content." ) );

        $hint = str_replace( "'", "\\'", $hint );

        $url = ncore_currentUrl();

        list( $hl, $msg, $button ) = $this->_getWaiverMessage();

        list( $find, $repl ) = $this->_renderPlaceholders();

        $hl     = str_replace( $find, $repl, $hl );
        $msg    = str_replace( $find, $repl, $msg );
        $button = str_replace( $find, $repl, $button );

        $js_attr = $is_button_active
                 ? ""
                 : "onclick=\"alert('$hint'); return false;\"";

        $css = $is_button_active
             ? ''
             : 'dm_button_disabled';

        $button = "<button $js_attr type='submit' class='button button-primary ncore_custom_button digimember_waiver_declaration_button $css' style=\"$this->button_style\" name='action' value='waive_right'>$button</button>";

        $xss_check = ncore_XssPasswordHiddenInput();
        $now_unix  = time();

        echo "<div class='digimember_waiver_declaration'>
<form method='POST' action='$url'>

$xss_check
<input type='hidden' name='dm_product_id'  value='$this->product_id' />
<input type='hidden' name='dm_post_id'     value='$this->post_id' />
<input type='hidden' name='dm_redirect_to' value=\"$this->redirect_to\" />
<input type='hidden' name='dm_time'        value=\"$now_unix\" />

<h1>$hl</h1>
<div class='digimember_waiver_declaration_inner'>$msg</div>
<div class='digimember_waiver_declaration_button'>$button</div>

</form>
</div>";
    }


    private $user_id        = false;
    private $button_style   = '';
    private $product_id     = false;
    private $post_id        = false;
    /** @var bool | stdClass */
    private $product        = false;
    /** @var bool | stdClass */
    private $user_product   = false;

    private function _getWaiverMessage( $time=false )
    {
        $cache_key  = 'waive_msg_'.get_locale();
        $expire_sec = 86400;

        $headline     = false;
        $message      = false;
        $button_label = false;

        try
        {
            $rec = ncore_cacheRetrieve( $cache_key );

            $is_to_new = $time && $rec && $rec->created_at > $time;
            if ($is_to_new)
            {
                $old_rec = ncore_cacheRetrieve( 'old_'.$cache_key );
                if ($old_rec) {
                    $rec = $old_rec;
                }
            }

            if (!$rec)
            {
                /** @var digimember_DigistoreConnectorLogic $ds24lib */
                $ds24lib = $this->api->load->model( 'logic/digistore_connector' );

                $ds24    = $ds24lib->developerApi();
                try {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $result = $ds24->getText('right_of_rescission_cancellation');
                } catch (Exception $e) {
                    $result = null;
                }

                $langs_to_try = array(
                    substr( get_locale(), 0, 2 ),
                    'en',
                    'de'
                );

                foreach ($langs_to_try as $lang)
                {
                    $have_text = $result
                              && !empty($result->text )
                              && !empty($result->text->$lang );

                    if ($have_text && !$time)
                    {
                         ncore_cacheStore( 'old_'.$cache_key, $rec, $expire_sec );
                         $rec = $result->text->$lang;
                         $rec->created_at = time();
                         ncore_cacheStore( $cache_key, $rec, $expire_sec+10000 );
                         break;
                    }
                }
            }

            if ($rec)
            {
                $headline = $rec->headline;
                $message  = $rec->message;

                $button_label = empty( $rec->button )
                                 ? _digi( 'I agree' )
                                 : $rec->button;
            }
        }
        catch (Exception $e)
        {
        }

        if (!$message)
        {
            $headline = _digi( 'Waiver Declaration' );
            $message  = _digi( '<p>I agree and expressly request that the execution of the commissioned service be commenced before the end of the revocation period.</p><p>I am aware that gaining access terminates my right of cancellation, by reason of the agreed obligations being fulfilled and the contract completed.</p>' );
            $button_label = _digi( 'I agree' );
        }

        return array( $headline, $message, $button_label );


    }

    private function _renderPlaceholders()
    {
        $find = array();
        $repl = array();

        $find[] = '[ORDER_ID]';
        $repl[] = $this->_renderOrderLabel();

        return array( $find, $repl );
    }

    private function _renderOrderLabel()
    {
        $html = '';

        $order_id = $this->user_product
                  ? $this->user_product->order_id
                  : false;

        $order_date = $this->user_product
                  ? $this->user_product->order_date
                  : false;

        if ($order_id)
        {
            $html = "<span class='dm_waiver_strong'>$order_id</span>";

            if ($order_date)
            {
                $html .= ' ' . _digi( 'of' ) . ' ' . ncore_formatDate( $order_date );
            }
        }

        if ($this->product)
        {
            if ($html)
            {
                $html .= ' (' . $this->product->name . ')';
            }
            else
            {
                $html = "<span class='dm_waiver_strong'>" . $this->product->name . '</span>';
            }
        }

        return $html
               ? "<span class='dm_waiver_order_id dm_active'>$html</span>"
               : "<span class='dm_waiver_order_id dm_inactive'>((" . _digi( 'Your order id' ) . '))</span>';
    }

    private function _waiveRight()
    {
        $time = max( ncore_retrievePOST( 'dm_time' ), time() - 3600 );

        list( $hl, $message, $button_label ) = $this->_getWaiverMessage( $time );

        $waived_purchase_ids = array();

        /** @var digimember_UserProductData $userProductData */
        $userProductData = $this->api->load->model( 'data/user_product' );
        $all  = $userProductData->getAll( array( 'user_id' => $this->user_id, 'product_id' => $this->product_id ) );
        $data = array( 'right_of_rescission_waived_at' => ncore_dbDate(), 'right_of_rescission_waived_by' => 'user' );

        foreach ($all as $one)
        {
            $is_modified = $userProductData->update( $one, $data );
            if ($is_modified && $one->payment_provider_id>0) {
                $waived_purchase_ids[ $one->order_id ] = $one->ds24_purchase_key;
            }
        }

        if (!$waived_purchase_ids) {
            return;
        }

        try
        {
            $user = ncore_getUserById( $this->user_id );

            $ip = ncore_clientIp( 'unknown' );

            /** @var digimember_DigistoreConnectorLogic $ds24lib */
            $ds24lib = $this->api->load->model( 'logic/digistore_connector' );
            if ($ds24lib->isConnected())
            {
                $ds24    = $ds24lib->ds24();

                foreach ($waived_purchase_ids as $purchase_id => $auth_key)
                {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $ds24->waiveRightOfRecission( site_url(), $user->ID, $user->user_email, $ip, $purchase_id, $auth_key, $hl, $message, $button_label );
                }
            }

        }
        catch (Exception $e)
        {
        }
    }
}
