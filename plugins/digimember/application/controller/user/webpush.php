<?php

$load->controllerBaseClass( 'user/form' );

class digimember_UserWebpushController extends ncore_UserBaseController
{
    private $is_checkbox_list;
    private $is_checkbox_webpush;
    private $is_checkbox_email;

    public function init( $settings=array() )
    {
        parent::init( $settings );

        $layout = ncore_retrieve( $settings, 'layout', 'default' );
        $show   = ncore_retrieve( $settings, 'show',   'all' );

        $this->is_checkbox_list = $layout == 'checkbox_list';

        $this->is_checkbox_webpush = $show === 'all' || $show === 'webpush';
        $this->is_checkbox_email   = $show === 'all' || $show === 'email';

        $this->show_optout_button = $settings && is_array( $settings )
                                    ? in_array( 'optout', $settings ) || !empty( $settings['optout'] )
                                    : $settings === 'optout';


        /** @var digimember_WebpushSettingsData $webpushSettingsData */
        $webpushSettingsData = $this->api->load->model( 'data/webpush_settings' );
        /** @var digimember_WebpushLogic $webpushLogic */
        $webpushLogic = $this->api->load->model( 'logic/webpush' );

        $can_use = $webpushLogic->canUse();
        if (!$can_use) {
            $this->settings = false;
            return;
        }

        $this->html_logic = $this->api->load->model( 'logic/html' );
        $this->settings   = $webpushSettingsData->getDefaultSettings();

        list( $this->public_key/*, $private_key*/ ) = $webpushLogic->getPublicAndPrivateKey();

        if (!$this->public_key) {
            $this->settings = false;
        }

        if (empty($this->settings))
        {
            $this->is_checkbox_webpush = false;
        }

        if ($this->is_checkbox_email && !ncore_userId())
        {
            $this->is_checkbox_email = false;
        }

//FEATURE: LIMIT PUSH OPTIN TRIES//        if ($this->isDirectAndMustSkrip())
//FEATURE: LIMIT PUSH OPTIN TRIES//        {
//FEATURE: LIMIT PUSH OPTIN TRIES//            $this->settings = false;
//FEATURE: LIMIT PUSH OPTIN TRIES//        }

        $this->initJs();
    }


    protected function view()
    {
        if (!$this->settings) {
            if ($this->is_checkbox_list )
            {
                echo $this->renderCheckboxList();
            }
            return;
        }

        $optin_methods_with_button = array( 'button', 'image' );

        $have_button    = in_array( $this->settings->optin_method, $optin_methods_with_button );

        $js_onload = "digimember_webpushInit();";

        if ($this->is_checkbox_list )
        {
            echo $this->renderCheckboxList();
        }
        elseif ($have_button) {

            echo $this->renderButton();

        } else {
            $function = $this->jsSubscribeWithConfirmDialogFunctionName();
            $js_onload .= "$function();";
        }


        $this->html_logic->jsOnLoad( $js_onload );
    }

    /** @var bool | stdClass */
    private $settings = false;
    /** @var string */
    private $public_key;
    /** @var ncore_HtmlLogic */
    private $html_logic;
    /** @var bool */
    private $show_optout_button = false;

    private function renderCheckboxList()
    {
        $is_nothing_to_do = !$this->is_checkbox_webpush && !$this->is_checkbox_email;
        if ($is_nothing_to_do) {
            return;
        }

        $this->api->load->helper( 'html_input' );

        echo "
<div class='dm_manage_subscription'>
<form onsubmit='return false;' action='#' method='GET' >";

        if ($this->is_checkbox_webpush)
        {
            $checked = true;

            $label = _digi( 'Receive news via desktop notifications' );

            echo '<div class="dm_manage_subscription_webpush" style="display: none;">', ncore_htmlCheckbox( 'dummy', $checked, $label ), '</div>';
        }
        if ($this->is_checkbox_email)
        {
            /** @var digimember_ActionLogic $actionLogic */
            $actionLogic = $this->api->load->model( 'logic/action' );
            $is_opted_out = $actionLogic->isOptedOutFromEmail();
            $checked      = !$is_opted_out;

            $label = _digi( 'Receive news via email' );
            echo '<div class="dm_manage_subscription_email">', ncore_htmlCheckbox( 'dummy', $checked, $label ), '</div>';
        }

        echo '</form></div>';
    }

    private function renderButton()
    {
        if (empty($this->settings)) {
            return;
        }

        $function = $this->jsSubscribeWithConfirmDialogFunctionName();
        $js_subscribe = "$function();";

        echo $this->_renderButtonInner( 'optin', $js_subscribe, $is_primary_button=true );

        if ($this->show_optout_button)
        {
            $js_unsubscribe = "digimember_webpushUnsubscribe();";

            echo $this->_renderButtonInner( 'optout', $js_unsubscribe, $is_primary_button=false );
        }
    }

    private function initJs()
    {
        if (empty($this->settings)) {
            return;
        }

        static $initialized;

        $must_init_subscribe_function = !isset($initialized);


        if ($must_init_subscribe_function)
        {
            $initialized = array();
            $js = $this->_renderSubcribeFunctionJs();
            $this->html_logic->jsFunction( $js );
        }

        if (empty($initialized[ $this->settings->id ]))
        {
            $initialized[ $this->settings->id ] = array();
        }

        $must_init_confirm_dialog = !in_array( 'confirm',    $initialized[ $this->settings->id ] );
        $must_init_exit_popup     = !in_array( 'exit_popup', $initialized[ $this->settings->id ] )
                                 && !$this->is_checkbox_list
                                 && ncore_isTrue( $this->settings->use_exit_popup_dialog );

        if ($must_init_confirm_dialog)
        {
            $initialized[ $this->settings->id ][] = 'confirm';

            $have_init_confirm_dlg = ncore_isTrue( $this->settings->use_confirm_dialog );

            $js_subscribe = $have_init_confirm_dlg
                          ? $this->_renderConfirmDialog()
                          : 'digimember_webpushSubscribe();';

            $js_function_name = $this->jsSubscribeWithConfirmDialogFunctionName();

            $scope = $this->api->pluginUrl();

            $js_function = "
function ${js_function_name}_exec()
{
    $js_subscribe;
}
function $js_function_name()
{
    console.log( '$js_function_name: init' );

    var is_valid = typeof navigator.serviceWorker != 'undefined'
                && typeof navigator.serviceWorker.getRegistration != 'undefined';

    if (is_valid && !digimember_webpushIsSubscribed()) {

        navigator.serviceWorker.getRegistration('$scope').then(
            function(registration) {
                console.log( '$js_function_name: got registration ', registration );
                var is_valid = registration && registration.pushManager;
                if (is_valid)
                {
                    registration.pushManager.getSubscription().then(
                        function(subscription) {
                            if (subscription)
                            {
                                console.log( '$js_function_name: is already subscribed' );
                            }
                            else
                            {
                                console.log( '$js_function_name: have registration, but no subscription' );
                                ${js_function_name}_exec();
                            }
                        },
                        function() {
                            console.log( '$js_function_name: subscription check failed' );
                            ${js_function_name}_exec();
                        }
                    );
                }
                else
                {
                    console.log( '$js_function_name: have no registration' );
                    ${js_function_name}_exec();
                }

            },
            function() {
                console.log( '$js_function_name: registration check failed' );
                ${js_function_name}_exec();
            }
        );
    }
}
";
            $this->html_logic->jsFunction( $js_function );
        }

        if ($must_init_exit_popup) {

            $initialized[ $this->settings->id ][] = 'exit_popup';

            $js_show = $this->_renderExitPopupDialog();

            $dialog_id = $this->exitPopupDialogId();

            $js = "
if (digimember_haveWebPush() && !digimember_webpushIsSubscribed() && digimember_canShowExitPopup(false))
{
    ncoreJQ(document).mousemove(function(e) {

        if (e.clientY < 5) {

            var is_visible = ncoreJQ('#$dialog_id').is(':visible');

            if (!is_visible && !digimember_webpushIsSubscribed() && digimember_canShowExitPopup(true)) {
                $js_show;
            }
        }
    });
}
";
            $this->html_logic->jsOnLoad( $js );
        }
    }

    private function confirmDialogId()
    {
        return 'dm_webpush_confirm_' . $this->settings->id;
    }
    private function exitPopupDialogId()
    {
        return 'dm_webpush_exitpopup_' . $this->settings->id;
    }

    private function jsSubscribeWithConfirmDialogFunctionName()
    {
         return 'digimember_webpushSubscribeWithConfirmDialog_' . $this->settings->id;
    }


    private function _renderConfirmDialog()
    {
        /** @var ncore_AjaxLib $lib */
        $lib = $this->api->load->library( 'ajax' );

        $dialog_id = $this->confirmDialogId();

        $js_subcribe = "
ncoreJQ( '#$dialog_id' ).dmDialog( 'close' );
digimember_webpushSubscribe();
";

        $meta = array(
            'type'                => 'confirm',
            'ajax_dlg_id'         => $dialog_id,
            'dialogClass'         => 'dm_webpush_confirm_dialog',
            'message'             => $this->settings->confirm_msg,
            'title'               => ($this->settings->confirm_title ? $this->settings->confirm_title : '&nbsp;'),
            'ok_button_label'     => $this->settings->confirm_button_ok,
            'cancel_button_label' => $this->settings->confirm_button_cancel,
            'width'               => '500px',
            'cb_js_code'          => $js_subcribe,
        );

        $dialog = $lib->dialog( $meta );

        $js = $dialog->showDialogJs();

        return $js;
    }

    private function _renderExitPopupDialog()
    {
        /** @var ncore_AjaxLib $lib */
        $lib = $this->api->load->library( 'ajax' );

        $dialog_id = $this->exitPopupDialogId();

        $js_subcribe = "
ncoreJQ( '#$dialog_id' ).dmDialog( 'close' );
digimember_webpushSubscribe();
";

        $meta = array(
            'type'                => 'confirm',
            'ajax_dlg_id'         => $dialog_id,
            'dialogClass'         => 'dm_webpush_exit_popup',
            'message'             => $this->settings->exit_popup_msg,
            'title'               => ($this->settings->exit_popup_title ? $this->settings->exit_popup_title : '&nbsp;'),
            'ok_button_label'     => $this->settings->exit_popup_button_ok,
            'cancel_button_label' => $this->settings->exit_popup_button_cancel,
            'width'               => '500px',
            'cb_js_code'          => $js_subcribe,
        );

        $dialog = $lib->dialog( $meta );

        $js = $dialog->showDialogJs();

        return $js;
    }


    private function _renderSubcribeFunctionJs()
    {
        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model( 'logic/link' );

        $public_key  = $this->public_key;

        $store_subscription_url = $linkLogic->ajaxUrl( 'ajax/webpush', 'store_subscription', array( 'key' => '__KEY__', 'token' => '__TOKEN__', 'endpoint' => '__ENDPOINT__', 'action' => '__ACTION__' ) );
        $local_js_base_url      = parse_url( $this->api->pluginUrl(), PHP_URL_PATH );
        $service_worker_url     = rtrim( $local_js_base_url, '/ ') . '/webpush_service_worker.js.php';

        $email_subscribe_url   = $linkLogic->ajaxUrl( 'ajax/unsubscribe', 'set', array( 'is_subscribe' => 'Y', 'input_container_class' => 'dm_manage_subscription_email' ) );
        $email_unsubscribe_url = $linkLogic->ajaxUrl( 'ajax/unsubscribe', 'set', array( 'is_subscribe' => 'N', 'input_container_class' => 'dm_manage_subscription_email' ) );

        $max_count = max( 1, $this->settings->max_exit_popup_count );
        $max_days  = max( 1, $this->settings->max_exit_popup_days );

        $use_confirm_dialog   = ncore_isTrue( $this->settings->use_confirm_dialog );
        $js_webpush_subscribe = $use_confirm_dialog
                              ? $this->jsSubscribeWithConfirmDialogFunctionName() . '();'
                              : "dmDialogAjax_Start(); digimember_webpushSubscribe(); window.Settimeout( 'dmDialogAjax_Stop();', 500 );";

        return "
function digimember_haveWebPush()
{
    return navigator.serviceWorker
           ? true
           : false;
}

function digimember_webpushInit()
{
    ncoreJQ( '.dm_manage_subscription_webpush input[type=checkbox]' ).change(function() {

        var is_checked = ncoreJQ( this ).prop( 'checked' );

        if (is_checked) {
            $js_webpush_subscribe
        } else {
            dmDialogAjax_Start();
            digimember_webpushUnsubscribe();
            window.Settimeout( 'dmDialogAjax_Stop();', 500 );
        }

        return true;
    });

    ncoreJQ( '.dm_manage_subscription_email input[type=checkbox]' ).change(function() {

        dmDialogAjax_Start();

        var is_checked = ncoreJQ( this ).prop( 'checked' );

        if (is_checked)
            dmDialogAjax_FetchUrl( '$email_subscribe_url' );
        else
            dmDialogAjax_FetchUrl( '$email_unsubscribe_url' );

        return true;
    });

    navigator.serviceWorker.register( '$service_worker_url' ).then( function(registration) {

        console.log( 'digimember_webpushInit: Done register service worker', registration );

        if (registration.pushManager)
        {
            console.log( 'digimember_webpushInit: Have push manager' );

            registration.pushManager.getSubscription().then( function(subscription)
            {
                console.log( 'digimember_webpushInit: Got subscription: ', subscription );

                ncoreJQ( '.dm_manage_subscription_webpush' ).show();

                if (subscription) {
                    digimember_hideSubscribeButton();
                    return digimember_sendSubscriptionToServer(subscription,'update');
                }
                else
                {
                    digimember_showSubscribeButton();
                }
            } );
        }
    } );
}



function digimember_hideSubscribeButton()
{
    DM_WEBPUSH_IS_SUBSCRIBED = true;

    ncoreJQ( 'button.dm_webpush_optin' ).hide();
    ncoreJQ( 'button.dm_webpush_optout' ).show();

    ncoreJQ( '.dm_manage_subscription_webpush input' ).prop( 'checked', true );
}

function digimember_showSubscribeButton()
{
    DM_WEBPUSH_IS_SUBSCRIBED = false;

    ncoreJQ( 'button.dm_webpush_optout' ).hide();
    ncoreJQ( 'button.dm_webpush_optin' ).show();

    ncoreJQ( '.dm_manage_subscription_webpush input' ).prop( 'checked', false );
}


function digimember_sendSubscriptionToServer( subscription, action )
{
        var key      = subscription.getKey('p256dh');
        var token    = subscription.getKey('auth');
        var endpoint = subscription.endpoint;

        key   = (key   ? btoa(String.fromCharCode.apply(null, new Uint8Array(key)))   : null);
        token = (token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null);

        var url = \"$store_subscription_url\";

        url = url.replace( /__KEY__/, key ).replace( /__TOKEN__/, token ).replace( /__ENDPOINT__/, endpoint ).replace( /__ACTION__/, action );

        dmDialogAjax_FetchUrl( url, true );
}

function digimember_webpushSubscribe()
{
    if (!digimember_haveWebPush()) return;

    digimember_hideSubscribeButton();
    DM_WEBPUSH_IS_SUBSCRIBED = true;

    if (navigator.serviceWorker) {

        console.log( 'digimember_webpushSubscribe: Subcribing ...' );

        navigator.serviceWorker.register( '$service_worker_url' ).then( function(registration) {

                console.log( 'digimember_webpushSubscribe: Have registration:', registration );

                if (!registration.pushManager) {
                    console.log( 'digimember_webpushSubscribe: Do not have push manager' );
                    return;
                }
                registration.pushManager.getSubscription().then( function(subscription)
                {
                    if (subscription)
                    {
                        digimember_sendSubscriptionToServer( subscription, 'update' );
                        return;
                    }

                    console.log( 'digimember_webpushSubscribe: Before Subscribing ...' );

                    registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: ncore_urlBase64ToUint8Array('$public_key'),
                    }).then( function(subscription) {
                        console.log( 'digimember_webpushSubscribe: Subscribing' );
                        return digimember_sendSubscriptionToServer( subscription, 'update' );
                    } ).catch(function (ex) {
                        console.error('Could not subscribe', ex);
                    });
                });
        });
    }
}

function digimember_webpushUnsubscribe()
{
    if (!digimember_haveWebPush()) return;

    digimember_showSubscribeButton();
    DM_WEBPUSH_IS_SUBSCRIBED = false;

    if (navigator.serviceWorker) {

        navigator.serviceWorker.register( '$service_worker_url' ).then( function(registration) {

                if (!registration.pushManager) {
                    return;
                }
                registration.pushManager.getSubscription().then( function(subscription)
                {
                    if (!subscription)
                    {
                        return;
                    }

                    digimember_sendSubscriptionToServer( subscription, 'delete' );

                    subscription.unsubscribe();
                });
        });
    }
}

function digimember_webpushIsSubscribed() {

    return typeof DM_WEBPUSH_IS_SUBSCRIBED !== 'undefined' && DM_WEBPUSH_IS_SUBSCRIBED;
}

function digimember_canShowExitPopup( inc_counter )
{
    return ncore_canShowByCookieCount( 'webpush_exit_popup', $max_count, $max_days, inc_counter );
}
";
    }


    private function _renderButtonInner( $optin, $js, $is_primary_button )
    {
        $method = ncore_retrieve( $this->settings, $optin.'_method' );

        $is_image  = $method == 'image';
        $is_button = $method == 'button';

        if (!$is_image && !$is_button) {
            return '';
        }

        $style = 'display: none;';

        $inner_html = '';
        $is_text    = true;
        if ($is_image )
        {
            $image_id = ncore_retrieve( $this->settings, $optin.'_button_image_id' );
            if ($image_id)
            {
                $url  = wp_get_attachment_url( $image_id );

                if ($url) {
                    $meta = wp_get_attachment_metadata( $image_id );
                    $have_size = !empty( $meta['width'] ) && !empty( $meta['height'] );
                    $size_attr = $have_size
                               ? "style=\"width: ${meta['width']}px; height: ${meta['height']};\""
                               : '';
                    $inner_html = "<img $size_attr src=\"$url\" alt='' />";
                    $is_text    = false;
                }
            }
        }
        else
        {
            $label      = ncore_retrieve( $this->settings, $optin.'_button_label' );
            $inner_html = $label;
        }

        if (!$inner_html)
        {
            /** @var digimember_WebpushSettingsData $webpushSettingsData */
            $webpushSettingsData = $this->api->load->model('data/webpush_settings');
            $inner_html = $webpushSettingsData->defaultValue( $optin.'_button_label' );
        }

        if ($is_text)
        {
            $bg_color      = ncore_retrieve( $this->settings, $optin.'_button_bg_color', false );
            $fg_color      = ncore_retrieve( $this->settings, $optin.'_button_fg_color', false );
            $border_radius = ncore_retrieve( $this->settings, $optin.'_button_radius',   false );


            $style .= "border-radius: ${border_radius}px; background-color: $bg_color; color: $fg_color;";
        }

        $css = "dm_webpush_button dm_webpush_$optin";

        $css .= $is_text
              ? ' dm_webpush_text_button button' . ($is_primary_button ? ' button-primary' : '')
              : ' dm_webpush_image_button';

        return "<button style='$style' onclick=\"$js;\" class='$css'>$inner_html</button>";
    }

//FEATURE: LIMIT PUSH OPTIN TRIES//    private function isDirectAndMustSkrip()
//FEATURE: LIMIT PUSH OPTIN TRIES//    {
//FEATURE: LIMIT PUSH OPTIN TRIES//        if (!$this->settings
//FEATURE: LIMIT PUSH OPTIN TRIES//            || $this->settings->optin_method != 'direct'
//FEATURE: LIMIT PUSH OPTIN TRIES//            || $this->settings->direct_show_count >= 999)
//FEATURE: LIMIT PUSH OPTIN TRIES//        {
//FEATURE: LIMIT PUSH OPTIN TRIES//            return false;
//FEATURE: LIMIT PUSH OPTIN TRIES//        }

//FEATURE: LIMIT PUSH OPTIN TRIES//        static $cache;

//FEATURE: LIMIT PUSH OPTIN TRIES//        $is_skipped = $cache[ $this->settings->id ];
//FEATURE: LIMIT PUSH OPTIN TRIES//        if (isset($is_skipped)) {
//FEATURE: LIMIT PUSH OPTIN TRIES//            return $is_skipped;
//FEATURE: LIMIT PUSH OPTIN TRIES//        }

//FEATURE: LIMIT PUSH OPTIN TRIES//        $cookie_name = 'dm_webpush_optin_count_'.$this->settings->id;

//FEATURE: LIMIT PUSH OPTIN TRIES//        list( $date_time, $count ) = ncore_retrieveList( '/', ncore_retrieve( $_COOKIE, $cookie_name, '2010-01-01 00:00:00/0' ) );

//FEATURE: LIMIT PUSH OPTIN TRIES//        $is_date_valid = $date_time && $date_time > ncore_dbDate();
//FEATURE: LIMIT PUSH OPTIN TRIES//        if (!$is_date_valid)
//FEATURE: LIMIT PUSH OPTIN TRIES//        {
//FEATURE: LIMIT PUSH OPTIN TRIES//            $timeout = max( 600, $this->settings->direct_show_interval_seconds );
//FEATURE: LIMIT PUSH OPTIN TRIES//            $count = 0;
//FEATURE: LIMIT PUSH OPTIN TRIES//            $date_time = ncore_dbDate( time() + $timeout );
//FEATURE: LIMIT PUSH OPTIN TRIES//        }

//FEATURE: LIMIT PUSH OPTIN TRIES//        $count++;

//FEATURE: LIMIT PUSH OPTIN TRIES//        $is_skipped = $count > $this->settings->direct_show_count;

//FEATURE: LIMIT PUSH OPTIN TRIES//        if (!$is_skipped) {
//FEATURE: LIMIT PUSH OPTIN TRIES//            setcookie( $cookie_name, "$date_time/$count", 400*86400, '/' );
//FEATURE: LIMIT PUSH OPTIN TRIES//        }

//FEATURE: LIMIT PUSH OPTIN TRIES//        return $is_skipped;
//FEATURE: LIMIT PUSH OPTIN TRIES//    }
}