<?php

class digimember_ShortCodeController extends ncore_ShortCodeController
{
    private static $widgets = array(
        'account'          => array( 'has_product_selection', 'has_login_selection' ),
        'login'            => array( 'has_login_selection' ),
        'signup'           => array(),
        'menu'             => array( 'has_product_selection', 'has_login_selection' ),
        'lecture_buttons'  => array( 'has_product_selection'  ),
        'lecture_progress' => array( 'has_product_selection'  ),
        'webpush'          => array( 'has_product_selection', 'has_login_selection' ),
    );


    public function availableWidgets()
    {
        return array_merge(
                    parent::availableWidgets(),
                    array_keys( self::$widgets )
        );
    }

    public static function widgetLabel( $shortcode )
    {
        $map = array(
            'lecture_buttons'  => _digi( 'Course Navigation Buttons' ),
            'lecture_progress' => _digi( 'Course Progress Bar' ),
            'webpush'          => _digi( 'Optin to web push notifications' ),
        );

        if (!empty( $map[ $shortcode] ))
        {
            return $map[ $shortcode];
        }

        return parent::widgetLabel( $shortcode );
    }

    public function prepareWidgetInputMetas( $shortcode, &$metas )
    {
        parent::prepareWidgetInputMetas( $shortcode, $metas );

        $settings = ncore_retrieve( self::$widgets, $shortcode, array() );

        $has_login_selection   = in_array( 'has_login_selection',   $settings );
        $has_product_selection = in_array( 'has_product_selection', $settings );


        if ($has_login_selection) {
            $this->_prepareWidgetInputMetasLoginSelection( $metas );
        }

        if ($has_product_selection) {
            $this->_prepareWidgetInputMetasProductSelection( $metas );
        }
    }

    public function shortcodeIf( $attributes=array(), $contents='' )
    {
        $has_product     = ncore_retrieve( $attributes, 'has_product' );
        $has_not_product = ncore_retrieve( $attributes, 'has_not_product' );
        $logged_in       = ncore_retrieve( $attributes, 'logged_in' );
        $mode            = ncore_retrieve( $attributes, 'mode' );

        $is_condition_true = true;
        if ($has_product
            && !$this->hasProduct( $has_product )) {
            $is_condition_true = false;
        }

        if ($has_not_product
            && $this->hasProduct( $has_not_product )) {
            $is_condition_true = false;
        }

        if ($logged_in) {
            $is_user_logged_in = ncore_isLoggedIn();
            $logged_in = trim( strtolower($logged_in));
            switch ($logged_in) {
                case 'y':
                case 'yes':
                    if (!$is_user_logged_in) {
                        $is_condition_true = false;
                    }
                    break;
                case 'n':
                case 'no':
                    if ($is_user_logged_in) {
                        $is_condition_true = false;
                    }
                    break;
                case '':
                    break;
                default:
                    return $this->shortcodeError( _digi( 'Invalid value for parameter "%s". Allowed values: %s', 'logged_in', 'yes,no' ));
            }
        }

        if ($mode) {
            switch ($mode)
            {
                case 'else':
                    if ($this->last_condition_matched) {
                        return '';
                    }
                    break;
                case 'finally':
                    if ($this->any_condition_matched) {
                        return '';
                    }
                    break;
                default:
                    return $this->shortcodeError( _digi( 'Invalid value for parameter "%s". Allowed values: %s', 'mode', 'else,finally' ));
            }
        }

        // $contents = $this->removeShortcodeComment( $contents);

        $contents = do_shortcode( $contents );


        if ($is_condition_true) {
            $this->last_condition_matched = true;
            $this->any_condition_matched  = true;
        }
        else
        {
           $this->last_condition_matched = false;
           return '';
        }

        return $contents;
    }

    public function shortcodePreview( /** @noinspection PhpUnusedParameterInspection */ $attributes=array() )
    {
        return '<!-- ' . $this->shortcode( 'preview' ) . ' -->';
    }

    public function shortocdeExamCertificate( $attributes=array() )
    {
        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        if (!$model->canUseExams())
        {
            return '';
        }

        /** @var digimember_UserExamCertificateController $controller */
        $controller = $this->api->load->controller( 'user/exam_certificate', $attributes );

        ob_start();
        $controller->dispatch();
        return ob_get_clean();
    }

    public function shortcodeWaiverDeclaration( $attributes=array() )
    {
        /** @var digimember_UserWaiverDeclarationController $controller */
        $controller = $this->api->load->controller( 'user/waiver_declaration', $attributes );

        ob_start();
        $controller->dispatch();
        return ob_get_clean();
    }

    public function shortcodeExam( $attributes=array() )
    {
        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        if (!$model->canUseExams())
        {
            return '';
        }

        /** @var digimember_UserExamController $controller */
        $controller = $this->api->load->controller( 'user/exam', $attributes );

        ob_start();
        $controller->dispatch();
        return ob_get_clean();
    }

    public function shortcodeInvoices( /** @noinspection PhpUnusedParameterInspection */ $attributes=array() )
    {
        $api   = ncore_api();
        /** @var digimember_UserProductData $user_product_model */
        $user_product_model = $api->load->model( 'data/user_product' );
        /** @var digimember_ProductData $product_model */
        $product_model = $api->load->model( 'data/product' );
        $orders = $user_product_model->getForUser( $user='current', $order_by = 'id DESC' );

        if (!$orders) {
            return '';
        }

        /** @var digimember_DigistoreConnectorLogic $digistoreConnectorLogic */
        $digistoreConnectorLogic = $api->load->model( 'logic/digistore_connector' );
        $api->load->helper( 'date' );

        $have_invoice = false;

        $hl = _digi( 'Your %s invoices', 'Digistore24' );

        $html = "
<div class='digistore_invoices'>
    <h3>$hl</h3>";

        try
        {
            $ds24 = $digistoreConnectorLogic->ds24();

            foreach ($orders as $order)
            {
                $order_id = ncore_washText( str_replace( ' ', '', $order->order_id ) );

                if (!$order_id) {
                    continue;
                }

                $product = $product_model->get( $order->product_id );

                try {
                    $data = $ds24->listInvoices( $order_id );
                }
                catch (Exception $e) {
                    $data = null;
                }

                if (empty( $data->invoice_list )) {
                    continue;
                }

                $hl = _digi( 'Order %s', $order_id );
                if ($product) {
                    $hl .= ' - ' . $product->name;
                }
                $html .= "<h4>$hl</h4><ul>";

                foreach ($data->invoice_list as $invoice)
                {
                    $have_invoice = true;

                    $url = $invoice->invoice_url;

                    $label = $invoice->invoice_label;

                    $pay_method = $invoice->pay_method_msg
                                ? ' - <span class="dm_ds24_invoice_paymethod">' . $invoice->pay_method_msg . '</span>'
                                : '';


                    $html .= "<li><a href='$url' target='_blank' class='dm_ds24_invoice_link'>$label</a>$pay_method</li>";
                }

                $html .= '</ul>';
            }
        }
        catch (Exception $e)
        {
            $msg = $e->getMessage();

            return $this->shortcodeError( $msg );
        }

        $html .= "</div>";

        if (!$have_invoice) {
            return '';
        }

        return $html;
    }


    public function shortcodeDigistoreDownload( $attributes=array() )
    {
        $product_ids = trim(ncore_retrieve( $attributes, 'product', '' ));
        $show_texts  = $this->isBoolAttributeSet( $attributes, 'show_texts' );
        $icon_type   = ncore_retrieve( $attributes, 'icon', 'download' );

        $product_ids = $product_ids
                     ? explode( ',', $product_ids )
                     : false;


        $api = ncore_api();
        /** @var digimember_UserProductData $model */
        $model = $api->load->model( 'data/user_product' );
        $orders = $model->getForUser();

        $order_ids = array();

        foreach ($orders as $one)
        {
            $product_id = (int) $one->product_id;

            $is_match = !$product_ids
                     || in_array( $product_id, $product_ids );

            if (!$is_match) {
                continue;
            }

            $order_id = ncore_washText( str_replace( ' ', '', $one->order_id ) );

            if ($order_id) {
                $order_ids[] = $order_id;
            }
        }
        if (!$order_ids) {
            return '';
        }

        try
        {

            /** @var digimember_DigistoreConnectorLogic $ds24 */
            $ds24 = $this->api->load->model( 'logic/digistore_connector' );

            $data = $ds24->getPurchaseDownloads( $order_ids );
        }
        catch (Exception $e)
        {
            $msg = $e->getMessage();

            return $this->shortcodeError( $msg );
        }

        $html = '';

        $api->load->helper( 'format' );

        foreach( (array) $data->downloads as $purchase_id => $product_files)
        {
            foreach( (array) $product_files as $product_id => $files)
            {
                foreach( (array) $files as $file)
                {
                    if ($show_texts && !empty( $file->headline))
                    {
                        $html .= "<h3>$file->headline</h3>";
                    }
                    if ($show_texts && !empty( $file->instructions))
                    {
                        $html .= ncore_paragraphs( $file->instructions, array( 'use_double_linkbreaks' => true ) );
                    }

                    switch ($icon_type)
                    {
                        case 'none':
                            $icon_url = false;
                            break;

                        case 'file':
                            $icon_url = ncore_FileTypeIconUrl( $file->file_ext, 'm' );
                            break;

                        case 'download':
                        default:
                            $icon_url = ncore_imgUrl( 'icon/download.png' );
                    }

                    $icon = $icon_url
                          ? "<img src='$icon_url' alt='$file->file_ext' />"
                          : '';

                    $is_access_granted = ncore_isTrue( $file->is_access_granted );
                    $is_purchase_paid  = ncore_isTrue( $file->is_purchase_paid );

                    $css_disabled = $is_access_granted
                                  ? ''
                                  : 'digimember_download_disabled';

                    if ($is_access_granted)
                    {
                        $js = "location.href='$file->url'";
                    }
                    else
                    {
                        $msg = $is_purchase_paid
                               ? _digi( 'Your download limits are exceeded.|The files cannot be downloaded any more.' )
                               : _digi( 'The order is not paid completely, so the download is not possible.' );

                        $msg = str_replace( array( "'", '|' ), array( "\\'", "\\n\\n" ), $msg );
                        $js = "alert('$msg');";
                    }

                    $msg = ncore_formatDataSize( $file->file_size );

                    $msg .= ' - ' . _digi( 'available until %s', ncore_formatDate( $file->download_until ) );

                    $is_download_limited = $file->downloads_total > 0;
                    if ($is_download_limited)
                    {
                        $left = $file->downloads_total - $file->downloads_tries;
                        $msg .= ' - ' . _digi( '%s of %s downloads left', $left, $file->downloads_total );
                    }

                    $href = $is_access_granted
                          ? "href='$file->url'"
                          : '';

                    $button = "<a $href onclick=\"$js; return false;\" class='digimember_download $css_disabled'>$icon$file->file_name</a>";

                    $html .= "<p class='digistore_download_file'>$button<br /><span class='digistore_download_note'>$msg</span></p>";
                }
            }
        }

        return $html
               ? "<div class='digistore_download'>$html</div>"
               : '';
    }

    public function shortcodeDownloadsleft( $attributes=array() )
    {
        $url = ncore_retrieve( $attributes, 'url' );

        $url = str_replace( '&amp;', '&', $url );

        if (!$url)
        {
            return $this->shortcodeErrorMissingArg( 'url' );
        }

        /** @var digimember_DownloadData $model */
        $model = $this->api->load->model( 'data/download' );

        $left = $model->downloadsLeft( $url );

        if ($left===false) {
            return _digi( 'unlimited' );
        }

        return $left;
    }

    public function shortcodeDownload( $attributes=array() )
    {
        $url       = ncore_retrieve( $attributes, 'url' );

        $url = str_replace( '&amp;', '&', $url );


        if (!$url)
        {
            return $this->shortcodeError( _ncore( 'URL is required.') );
        }

        /** @var digimember_DownloadData $model */
        $model = $this->api->load->model( 'data/download' );
        $left = $model->downloadsLeft( $url );

        $can_download = $left > 0 || $left === false || ncore_canAdmin();
        if (!$can_download)
        {
            static $access_checked;
            if (!isset($access_checked))
            {
                $access_checked = true;
                $page_id = get_the_ID();
                $page = get_post( $page_id );
                /** @var digimember_PageProductData $pageProductData */
                $pageProductData = $this->api->load->model( 'data/page_product' );

                $is_protected = (bool) $pageProductData->getForPage( $page->post_type, $page->ID, $active_only=true);

                if (!$is_protected)
                {
                    $model->checkAccess();
                }
            }
        }

        /** @var digimember_DownloadLogic $model */
        $model = $this->api->load->model( 'logic/download' );
        $masked_url = $model->protectedUrl( $url );

        return $this->_shortcodeUrl( $masked_url, $attributes );
    }

    public function shortcodeRenew( $attributes=array() )
    {
        return $this->_DS24predefinedUrl( 'ds24_renew_url', $attributes );
    }

    public function shortcodeReceipt( $attributes=array() )
    {
        return $this->_DS24predefinedUrl( 'ds24_receipt_url', $attributes );
    }

    public function shortcodeAdd( $attributes=array() )
    {
        return $this->_DS24predefinedUrl( 'ds24_add_url', $attributes );
    }

//    public function shortcodeBecomeAffiliate( $attributes=array() )
//    {
//        return $this->_DS24predefinedUrl( 'ds24_become_affiliate_url', $attributes );
//    }


    public function shortcodeDS24Orderform( $attributes=array() )
    {

        $id       = ncore_retrieve( $attributes, 'id'      );
        $product  = ncore_retrieve( $attributes, 'product' );
        $width    = ncore_retrieve( $attributes, 'width', 0 );
        $base_url = rtrim( ncore_retrieve( $attributes, 'baseurl' ), '/ ' );

        unset( $attributes[ 'id' ] );
        unset( $attributes[ 'product' ] );
        unset( $attributes[ 'width' ] );

        if (!$id)
        {
            return $this->shortcodeErrorMissingArg( 'id' );
        }
        if (!$product)
        {
            return $this->shortcodeErrorMissingArg( 'product' );
        }

        $width = intval( $width );
        if ($width >=100 && $width<=10000) {
            $css_width = '' . $width . 'px';
        }
        else {
            $css_width = '100%';
        }

        $args = '';
        foreach ($attributes as $k => $v) {
            $args .= $args? '&' : '?';

            $args .= ncore_washText($k) . '=' . ncore_washText($v);
        }

        if (!$base_url ) {
            $base_url = NCORE_DEBUG
                ? 'http://ds24.de'
                : 'https://www.digistore24.com';
        }

        $html = "<script src='$base_url/service/js/orderform_widget.js'></script><script>const DS24_ORIGIN='$base_url';</script>
<iframe class='ds24_payIFrame' style='overflow: hidden; width: $css_width; height: 600px; border: none; background: transparent;' src='$base_url/product/$product/$id$args'></iframe>";

        return $html;
    }

    public function shortcodeDS24Countdown( $attributes=array() )
    {
        $id     = ncore_retrieve( $attributes, 'id' );
        $key    = ncore_retrieve( $attributes, 'key' );

        if (!$id)
        {
            return $this->shortcodeErrorMissingArg( 'id' );
        }
        if (!$key)
        {
            return $this->shortcodeErrorMissingArg( 'key' );
        }

        $base_url = NCORE_DEBUG
                  ? 'http://ds24.de'
                  : 'https://www.digistore24.com';

        $url = "$base_url/countdown/$id/$key.js";

        $html = "<div><script src=\"$url\"></script></div>";

        return $html;
    }

    public function shortcodeDS24Socalproof( $attributes=array() )
    {
        $id     = ncore_retrieve( $attributes, 'id' );
        $key    = ncore_retrieve( $attributes, 'key' );
        $height = ncore_retrieve( $attributes, 'height' );
        $width  = ncore_retrieve( $attributes, 'width' );
        $type   = ncore_retrieve( $attributes, 'type', 'iframe' );
        $lang   = ncore_retrieve( $attributes, 'lang' );

        if (!$id)
        {
            return $this->shortcodeErrorMissingArg( 'id' );
        }
        if (!$key)
        {
            return $this->shortcodeErrorMissingArg( 'key' );
        }
        if (!$height)
        {
            return $this->shortcodeErrorMissingArg( 'height' );
        }
        if (!$width)
        {
            return $this->shortcodeErrorMissingArg( 'width' );
        }

        $is_dropin = $type == 'dropin';

        $base_url = 'https://www.digistore24.com';

        $url = "$base_url/socialproof/$id/$key/$height/$width";

        if ($lang && $lang != 'XX') {
            $url .= "/$lang";
        }

        if ($is_dropin)
        {
            $html = "<script src=\"$url.js\" defer></script>";
        }
        else
        {
            $html = "<iframe src=\"$url\" height=\"${height}px\" width=\"${width}px\" allowtransparency='true' scrolling='no' frameborder='0'></iframe>";
        }

        return $html;
    }

    public function shortcodeDS24SmartUpgrade( $attributes=array() )
    {
        $user_id = ncore_userId();
        if (!$user_id) {
            return '';
        }

        $id     = ncore_retrieve( $attributes, 'id' );
        $key    = ncore_retrieve( $attributes, 'key' );

        if (!$id)
        {
            return $this->shortcodeErrorMissingArg( 'id' );
        }
        if (!$key)
        {
            return $this->shortcodeErrorMissingArg( 'key' );
        }

        /** @var digimember_AjaxShortcodeController $controller */
        $controller = $this->api->load->controller( 'ajax/shortcode' );

        $js = $controller->ds24smartupgradeJs( $id, $key );

        /** @var ncore_HtmlLogic $model */
        $model = $this->api->load->model( 'logic/html' );
        $model->jsOnLoad( $js );

        return '';
    }

    public function shortcodeUpgrade( $attributes=array() )
    {
        $upgrade_id = ncore_retrieve( $attributes, 'id' );
        if (!$upgrade_id)
        {
            return '';
        }

        $api = ncore_api();
        /** @var digimember_UserProductData $model */
        $model = $api->load->model( 'data/user_product' );

        $upgrade_id = urlencode( $upgrade_id );

        $order_id='';
        $upgrade_key='';

        $orders = $model->getForUser();
        foreach ($orders as $one)
        {
            $one_order_id = urlencode( ncore_washText( str_replace( ' ', '', $one->order_id ) ) );

            if (!$one_order_id) continue;

            if (!$one->ds24_upgrade_key) continue;

            if ($order_id) $order_id .= ',';

            $order_id .= $one_order_id;

            if ($upgrade_key) $upgrade_key .= ',';

            $upgrade_key .= urlencode( $one->ds24_upgrade_key );
        }

        // $have_order_id_in_cookie = isset($_COOKIE['digimember_order_id']) && is_string($_COOKIE['digimember_order_id']) && !is_numeric( $_COOKIE['digimember_order_id'] );
        $have_order_id_in_url = isset($_GET['order_id']) && is_string($_GET['order_id']) && !is_numeric( $_GET['order_id'] );

        $request_order_ids_comma_seperated = '';
        // if ($have_order_id_in_cookie) {
        //     $request_order_ids_comma_seperated .= ',' . ncore_washText( $_COOKIE['digimember_order_id'], ',', '<>;' );
        // }
        if ($have_order_id_in_url) {
            $request_order_ids_comma_seperated .= ',' . ncore_washText( $_GET['order_id'], ',', '<>;' );
        }

        $request_order_ids = array_unique( explode( ',', $request_order_ids_comma_seperated ) );

        foreach ($request_order_ids as $one)
        {
            $one = trim($one);
            if (strlen($one)>=6)
            {
                if ($order_id) $order_id .= ',';

                $order_id .= $one;
            }
        }

        if (!$order_id) {
            return '';
        }

        $ds24url = NCORE_DEBUG
                  ? 'http://ds24.de'
                  : 'https://www.digistore24.com';

        $url = "$ds24url/upgrade/$upgrade_id/$order_id/$upgrade_key";

        return $this->_shortcodeUrl( $url, $attributes );
    }

    public function shortcodeEmail( /** @noinspection PhpUnusedParameterInspection */ $attr = array() ) {

        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );
        list( $user_id/*, $password*/ ) = $model->getUsernameAndPasswordOfThankyouPageVisitor();
        if ($user_id) {
            $user = ncore_getUserById( $user_id );
            return $user->user_email;
        }

        $this->maybeHandleAutojoin();

        $email = $this->_get_user_email();

        return $email;
    }


    public function shortcodeUsername( /** @noinspection PhpUnusedParameterInspection */ $attr = array() ) {

        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );
        list( $user_id/*, $password*/ ) = $model->getUsernameAndPasswordOfThankyouPageVisitor();
        if ($user_id) {
            $user = ncore_getUserById( $user_id );
            return $user->user_login;
        }

        $this->maybeHandleAutojoin();

        $username = $this->_get_user_login();

        return $username;
    }

    public function shortcodePassword( $attr = array() ) {

        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );
        list( $user_name, $password ) = $model->getUsernameAndPasswordOfThankyouPageVisitor();
        if ($user_name) {
            if ($password) {
                return $password;
            }

            $no_pw_text = isset( $attr['no_pw_text'] )
                    ? $attr['no_pw_text']
                    : '<em class="ncore_hidden_pw">('._digi('Hidden for security reasons' ).')</em>';
            return $no_pw_text;
        }

        $this->maybeHandleAutojoin();

        if (!isset($this->user_data_cache[ 'password' ]))
        {
            $user_id = ncore_userId();

            /** @var digimember_UserData $model */
            $model = $this->api->load->model( 'data/user' );
            $stored_password = $model->getPassword($user_id);

            $this->user_data_cache[ 'password' ] = $stored_password;
        }

        if ($this->user_data_cache[ 'password' ]) {
            return $this->user_data_cache[ 'password' ];
        }

        if (!$this->shortcodeUsername()) {
            return '';
        }

        $no_pw_text = isset( $attr['no_pw_text'] )
                    ? $attr['no_pw_text']
                    : '<em class="ncore_hidden_pw">('._digi('Hidden for security reasons' ).')</em>';

        return $no_pw_text;
    }

    public function shortcodeFirstname( $attr = array() ) {

        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );
        list( $user_id/*, $password */) = $model->getUsernameAndPasswordOfThankyouPageVisitor();
        if ($user_id) {
            $user = get_userdata( $user_id );
            return $user->first_name;
        }

        $this->maybeHandleAutojoin();

        list( $first_name/*, $last_name */) = $this->_get_first_and_last_name();

        return $this->_renderSpaces( $attr, $first_name );
    }

    public function shortcodeCustomfield($attr = array()) {
        $values = array();
        $current_user = wp_get_current_user();
        $userSettingsObject = $this->api->load->model('data/user_settings');
        $customfieldsObject = $this->api->load->model('data/custom_fields');
        if ($cfId = ncore_retrieve($attr,'customfield',false)) {
            $attrCustomField = $customfieldsObject->get($cfId);
            $value = $userSettingsObject->getForUser($current_user->ID, 'customfield_'.$cfId);
            if ($attrCustomField->type == 'select') {
                $value = $customfieldsObject->resolveSelection($attrCustomField->content_type, $value, $attrCustomField->content);
            }
            return $this->_renderSpaces( $attr, $value );
        }
        else {
            $output = '';
            $customFields = $customfieldsObject->getCustomFieldsBySectionList(array("account", "general"), true);
            foreach ($customFields as $customfield) {
                $value = $userSettingsObject->getForUser($current_user->ID, 'customfield_'.$customfield->id);
                if ($value) {
                    if ($customfield->type == 'select' && $customfield->content_type == 'country') {
                        $countryList = $customfieldsObject->resolveCountrySelectContent();
                        if (array_key_exists($value, $countryList)) {
                            $value = $countryList[$value];
                        }
                    }
                    $values[$customfield->label] = $value;
                }
            }
            foreach ($values as $label => $content) {
                $output .= '<p>'.$label.': '.$content.'</p>';
            }
            return $output;
        }
    }

    public function shortcodeAdvancedSignupForm($attr = array()) {
        if ($asfId = ncore_retrieve($attr,'id',false)) {
            $htmlLogic = $this->api->load->model('logic/html');
            $htmlLogic->loadPackage('advanced-signup-forms-frontend.js');
            $htmlLogic->loadPackage('dm-frontend-styles.css');

            $formId = $asfId;
            $locale = substr(get_locale(), 0, 2);
            $linkLogic = $this->api->load->model('logic/link');
            $ajaxUrl = $linkLogic->ajaxUrl('admin/advanced_signup_forms', 'ajaxRequest');

            $output = '';
            $output .= '<div id="dm-advanced-signup-forms-frontend"></div>';
            $output .= '<script>';
            $output .= 'var advancedformsData = {';
            $output .= 'formId: '.$formId.',';
            $output .= 'locale: "'.$locale.'",';
            $output .= 'ajaxUrl: "'.$ajaxUrl.'",';
            $output .= '};';
            $output .= '</script>';
            return $output;
        }
        return '';
    }

    public function shortcodeAdvancedForms($attr = array()) {
        if ($asfId = ncore_retrieve($attr,'id',false)) {
            $htmlLogic = $this->api->load->model('logic/html');
            $htmlLogic->loadPackage('advanced-forms-frontend.js');
            $htmlLogic->loadPackage('dm-frontend-styles.css');

            $formId = $asfId;
            $locale = substr(get_locale(), 0, 2);
            $linkLogic = $this->api->load->model('logic/link');
            $ajaxUrl = $linkLogic->ajaxUrl('admin/advanced_forms_edit', 'ajaxRequest');
            $advancedFormsController = $this->api->load->controller('admin/advanced_forms_edit');
            $translations = json_encode($advancedFormsController->getTranslations());

            $output = '';
            $output .= '<div id="dm-advanced-forms-frontend"></div>';
            $output .= '<script>';
            $output .= 'var advancedformsData = {';
            $output .= 'formId: '.$formId.',';
            $output .= 'locale: "'.$locale.'",';
            $output .= 'translations: '.$translations.',';
            $output .= 'ajaxUrl: "'.$ajaxUrl.'",';
            $output .= '};';
            $output .= '</script>';
            return $output;
        }
        return '';
    }

    public function shortcodeCancel( $attributes=array() )
    {
        /** @var digimember_UserLoginFormController $controller */
        $controller = $this->api->load->controller( 'user/cancel', $attributes );
        ob_start();
            $controller->dispatch();
        return ob_get_clean();
    }

    public function shortcodeLastname( $attr = array() ) {

        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );
        list( $user_id/*, $password */) = $model->getUsernameAndPasswordOfThankyouPageVisitor();
        if ($user_id) {
            $user = get_userdata( $user_id );
            return $user->last_name;
        }

        $this->maybeHandleAutojoin();

        /** @noinspection PhpUnusedLocalVariableInspection */
        list( $first_name, $last_name ) = $this->_get_first_and_last_name();
        return $this->_renderSpaces( $attr, $last_name );
    }

    public function shortcodeLoginkey( /** @noinspection PhpUnusedParameterInspection */ $attr = array() ) {

        $this->maybeHandleAutojoin();

        if (!isset($this->user_data_cache[ 'loginkey' ]))
        {
            $user_id = ncore_userId();
            /** @var digimember_LoginkeyData $model */
            $model = $this->api->load->model( 'data/loginkey' );
            $this->user_data_cache[ 'loginkey' ] = $model->getForUser( $user_id );
        }

        return $this->user_data_cache[ 'loginkey' ];
    }


    public function shortcodeAutojoin( $attr = array(), $contents='' ) {

        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use_autojoin = $model->canUseAutoJoin();

        if (!$can_use_autojoin)
        {
            /** @var digimember_LinkLogic $link */
             $link = $this->api->load->model( 'logic/link' );

            return "<div='ncore_upgrade_hint'>"
                  . _digi( 'To automatically add new autoresponder contacts to your site, upgrade to %s.', $this->api->pluginNamePro() )
                  . ' '
                  . $link->upgradeButton(_digi( 'Upgrade now!' ) )
                  . '</div>';
        }

        $autoresponder_id = ncore_retrieve( $attr, 'autoresponder', 'auto' );
        $product_ids      = ncore_retrieve( $attr, 'product' );

        $do_login       = $this->isBoolAttributeSet( $attr, 'do_login' );
        $do_show_errors = $this->isBoolAttributeSet( $attr, 'show_errors' );

        /** @var digimember_AutoresponderHandlerLib $lib */
        $lib = $this->api->load->library( 'autoresponder_handler' );

        try
        {
            list( $user_id, $username, $password, $loginkey, $firstname, $lastname ) = $lib->handleAutojoin( $autoresponder_id, $product_ids );

            if ($username) {
                $this->have_autojoin_account_creation = true;

                if ($do_login && $user_id)
                {
                    ncore_setSessionUser( $user_id );
                }
            }

            $this->_get_first_and_last_name( $firstname, $lastname );

            $this->_get_user_login( $username );
            $this->_get_user_email( $username );

            if (empty($this->user_data_cache[ 'loginkey' ])) $this->user_data_cache[ 'loginkey' ]  = $loginkey;
            if (empty($this->user_data_cache[ 'password' ])) $this->user_data_cache[ 'password' ]  = $password;


        }
        catch (Exception $e)
        {
            return $do_show_errors
                   ? $this->shortcodeError( $e->getMessage() )
                   : '';
        }

        if (empty($contents)) {
            return '';
        }

        if (!$this->have_autojoin_account_creation) {
            return '';
        }

//      SECURITY HOLE: if s.o. know the email and guesses the contact id, he could retrieve the password:
//        if ($user_id && !$password) {
//            $model = $this->api->load->model( 'data/user' );
//            $password = $model->getPassword($user_id);
//        }

//        if (!$username) {
//            $username = $this->_get_user_login();
//            $password = $model->getPassword($user_id);
//        }

        $find = array( '[username]','['.self::prefix.'_username]', '[password]','['.self::prefix.'_password]' );
        $repl = array( $username, $username, $password, $password );

        $contents = str_replace( $find, $repl, $contents );

        // $contents = $this->removeShortcodeComment( $contents);

        return do_shortcode( $contents );
    }

    public function tagProductsShortcode()
    {
        $tag = $this->shortCode( 'products' );
        return $this->renderTag( $tag );
    }

    public function shortcodeSubscriptions( $attributes=array(), $content='' )
    {
        $attributes[ 'layout' ] = 'checkbox_list';

        return $this->shortcodeWebpush( $attributes, $content );
    }


    public function shortcodeWebpush($attributes=array(), /** @noinspection PhpUnusedParameterInspection */ $content='' )
    {
        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        if (!$model->canUsePushNotifications())
        {
            return '';
        }

        /** @var digimember_UserWebpushController $controller */
        $controller = $this->api->load->controller( 'user/webpush', $attributes );

        ob_start();
        $controller->dispatch();
        return ob_get_clean();
    }

    public function shortcodeCourseLectureProgress( $attributes=array() )
    {
        $for        = ncore_retrieveAndUnset( $attributes, 'for',     'course' );
        $product_id = ncore_retrieveAndUnset( $attributes, 'product', 'current' );
        $color      = ncore_retrieveAndUnset( $attributes, 'color',   '#2196F3' );
        $bg         = ncore_retrieveAndUnset( $attributes, 'bg',      '#505050' );
        $radius     = (int) ncore_retrieveAndUnset( $attributes, 'round',  0 );
        $rate       = ncore_retrieveAndUnset( $attributes, 'progress', 'auto' );


        if ($rate==='auto')
        {
            $show_module = $for === 'module';

            /** @var digimember_CourseLogic $courseLogic */
            $courseLogic = $this->api->load->model( 'logic/course' );
            $rec = $courseLogic->getCourseProgress( $product_id );

            if (!$rec) {
                return '';
            }

            $stats = $show_module
                   ? $rec[ 'module' ]
                   : $rec[ 'course' ];

            $completed_count = $stats[ 'completed_count' ];
            $total_count     = $stats[ 'total_count' ];
            $progress_rate   = $stats[ 'progress_rate' ];

            $details_text = $show_module
                          ? ($completed_count == 1
                              ? _digi( '%s of %s lecture of this module completed', $completed_count, $total_count  )
                              : _digi( '%s of %s lectures of this module completed', $completed_count, $total_count  ))
                          : ($completed_count == 1
                              ? _digi( '%s lecture of %s completed', $completed_count, $total_count  )
                              : _digi( '%s lectures of %s completed', $completed_count, $total_count  ));
        }
        else
        {
            $progress_rate = min( 100, max( 0, intval( $rate ) ) );
            $details_text  = '';
        }

        $rate_disp = round( $progress_rate ) . ' %';

        $outer_radius = $radius;
        $padding      = 7;
        $inner_radius = max( 0, $radius - $padding );

        $progress_rate = round(max(1,$progress_rate));

        return "<div title=\"$details_text\" class='digimember_lecture_progress' style='padding: {$padding}px; width: 100%; background-color: $bg; border-radius: ${outer_radius}px;'><div style='border-radius: ${inner_radius}px; width: ${progress_rate}%; height: 100%; background-color: $color; overflow: visible; white-space: nowrap;'>$rate_disp</div></div>";
    }

    public function shortcodeCourseLectureButtons( $attributes=array() )
    {
        $have_2nd_level = $this->isBoolAttributeSet( $attributes, '2nd_level' );
        $color          = ncore_retrieveAndUnset( $attributes, 'color', 'white' );
        $bg             = ncore_retrieveAndUnset( $attributes, 'bg', '' );
        $radius         = (int) ncore_retrieveAndUnset( $attributes, 'round',  0 );
        $align          = ncore_washText( ncore_retrieveAndUnset( $attributes, 'align', '' ) );


        $product_id = ncore_retrieveAndUnset( $attributes, 'product', 'current' );

        /** @var digimember_CourseLogic $courseLogic */
        $courseLogic = $this->api->load->model( 'logic/course' );

        list( $start_of_course, $prev_module, $prev_lecture, $next_lecture, $next_module, $end_of_course ) = $courseLogic->getLectureNavLinks( $product_id );

        if (!$start_of_course) {
            return '';
        }

        $button_count = 2;

        $have_modules = $have_2nd_level && ($prev_module || $next_module);

        $start_of_course = $this->_shortcodeCourseGotoLink( 'start_of_course', $start_of_course, $is_first=true,  $is_last=false, $color, $bg, $radius );
        $end_of_course   = $this->_shortcodeCourseGotoLink( 'end_of_course',   $end_of_course,   $is_first=false, $is_last=true,  $color, $bg, $radius);

        if ($have_modules)
        {
            $button_count += 2;

            $prev_module     = $this->_shortcodeCourseGotoLink( 'prev_module',     $prev_module, $is_first=false, $is_last=false, $color, $bg, $radius );
            $next_module     = $this->_shortcodeCourseGotoLink( 'next_module',     $next_module, $is_first=false, $is_last=false, $color, $bg, $radius );
        }
        else
        {
            $prev_module = '';
            $next_module = '';
        }

        $button_count += 2;

        $prev_lecture = $this->_shortcodeCourseGotoLink( 'prev_lecture', $prev_lecture, $is_first=false, $is_last=false, $color, $bg, $radius );
        $next_lecture = $this->_shortcodeCourseGotoLink( 'next_lecture', $next_lecture, $is_first=false, $is_last=false, $color, $bg, $radius );

        $align_attr = $align
                    ? "style='text-align: $align; width: 100%;'"
                    : '';



        return "<div $align_attr class='digimember_lecture_nav digimember_lecture_nav_${button_count}_buttons'>$start_of_course$prev_module$prev_lecture$next_lecture$next_module$end_of_course</div>";
    }

    public function shortcodeCourseMenu( $attributes=array() )
    {
        $menu_legacy = ncore_retrieveAndUnset( $attributes, 'menu');
        $what        = ncore_retrieveAndUnset( $attributes, 'what');
        $depth       = ncore_retrieveAndUnset( $attributes, 'depth', '0');
        /** @var digimember_CourseLogic $courseLogic */
        $courseLogic = $this->api->load->model( 'logic/course' );

        if ($menu_legacy)
        {
            $menu = $menu_legacy;
            $type = 'wordpress';
        }
        elseif ($what)
        {
            list( $type, $menu ) = ncore_retrieveList( '_', $what, 2, true  );
            switch ($type)
            {
                case 'menu':
                    $type = 'wordpress';
                    break;

                case 'product':
                    $type = 'ncore';
                    $menu = $courseLogic->getLectureMenu( $menu );
                    break;

                default:
                    return '';
            }
        }
        else
        {
            $menu = array();
            $type = '';
        }

        switch ($type)
        {
            case 'wordpress':
                $params = shortcode_atts(array(
                    'menu'            => '',
                    'container'       => 'div',
                    'container_class' => 'digimember_menu',
                    'container_id'    => '',
                    'menu_class'      => 'menu',
                    'menu_id'         => '',
                    'echo'            => false,
                    'fallback_cb'     => '',
                    'before'          => '',
                    'after'           => '',
                    'link_before'     => '',
                    'link_after'      => '',
                    'depth'           => 0,
                    'walker'          => '',
                    'theme_location'  => ''),
                    $attributes);

                $params[ 'menu' ] = $menu;

                return wp_nav_menu( $params );

            case 'ncore':
                $return = $courseLogic->renderLectureMenu( $menu );
                if ($depth > 0)  {
                    $menuId = ncore_id();
                    $return = '<div id="' . $menuId . '">' . $return . '</div>';
                    $return .= '<style>';
                    for ($i=$depth;$i < 10;$i++) {
                        $return .= '#' . $menuId . ' ul.digimember-depth-' . $i . ' { display: none; }';
                    }
                    $return .= '</style>';
                }
                return $return;

            default:
                return '';
        }
    }

	public function shortcodeSignUp( $attributes=array() )
	{
		$attributes = $this->santizeAttributes( $attributes );

		$have_product = (bool) ncore_retrieve( $attributes, 'product' );
		if (!$have_product)
		{
		 	return $this->shortcodeError( _digi( 'Parameter %s is required', '<strong>product</strong>' ) );
		}

        $must_validate_product = ncore_canAdmin();
		if ($must_validate_product)
		{
			$messages = array();

			/** @var digimember_ProductData $model */
			$model = $this->api->load->model( 'data/product' );
			$product_ids_comma_seperated = ncore_retrieve( $attributes, 'product' );

			$product_ids = explode( ',', $product_ids_comma_seperated );

			foreach ($product_ids as $product_id)
			{
				if ($product_id === 'demo' || empty($product_id))
				{
					continue;
				}

				$product = $model->get( $product_id );
				if (!$product)
				{
					$plugin_name = ncore_api()->pluginDisplayName();
					$messages[] = _digi( 'Invalid %s product id: #%s', $plugin_name, $product_id );
					continue;
				}

				if ($product->status != 'published')
				{
					$messages[] = _digi( 'Product id #%s "<em>%s</em>" is not published.',$product_id, $product->name );
					continue;
				}
			}

			if ($messages)
			{
				return $this->shortcodeError( $messages );
			}
		}

		/** @var digimember_UserSignupFormController $controller */
		$controller = $this->api->load->controller( 'user/signup_form', $attributes );

		ob_start();
		$controller->dispatch();
		return ob_get_clean();
	}

    public function shortcodeCancelForm( $attributes=array() )
    {
        $attributes = $this->santizeAttributes( $attributes );

        $cancel_email = (bool) ncore_retrieve( $attributes, 'cancel_email' );
        if (!$cancel_email)
        {
            $warningField = _digi('Admin email for cancellation');
            return $this->shortcodeError( _digi( 'Parameter %s is required', '<strong>'.$warningField.'</strong>' ) );
        }

        /** @var digimember_UserSignupFormController $controller */
        $controller = $this->api->load->controller( 'user/cancel_form', $attributes );

        ob_start();
        $controller->dispatch();
        return ob_get_clean();
    }

	public function shortcodeAccountEdit( $attributes=array() )
	{
		$logged_in = is_user_logged_in();
		if (!$logged_in)
		{
			return '';
		}

		/** @var digimember_UserAccountEditorController $controller */
		$controller = $this->api->load->controller( 'user/account_editor', $attributes );

		ob_start();
		$controller->dispatch();
		return ob_get_clean();
	}

	public function shortcodeOpLockedHint()
	{
	    /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->shortcode('op_locked_hint');
		return '<!-- ' . $shortcode . ' -->';
	}

    public function shortcodeOpShowAlways()
    {
        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );
        $shortcode = $controller->shortcode('op_show_always');
        return '<!-- ' . $shortcode . ' -->';
    }

    public function shortcodeWebinar( $attributes=array() )
    {
        $settings = array(
            'arg_last_email' => 'email',
            'arg_first_name' => 'fname',
            'arg_last_name'  => 'lname',
            'height'         => '380',
            'width'          => '520',
            'extra_args'     => array( 'secret' => 'true', 'readonly' => 'true' ),
        );

        $url             = ncore_retrieve( $attributes, 'url' );
        $override_width  = ncore_retrieve( $attributes, 'width' );
        $override_height = ncore_retrieve( $attributes, 'height' );

        if ($url)
        {
            $url = str_replace( '&amp;', '&', $url );
        }
        else
        {
            foreach ($attributes as $one)
            {
                $one = str_replace( '&amp;', '&', $one );

                $url = filter_var($one, FILTER_VALIDATE_URL);
                if ($url)
                {
                    break;
                }
            }
        }
        if (!$url)
        {
            return $this->shortcodeError( _digi( 'URL required.' ) );
        }

        list( $first_name, $last_name ) = $this->_get_first_and_last_name();
        $email    = $this->_get_user_email();

        extract( $settings );
        /** @var string $arg_last_email */
        /** @var string $arg_first_name */
        /** @var string $arg_last_name */
        /** @var string $height */
        /** @var string $width */
        /** @var array $extra_args */

        $args = array();
        $args[ $arg_last_email ] = $email;
        $args[ $arg_first_name ] = $first_name;
        $args[ $arg_last_name]   = $last_name;

        foreach ($extra_args as $key => $value)
        {
            $have_arg = strpos( $url, $key .'=' ) !== false;
            if (!$have_arg)
            {
                $args[ $key ] = $value;
            }
        }

        $url = ncore_removeArgs( $url, array_keys( $args ), '&', false );

        $url = ncore_addArgs( $url, $args, '&amp;', true );

        if ($override_width)  $width  = $override_width;
        if ($override_height) $height = $override_height;

        return "<iframe src=\"$url\" height='$height' width='$width' scrolling='no' frameborder='0' align='middle' allowtransparency='true'></iframe>";
    }

    public function shortcodeInviteAffiliates( $attributes=array() )
    {
        return $this->_DS24constructedUrl( DIGIMEMBER_INVIATE_AFFILIATES_URL, $attributes );
    }

    public function shortcodeDigistoreAffiliateNameForUser( /** @noinspection PhpUnusedParameterInspection */ $attributes=array() )
    {
        return digimember_getDs24AffiliateName();
    }

	public function shortcodeLogin( $attributes=array() )
	{
		$logged_in = isset( $attributes['logged_in'] )
				   ? $attributes['logged_in']
				   : ncore_isLoggedIn();

        $facebook      = ncore_retrieve( $attributes, 'facebook' );
        $have_facebook = $facebook == 'only' || $facebook == 'also';
        $have_product  = (bool) ncore_retrieve( $attributes, 'fb_product' );
        if ($have_facebook && !$have_product)
        {
             return $this->shortcodeError( _digi( 'Parameter %s is required, if option %s is used.', '<strong>fb_product</strong>', '<strong>facebook</strong>' ) );
        }


		$hidden_if_logged_in   = $this->isBoolAttributeSet( $attributes, 'hidden_if_logged_in' );
        $redirect_if_logged_in = $this->isBoolAttributeSet( $attributes, 'redirect_if_logged_in' );

        if ($logged_in)
		{
            if ($redirect_if_logged_in)
            {
                $url = ncore_retrieve( $attributes, 'redirect_url' );
                if (!$url) {
                    $url = ncore_retrieve( $attributes, 'url' );
                }
                if (!$url)
                {
                    /** @var digimember_AccessLogic $model */
                    $model = $this->api->load->model( 'logic/access' );
                    $url   = $model->loginUrl();
                }

                if ($url && !ncore_isAdminArea())
                {
                    ncore_redirect( ncore_resolveUrl( $url ) );
                }
            }
			return $hidden_if_logged_in
				   ? ''
				   : $this->shortcodeLoginInfo( $attributes );
		}
		else
		{
		    /** @var digimember_UserLoginFormController $controller */
			$controller = $this->api->load->controller( 'user/login_form', $attributes );

			ob_start();
			$controller->dispatch();
			return ob_get_clean();
		}
	}

	public function shortcodeCounter( $attributes=array() )
	{
		$defaults = array(
			 'product' => 0,
			 'start' => 0,
		);

		extract( shortcode_atts( $defaults, $attributes ) );
		/** @var int $product */
		/** @var int $start */

		/** @var digimember_UserProductData $model */
		$model = $this->api->load->model( 'data/user_product' );
		$where = array();
		if ($product)
		{
			$where['product_id'] = $product;
		}
		$count = $model->getCount( $where );

		$count += $start;

		return $count;
	}

	public function shortcodeLoginInfo( $attributes=array() )
	{
		$url = ncore_retrieve( $attributes, 'url', ncore_currentUrl() );

		$inner_html = $this->renderLoginInfoInner( $url );

		return "<div class='digimember_login_info_container'>$inner_html</div>";
	}

	public function shortcodeLogout( $attributes=array() )
	{
        if (ncore_isInSidebar())
        {
            return $this->shortcodeError( _digi( 'The %s shortcode may not be used in widgets, because widgets are shown on every page.', 'logout' ) );
        }

        $is_non_user_call = ncore_isAdminArea() || ncore_isAjax();
        $is_logged_in     = ncore_isLoggedIn();

		$url = $this->_redirectUrl( $attributes );

		if ($is_non_user_call) {

            $redirect_url = false;
            $msg = 'ds_logout is disabled in admin area preview';

        } elseif ($is_logged_in)
		{
            $this->api->load->helper( 'url' );
			$redirect_url = ncore_logoutUrl( $url );

            $msg = 'ds_logout logged you out';
		}
		else
		{
			$redirect_url = $url;

            $msg = 'ds_logout noticed, that you are logged out';
		}

		$must_redirect = $redirect_url && ncore_CurrentUrl() != $redirect_url;
		if ($must_redirect)
		{
            if (headers_sent())
            {
                echo "<!-- DIGIMEMBER: $msg -->";
            }
            else
            {
                header( "x-digimember-info: $msg" );
            }
			ncore_redirect( $redirect_url );
		}

        return "<!-- DIGIMEMBER: $msg -->";
	}

	/**
     * function that creates the ds_products shortcode list
     */
	public function shortcodeProducts( /** @noinspection PhpUnusedParameterInspection */ $attributes=array() )
	{
	    /** @var digimember_UserProductData $model */
		$model = $this->api->load->model( 'data/user_product' );

		$user_id = ncore_userId();
		if (!$user_id)
		{
			return '';
		}

		$links = $model->getAccessableProductLinks( $user_id );

		return $this->_renderProductLinks( $links );
	}

    public function shortcodeGiveProduct( $attributes=array(), $output = '' )
    {
        $product_id = ncore_retrieve( $attributes, 'product', false );
        $order_id   = ncore_retrieve( $attributes, 'order_id', _digi( 'by shortcode' ) );

        if (!$product_id) {
            return $this->shortcodeErrorMissingArg( 'product' );
        }

        if (!is_user_logged_in()) {
            return '';
        }

        $current_user = wp_get_current_user();

        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );
        $where = array( 'product_id' => $product_id, 'user_id' => $current_user->ID );
        $user_products = $model->getAll( $where, $limit=false, $order_by='id DESC' );

        foreach ($user_products as $one) {
            $is_active = ncore_isTrue( $one->is_access_granted );
            if ($is_active)
            {
                return '';
            }
            else {
                $order_id = $one->order_id;
            }
        }

        $email        = $current_user->user_email;
        $first_name   = $current_user->user_firstname;
        $last_name    = $current_user->user_lastname;

        /** @var digimember_PaymentHandlerLib $lib */
        $lib = $this->api->load->library( 'payment_handler' );
        /** @noinspection PhpUnusedLocalVariableInspection */
        list( $login, $password, $type, $id ) = $lib->manuallyCreateSale( $email, $first_name, $last_name, $order_id, $product_id );

        $has_given = $id > 0;

        return $has_given
               ? $output
               : '';
    }

    public function shortcodeDaysLeft( $attributes=array() )
    {
        $product_id = ncore_retrieve( $attributes, 'product', false );

        if (!$product_id)
        {
            /** @var digimember_PageProductData $pageProductData */
            $pageProductData = $this->api->load->model( 'data/page_product' );
            $page_product = $pageProductData->getCurrent();
            $product_id = ncore_retrieve( $page_product, 'product_id' );
        }

        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );

        $user_id = ncore_userId();
        if (!$user_id)
        {
            return '';
        }

        $user_product = $model->getForUserAndProduct( $user_id, $product_id );
        if (!$user_product)
        {
            return '';
        }

        /** @var digimember_ProductData $model */
        $model = $this->api->load->model( 'data/product' );

        $product = $model->get( $product_id );
        if (!$product)
        {
            return '';
        }

        if ($product->access_granted_for_days <= 0)
        {
            return '';
        }

        $days_left = max( 0, $product->access_granted_for_days - $user_product->last_payment_days );

        return $days_left;
    }


    public function shortcodeApiVersion( /** @noinspection PhpUnusedParameterInspection */ $attributes=array() )
    {
        return digimember_ApiVersion();
    }

    public function shortcodeSqlUpgradeInfo( $attributes=array() )
    {
        /** @var ncore_BaseData[] $models */
        $models = $this->api->load->allModels( array( 'system', 'application' ), array( 'data', 'queue' ) );

        $table_prefix = ncore_retrieve( $attributes, 'prefix', 'auto' );

        $sql = '';

        foreach ( $models as $one )
        {
            list( $table_name, $columns ) = $one->sqlLockedDateInfo( $table_prefix );

            if ($columns)
            {
                $sql .= "UPDATE $table_name";

                foreach ($columns as $index => $column)
                {
                    $is_first = $index == 0;

                    $sql .= $is_first
                            ? ' SET '
                            : ', ';

                    $sql .= "$column=IF($column='0000-00-00 00:00:00',NULL,$column)";
                }
                $sql .= ";\n";
            }
        }

        return "<pre>$sql</pre>";
    }

    public function shortcodeApiReference( /** @noinspection PhpUnusedParameterInspection */ $attributes=array() )
    {
        $api = ncore_api();

        $file = $api->rootDir() . '/api-doc.php';

        $code = file_get_contents( $file );

        $code = trim( str_replace( array( "<?php", "?>" ), '', highlight_string( $code, $return=true ) ) );

        return "<div class='digimember_api_reference'>

$code

</div>";
    }


    public function shortcodeShortcode($attributes=array())
    {
        if (!$attributes) {
            return '';
        }

        if (is_string($attributes))
        {
            return "[$attributes]";
        }

        $code = '';
        foreach ($attributes as $k => $v)
        {
            if ($code) {
                $code .= ' ';
            }

            if (!$v)
            {
                $code .= $k;
            }
            elseif (is_numeric($k))
            {
                $code .= $v;
            }
            else
            {
                $code .= $k . '="' . $v. '"';
            }
        }

        return "[$code]";
    }

	public function shortcodeExampleProduct()
	{
		$link1 = new stdClass();
		$link1->url = '/';
		$link1->label = _digi( 'Some product' );

		$link2 = new stdClass();
		$link2->url = '/';
		$link2->label = _digi( 'Another product' );

		$link3 = new stdClass();
		$link3->url = '/';
		$link3->label = _digi( 'A third product' );

		$links = array(
			$link1, $link2, $link3
		);

		return $this->_renderProductLinks( $links );
	}

    public function shortcodeSections()
    {
        return array(
            'account' => array(
                'label' => _digi('Account'),
                'sort'  => 10,
            ),
            'userdata' => array(
                'label' => _digi('User data'),
                'sort'  => 20,
            ),
            'course' => array(
                'label' => _digi('Course'),
                'sort'  => 30,
            ),
            'products' => array(
                'label' => _digi('Products'),
                'sort'  => 40,
            ),
            'sales' => array(
                'label' => _digi('Sales'),
                'sort'  => 50,
            ),
            'protected' => array(
                'label' => _digi('Protect'),
                'sort'  => 60,
            ),
        );
    }

    protected function shortcodeCallbacks()
    {
        return array_merge(
            parent::shortcodeCallbacks(),
            $this->shortcodeCallbacksStyled(),
            array(
                'login'                 => 'shortcodeLogin',
                'logout'                => 'shortcodeLogout',
                'account'               => 'shortcodeAccountEdit',
                'signup'                => 'shortcodeSignUp',
                'cancel_form'           => 'shortcodeCancelForm',
                'username'              => 'shortcodeUsername',
                'password'              => 'shortcodePassword',
                'email'                 => 'shortcodeEmail',
                'exam'                  => 'shortcodeExam',
                'exam_certificate'      => 'shortocdeExamCertificate',
                'firstname'             => 'shortcodeFirstname',
                'lastname'              => 'shortcodeLastname',
                'loginkey'              => 'shortcodeLoginkey',
                'products'              => 'shortcodeProducts',
                'give_product'          => 'shortcodeGiveProduct',
                'days_left'             => 'shortcodeDaysLeft',
                'op_locked_hint'        => 'shortcodeOpLockedHint',
                'op_show_always'        => 'shortcodeOpShowAlways',
                'menu'                  => 'shortcodeCourseMenu',
                'lecture_buttons'       => 'shortcodeCourseLectureButtons',
                'lecture_progress'      => 'shortcodeCourseLectureProgress',
                'counter'               => 'shortcodeCounter',
                'autojoin'              => 'shortcodeAutojoin',
                'upgrade'               => 'shortcodeUpgrade',
                'renew'                 => 'shortcodeRenew',
                'receipt'               => 'shortcodeReceipt',
                'invoices'              => 'shortcodeInvoices',
                'add_package'           => 'shortcodeAdd',
                'webinar'               => 'shortcodeWebinar',
                'buyer_to_affiliate'    => 'shortcodeInviteAffiliates',
                'buyers_affiliate_name' => 'shortcodeDigistoreAffiliateNameForUser',
                'preview'               => 'shortcodePreview',
                'download'              => 'shortcodeDownload',
                'downloads_left'        => 'shortcodeDownloadsleft',
                'if'                    => 'shortcodeIf',
                'digistore_download'    => 'shortcodeDigistoreDownload',
                'socialproof'           => 'shortcodeDS24Socalproof',
                'countdown'             => 'shortcodeDS24Countdown',
                'orderform'             => 'shortcodeDS24Orderform',
                'smartupgrade'          => 'shortcodeDS24SmartUpgrade',
                'api'                   => 'shortcodeApiReference',
                'api_version'           => 'shortcodeApiVersion',
                'sql_upgrade_info'      => 'shortcodeSqlUpgradeInfo',
                'shortcode'             => 'shortcodeShortcode',
                'webpush'               => 'shortcodeWebpush',
                'subscriptions'         => 'shortcodeSubscriptions',
                'waiver_declaration'    => 'shortcodeWaiverDeclaration',
                'customfield'           => 'shortcodeCustomfield',
                'asf'                   => 'shortcodeAdvancedForms',
                'af'                    => 'shortcodeAdvancedForms',
                'forms'                 => 'shortcodeAdvancedForms',
                'cancel'                => 'shortcodeCancel',
            )
        );
    }

    /**
     * @return array
     */
    protected function shortcodeCallbacksStyled()
    {
        return [
            'login_styled' => 'shortcodeStyled',
            'account_styled' => 'shortcodeStyled',
            'signup_styled' => 'shortcodeStyled',
        ];
    }

    /**
     * @param array $attributes
     *
     * @return string|void
     * @throws Exception
     */
    public function shortcodeStyled($attributes = []) {
        $id = ncore_retrieve($attributes, 'id');
        if (!$id || !is_numeric($id)) {
            return _digi('Warning: Missing attribute "id" for styled shortcode');
        }

        /** @var digimember_StyledShortcodeRendererLib $renderer */
        $renderer = $this->api->load->library('styled_shortcode_renderer');
        return $renderer->renderShortcode($id);
    }

    /**
     * @return array
     */
	public function shortcodeMetas()
	{
		$metas = array();

		$label_stay_on_same_page     = _digi( 'Stay on same page' );
        $label_redirect_if_logged_in = _digi( 'Redirect if logged in' );

        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        /** @var digimember_FacebookConnectorLib $lib */
        $lib   = $this->api->load->library( 'facebook_connector' );

        $customFields = array();
        $customFields[0] = _ncore('Show all fields');
        $customfieldsModel = $this->api->load->model('data/custom_fields');
        $customFieldsData = $customfieldsModel->getCustomFieldsBySectionList(array("account", "general"), true);
        foreach ($customFieldsData as $customField) {
            $customFields[$customField->id] = $customField->label;
        }

        $forms = array();
        $formsModel = $this->api->load->model('data/advanced_forms');
        $formsData = $formsModel->getAll();
        foreach ($formsData as $form) {
            $forms[$form->id] = $form->name;
        }

        $have_facebook          = ncore_hasFacebookApp();
        $can_use_facebook       = $model->canUseFacebook();
        $is_facebook_configured = $lib->isFacebookConfigured();

        $fb_login_args =  array();
        $fb_signup_args = array();
        if (!$have_facebook) {
            $fb_signup_args[] = array(
                    'type' => 'html',
                    'label' => '',
                    'hide'=> true,
                );
        }
        elseif (!$is_facebook_configured) {
            $fb_login_args[] = array(
                    'type' => 'html',
                    'label' => _digi( 'Facebook login' ),
                    'callback'=> array( 'api' => $this->api, 'lib' => 'facebook_connector', 'method' => 'renderSetupHint' ),
                );
            $fb_signup_args = $fb_login_args;
        }
        elseif ($can_use_facebook)
        {
            $fb_signup_args[] = array(
                    'label' => _digi( 'Facebook login' ),
                    'name' => 'facebook',
                    'type' => 'select',
                    'rules' => 'required',
                    'options' => array(
                        'no'   => _digi( 'No, hide Facebook login' ),
                        'also' => _digi( 'Show Facebook login ALSO' ),
                        'only' => _digi( 'Show ONLY Facebook login' ),
                    ),
               );

            $fb_login_args[] = array(
                    'label' => _digi( 'Facebook login' ),
                    'name' => 'facebook',
                    'type' => 'select',
                    'rules' => 'required',
                    'options' => array(
                        'no'   => _digi( 'No, hide Facebook login' ),
                        'also' => _digi( 'Show Facebook login ALSO' ),
                        'only' => _digi( 'Show ONLY Facebook login' ),
                    ),
               );

            $fb_login_args[] = array(
                    'label' => _digi( 'Products for new Facebook users' ),
                    'name' => 'fb_product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
                    'depends_on' => array( 'facebook' => array( 'also', 'only' ) ),
                    'rules' => 'required',
               );
        }
        else
        {
            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model( 'logic/link' );
            $html = $model->upgradeButton( $label='', $type='link' );

            $fb_login_args[] = array(
                    'label' => _digi( 'Facebook login' ),
                    'type' => 'html',
                    'html'=> $html,
                );

            $fb_signup_args = $fb_login_args;
        }


        $linkModel = $this->api->load->model('logic/link');
        $customfields_link = $linkModel->adminMenuLink('customfields');

        $linkModel = $this->api->load->model('logic/link');
        $advanced_forms_link = $linkModel->adminMenuLink('advancedforms');


		$metas[] = array(
			'code' => 'login',
			'description' => _digi('Loginbox, where the user can enter his username and password.'),
            'section'  => 'account',
			'args' => array_merge(
               array(
                   array(
                        'label'       => _digi( 'Type' ),
                        'name'        => 'type',
                        'type'        => 'select',
                        'options'     => array( 'widget' => _digi( 'Widget (form is shown on the page)' ), 'button' => _digi( 'Button (form is shown in a popup window)' ) ),
                        'is_only_for' => 'shortcode',
                        'rules'       => 'required',
                   ),
               ),
               $fb_login_args,
               array(
			       array(
					    'label'       => _digi( 'Hidden if logged in' ),
					    'name'        => 'hidden_if_logged_in',
					    'type'        => 'checkbox',
                        'is_only_for' => 'shortcode',
			       ),
			       array(
					    'label' => $label_stay_on_same_page,
					    'name' => 'stay_on_same_page',
					    // 'hint' => _digi('If unchecked, the user is redirected to the product\'s start page.' ),
					    'type' => 'checkbox',
			       ),
                   array(
                        'label' => _digi( 'Redirect URL' ),
                        // 'hint' => _digi('Applies only, if %s is unchecked.','<em>'.$label_stay_on_same_page.'</em>' ),
                        'name' => 'url',
                        'type' => 'page_or_url',
                   ),
                   array(
                        'label' => $label_redirect_if_logged_in,
                        'name' => 'redirect_if_logged_in',
                        // 'hint' => _digi('If unchecked, the user is redirected to the product\'s start page.' ),
                        'type' => 'checkbox',
                   ),
                   array(
                        'label' => _digi( 'Style' ),
                        'name' => 'style',
                        'type' => 'select',
                        'default' => 'modern',
                        'options' => array( 'classic' => _digi( 'Classic' ), 'modern' => _digi( 'Modern' ) ),
                        'rules' => 'required',
                    ),
			       array(
					    'label' => _digi( 'Button background color' ),
					    'name' => 'button_bg',
					    'type' => 'color',
                        'default' => '#2196F3',
                        'rules'       => 'required',
				    ),
                   array(
                        'label' => _digi( 'Button text color' ),
                        'name' => 'button_fg',
                        'type' => 'color',
                        'default' => '#FFFFFF',
                        'rules'       => 'required',
                    ),
                    array(
                        'label'   => _digi( 'Corner radius' ),
                        'name'    => 'button_radius',
                        'type'    => 'select',
                        'options' => 'border_radius',
                   ),
                   array(
                        'label' => _digi( 'Signup URL' ),
                        'name' => 'signup_url',
                        'type' => 'page_or_url',
                   ),
                   array(
                        'label' => _digi( 'Signup text' ),
                        'name' => 'signup_msg',
                        'type' => 'text',
                        'hint' => _digi('Use %s to mark the sign up link. E.g.: No account? %sClick here to sign up%s', '__', '__', '__' ),
                   ),
                   array(
                        'label' => _digi( 'Button text' ),
                        'name' => 'button_text',
                        'type' => 'text',
                        'default' => _digi('Login' ),
                   ),
                   array(
                        'label' => _digi( 'Window headline' ),
                        'name' => 'dialog_headline',
                        'type' => 'text',
                        'default' => _digi('Login' ),
                        'depends_on' => array( 'type' => 'button' ),
                   ),

                )
			),
		);
		$metas[] = array(
			'code' => 'logout',
			'description' => _digi('Perform a logout.'),
            'section'  => 'account',
			'args' => array(
			   array(
					'label' => _digi('Redirect to Page' ),
					'name' => 'page',
					'type' => 'page',
			   ),
			   array(
					'type' => 'html',
					'label' => 'none',
					'html'  =>_digi('Or'),
			   ),
			   array(
					'label' => _digi('Redirect URL'),
					'name' => 'url',
					'type' => 'url',
				),
			),
		);

		$metas[] = array(
			'code' => 'account',
			'description' => _digi('Allows a user to edit his display name and his password.'),
            'section'  => 'account',
            'args' => array(
                array(
                    'label' => _digi('Show firstname'),
                    'name' => 'first_name',
                    'type' => 'checkbox',
                ),
                array(
                    'label' => _digi('Show lastname'),
                    'name' => 'last_name',
                    'type' => 'checkbox',
                ),
               array(
                    'label' => _digi('Hide display name' ),
                    'name' => 'hide_display_name',
                    'type' => 'checkbox',
               ),
               array(
                    'label' => _digi('Show account deletion button' ),
                    'name' => 'delete_button',
                    'type' => 'checkbox',
               ),
                                         array(
                    'label' => _digi('Show personal data export button' ),
                    'name' => 'data_export_button',
                    'type' => 'checkbox',
               ),
                array(
                    'label' => _ncore('Show %s',$customfields_link),
                    'name' => 'custom_fields',
                    'type' => 'checkbox',
                ),
            ),
		);

		$metas[] = array(
			'code' => 'signup',
			'description' => _digi('Signup form - new users get a product.'),
            'section'  => 'account',
			'args' => array_merge(
                array(
			       array(
					    'label' => _digi( 'Products' ),
					    'name' => 'product',
					    'type' => 'checkbox_list',
					    'options' => 'product',
					    'seperator' => '<br />',
                        'rules' => 'required',
			       ),
			       array(
					    'label' => _digi( 'With first name inputs' ),
					    'name' => 'first_name',
					    'type' => 'checkbox',
			       ),
                   array(
                        'label' => _digi( 'With last name inputs' ),
                        'name' => 'last_name',
                        'type' => 'checkbox',
                   ),
                   array(
                        'label' => _ncore('Enter %s',$customfields_link),
                        'name' => 'custom_fields',
                        'type' => 'checkbox',
                   ),
                   array(
                        'label'   => _digi( 'Confirmation checkbox' ),
                        'name'    => 'confirm',
                        'type'    => 'text',
                        'length'  => 255,
                        'hint'    => _digi( 'E.g.: I accept the %sterms and conditions%s', '&lt;a href="'._digi('/terms').'" target="_blank"&gt;', '&lt;/a&gt;' ),
                    ),
			       array(
					    'label' => _digi( 'Autologin after signup' ),
					    'name' => 'login',
					    'type' => 'checkbox',
			       ),
                   array(
                        'label' => _digi( 'Hide form after signup' ),
                        'name' => 'hideform',
                        'type' => 'checkbox',
                        'checkbox_label' => _digi( 'If selected, after signup only the success message is shown, but not anymore the registration form.' ),
                   ),
                   array(
                        'label'       => _digi( 'Type' ),
                        'name'        => 'type',
                        'type'        => 'select',
                        'options'     => array( 'widget' => _digi( 'Widget (form is shown on the page)' ), 'button' => _digi( 'Button (form is shown in a popup window)' ) ),
                        'is_only_for' => 'shortcode',
                        'rules'       => 'required',
                   ),
                   array(
                        'label' => _digi( 'Button background color' ),
                        'name' => 'button_bg',
                        'type' => 'color',
                        'default' => '#2196F3',
                        'rules'       => 'required',
                    ),
                   array(
                        'label' => _digi( 'Button text color' ),
                        'name' => 'button_fg',
                        'type' => 'color',
                        'default' => '#FFFFFF',
                        'rules'   => 'required',
                   ),
                   array(
                        'label'   => _digi( 'Corner radius' ),
                        'name'    => 'button_radius',
                        'type'    => 'select',
                        'options' => 'border_radius',
                   ),
                   array(
                        'label' => _digi( 'Button label' ),
                        'name' => 'button_text',
                        'type' => 'text',
                        'default' => _ncore( 'Login' ),
                        'rules'   => 'required',
                        'depends_on '=> array( 'type' => 'button' ),
                    ),

                   array(
                        'label' => _digi( 'Add reCAPTCHA' ),
                        'name' => 'recaptcha_active',
                        'type' => 'checkbox',
                        'checkbox_label' => _digi( 'If you want add a Google reCAPTCHA to the form, <a target="_blank" href="https://www.google.com/recaptcha/">register your domain at google</a> and enter the site key and the secret key here.' ),
                        'checked_value' => 'Y',
                    ),
                   array(
                        'label' => _digi( 'reCAPTCHA key' ),
                        'name' => 'recaptcha_key',
                        'type' => 'text',
                        'depends_on' => array( 'recaptcha_active' => 'Y' ),
                    ),
                    array(
                        'label' => _digi( 'reCAPTCHA secret' ),
                        'name' => 'recaptcha_secret',
                        'type' => 'text',
                        'depends_on' => array( 'recaptcha_active' => 'Y' ),
                    ),
                ),
                $fb_signup_args
			),
		);

        list($lang,$country) = explode('_',get_locale());
        if ($lang == 'de') {
            $label = 'hier';
        }
        else {
            $label = 'here';
        }
        $linkLogic = $this->api->load->model( 'logic/link' );
        $url = $linkLogic->adminPage( 'digimember_mails' );
        $url = $url.'&element=1&tab=cancel';
        $description = ncore_linkReplace(_digi( 'Generates a generic form to declare an intention to cancel. When the form is submitted, a confirmation is sent to the sender and one to an admin email defined here. The texts of the two emails can be customized <a>here</a>.', "<strong>$label</strong>"), $url, true );

        $metas[] = array(
            'code' => 'cancel_form',
            'description' => $description,
            'section'  => 'account',
            'args' => array_merge(
                array(
                    array(
                        'label' => _digi( 'Admin email for cancellation' ),
                        'name' => 'cancel_email',
                        'type' => 'email',
                        'rules'   => 'required',
                        'tooltip' => _digi('A notification will be sent to this email address when someone has completed the cancellation form. With ; further e-mail addresses can be added, which receive the same e-mail as CC.'),
                    ),
                    array(
                        'label'   => _digi( 'Hint To find order ID' ),
                        'name'    => 'hintForOrderID',
                        'type'    => 'text',
                        'length'  => 255,
                        'hint'    => _digi( 'E.g.: Where do I find my %sorder ID%s?', '&lt;a href="'._digi('/findOrderID').'" target="_blank"&gt;', '&lt;/a&gt;' ),
                        'tooltip' => _digi('Here you can include information or a link for the user to find their order ID.'),
                    ),
                    array(
                        'label' => _digi( 'Show field for first names' ),
                        'name' => 'first_name',
                        'type' => 'checkbox',
                    ),
                    array(
                        'label' => _digi( 'Show field for last names' ),
                        'name' => 'last_name',
                        'type' => 'checkbox',
                    ),
                    array(
                        'label' => _digi( 'Show field for type of cancellation/reason for cancellation' ),
                        'name' => 'type_reason',
                        'type' => 'checkbox',
                    ),
                    array(
                        'label' => _digi( 'Show field for date of cancellation' ),
                        'name' => 'cancellation_date',
                        'type' => 'checkbox',
                    ),
                    array(
                        'label' => _digi( 'Button background color' ),
                        'name' => 'button_bg',
                        'type' => 'color',
                        'default' => '#2196F3',
                        'rules'       => 'required',
                    ),
                    array(
                        'label' => _digi( 'Button text color' ),
                        'name' => 'button_fg',
                        'type' => 'color',
                        'default' => '#FFFFFF',
                        'rules'   => 'required',
                    ),
                    array(
                        'label'   => _digi( 'Corner radius' ),
                        'name'    => 'button_radius',
                        'type'    => 'select',
                        'options' => 'border_radius',
                    ),
                    array(
                        'label' => _digi( 'Button label' ),
                        'name' => 'button_text',
                        'type' => 'text',
                        'default' => _digi( 'Cancel' ),
                        'rules'   => 'required',
                        'depends_on '=> array( 'type' => 'button' ),
                    ),

                    array(
                        'label' => _digi( 'Add reCAPTCHA' ),
                        'name' => 'recaptcha_active',
                        'type' => 'checkbox',
                        'checkbox_label' => _digi( 'If you want add a Google reCAPTCHA to the form, <a target="_blank" href="https://www.google.com/recaptcha/">register your domain at google</a> and enter the site key and the secret key here.' ),
                        'checked_value' => 'Y',
                    ),
                    array(
                        'label' => _digi( 'reCAPTCHA key' ),
                        'name' => 'recaptcha_key',
                        'type' => 'text',
                        'depends_on' => array( 'recaptcha_active' => 'Y' ),
                    ),
                    array(
                        'label' => _digi( 'reCAPTCHA secret' ),
                        'name' => 'recaptcha_secret',
                        'type' => 'text',
                        'depends_on' => array( 'recaptcha_active' => 'Y' ),
                    ),
                )
            ),
        );

        $metas[] = array(
            'code' => 'username',
            'description' => _digi('Show the current user\'s login name.'),
            'section'  => 'userdata',
        );

        $metas[] = array(
            'code' => 'password',
            'description' => _digi('Show the current user\'s password, if the password was generated by %s and the user did not change it.', $this->api->pluginDisplayName()),
            'section'  => 'userdata',
            'args' => array(
                array(
                    'label' => _digi( 'Fallback' ),
                    'name' => 'no_pw_text',
                    'type' => 'text',
                    'default' => '('._digi('Hidden for security reasons' ).')',
                    'hint' => _digi( 'This text is shown, if the user has changed his password' ),
                ),
            ),
        );

        $metas[] = array(
            'code' => 'email',
            'description' => _digi('Show the current user\'s email address. For accounts created by %s, this is usually, but not always the same as the user\'s login name.', $this->api->pluginDisplayName()),
            'section'  => 'userdata',
        );


        $metas[] = array(
            'code' => 'firstname',
            'description' => _digi('Show the current user\'s first name.'),
            'section'  => 'userdata',
            'args' => array(
                array(
                    'label' => _digi( 'Space' ),
                    'name' => 'space',
                    'type' => 'checkbox_list',
                    'options' => array( 'before' => _ncore( 'before'), 'after' => _ncore( 'after' ) ),
                    'hint' => _digi( 'Puts a space before/after the name. If no name is shown, no space is shown.' ),
               ),
            ),
        );

        $metas[] = array(
            'code' => 'lastname',
            'description' => _digi('Show the current user\'s last name.'),
            'section'  => 'userdata',
            'args' => array(
                array(
                    'label' => _digi( 'Space' ),
                    'name' => 'space',
                    'type' => 'checkbox_list',
                    'options' => array( 'before' => _ncore( 'before'), 'after' => _ncore( 'after' ) ),
                    'hint' => _digi( 'Puts a space before/after the name. If no name is shown, no space is shown' ),
               ),
            ),
        );

        $metas[] = array(
            'code' => 'loginkey',
            'description' => _digi('Show the current user\'s login key. Add this add GET-Parameter %s to a url to enable auto login.', '<tt>'.DIGIMEMBER_LOGINKEY_GET_PARAM.'</tt>' ),
            'section'  => 'userdata',
        );

        $metas[] = array(
            'code' => 'customfield',
            'description' =>  _ncore('Shows %s.', $customfields_link).'<br>'._ncore('Only active, visible fields can be displayed. If no data has yet been stored for a logged-in user, the field will not be displayed.'),
            'section'  => 'userdata',
            'args' => array(
                array(
                    'label' => _ncore('Custom field'),
                    'name' => 'customfield',
                    'type' => 'select',
                    'options' => $customFields,
                    'rules' => 'required',
                    'default' => '0'
                ),
                array(
                    'label' => _digi( 'Space' ),
                    'name' => 'space',
                    'type' => 'checkbox_list',
                    'options' => array( 'before' => _ncore( 'before'), 'after' => _ncore( 'after' ) ),
                    'hint' => _digi( 'Puts a space before/after the name. If no name is shown, no space is shown.' ),
                ),
            ),
        );

        $metas[] = array(
            'code' => 'forms',
            'description' =>  _ncore('Shows %s.', $advanced_forms_link).'<br>'._ncore('Forms, that containing errors, are not visible in frontend.'),
            'section'  => 'userdata',
            'args' => array(
                array(
                    'label' => _ncore('Form'),
                    'name' => 'id',
                    'type' => 'select',
                    'options' => $forms,
                    'rules' => 'required',
                    'default' => '0'
                ),
            ),
        );

		$metas[] = array(
			'code' => 'products',
			'description' => _digi('List products the user has purchased.'),
            'section'  => 'products',
		);

        $metas[] = array(
            'code' => 'give_product',
            'description' => _digi('Give a product to a logged in user. If a text should be shown after the product has been assigned, put it between the two shortcode tags %s and %s.', '[give_product ...]', '[/give_product]' ),
            'section'  => 'products',
            'args' => array(
                   array(
                        'label' => _digi( 'Product' ),
                        'name' => 'product',
                        'type' => 'select',
                        'options' => 'product',
                        'rules' => 'required',
                   ),
                   array(
                        'label' => _digi( 'Order id' ),
                        'name' => 'order_id',
                        'type' => 'text',
                        'rules' => 'required',
                        'default' => _digi( 'by shortcode' ),
                   ),
            ),
        );

        $metas[] = array(
            'code' => 'days_left',
            'description' => _digi('Displays the number of days, the user has access to the product (if the access is limited).'),
            'section'  => 'products',
            'args' => array(
                   array(
                        'label' => _digi( 'Product' ),
                        'name' => 'product',
                        'type' => 'select',
                        'options' => 'product',
                        'rules' => 'required',
                   ),
            ),
        );

        $metas[] = array(
            'code' => 'op_locked_hint',
            'description' => _digi('For use with OptimizePress2 and the live editor: Use this short code to display the message, if the content is locked.'),
            'section'  => 'products',
        );

        $metas[] = array(
            'code' => 'op_show_always',
            'description' => _digi('For use with OptimizePress2 and the live editor: Use this short code to display the content even if the page is locked. This is useful for headlines.'),
            'section'  => 'products',
        );

        $metas[] = array(
            'section'  => 'account',
            'code' => 'webpush',
            'description' => _digi('Allows the user to optin to notifications - see %s, Tab %s', '<strong>' . $this->api->pluginDisplayName() . ' - ' . _digi( 'Push Notifications' ).'</strong>', '<strong>'._ncore( 'Settings' ).'</strong>' ),
            'args' => array(
                   array(
                        'label' => _digi( 'Show optout button' ),
                        'name' => 'optout',
                        'type' => 'checkbox',
                        'hint' => _digi( 'If enabled, an optout button is shown, if the user has subscribed.' ),
                   ),
            ),
        );

        $metas[] = array(
            'section'  => 'account',
            'code' => 'subscriptions',
            'description' => _digi('Shows checkboxes to allow the user to manage his subscriptions for web push notifications or action emails.' ),

            'args' => array(
                   array(
                        'label' => _digi( 'Show ...' ),
                        'name' => 'show',
                        'type' => 'select',
                        'options' => 'subscriptions_show',
                        'rules' => 'required',
                        'default' => 'all',
                   ),
            ),
        );



        $metas[] = array(
            'code' => 'menu',
            'description' => _digi('Shows a menu inside the content area.'),
            'section'  => 'course',
            'args' => array(
                   array(
                        'label' => _digi( 'Menu' ),
                        'name' => 'what',
                        'type' => 'select',
                        'options' => 'lecture_or_menu',
                        'rules' => 'required',
                   ),
                   array(
                        'label' => _digi( 'Depth' ),
                        'name' => 'depth',
                        'type' => 'select',
                        'options' => array(
                                        '0' => _digi('Show all menu levels' ),
                                        '1' => _digi('Show only the top menu level' ),
                                        '2' => _digi('Show top and sub menu levels' ),
                                        '3' => _digi('Show three menu levels' ),
                                     ),
                        'rules' => 'required',
                   ),
            ),
        );

        $metas[] = array(
            'code' => 'lecture_buttons',
            'description' => _digi('Create a navigation bar to move between the lectures of the current course.'),
            'section'  => 'course',
            'args' => array(
                   array(
                        'label' => _digi( 'Add buttons for 2nd level lectures' ),
                        'name' => '2nd_level',
                        'type' => 'checkbox',
                   ),
                   array(
                        'label'   => _digi( 'Icon color' ),
                        'name'    => 'color',
                        'type'    => 'select',
                        'default' => 'white',
                        'rules'   => 'required',
                        'options' => 'lecture_button_styles',
                   ),

                   array(
                        'label'   => _digi( 'Background color' ),
                        'name'    => 'bg',
                        'type'    => 'color',
                        'default' => '#707070',
                   ),
                   array(
                        'label'   => _digi( 'Corner radius' ),
                        'name'    => 'round',
                        'type'    => 'select',
                        'options' => 'border_radius',
                   ),
                   array(
                        'label'   => _digi( 'Alignment' ),
                        'name'    => 'align',
                        'type'    => 'select',
                        'options' => array(
                            'left'   => _digi( 'left' ),
                            'right'  => _digi( 'right' ),
                            'center' => _digi( 'center' ),
                        ),
                   ),
                   array(
                        'label' => _digi( 'For product' ),
                        'name' => 'product',
                        'type' => 'select',
                        'options' => 'product',
                        'null_label' => _digi( 'Current product' ),
                   ),
            ),
        );

    $metas[] = array(
            'code' => 'lecture_progress',
            'description' => _digi('Display a progress bar to show the completed percentage of a course.'),
            'section'  => 'course',
            'args' => array(
                   array(
                        'label' => _digi( 'Show progress of ...' ),
                        'name' => 'for',
                        'type' => 'select',
                        'allow_null' => false,
                        'default' => 'course',
                        'rules' => 'required',
                        'options' => array(
                                        'course' => _digi('... the complete course' ),
                                        'module' => _digi('... the current module' ),
                                     ),
                   ),
                   array(
                        'label'   => _digi( 'Bar color' ),
                        'name'    => 'color',
                        'type'    => 'color',
                        'default' => '#2196F3',
                   ),

                   array(
                        'label'   => _digi( 'Background color' ),
                        'name'    => 'bg',
                        'type'    => 'color',
                        'default' => '#707070',
                   ),

                   array(
                        'label'   => _digi( 'Corner radius' ),
                        'name'    => 'round',
                        'type'    => 'select',
                        'options' => array(
                            0 => _digi( 'none - sharp corners' ),
                            3 => '3 - ' . _digi( 'small' ),
                            5 => 5,
                            7 => 7,
                           10 => 10,
                           15 => 15,
                          100 => '20 - ' . _digi( 'full' ),
                        ),
                   ),
                   array(
                        'label' => _digi( 'For product' ),
                        'name' => 'product',
                        'type' => 'select',
                        'options' => 'product',
                        'null_label' => _digi( 'Current product' ),
                    ),
            ),
        );


        $metas[] = array(
            'code' => 'exam',
            'description' => _digi('Show the exam.'),
            'section'  => 'course',
            'args' => array(
                   array(
                        'label'   => _digi( 'Exam' ),
                        'name'    => 'id',
                        'type'    => 'select',
                        'options' => 'exam',
                        'rules'   => 'required',
                   ),
            ),
        );

        $metas[] = array(
            'code' => 'exam_certificate',
            'description' => _digi('Show the download area for the exam certificate.'),
            'section'  => 'course',
            'args' => array(
                   array(
                        'label'   => _digi( 'Exam certificate' ),
                        'name'    => 'id',
                        'type'    => 'select',
                        'options' => 'exam_certificate',
                        'rules'   => 'required',
                   ),
            ),
        );





		$metas[] = array(
			'code' => 'counter',
			'description' => _digi('Social proof counter - display the number of sales.'),
            'section'  => 'sales',
			'args' => array(
			   array(
					'label' => _digi( 'Product' ),
					'name' => 'product',
					'type' => 'select',
					'options' => 'product',
			   ),
			   array(
					'label' => _digi( 'Start value' ),
					'name' => 'start',
					'type' => 'int',
				),
			),
		);

		/** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use_autojoin = $model->canUseAutoJoin();

        if ($can_use_autojoin)
        {
            $saleshint = '';
        }
        else
        {
            /** @var digimember_LinkLogic $link */
            $link = $this->api->load->model( 'logic/link' );

            $saleshint = ' '. _digi( '%s required.', $this->api->pluginNamePro() )
                        . ' ' . $link->upgradeButton(_digi( 'Upgrade now!' ), $style='link' );
        }




        $metas[] = array(
            'code' => 'autojoin',
            'description' => _digi('Put this on the autoresponders thankyou page to automatically create an account for the new subscriber.') . $saleshint,
            'section'  => 'products',
            'args' => array(
               array(
                    'label' => _digi( 'Autoresponder' ),
                    'name' => 'autoresponder',
                    'type' => 'select',
                    'options' => 'autojoin_autoresponder',
                    'rules' => 'required',
               ),
               array(
                    'label' => _digi( 'Give product' ),
                    'name' => 'product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
               ),
               array(
                    'label' => _digi( 'Show text' ),
                    'name' => 'has_contents',
                    'type' => 'select',
                    'options' => 'yes_no',
                    'rules' => 'required',
                    'tooltip' => _digi( 'Select Yes to display text, if a new account has been created. Use the shortcodes %s and %s for the credentials of the new account.', '[username]', '[password]' ),
               ),
               array(
                    'label' => _digi( 'Login new users' ),
                    'name' => 'do_login',
                    'type' => 'checkbox',
                    'tooltip' => _digi( 'If enabled, new users will be automatically logged in, when an account is created on this page. For security reasons, existing users will not be logged in automatically.' ),
               ),
               array(
                    'label' => _digi( 'Show error messages' ),
                    'name' => 'show_errors',
                    'type' => 'checkbox',
               ),
            ),
        );

        $metas[] = array(
            'code' => 'upgrade',
            'description' =>  _digi( 'Display an upgrade URL for Digistore24. You need to setup the upgrade process in Digistore24. There you\'ll get an upgrade id like: %s', '1234-A1b2C3d4E5f6' ),
            'section'  => 'sales',
            'args' => array(
               array(
                    'label' => _digi( 'Upgrade id' ),
                    'name' => 'id',
                    'type' => 'text',
               ),
               array(
                    'label' => _digi( 'Link text' ),
                    'name' => 'text',
                    'type' => 'text',
               ),
               array(
                    'label' => _digi( 'Image URL' ),
                    'name' => 'img',
                    'type' => 'image_url',
               ),
               array(
                    'label' => _digi( 'Button background color' ),
                    'name' => 'button_bg',
                    'type' => 'color',
                    'default' => '#2196F3',
                    'rules'       => 'required',
                ),
               array(
                    'label' => _digi( 'Button text color' ),
                    'name' => 'button_fg',
                    'type' => 'color',
                    'default' => '#FFFFFF',
                    'rules'       => 'required',
                ),
                array(
                    'label'   => _digi( 'Corner radius' ),
                    'name'    => 'button_radius',
                    'type'    => 'select',
                    'options' => 'border_radius',
               ),
               array(
                    'label' => _digi( 'Confirm message' ),
                    'name' => 'confirm',
                    'type' => 'text',
                    'hint' => _digi( 'Use %s for line breaks.', '|' ),
               ),
            )
        );

        $metas[] = array(
            'code' => 'renew',
            'description' =>  _digi( 'Display a renew URL for Digistore24. The user may change his payment details using this URL.' ),
            'section'  => 'sales',
            'args' => array(
                array(
                    'label' => _digi( 'Product' ),
                    'name' => 'product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
                    'have_all' => true,
               ),
               array(
                    'label' => _digi( 'Link text' ),
                    'name' => 'text',
                    'type' => 'text',
               ),
               array(
                    'label' => _digi( 'Image URL' ),
                    'name' => 'img',
                    'type' => 'image_url',
               ),
                array(
                    'label' => _digi( 'Button background color' ),
                    'name' => 'button_bg',
                    'type' => 'color',
                    'default' => '#2196F3',
                    'rules'       => 'required',
                ),
               array(
                    'label' => _digi( 'Button text color' ),
                    'name' => 'button_fg',
                    'type' => 'color',
                    'default' => '#FFFFFF',
                    'rules'       => 'required',
                ),
                array(
                    'label'   => _digi( 'Corner radius' ),
                    'name'    => 'button_radius',
                    'type'    => 'select',
                    'options' => 'border_radius',
               ),
               array(
                    'label' => _digi( 'Confirm message' ),
                    'name' => 'confirm',
                    'type' => 'text',
                    'hint' => _digi( 'Use %s for line breaks.', '|' ),
               ),
            )
        );

        $metas[] = array(
            'code' => 'receipt',
            'description' =>  _digi( 'Display a receipt URL for Digistore24. The user may download his invoices using this URL.' ),
            'section'  => 'sales',
            'args' => array(
                array(
                    'label' => _digi( 'Product' ),
                    'name' => 'product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
                    'have_all' => true,
               ),
               array(
                    'label' => _digi( 'Link text' ),
                    'name' => 'text',
                    'type' => 'text',
               ),
               array(
                    'label' => _digi( 'Image URL' ),
                    'name' => 'img',
                    'type' => 'image_url',
               ),
               array(
                    'label' => _digi( 'Button background color' ),
                    'name' => 'button_bg',
                    'type' => 'color',
                    'default' => '#2196F3',
                    'rules'       => 'required',
                ),
               array(
                    'label' => _digi( 'Button text color' ),
                    'name' => 'button_fg',
                    'type' => 'color',
                    'default' => '#FFFFFF',
                    'rules'       => 'required',
                ),
                array(
                    'label'   => _digi( 'Corner radius' ),
                    'name'    => 'button_radius',
                    'type'    => 'select',
                    'options' => 'border_radius',
               ),
               array(
                    'label' => _digi( 'Confirm message' ),
                    'name' => 'confirm',
                    'type' => 'text',
                    'hint' => _digi( 'Use %s for line breaks.', '|' ),
               ),
            )
        );



        $metas[] = array(
            'code' => 'invoices',
            'description' =>  _digi( 'Display a list of Digistore24 invoices to the user. Or nothing, if the user has no Digistore24 orders.' ),
            'section'  => 'sales',
            'args' => array(
                array(
                    'label' => '',
                    'type'  => 'html',
                    'html'  => _digi( 'Test payments always have a invoice number of 1. They don\'t have a real invoice number.' ),
               ),
            ),
        );


        $metas[] = array(
            'code' => 'add_package',
            'description' =>  _digi( 'Display a sales URL for Digistore24, where the user may manage packages (if in Digistore the product\'s or its addons\' quantity are setup to be changed after the sales).' ),
            'section'  => 'sales',
            'args' => array(
                array(
                    'label' => _digi( 'Product' ),
                    'name' => 'product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
                    'have_all' => true,
               ),
               array(
                    'label' => _digi( 'Link text' ),
                    'name' => 'text',
                    'type' => 'text',
               ),
               array(
                    'label' => _digi( 'Image URL' ),
                    'name' => 'img',
                    'type' => 'image_url',
               ),
                array(
                    'label' => _digi( 'Button background color' ),
                    'name' => 'button_bg',
                    'type' => 'color',
                    'default' => '#2196F3',
                    'rules'       => 'required',
                ),
               array(
                    'label' => _digi( 'Button text color' ),
                    'name' => 'button_fg',
                    'type' => 'color',
                    'default' => '#FFFFFF',
                    'rules'       => 'required',
                ),
                array(
                    'label'   => _digi( 'Corner radius' ),
                    'name'    => 'button_radius',
                    'type'    => 'select',
                    'options' => 'border_radius',
               ),
               array(
                    'label' => _digi( 'Confirm message' ),
                    'name' => 'confirm',
                    'type' => 'text',
                    'hint' => _digi( 'Use %s for line breaks.', '|' ),
               ),
            )
        );

        $metas[] = array(
            'code' => 'webinar',
            'description' =>  ncore_linkReplace( _digi( 'Display an signup form for a <a>Webinaris webinar</a>.' ), DIGIMEMBER_WEBINARIS_URL, $asPoptup=true ),
            'section'  => 'sales',
            'args' => array(
               array(
                    'label' => _digi( 'HTML link' ),
                    'name' => 'url',
                    'type' => 'url',
               ),
               array(
                    'label' => _digi( 'Width' ),
                    'name' => 'width',
                    'type' => 'int',
                    'unit' => _digi('pixels' ),
                    'display_zero_as' => '',

               ),
               array(
                    'label' => _digi( 'Height' ),
                    'name' => 'height',
                    'type' => 'int',
                    'unit' => _digi('pixels' ),
                    'display_zero_as' => '',
               ),
            )
        );

        $metas[] = array(
            'code' => 'cancel',
            'description' =>  _digi( 'Displays one or more buttons that link to their corresponding cancel page on Digistore24. If the user is not logged in, a link to a generic Digistore24 cancel page is displayed. If a user has no active recurring payments, no button is displayed.' ),
            'section'  => 'sales',
            'args' => array(
                array(
                    'label' => _digi( 'Product' ),
                    'name' => 'product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
                    'have_all' => true,
                    'tooltip' => _digi('If more than one product is selected, a separate button is displayed for each active recurring payment.')
                ),
                array(
                    'label' => _digi( 'Use generic link for logged in users' ),
                    'name' => 'use_generic',
                    'type' => 'checkbox',
                    'default' => 0,
                    'tooltip' => _digi('In general, for logged-in users, the correct link for canceling a specific order is displayed. With this setting, logged-in users also get linked to the generic cancel page from Digistore24.')
                ),
                array(
                    'label' => _digi( 'Show product name' ),
                    'name' => 'show_product_name',
                    'type' => 'checkbox',
                    'depends_on' => array('use_generic' => 0),
                ),
                array(
                    'label' => _digi( 'Show order ID' ),
                    'name' => 'show_order_id',
                    'type' => 'checkbox',
                    'depends_on' => array('use_generic' => 0),
                ),
                array(
                    'label' => _digi( 'Style' ),
                    'name' => 'style',
                    'type' => 'select',
                    'default' => 'modern',
                    'options' => array( 'classic' => _digi( 'Classic' ), 'modern' => _digi( 'Modern' ) ),
                    'rules' => 'required',
                ),
                array(
                    'label' => _digi( 'Button background color' ),
                    'name' => 'button_bg',
                    'type' => 'color',
                    'default' => '#2196F3',
                ),
                array(
                    'label' => _digi( 'Button text color' ),
                    'name' => 'button_fg',
                    'type' => 'color',
                    'default' => '#FFFFFF',
                ),
                array(
                    'label'   => _digi( 'Corner radius' ),
                    'name'    => 'button_radius',
                    'type'    => 'select',
                    'options' => 'border_radius',
                ),
                array(
                    'label' => _digi( 'Button label' ),
                    'name' => 'button_text',
                    'type' => 'text',
                    'default' => _digi( 'Cancel' ),
                ),
            )
        );

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $signup_url = $model->digistoreReferenceUrl();

        $metas[] = array(
            'code' => 'buyer_to_affiliate',
            'description' =>  ncore_linkReplace( _digi( 'Shows a link for your members to become an affiliate in Digistore24. Remember to setup a <a>customer to affiliates programm</a> in Digistore24. If the user has no appropiate Digistore24 order, this shortcode is hidden.' ), $signup_url, $asPoptup=true ),
            'section'  => 'sales',
            'args' => array(
              array(
                    'label' => _digi( 'Product' ),
                    'name' => 'product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
                    'have_all' => true,
               ),
               array(
                    'label' => _digi( 'Link text' ),
                    'name' => 'text',
                    'type' => 'text',
               ),
               array(
                    'label' => _digi( 'Image URL' ),
                    'name' => 'img',
                    'type' => 'image_url',
               ),
               array(
                    'label' => _digi( 'Button background color' ),
                    'name' => 'button_bg',
                    'type' => 'color',
                    'default' => '#2196F3',
                    'rules'       => 'required',
                ),
               array(
                    'label' => _digi( 'Button text color' ),
                    'name' => 'button_fg',
                    'type' => 'color',
                    'default' => '#FFFFFF',
                    'rules'       => 'required',
                ),
                array(
                    'label'   => _digi( 'Corner radius' ),
                    'name'    => 'button_radius',
                    'type'    => 'select',
                    'options' => 'border_radius',
               ),
               array(
                    'label' => _digi( 'Confirm message' ),
                    'name' => 'confirm',
                    'type' => 'text',
                    'hint' => _digi( 'Use %s for line breaks.', '|' ),
                    'default' => _digi( 'You will now be redirected to Digistore24. There an Digistore24 account will be setup automatically for your.|Continue?' ),
               ),
            )
        );

        $metas[] = array(
            'code' => 'buyers_affiliate_name',
            'description' =>  _digi( 'Shows the Digistore24 affiliate name the current user has or will get (if he joins your Digistore24 buyer to affiliate programm). This works with wordpress accounts created by Digistore24 either because of an order or an affiliation.' ),
            'section'  => 'sales',
        );



        $metas[] = array(
            'code' => 'preview',
            'description' => _digi('For protected content: Marks the end of the free preview. Protected content after this shortcode stays protected.'),
            'section'  => 'protected',
        );


        $metas[] = array(
            'code' => 'download',
            'description' =>  _digi( 'Add a secured download link to your page. The URL is kept secret from the user.' ),
            'section'  => 'protected',
            'args' => array(
               array(
                    'label'   => _digi( 'Source URL' ),
                    'name'    => 'url',
                    'type'    => 'url',
               ),
               array(
                    'label'   => _digi( 'Link text' ),
                    'name'    => 'text',
                    'type'    => 'text',
                    'default' => _digi( 'Download now' ),
               ),
               array(
                    'label' => _digi( 'Image URL' ),
                    'name' => 'img',
                    'type' => 'image_url',
               ),
               array(
                    'label' => _digi( 'Button background color' ),
                    'name' => 'button_bg',
                    'type' => 'color',
                    'default' => '#2196F3',
                    'rules'       => 'required',
                ),
               array(
                    'label' => _digi( 'Button text color' ),
                    'name' => 'button_fg',
                    'type' => 'color',
                    'default' => '#FFFFFF',
                    'rules'       => 'required',
                ),
                array(
                    'label'   => _digi( 'Corner radius' ),
                    'name'    => 'button_radius',
                    'type'    => 'select',
                    'options' => 'border_radius',
               ),
            )
        );

        $metas[] = array(
            'code' => 'downloads_left',
            'description' =>  _digi( 'Display the number times the user may download the URL.' ),
            'section'  => 'protected',
            'args' => array(
               array(
                    'label' => _digi( 'Source URL' ),
                    'name' => 'url',
                    'type' => 'url',
               ),
//               array(
//                    'label' => _digi( 'Only for sales since' ),
//                    'name' => 'since',
//                    'type' => 'date',
//               ),
            )
        );

        $metas[] = array(
            'code' => 'if',
            'description' =>  _digi( 'Displays text depending on a condition.' ).'<br>'._digi('When using the Gutenberg Editor you can also use the separate DigiMember block “DigiMember If Block” for the ds_if shortcode.'),
            'section'  => 'protected',
            'args' => array(
               array(
                    'label' => _digi( 'has product' ),
                    'name' => 'has_product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
                    'hint' => _digi('If multiple products selected: The text is shown, if the user has any them.' ),
               ),
               array(
                    'label' => _digi( 'has not product' ),
                    'name' => 'has_not_product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
                    'hint' => _digi('If multiple products selected: The text is shown, if the user has neither of them.' ),
               ),
               array(
                    'label' => _digi( 'is logged in' ),
                    'name' => 'logged_in',
                    'type' => 'select',
                    'options' => array(
                        ''    => '',
                        'yes' => _digi('yes'),
                        'no'  => _digi('no'),
                    ),
               ),
               array(
                    'label' => _digi('only if'),
                    'name' => 'mode',
                    'type' => 'select',
                    'options' => array(
                        ''    => '',
                        'else'    => _digi('the previous condition did not match'),
                        'finally' => _digi('any condition so far did not match'),
                    ),
               ),
            )

        );


        $metas[] = array(
            'sort' => 100,
            'code' => 'digistore_download',
            'description' => _digi('For protected content hosted by Digistore24: Add download links for these files.'),
            'section'  => 'protected',
            'args' => array(
               array(
                    'label' => _digi( 'Product' ),
                    'name' => 'product',
                    'type' => 'checkbox_list',
                    'options' => 'product',
                    'seperator' => '<br />',
               ),
               array(
                'label' => _digi( 'Show texts of Digistore24' ),
                'name' => 'show_texts',
                'type' => 'checkbox',
               ),
               array(
                'label' => _digi( 'Icon' ),
                'name' => 'icon',
                'type' => 'select',
                'options' => array(
                                'download' => _digi( 'Download' ),
                                'file'     => _digi( 'File type' ),
                                'none'     => _digi( 'None' ),
                             ),
                'allow_null' => false,
               )
            ),
        );

        $metas[] = array(
            'sort' => 999,
            'code' => 'waiver_declaration',
            'description' => _digi('Ask the user to waive his right of revocation.'),
            'section'  => 'protected',
            'args' => array(
              array(
                    'label' => _digi( 'Button background color' ),
                    'name' => 'button_bg',
                    'type' => 'color',
                    'default' => '#2196F3',
                    'rules'       => 'required',
              ),
              array(
                    'label' => _digi( 'Button text color' ),
                    'name' => 'button_fg',
                    'type' => 'color',
                    'default' => '#FFFFFF',
                    'rules'       => 'required',
              ),
              array(
                    'label'   => _digi( 'Corner radius' ),
                    'name'    => 'button_radius',
                    'type'    => 'select',
                    'options' => 'border_radius',
              ),
            ),
        );

        $metas[] = array(
            'code' => 'socialproof',
            'description' => '',
            'hide' => true,
        );

        $metas[] = array(
            'code' => 'countdown',
            'description' => '',
            'hide' => true,
        );

        $metas[] = array(
            'code' => 'orderform',
            'description' => '',
            'hide' => true,
        );


        $metas[] = array(
            'code'     => 'smartupgrade',
            'hide'     => true,
        );

        $metas[] = array(
            'code' => 'api',
            'hide' => true,
        );

        $metas[] = array(
            'code' => 'api_version',
            'hide' => true,
        );

        $metas[] = array(
            'code' => 'sql_upgrade_info',
            'description' => '',
            'hide' => true,
        );


        $metas[] = array(
            'code' => 'shortcode',
            'hide' => true,
        );

        // $metas = apply_filters( 'ncore_shortcodes', $metas );

		return $metas;
	}

	//
	// protected
	//

	protected function filters()
	{
		$filters = parent::filters();

		// $filters['shortcodeLogoutAction'] = 'the_content';

		return $filters;
	}

	protected function actions()
	{
		$actions = parent::actions();

		// $actions[] = 'shortcodeLogoutAction';

		return $actions;
	}

	//
	// private
	//
    private $user_data_cache = array();
    private $auto_join_handled_for_post = array();
    private $have_autojoin_account_creation = false;
    private $last_condition_matched = false;
    private $any_condition_matched  = false;
    private $products_of_session_user = false;


    private function maybeHandleAutojoin()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );
        $can_use_autojoin = $model->canUseAutoJoin();
        if (!$can_use_autojoin) {
            return;
        }

        $post = get_post();
        if (empty($post) ||!empty($this->auto_join_handled_for_post[$post->ID])) {
            return;
        }

        $this->auto_join_handled_for_post[$post->ID] = true;

        $short_code = '[' . $this->shortcode( 'autojoin' );

        $have_shorcode = strpos( $post->post_content, $short_code ) !== false;
        if (!$have_shorcode) {
            return;
        }

        $pattern = get_shortcode_regex();
        if (!preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches )) {
            return;
        }

        foreach ($matches[0] as $code) {
            $have_autoresponder =strpos( $code, 'autoresponder' ) !== false;
            if ($have_autoresponder)
            {
                do_shortcode( $code );
            }
        }
    }


	private function renderLoginInfoInner( $target_url )
	{
		$avatar_size = 64;

		/** @var digimember_LinkLogic $model */
		$model = $this->api->load->model( 'logic/link' );

		$user_id   = ncore_userId();
		$user_name = ncore_userFirstName();

		$user_name = '<span class="digimember_display_name">' . $user_name . '</span>';

		$avatar = ncore_userImage( $user_id, $avatar_size );

		$logoff_url = $model->logoff( $target_url );
		$logoff_label = _ncore('Logoff');
		$logoff = "<a href='$logoff_url'>$logoff_label</a>";

		if ($avatar)
		{
			$css = '';
			$html_avatar = "<div class='digimember_image'>$avatar</div>";
		}
		else
		{
			$css = ' without-avatar';
			$html_avatar = '';
		}

		$welcome = _digi( 'Howdy, %1$s', $user_name );

		return "
<div class='digimember_login_info$css'>
	$html_avatar
	<div class='digimember_howdi'>$welcome</div>
	<div class='digimember_logoff'>$logoff</div>
</div>";
	}

	private function _renderProductLinks( $links )
	{
		$html = '<ul class="digimember_product_links">';
		foreach ($links as $one)
		{
			$label = $one->label;
			$url = $one->url;

			$html .= "<li><a href=\"$url\">$label</a></li>";

		}
		$html .= '</ul>';

		return $html;
	}

 	private function _redirectUrl( $attributes )
	{
		$url = ncore_retrieve( $attributes, 'url' );
		$page_id = ncore_retrieve( $attributes, 'page' );

		if ($page_id)
		{
			$url = $page_id;
		}
		elseif ($url)
		{
			// empty
		}
		else
		{
			$url = ncore_currentUrl();
		}

		$url = ncore_resolveUrl( $url );

		return $url;
	}

    private function _renderSpaces( $attr, $contents )
    {
        if (empty($contents)) {
            return '';
        }

        $space = ncore_retrieve( $attr, 'space' );
        if (empty($space)) {
            return $contents;
        }

        $prefix = (strpos( $space, 'before' ) !== false ? ' ' : '' );
        $suffix = (strpos( $space, 'after' )  !== false ? ' ' : '' );

        return $prefix.$contents.$suffix;

    }

    private function _get_first_and_last_name( $first_name=false, $last_name=false )
    {
        if ($first_name)
        {
            $this->user_data_cache[ 'first_name' ] = $first_name;
        }
        if ($last_name)
        {
            $this->user_data_cache[ 'last_name' ] = $last_name;
        }

        if (!isset($this->user_data_cache[ 'first_name' ]) || !isset( $this->user_data_cache[ 'last_name' ] ))
        {
            if (is_user_logged_in())
            {
                $current_user = wp_get_current_user();
                $this->user_data_cache[ 'first_name' ] = $current_user->user_firstname;
                $this->user_data_cache[ 'last_name' ]  = $current_user->user_lastname;
            }
            else
            {
                $this->user_data_cache[ 'first_name' ] = '';
                $this->user_data_cache[ 'last_name' ]  = '';
            }
        }

        return array( $this->user_data_cache[ 'first_name' ], $this->user_data_cache[ 'last_name' ] );
    }

    private function _get_user_email( $email=false )
    {
        if ($email)
        {
            $this->user_data_cache[ 'email' ] = $email;
        }

        if (!isset( $this->user_data_cache[ 'email' ] ))
        {
            if (is_user_logged_in())
            {
                $current_user = wp_get_current_user();
                $this->user_data_cache[ 'email' ]  = $current_user->user_email;
            }
            else
            {
                $this->user_data_cache[ 'email' ]  = '';
            }
        }


        return $this->user_data_cache[ 'email' ];
    }

    private function _get_user_login( $username=false )
    {
        if ($username)
        {
            $this->user_data_cache[ 'user_login' ] = $username;
        }
        if (!isset($this->user_data_cache[ 'user_login' ]))
        {
            if (is_user_logged_in())
            {
                $current_user = wp_get_current_user();
                $this->user_data_cache[ 'user_login' ] = $current_user->user_login;
            }
            else
            {
                $this->user_data_cache[ 'user_login' ] = '';
            }
        }

        return $this->user_data_cache[ 'user_login' ];
    }

    private function sessionUsersProducts()
    {
        if ($this->products_of_session_user===false)
        {
            /** @var digimember_UserProductData $model */
            $model = $this->api->load->model( 'data/user_product' );
            $this->products_of_session_user = $model->getForUser();
        }

        return $this->products_of_session_user;
    }

    private function hasProduct( $look_for_product_ids )
    {
        if (!$look_for_product_ids) {
            return false;
        }

        // if (ncore_canAdmin()) {
        //     return true;
        // }

        if (is_string($look_for_product_ids)) {
            $look_for_product_ids = explode( ',', $look_for_product_ids );
        }

        $products = $this->sessionUsersProducts();

        foreach ($products as $one)
        {
            $id = $one->product_id;

            if (in_array( $id, $look_for_product_ids)) {
                return true;
            }
        }

        return false;
    }


    private function _shortcodeUrl( $url, $attributes )
    {
        $url = str_replace( '&amp;', '&', $url );

        $label     = ncore_retrieve( $attributes, 'text' );
        $image_url = ncore_retrieve( $attributes, 'img' );
        $confirm   = ncore_retrieve( $attributes, 'confirm' );

        if (!$label && !$image_url) {
            return $url;
        }

        $this->api->load->helper( 'html' );

        $image_url = str_replace( '&amp;', '&', $image_url );
        $label = str_replace( '"', '', $label);

        $on_click_js = '';
        if ($confirm)
        {
            $find = array( "\r", "\r\n", '|' );
            $repl = "\\n\\n";
            $confirm = str_replace( $find, $repl, $confirm );

            $find = array( '"', "'" );
            $repl = array( "''", "\\'" );
            $confirm = str_replace( $find, $repl, $confirm );

            $on_click_js = "onclick=\"return confirm( '$confirm' );\"";
        }

        $css = '';
        $atr = '';
        $style = ncore_renderButtonStyle( $attributes, 'button_' );
        if ($style)
        {
            $css .= 'button button-primary ncore_custom_button';
            $atr = "style=\"$style\"";
        }




        if (!$image_url)
        {
            return "<a $atr $on_click_js class='digimember_protected_download digimember_link $css' target='_blank' href=\"$url\">$label</a>";
        }

        $image = "<img src=\"$image_url\">";

        return "<a $atr $on_click_js class='digimember_protected_download digimember_button $css' target='_blank' href=\"$url\" title=\"$label\">$image</a>";
    }

    private function _DS24constructedUrl( $base_url, $attributes )
    {
        $products_comma_seperated = trim( ncore_retrieve( $attributes, 'product' ) );

        $have_all = strpos( $products_comma_seperated, 'all' ) !== false;

        $product_ids = $products_comma_seperated && !$have_all
                  ? explode( ',' , $products_comma_seperated )
                  : array();


        $api = ncore_api();
        /** @var digimember_UserProductData $model */
        $model = $api->load->model( 'data/user_product' );

        $order_ids = array();
        $auth_keys = array();

        $orders = $model->getForUser();
        foreach ($orders as $one)
        {
            if ($have_all)
            {
                $is_product_match = true;
            }
            elseif ($product_ids)
            {
                $is_product_match = false;
                foreach ($product_ids as $product_id) {
                    $product_id = (int) trim($product_id);
                    if ($product_id && $one->product_id == $product_id)
                    {
                        $is_product_match = true;
                        break;
                    }
                }
            }
            else
            {
                $is_product_match = true;
            }

            if (!$is_product_match) {
                continue;
            }

            $ds24_purchase_id = ncore_retrieve( $one, 'order_id' );
            $ds24_auth_key    = ncore_retrieve( $one, 'ds24_purchase_key' );

            if ($ds24_purchase_id && $ds24_auth_key)
            {
                $order_ids[] = $ds24_purchase_id;
                $auth_keys[] = $ds24_auth_key;
            }
        }
        if (!$order_ids) {
            return '';
        }

        $url = rtrim( $base_url, '/' ) . '/' . implode( ',', $order_ids ) . '/' . implode( ',', $auth_keys ) . '/';

        return $this->_shortcodeUrl( $url, $attributes );
    }

    private function _DS24predefinedUrl( $url_column, $attributes )
    {
        $products_comma_seperated = trim( ncore_retrieve( $attributes, 'product' ) );

        $have_all = strpos( $products_comma_seperated, 'all' ) !== false;

        $product_ids = $products_comma_seperated && !$have_all
                  ? explode( ',' , $products_comma_seperated )
                  : array();


        $api = ncore_api();

        /** @var digimember_UserProductData $model */
        $model = $api->load->model( 'data/user_product' );

        $orders = $model->getForUser('current', 'order_date DESC');
        foreach ($orders as $one)
        {
            if ($have_all)
            {
                $is_product_match = true;
            }
            elseif ($product_ids)
            {
                $is_product_match = false;
                foreach ($product_ids as $product_id) {
                    $product_id = (int) trim($product_id);
                    if ($product_id && $one->product_id == $product_id)
                    {
                        $is_product_match = true;
                        break;
                    }
                }
            }
            else
            {
                $is_product_match = true;
            }

            if (!$is_product_match) {
                continue;
            }

            $url = ncore_retrieve( $one, $url_column );

            if ($url)
            {
                return $this->_shortcodeUrl( $url, $attributes );
            }
        }

        return '';
    }

    private function _prepareWidgetInputMetasLoginSelection( &$metas )
    {
        $visible_options = array(
            ''           => _digi( 'Logged in and logged out users' ),
            'logged_in'  => _digi( 'Logged in users' ),
            'logged_out' => _digi( 'Logged out users' ),
        );

        $metas[] = array(
                'name' => 'dm_visible',
                'section' => 'access',
                'type' => 'select',
                'options' => $visible_options,
                'label' => _digi('Visible for' ),
        );
    }


    private function _prepareWidgetInputMetasProductSelection( &$metas )
    {
        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );
        $product_options = $productData->options( 'membership' );

        $all_products_label = _digi( 'Any product' );

        $metas[] = array(
                'name' => 'dm_owned_product_ids',
                'section' => 'access',
                'type' => 'checkbox_list',
                'options' => $product_options,
                'label' => _digi('Only visible if product owned' ),
                'have_all' => true,
                'row_size' => 1,
                'all_label' => $all_products_label,
                'depends_on' => array( 'dm_visible' => 'logged_in' ),
        );

        $metas[] = array(
                'name' => 'dm_pages_of_product_ids',
                'section' => 'access',
                'type' => 'checkbox_list',
                'options' => $product_options,
                'label' => _digi('Only visible on pages of product' ),
                'have_all' => true,
                'row_size' => 1,
                'all_label' => $all_products_label,
                'depends_on' => array( 'dm_visible' => 'logged_in' ),
        );
    }

    private function _shortcodeCourseGotoLink($type, $rec, $is_first=false, /** @noinspection PhpUnusedParameterInspection */ $is_last=false, $color='', $bg='', $radius=0 )
    {
        $type                = ncore_washText( $type );
        $color               = ncore_washText( $color );
        $icon_collection_url = $this->api->pluginUrl( "/webinc/image/page/player/$color/$type.png" );

        $is_disabled = empty( $rec[ 'url' ] );

        $extra_css = $is_disabled
                   ? 'digimember_disabled'
                   : '';

        $style = '';
        if ($bg || $color || $radius)
        {
            if ($bg) {
                $style .= "background-color: $bg;";
            }

            if ($radius) {
                $style .= "border-radius: ${radius}px;";
            }
        }

        $html = "<img class='dm_lecture_nav_button' src='$icon_collection_url' style=\"$style\"  />";

        $prefix = $is_first
                ? ''
                : '&nbsp;';

        $title = $rec[ 'title' ];
        $label = $rec[ 'label' ];

        $tooltip = $label . ': ' . $title;

        return "$prefix<a style=\"$style\"  class='dm_lecture_nav_button dm_lecture_nav_$type $extra_css' href='${rec['url']}' \" title=\"$tooltip\">$html</a>";
    }

}
