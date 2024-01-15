<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass( 'admin/form' );

class digimember_AdminProductEditController extends ncore_AdminFormController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );

        $this->api->load->model( 'data/product' );
        /** @var digimember_FeaturesLogic $featuresLogic */
        $featuresLogic = $this->api->load->model( 'logic/features' );

        $this->max_product_count = $featuresLogic->maxProductCount();
        $this->cur_product_count = $featuresLogic->curProductCount();

        $is_new = $this->getElementId() == 0;

        $this->is_readonly = $this->max_product_count !== false
                          && ($is_new
                              ? $this->cur_product_count >= $this->max_product_count
                              : $this->cur_product_count  > $this->max_product_count );

        if ($this->is_readonly) {
            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model( 'logic/link' );

            $dm_free = $this->api->pluginNameFree();
            $dm_pro  = $this->api->pluginNamePro();

            $txt = $this->max_product_count == 1
                 ? _digi( 'In %s one product is included. The free product is already in use.',  $dm_free )
                 : _digi( 'In %s %s products are included. The free products are already in use.', $dm_free, $this->max_product_count );

            $txt .= ' '
                  . _digi( 'Upgrade to %s for unlimited products.', $dm_pro );

            $msg = $model->upgradeHint( $txt, $label='', $tag='span' );

            $this->formDisable( $msg );
        }
    }

    protected function pageHeadline()
    {
        /** @var digimember_ProductData $model */
        $model = $this->api->load->model( 'data/product' );

        $product_id = $this->getElementId();
        if ($product_id>0)
        {
            $product = $model->get( $product_id );

            $title = ncore_retrieve( $product, 'name', _digi('New product' ) );
        }
        else
        {
            $title = _digi('new product');
        }

        return array( _digi( 'Products' ), $title );
    }

    protected function formSettings() {

        $settings = parent::formSettings();

        if ($this->is_readonly) {
            $settings[ 'is_form_readonly' ] = true;
        }

        return $settings;
    }


    protected function inputMetas()
    {
        switch ($this->currentTab()) {

            case 'ds24':
                $metas = $this->_inputMetasDs24();
                break;

            case 'settings':
            default:
                $metas = $this->productType() == 'membership'
                       ? $this->_intputMetasMembership()
                       : $this->_intputMetasDownload();
                break;
        }

        $product_id = $this->getElementId();

        foreach ($metas as $index => $meta) {
            $metas[ $index ][ 'element_id'] = $product_id;
        }

        return $metas;
    }

    protected function sectionMetas()
    {
        $ds24name = $this->api->Digistore24DisplayName(false);

        $metas = array();

        $label = $this->productType() == 'membership'
               ? _digi( 'Membership product' )
               : _digi( 'Download product' );

        $metas['product'] = array(
                            'headline' => $label,
                            'instructions' => '',
                         );

        $metas['digistore_connection'] = array(
                            'headline' => _digi( 'Connect this DigiMember product to an existing %s product', $ds24name ),
                            'instructions' => '',
                         );
        $metas['digistore_sync'] = array(
                            'headline' => _digi( 'Create new product in %s for this DigiMember product', $ds24name ),
                            'instructions' => '',
                         );

        $metas['protect'] = array(
                            'headline' => _digi( 'Protect content' ),
                            'instructions' => '',
                         );

        $metas['texts'] = array(
                            'headline' => _digi( 'Texts' ),
                            'instructions' => '',
                         );

        $metas['login'] = array(
                            'headline' => _digi( 'Login redirect URLs' ),
                            'instructions' => '',
                         );


        $metas['download'] = array(
                            'headline' => _digi( 'Download' ),
                            'instructions' => '',
                         );


        $product_sections = digimember_private_getProductSections();
        unset( $product_sections['product'] );



        return array_merge( $metas, $product_sections );
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');
        $link = $linkLogic->adminPage( 'products' );
        $metas[] = array(
                'type' => 'link',
                'label' => _ncore('Back'),
                'url' => $link,
                );

        return $metas;
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        /** @var digimember_ProductData $model */
        $model = $this->api->load->model( 'data/product' );

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            $obj = $model->get( $id );

            if (!$obj)
            {
                $this->formDisable( _ncore( 'The element has been deleted.' ) );
                return false;
            }

            $properties = ncore_retrieve( $obj, 'properties', array() );
            foreach ($properties as $name => $value)
            {
                $key = "property_$name";
                $obj->$key = $value;
            }

            if ($this->currentTab() == 'ds24')
            {
                $this->pullDs24Produkt( $obj );
            }
        }
        else
        {
            $obj = $model->emptyObject();
        }

        $obj->is_published = ncore_toYesNoBit( $obj->published );

        if ($obj->is_published == 'N')
        {
            $tab   = _digi( 'Membership' );
            $label = _ncore('Is published' );
            $this->formWarning( _digi( 'The product is not yet published and cannot be sold. In the tab "%s" set the option "%s" to YES.', $tab, $label ) );
        }


        if ($this->currentTab() == 'settings')
        {
            $message = $model->validateProduct( $obj );
            $this->formWarning( $message );
        }

        return $obj;
    }

    protected function setData( $id, $data )
    {
        /** @var digimember_ProductData $model */
        $model = $this->api->load->model( 'data/product' );

        $have_id = is_numeric( $id ) && $id > 0;

        $is_published = ncore_retrieveAndUnset( $data, 'is_published' );

        $properties = array();
        $metas = digimember_private_getProductPropertyMetas();

        foreach ($metas as $section_metas)
        {
            foreach ($section_metas as $one)
            {
                $name = $one['name'];
                $key = "property_$name";
                $properties[ $name ] = ncore_retrieve( $data, $key );
                unset( $data[ $key ] );
            }
        }


        if ($this->is_readonly)
        {
            $msg = _digi( 'Please upgrade for more product.' );

            $this->formError( $msg );

            return false;
        }
        elseif ($have_id)
        {
            $is_modified = $model->update( $id, $data );
        }
        else
        {
            $data[ 'type' ] = $this->productType();
            $id = $model->create( $data );

            $this->setElementId( $id );

            $is_modified = (bool) $id;
        }

        $obj = $model->get( $id );
        if ($is_published=='Y' && !$obj->published){
            $model->publish( $id );
        }
        if ($is_published=='N' && $obj->published){
            $model->unpublish( $id );
        }

        $model->addProperties( $id, $properties );

        if ($this->currentTab() == 'ds24')
        {
            /** @var digimember_DigistoreSyncLogic $sync */
            $sync  = $this->api->load->model( 'logic/digistore_sync' );

            try
            {
                /** @var digimember_ProductData $model */
                $model = $this->api->load->model( 'data/product' );
                $product = $model->get( $id );

                $was_synched = $product->ds24_sync_product_id > 0;

                $sync->pushProduct( $product );

                if (!$was_synched)
                {
                    $this->onElementIdChanged();
                }
            }
            catch (Exception $e)
            {
                $this->formError( $e->getMessage() );
            }
        }

        if ($is_modified) {
            do_action( 'digimember_invalidate_cache' );
        }

        return $is_modified;
    }


    protected function onElementIdChanged()
    {
        $element_id = $this->getElementId();

        $url = ncore_currentUrl();
        $url = ncore_removeArgs( $url, 'id', '&', false );
        $redirect_to_url = ncore_addArgs( $url, array( 'id' => $element_id ), '&', false );

        ncore_flashMessage( NCORE_NOTIFY_SUCCESS, _ncore( 'Your changes have been saved.' ) );

        ncore_redirect( $redirect_to_url );
    }




    protected function formActionUrl()
    {
        $this->api->load->helper( 'url' );

        $action_url = parent::formActionUrl();

        $id =  $this->getElementId();

        if ($id)
        {

            $args = array( 'id' => $id );

            return ncore_addArgs( $action_url, $args );
        }
        else
        {
            return $action_url;
        }
    }

    protected function tabs()
    {
        $tabs = array(
            'settings'  => ($this->productType() == 'membership'
                           ? _digi( 'Membership' )
                           : _digi( 'Download' )),
            'ds24'      => _digi( 'Digistore24' ),
        );

        return $tabs;
    }

    private $ds24_product_pulled = false;
    private $product_type = false;
    private $max_product_count = 1;
    private $cur_product_count = 0;
    private $is_readonly       = false;

    private function productType()
    {
        if ($this->product_type !== false)
        {
            return $this->product_type;
        }

        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );
        $product_id = $this->getElementId();
        $product = $product_id > 0
                 ? $productData->get( $product_id )
                 : false;

        $this->product_type = $product
                              ? $product->type
                              : ncore_retrieveGET( 'type', 'membership' );

        return $this->product_type;
    }

    private function pullDs24Produkt( $obj )
    {
        if ($this->ds24_product_pulled)
        {
            return;
        }
        $this->ds24_product_pulled = true;

        /** @var digimember_DigistoreSyncLogic $sync */
        $sync = $this->api->load->model( 'logic/digistore_sync' );

        try
        {
            $sync->pullProduct( $obj );
        }
        catch (Exception $e)
        {
            $this->formError( $e->getMessage() );
        }
    }


    private function _inputMetasDs24()
    {
        $api = $this->api;
        /** @var digimember_ProductData $productData */
        $productData = $api->load->model( 'data/product' );
        /** @var digimember_DigistoreConnectorLogic $ds24 */
        $ds24 = $api->load->model( 'logic/digistore_connector' );

        $product_id = $this->getElementId();
        $product    = $productData->get( $product_id );

        if ($product)
        {
            $this->pullDs24Produkt( $product );
        }

        $have_ds24 = $ds24->isConnected( $force_reload=false );

        $ds24link = $api->Digistore24DisplayName(true);
        $ds24name = $api->Digistore24DisplayName(false);

        $ds24_products = $have_ds24
                        ? $ds24->getProductOptions( $force_reload=true )
                        : false;
        if (!$ds24_products) {
            $have_ds24 = $ds24->isConnected( $force_reload=true );
        }

        $ds24_inputs = array(
                        'ds24_product_ids' => _digi( 'Matching %s Products', $ds24link ),
                       );

        $is_ds24_sync_enabled = ncore_isTrue( ncore_retrieve( $product, 'is_ds24_sync_enabled', false ) );
        $ds24_sync_product_id = ncore_retrieve( $product, 'ds24_sync_product_id', false );

        if ($have_ds24)
        {
            $metas[] = array(
                        'label' => _digi('Create new product in %s', $ds24name),
                        'name' => 'is_ds24_sync_enabled',
                        'type' => 'yes_no_bit',
                        'rules' => '',
                        'section' => 'digistore_sync',
                        'tooltip'  => _digi('You are able to create the equivalent for your DigiMember product in %s with this function or let it be created it directly in %s.', $ds24name ),
             );

            if ($ds24_sync_product_id) {
                $edit_link  = $ds24->url( 'edit_product', $ds24_sync_product_id );
                $order_link = $ds24->url( 'orderform_with_login', $ds24_sync_product_id );
                $html = '
<style>
    .dm-ds24-spacing > a {
        margin: 0 10px;
    }
</style>
<div class="dm-row"><div class="dm-col-md-6 dm-col-sm-7 dm-col-xs-12"><div style="display: flex; align-items: center;" class="dm-ds24-spacing">' . ncore_linkReplace(
                        _digi( 'in %1$s: <a>view order form</a> - <a>edit product</a>', $ds24name ),
                        [
                            'url' => $order_link,
                            'class' => 'dm-btn dm-btn-primary dm-btn-outlined',
                        ],
                        [
                            'url' => $edit_link,
                            'class' => 'dm-btn dm-btn-primary dm-btn-outlined',
                        ],
                        $as_popup=true
                    ) . '</div></div></div>';
            }
            else {
                $this->api->load->helper('html_input');
                $html = ncore_htmlAlert('info', _digi( 'Save to create a matching product in %s.', $ds24name ), 'info');
            }

            $metas[] = array(
                        'label' => _digi('%s product', $ds24name),
                        'type' => 'html',
                        'section' => 'digistore_sync',
                        'html'  => $html,
                        'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
             );

            $metas[] = array(
                        'label' => _digi('Sales text'),
                        'name' => 'ds24_description',
                        'type' => 'htmleditor',
                        'section' => 'digistore_sync',
                        'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
                        'hint' => _digi( 'Is shown on the %s order form', $ds24name ),
                        'simple_buttons' => true,
                        'hide_images' => true,
                        'rules' => 'required|defaults',
             );


            $can_edit_payment_plan = !$product
                                    || !$product->ds24_sync_product_id
                                    || $product->ds24_sync_payplan_id;

            $is_membership_product = $this->productType() == 'membership';
            if ($can_edit_payment_plan)
            {
                $currency_options = $ds24->getCurrencyOptions( $allow_null = false );
                $metas[] = array(
                            'label' => _digi('Price' ),
                            'name' => 'ds24_currency',
                            'type' => 'select',
                            'section' => 'digistore_sync',
                            'options'  => $currency_options,
                            'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
                            'in_row_with_next' => true,
                );

                $metas[] = array(
                            'label' => 'none',
                            'type' => 'float',
                            'name' => 'ds24_first_amount',
                            'section' => 'digistore_sync',
                            'options'  => $currency_options,
                            'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
                            'unit' => ($is_membership_product ? ' <span class="dm-input-hint">' . _digi( 'once + (optionally) monthly' ) . '</span> ' : '' ),
                            'rules' => 'required|defaults',
                            'in_row_with_next' => $is_membership_product,
                );

                if ($is_membership_product)
                {
                    $metas[] = array(
                                'label' => 'none',
                                'type' => 'float',
                                'name' => 'ds24_other_amounts',
                                'section' => 'digistore_sync',
                                'options'  => $currency_options,
                                'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),

                                'display_zero_as' => '',
                               );
                }
                else
                {
                    $metas[] = array(
                                'type' => 'hidden',
                                'name' => 'ds24_other_amounts',
                                );
                }
            }
            else
            {
                $url = $ds24->url( 'edit_payplans', $product->ds24_sync_product_id );
                $html = ncore_linkReplace( _digi('<a>Please edit in %s</a>', $ds24name ), $url, true );
                $metas[] = array(
                        'label' => _digi('Price' ),
                        'type' => 'html',
                        'section' => 'digistore_sync',
                        'html'  => $html,
                        'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
                );
            }

            $url = $ds24->url( 'edit_affiliates' );
            $hint = ncore_linkReplace( _digi('A change affects only new affiliates. <a>View affiliates in %s.</a>', $ds24name ), $url, true );
            $metas[] = array(
                        'label' => _digi('Affiliate commission'),
                        'type' => 'rate',
                        'name' => 'ds24_affiliate_commission',
                        'section' => 'digistore_sync',
                        'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
                        'display_zero_as' => '',
                        'hint' => $hint,
            );


            $metas[] = array(
                        'label' => _digi('Salespage URL' ),
                        'name' => 'ds24_salespage',
                        'type' => 'page_or_url',
                        'section' => 'digistore_sync',
                        'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
                        'rules' => 'required|defaults',
            );

            $label = _digi('Add these hints yo your thankyou page:' );
            $hint1 = _digi( 'In the next minutes, you will receive an email with your access data to the membership area.' );
            $hint2 = _digi( 'The payment will be processed by %s.', $ds24name );

            /** @var digimember_ShortCodeController $controller */
            $controller = $this->api->load->controller( 'shortcode' );
            $code1 = $controller->renderTag('username');
            $code2 = $controller->renderTag('password');
            $hint3 = _digi('On the thankyou page, use the shortcodes %s and %s to display the new users access data.', $code1, $code2 );

            $metas[] = array(
                        'label' => _digi('Thankyou page URL' ),
                        'name' => 'ds24_thankyoupage',
                        'type' => 'page_or_url',
                        'section' => 'digistore_sync',
                        'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
                        'rules' => 'required|defaults',
                        'hint' => "$label<br><em class='ncore_hint_bullet'>$hint1</em><br /><em class='ncore_hint_bullet'>$hint2</em><br />$hint3",
            );

         $image_meta = $ds24->getGlobalSetting( 'image_metas', 'product' );

         $hint = @$image_meta['limits_msg'];

         $metas[] = array(
                        'label' => _digi('Product image' ),
                        'type' => 'image_url',
                        'name' => 'ds24_image_url',
                        'section' => 'digistore_sync',
                        'hint'  => $hint,
                        'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
         );

             $metas[] = array(
                'label' => _digi('%s approval status', $ds24name),
                'name' => 'ds24_approval_status',
                'type' => 'ds24_approval_status',
                'section' => 'digistore_sync',
                'depends_on' => array( 'is_ds24_sync_enabled' => 'Y' ),
             );


             $must_warn = false;
             if ($ds24_sync_product_id && $is_ds24_sync_enabled)
             {
                 $selected_ids = explode( ',', $product->ds24_product_ids );
                 $is_selected = in_array( $ds24_sync_product_id, $selected_ids );
                 $must_warn = !$is_selected;
             }

             $hint = $must_warn
                   ? "<div class='ncore_msg_warning'>" . _digi( '%s product %s is not selected. Is this correct?', $ds24name, '#'.$ds24_sync_product_id ) . '</div>'
                   : '';


            $metas[] = array(
                        'label' => $ds24_inputs['ds24_product_ids'],
                        'name' => 'ds24_product_ids',
                        'type' => 'checkbox_list',
                        'options' => $ds24_products,
                        'rules' => '',
                        'seperator' => '<br />',
                        'section' => 'digistore_connection',
                        'have_all' => false,
                        'tooltip'  => _digi('Select the %1$s products, that match this %2$s product. If a product is sold by %1$s, then the buyer gets this %2s-product (you currently edit).', $ds24name, $this->api->pluginDisplayName() ),
                        'hint' => $hint,
             );
        }
        else
        {
            list( $type, $msg, $button ) = $ds24->renderStatusNotice( 'seperate', 'auto' );
            $html = ncore_htmlAlert($type, $msg, $type, '', $button);

            if($ds24_products === false)
            {
                $html = ncore_htmlAlert('error', _digi( 'Currently there is an error connecting to you %s account. Please try again later.', $ds24link ), 'error');
            }
            else
            {
                $html = ncore_htmlAlert('warning', _digi( 'You currently have no %s products.', $ds24link ), 'warning');
            }

            $metas[] = array(
                        'label' => $ds24_inputs['ds24_product_ids'],
                        'type' => 'html',
                        'html' => $html,
                        'section' => 'digistore',
             );

             $ds24_input_names = array_keys($ds24_inputs);
             foreach ($ds24_input_names as $one)
             {
                 $metas[] = array(
                            'type' => 'hidden',
                            'name' => $one,
                 );
             }
        }

        return $metas;
    }

    private function _intputMetasMembership()
    {
        $api = $this->api;
        /** @var digimember_ProductData $productData */
        $productData = $api->load->model( 'data/product' );
        $api->load->model( 'logic/digistore_connector' );

        $productData->setNextType( 'membership' );

        $access_denied_options        = $productData->accessDeniedOptions();
        $content_later_options        = $productData->contentLaterTypeOptions();
        $lock_options                 = $productData->accountLockOptions();
        $account_lock_default_msg     = $productData->accountLockDefaultMessage();
        $unlock_policy_options        = $productData->unlockPolicyOptions();

        $product_id = $this->getElementId();

        /** @var digimember_BlogConfigLogic $config */
        $config = $this->api->load->model( 'logic/blog_config' );
        $have_url_for_login_page = ncore_isTrue( $config->get('use_free_url_for_login_page', 'N') );
        $use_error_handling_prioritization = ncore_isTrue($config->get('use_error_handling_prioritization', 'N'));
        $url_type = $have_url_for_login_page
                  ? 'page_or_url'
                  : 'page';

        $metas = array(

            array(
                'name' => 'id',
                'section' => 'product',
                'type' => 'int',
                'label' => _digi('Product Id' ),
                'rules' => 'readonly',
                'hide' => $product_id == 0,
            ),

            array(
                'name' => 'name',
                'section' => 'product',
                'type' => 'text',
                'label' => _digi('Product Name' ),
                'rules' => 'required|defaults',
                'maxlength' => 63,
                'size' => 63,
            ),
            array(
                'name' => 'is_published',
                'section' => 'product',
                'type' => 'yes_no_bit',
                'label' => _ncore('Is published' ),
            ),
        );

        $metas = array_merge( $metas, $this->waiverMetas() );

        $metas[] = array(
                'name' => 'first_login_url',
                'section' => 'login',
                'type' => $url_type,
                'label' => _digi('First login redirect' ),
                'tooltip' => _digi('The user is redirected to this page, when he logs in the first login after purchasing the product. You may want to add some information about the payment, a "Thank You" for his purchase or some hints on how to use your membership area and the download page.'),
                'rules' => 'defaults',
                'allow_null' => true,
            );

        $login_redir_label =  _digi('Login redirect' );
        $metas[] = array(
                'name' => 'login_url',
                'section' => 'login',
                'type' => $url_type,
                'label' => $login_redir_label,
                'tooltip' => _digi('The user is redirected to this page starting with the second login. This page may be identical to the first page.'),
                'rules' => 'defaults',
                'allow_null' => true,
            );

        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->tagProductsShortcode();


        $metas[] = array(
                'name' => 'shortcode_url',
                'section' => 'login',
                'type' => $url_type,
                'label' => _digi('Link for products short code' ),
                'tooltip' => _digi('This link is used to generate links of the short code %s. If not given, page entered for %s above is used.', $shortcode, "<em>$login_redir_label</em>" ),
                'rules' => 'defaults',
                'allow_null' => true,
            );
            
        $metas[] = array(
                'name' => 'unlock_mode',
                'section' => 'protect',
                'type' => 'select',
                'label' => _digi('Start unlocking ...' ),
                'rules' => 'defaults',
                'options' => $productData->unlockModeOptions(),
                'hint' => _digi( 'Content with Unlock Day 0 is always visible - even if this date is in the future.' ),
            );       
            
        $this->api->load->helper( 'date' );
        $metas[] = array(
                'name' => 'unlock_start_date',
                'section' => 'protect',
                'type' => 'date',
                'label' => _digi('Unlock start date' ),
                'rules' => 'required',
                'depends_on' => array( 'unlock_mode' => 'fix_date' ),
                'with_time' => true,
                'hint' => _digi( 'Current server time is: %s', ncore_formatTime( time(), get_option('gmt_offset') )) . ' - ' . _digi( 'You may adjust the timezone in the wordpress menu %ssettings - general%s.', '<em>', '</em>' ),
            );
           
        $metas[] = array(
                'name' => 'unlock_policy',
                'section' => 'protect',
                'type' => 'select',
                'label' => _digi('Exclude ...' ),
                'rules' => 'defaults',
                'options' => $unlock_policy_options,
            );

        $metas[] = array(
                'name' => 'access_denied_type',
                'section' => 'protect',
                'type' => 'select',
                'label' => _digi('Error handling (access denied)' ),
                'tooltip' => _digi('Select what DigiMember should do, if a logged out user tries to access a protected page.'),
                'rules' => 'defaults',
                'options' => $access_denied_options,
            );

        if ($use_error_handling_prioritization) {
            $metas[] = array(
                'name' => 'access_denied_priority',
                'section' => 'protect',
                'type' => 'yes_no_bit',
                'default' => 'N',
                'label' => _digi('Prioritize error handling for this product'),
                'hint' => _digi('If the content this product is connected to has at last one more membership product, the error handling of this product will be prioritized.'),
            );
        }


        $metas[] = array(
                'name' => 'access_denied_url',
                'section' => 'protect',
                'type' => 'url',
                'label' => _digi('Error redirect to' ),
                'tooltip' => _digi('If a logged out user tries to access a protected page, he is redirected to this URL.'),
                'rules' => 'defaults|required',
                'depends_on' => array( 'access_denied_type' => DIGIMEMBER_AD_URL ),
            );


        $metas[] = array(
                'name' => 'access_denied_page',
                'section' => 'protect',
                'type' => 'page',
                'label' => _digi('Error redirect to' ),
                'tooltip' => _digi('If a logged out user tries to access a protected page, he is redirected to this page instead.<p>If the target page is also protected, a login form is displayed instead.'),
                'rules' => 'defaults',
                'depends_on' => array( 'access_denied_type' => DIGIMEMBER_AD_PAGE ),
            );



        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->shortcode('login');
        $shortcode_tag = $controller->renderTag( $shortcode );

        $metas[] = array(
                'name' => 'access_denied_text',
                'section' => 'protect',
                'type' => 'htmleditor',
                'rows' => 5,
                'label' => _digi('Error text' ),
                'tooltip' => _digi('If a logged out user tries to access a protected page, this text is shown instead. Make sure, you use the short code %s in the text.', $shortcode_tag ),
                'rules' => 'defaults',
                'depends_on' => array( 'access_denied_type' => DIGIMEMBER_AD_TEXT ),
            );




        $metas[] = array(
                'name' => 'lock_type',
                'section' => 'protect',
                'type' => 'select',
                'label' => _digi('Lock user account, if payment cancelled' ),
                'tooltip' => _digi('Select what DigiMember should do, if the order is cancelled or a rebilling payment is missing.|By default (no), only access to the product is cancelled.|If you select to lock the account, the user cannot log into wordpress, if a payment is cancelled or a rebilling payment is missing.|The account is unlocked, when the next payment comes in.|For admins this setting has no effect.'),
                'rules' => 'defaults',
                'options' => $lock_options,
            );


        $metas[] = array(
                'name' => 'lock_url',
                'section' => 'protect',
                'type' => 'url',
                'label' => _digi('Account lock redirect to' ),
                'tooltip' => _digi('If a user with a locked account tries to log in, he is redirected to this url instead.'),
                'rules' => 'defaults|required',
                'depends_on' => array( 'lock_type' => DIGIMEMBER_AL_URL ),
            );


        $metas[] = array(
                'name' => 'lock_page',
                'section' => 'protect',
                'type' => 'page',
                'label' => _digi('Account lock redirect to' ),
                'tooltip' => _digi('If a user with a locked account tries to log in, he is redirected to this page instead.'),
                'rules' => 'defaults',
                'depends_on' => array( 'lock_type' => DIGIMEMBER_AL_PAGE ),
            );


        $metas[] = array(
                'name' => 'lock_text',
                'section' => 'protect',
                'type' => 'text',
                'label' => _digi('Account lock message' ),
                'tooltip' => _digi('If a user with a locked account tries to log in, the login fails and this message is displayed.'),
                'rules' => 'defaults|trim',
                'depends_on' => array( 'lock_type' => DIGIMEMBER_AL_TEXT ),
                'default' => $account_lock_default_msg,
                'size' => 50,
            );



        $metas[] = array(
                'name' => 'flags',
                'section' => 'protect',
                'type' => 'flags',
                'label' => _digi('Menu settings' ),
                'flags' => $productData->flagsShort(),
                'tooltips' => $productData->flags(),
                'rules' => 'defaults',
            );

        $metas[] = array(
                'name' => 'are_comments_protected',
                'section' => 'protect',
                'type' => 'yes_no_bit',
                'label' => _digi('Protect comments' ),
                'tooltips' => _digi('If enabled, comments will only be visible to product owners (if the page/post is assigned to this product)' ),
            );


        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->shortcode('days_left');
        $shortcode_tag = $controller->renderTag( $shortcode );

        $metas[] = array(
            'name' => 'access_granted_for_days',
            'section' => 'protect',
            'type' => 'int',
            'label' => _digi('Days of access' ),
            'unit' => _ncore('days'),
            'tooltip' => _digi('The access will be granted for this number of days.|Leave blank for subscriptions, because for subscriptions the payment provider will cancel the access to the product, when a payment is missing. Otherwise the user may access the product this number of days after his last payment.|Use the shortcode %s to display the remaining days a user has access to the product.', $shortcode_tag ),
            'display_zero_as' => '',
            'hint' => _digi( 'Default is to leave this field empty. Then the payment provider handles the product access.' ),
        );

        $custom_metas = $productData->propertyMetas();
        $metas = array_merge( $metas, $custom_metas );


        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->shortcode('preview');
        $shortcode_tag = $controller->renderTag( $shortcode );
        $plugin = $this->api->pluginDisplayName();
        $menu_entry = _digi( 'Shortcodes' );
        $menu = "<em>$plugin - $menu_entry</em>";

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $url = $model->adminPage( 'shortcode' );
        $shortcode_link = "<a href=\"$url#$shortcode\">$shortcode_tag</a>";

        $metas[] = array(
                'name' => 'sales_letter',
                'section' => 'texts',
                'type' => 'htmleditor',
                'rows' => 5,
                'label' => _digi('Sales letter' ) . '<br /><span class="ncore_form_hint">(' . _digi( 'for %s only', $shortcode_link ) . ')</span>',
                'tooltip' => _digi( 'Only used, if the shortcode %s is used inside a protected content.|This text is displayed after the preview content.|For more infos, see %s', $shortcode_tag, $menu ),
                'rules' => 'defaults',
            );


        $metas[] = array(
                'name' => 'content_later_type',
                'section' => 'texts',
                'type' => 'select',
                'label' => _digi('If page not yet available' ),
                'tooltip' => _digi('Select what DigiMember should do, if a user tries to access a protected page, which will be available to the user later.'),
                'rules' => 'defaults',
                'options' => $content_later_options,
            );


        $metas[] = array(
                'name' => 'content_later_msg',
                'section' => 'texts',
                'type' => 'htmleditor',
                'rows' => 5,
                'label' => _digi('Not yet available message' ),
                'tooltip' => _digi( 'Will be shown, if the content of a product is not yet available and the user has to wait some days.|Use these shortcodes:|%s = number of days|%s = first date of acces|%s = text: in XX days', '[DAYS]', '[DATE]', '[IN_DAYS]' ),
                'hint' => _digi( 'Shortcodes: %s', '[DAYS] [DATE] [TIME] [IN_DAYS]' ),
                'rules' => 'defaults',
                'depends_on' => array( 'content_later_type' => DIGIMEMBER_AD_TEXT ),
            );


        $metas[] = array(
                'name' => 'content_later_url',
                'section' => 'texts',
                'type' => 'url',
                'label' => _digi('Error redirect to' ),
                'tooltip' => _digi('If a logged in user tries to access a protected page, he is redirected to this URL.'),
                'rules' => 'defaults|required',
                'depends_on' => array( 'content_later_type' => DIGIMEMBER_AD_URL ),
            );


        $metas[] = array(
                'name' => 'content_later_page',
                'section' => 'texts',
                'type' => 'page',
                'label' => _digi('Error redirect to' ),
                'tooltip' => _digi('If a logged in user tries to access a protected page, he is redirected to this page instead.<p>If the target page is also protected, a login form is displayed instead.'),
                'rules' => 'defaults',
                'depends_on' => array( 'content_later_type' => DIGIMEMBER_AD_PAGE ),
            );





        $product_sections   = digimember_private_getProductSections();
        $product_properties = digimember_private_getProductPropertyMetas();

        foreach ($product_sections as $section_key => $section_label)
        {
            $section_properties = ncore_retrieve( $product_properties, $section_key, array() );
            foreach ($section_properties as $one)
            {
                $name = $one['name'];

                $one['name']       = "property_$name";
                $one['section']    = $section_key;
                $metas[] = $one;
            }
        }

        return $metas;
    }

    private function _intputMetasDownload()
    {
        $api = $this->api;
        /** @var digimember_ProductData $productData */
        $productData = $api->load->model( 'data/product' );
        $api->load->model( 'logic/digistore_connector' );

        $productData->setNextType( 'download' );

        $id = $this->getElementId();

        /** @var digimember_BlogConfigLogic $config */
        $config = $this->api->load->model( 'logic/blog_config' );
        $have_url_for_login_page = ncore_isTrue( $config->get('use_free_url_for_login_page', 'N') );
        $url_type = $have_url_for_login_page
                  ? 'page_or_url'
                  : 'page';


        $metas = array();

        $metas[] = array(
                'name' => 'id',
                'section' => 'product',
                'type' => 'int',
                'label' => _digi('Product Id' ),
                'rules' => 'readonly',
                'hide' => $id == 0,
            );

        $metas[] = array(
                'name' => 'name',
                'section' => 'product',
                'type' => 'text',
                'label' => _digi('Product Name' ),
                'rules' => 'required|defaults',
                'maxlength' => 63,
                'size' => 63,
            );

        $metas[] = array(
                'name' => 'is_published',
                'section' => 'product',
                'type' => 'yes_no_bit',
                'label' => _ncore('Is published' ),
            );

        $metas = array_merge( $metas, $this->waiverMetas() );

        $this->api->load->helper( 'html_input' );
        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->shortcode( 'download' );
        $url  = _digi( 'DOWNLOAD_URL' );
        $text = _digi( 'Download now' );
        $shortcode = ncore_htmlTextInputCode( "[$shortcode text='$text' url=$url]" );

        $metas[] = array(
                'name' => 'login_url',
                'section' => 'download',
                'type' => $url_type,
                'label' => _digi('Download page'),
                'tooltip' => _digi('After the purchase, the user is redirected to this page. On this page your provide the download links.'),
                'rules' => 'required',
                'hint' => _digi('Shortcode for protected downloads:') . $shortcode,
            );

        $metas[] = array(
            'name' => 'access_granted_for_days',
            'section' => 'download',
            'type' => 'int',
            'label' => _digi('Days of access' ),
            'display_zero_as' => '',
            'hint' => _digi( 'If given, the user may access the download page for this number of days. Leave blank for no limit.' ),
            'unit' => _ncore( 'days' ),
        );


        $metas[] = array(
            'name' => 'max_download_times',
            'section' => 'download',
            'type' => 'int',
            'label' => _digi('Download limit'),
            'tooltip' => _digi('The access will be granted for this number of days.|Leave blank for unlimited access.' ),
            'display_zero_as' => '',
            'hint' => _digi( 'If given, the user may access each download URL on the page this number of times. Leave blank for no limit.' ),
            'unit' => _digi( 'times' ),
        );



       $access_denied_options    = $productData->accessDeniedOptions();

       $access_denied_options = array(
            DIGIMEMBER_AD_URL => $access_denied_options[DIGIMEMBER_AD_URL],
            DIGIMEMBER_AD_PAGE => $access_denied_options[DIGIMEMBER_AD_PAGE],
       );

       $metas[] = array(
                'name' => 'access_denied_type',
                'section' => 'protect',
                'type' => 'select',
                'label' => _digi('Error handling (access denied)' ),
                'tooltip' => _digi('Select what DigiMember should do, if a logged out user tries to access a protected page.'),
                'rules' => 'defaults',
                'options' => $access_denied_options,
            );


        $metas[] = array(
                'name' => 'access_denied_url',
                'section' => 'protect',
                'type' => 'url',
                'label' => _digi('Error redirect to URL' ),
                'tooltip' => _digi('If a logged out user tries to access a protected page, he is redirected to this URL.'),
                'rules' => 'defaults|required',
                'depends_on' => array( 'access_denied_type' => DIGIMEMBER_AD_URL ),
            );


        $metas[] = array(
                'name' => 'access_denied_page',
                'section' => 'protect',
                'type' => 'page',
                'label' => _digi('Error redirect to page' ),
                'tooltip' => _digi('If a logged in user tries to access a protected page, he is redirected to this page instead.<p>If the target page is also protected, a login form is displayed instead.'),
                'rules' => 'defaults',
                'depends_on' => array( 'access_denied_type' => DIGIMEMBER_AD_PAGE ),
            );



        return $metas;
    }


    private function waiverMetas()
    {
        $waiverSelection = 0;
        $productModel = $this->api->load->model('data/product');
        $waiverEnabledProducts = $productModel->getAll(array(
            'is_right_of_withdrawal_waiver_required' => 'Y'
        ));
        if (is_array($waiverEnabledProducts) && count($waiverEnabledProducts) > 0) {
            $waiverEnabledProduct = $waiverEnabledProducts[0];
            $waiverSelection = $waiverEnabledProduct->right_of_withdrawal_waiver_page_id;
        }
        /** @var digimember_UserProductData $userProductData */
        $userProductData = $this->api->load->model( 'data/user_product' );
        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model( 'logic/html' );
        $this->api->load->helper( 'html_input' );

        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->shortcode( 'waiver_declaration' );
        $shortcode = ncore_htmlTextInputCode( "[$shortcode]" );

        $js = "ncoreJQ('.pe_waiver_input input').change(function() { var v=ncoreJQ(this).val(); var o=ncoreJQ('.pe_waiver_hint'); if (v=='Y') o.show(); else o.hide(); } );";


        $js .= "var v=ncoreJQ('.pe_waiver_input input:checked ').trigger('change');";

        $ds24name  = $this->api->Digistore24DisplayName(false);
        $htmlLogic->jsOnLoad( $js );

        $days = $userProductData->legalRefundDays()+1;

        return array(
            array(
                'label' => _digi( 'Request waiver for right of rescission' ),
                'name' => 'is_right_of_withdrawal_waiver_required',
                'type' => 'yes_no_bit',
                'rules' => '',
                'section' => 'product',
                'tooltip'  => _digi('If selected, then the buyer can only access paid content after the right of rescission has expired or after he willingly agrees to waive this right.|If he waives this right and if the order has been processed by %s, %s will be notified about this.', $ds24name, $ds24name ),
                'hint' => "<span class='pe_waiver_hint'>" . _digi( 'This setting has no effect, if you have set a refund policy of %s or more days in %s.', $days, $ds24name ) . '</span>',
                'class' => 'pe_waiver_input',
            ),
            array(
                'name' => 'right_of_withdrawal_waiver_page_id',
                'section' => 'product',
                'type' => 'page',
                'label' => _digi('Page with Waiver Declaration' ),
                'hint' => _digi( 'Make sure, this page is NOT protected.' ). '<br />' . _digi('Add this shortcode to the page:') . $shortcode,
                'rules' => 'defaults|required',
                'depends_on' => array( 'is_right_of_withdrawal_waiver_required' => 'Y' ),
                'default' => $waiverSelection,
            ),
        );
    }



}