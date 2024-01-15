<?php

class ncore_ActionLogic extends ncore_BaseLogic
{
    function actionExpireDays( $condition_type=false )
    {
        $default_days = 120;

        $condition_type_days_map = array(
            'page_view'    => 60,
            'login'        => 30,
            'paused'       => 60,
            'never_online' => 60,
            'prd_expired'  => 21,
            'new_content'  => 7,
        );

        return $condition_type === 'all'
               ? $condition_type_days_map
               : ($condition_type
                  ? ncore_retrieve( $condition_type_days_map, $condition_type, $default_days )
                  : $default_days);
    }

    function validateUnsubscribeKey( $unsubscribe_key )
    {
        if (!$unsubscribe_key) {
            return false;
        }

        list( $user_id,  ) = ncore_retrieveList( '_', $unsubscribe_key, 2, true );

        if (!is_numeric($user_id) || $user_id<=0) {
            return false;
        }

        $epxected_key = $this->unsubscribeKey( $user_id );

        $this->api->load->helper( 'encryption' );
        $is_valid = ncore_hashCompare( $unsubscribe_key, $epxected_key );

        return $is_valid
               ? $user_id
               : false;
    }

    function renderQueueActionJs( $email_or_wp_user_id, $action )
    {
        $signature = $this->_actionSignature( $email_or_wp_user_id, $action->id );

        /** @var digimember_LinkLogic $link */
        $link = $this->api->load->model( 'logic/link' );

        $args = array(
                'action_id' => $action->id,
                'signature' => $signature,
                'user_id'   => $email_or_wp_user_id,
                );

        $url = $link->ajaxUrl( 'ajax/misc', 'action_delayed', $args );

        $js = "dmDialogAjax_FetchUrl( '$url', do_omit_waiting=true );";

        return $js;

    }

    function conditionOptions()
    {
        $options = array(
            'page_view'    => _ncore( 'by viewing a page' ),
            'login'        => _ncore( 'by a login' ),
            'paused'       => _ncore( 'after paused using this website' ),
            'never_online' => _ncore( 'if never logged in' ),
            'prd_expired'  => _ncore( 'if product access expires' ),
            'new_content'  => _digi( 'if user has access to new content' ),
        );

        if (!DIGIMEMBER_HAVE_NEW_CONTENT_ACTION)
        {
            unset( $options[ 'new_content' ] );
        }

        return $options;
    }

    function queueAction( $action_list_or_action, $wp_user_id_or_email, $is_test_run=false )
    {
        $this->_queueAction( $action_list_or_action, $wp_user_id_or_email, $is_test_run );
    }

    function onNewContent($user_id, /** @noinspection PhpUnusedParameterInspection */ $product_id )
    {
        if (!DIGIMEMBER_HAVE_NEW_CONTENT_ACTION)
        {
            return;
        }

        $where = array();

        $where[ 'is_active' ] = 'Y';
        $where[ 'condition_type' ] = 'new_content';

        $this->_queueActionsWhere( $where, $user_id );
    }

    function onLogin( $user_id,  $login_count, $last_login)
    {
        $where = array();
        $where[ 'is_active' ] = 'Y';
        $where[ 'condition_type' ] = 'login';
        $where[ 'condition_login_count <=' ] = $login_count;

        if ($login_count>=5) {
            $where[ 'condition_login_count >' ] = $login_count-5;
        }

        $days_since_login = max( 0, round( (ncore_unixDate() - ncore_unixDate( $last_login )) / 86400 ) );
        $where[ 'condition_login_after_days <=' ] = $days_since_login;

        $this->_queueActionsWhere( $where, $user_id );
    }

    function onPageView( $user_id,  $wp_page )
    {
        $where = array();
        $where[ 'is_active' ] = 'Y';
        $where[ 'condition_type' ] = 'page_view';
        $where[ 'condition_page' ] = $wp_page->ID;

        /** @var ncore_ActionData $actionData */
        $actionData = $this->api->load->model( 'data/action' );
        $all = $actionData->getAll( $where );

        foreach ($all as $one)
        {
            /** @var ncore_ActionOutQueue $model */
            $model       = $this->api->load->model( 'queue/action_out' );
            $have_action = $model->haveUnexpiredAction( $user_id, $one->id );


            if (!$have_action)
            {
                $call = $this->renderQueueActionJs( $user_id, $one );

                $is_delayed = $one->condition_page_view_time > 0;

                if ($is_delayed)
                {
                    $delay = $one->condition_page_view_time * 1000;

                    $js = "window.setTimeout(function(){ $call }, $delay );";
                }
                else
                {
                    $js = $call;
                }

                /** @var ncore_HtmlLogic $html */
                $html = $this->api->load->model( 'logic/html' );

                $html->jsOnLoad( $js );
            }
        }
    }

    function cronMinutely()
    {
        $this->_queuePausedUsingSiteActions();
        $this->_queueNeverLoggedinActions();
    }

    function cronDaily()
    {
        // empty
    }

    function queueDelayedAction( $email_or_wp_user_id, $action_id, $signature ) {

        $expected_sig = $this->_actionSignature( $email_or_wp_user_id, $action_id );

        $is_valid = ncore_hashCompare( $signature, $expected_sig );
        if (!$is_valid) {
            return;
        }

        $where = array();
        $where[ 'id' ]             = $action_id;
        $where[ 'is_active' ]      = 'Y';

        $this->_queueActionsWhere( $where, $email_or_wp_user_id );
    }

    function statusOptions()
    {
        return array(
            'skipped' => '-',
            'error'   => _ncore( 'Error' ),
            'success' => _digi( 'Sent' ),

            'kt_no_contact'      => _digi( '(Not a subscriber)' ),
            'webpush_no_contact' => _digi( '(Not a subscriber)' ),
            'webpush_no_message' => _digi( '(No push notification setup)' ),
            'email_optout'       => _digi( '(Not a subscriber)' ),
        );
    }

    function execute( $wp_user_id_or_email, $action_obj_or_id )
    {
        $result = array(
            'klicktipp_status' => '',
            'webpush_status'   => '',
            'email_status'     => '',
        );

        /** @var ncore_ActionData $model */
        $model = $this->api->load->model( 'data/action' );

        $action = $model->resolveToObj( $action_obj_or_id );

        if (!$action) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new Exception( "Invalid action object or id." );
        }

        $have_klicktipp = $action->klicktipp_is_active == 'Y';
        $have_webpush   = $action->webpush_is_active   == 'Y';
        $have_email     = $action->email_is_active     == 'Y';

        $result[ 'klicktipp_status' ] = $have_klicktipp
                                      ? $this->_executeKlicktipp( $wp_user_id_or_email, $action )
                                      : 'skipped';

        $result[ 'webpush_status' ] = $have_webpush
                                    ? $this->_executeWebpush( $wp_user_id_or_email, $action )
                                    : 'skipped';

        if ($action->email_is_sent_if_push_is_sent == 'N' && $result[ 'webpush_status' ] == 'success')
        {
            $have_email = false;
        }

        $result[ 'email_status' ] = $have_email
                                  ? $this->_executeEmail( $wp_user_id_or_email, $action )
                                  : 'skipped';

        return $result;

    }

    public function placeholders( $user_obj_or_id = false )
    {
        $user = ncore_getUserById( $user_obj_or_id );

        $first_name = '';
        $last_name  = '';

        if ($user)
        {
            $user_id = ncore_retrieve( $user, array( 'ID', 'id' ) );

            $first_name = get_user_meta( $user_id, "first_name", true );
            $last_name  = get_user_meta( $user_id, "last_name",  true );

            if (!$first_name) {
                $first_name = ncore_retrieve( $user, "display_name" );
                $last_name  = '';
            }
        }

        $placeholders = array(
            '[FIRST_NAME]' => $first_name,
            '[LAST_NAME]'  => $last_name,
        );

        if ($user)
        {
            $url = $this->emailUnsubscribeUrl( $user );

            $placeholders[ '[UNSUBSCRIBE]' ]  = "<a href=\"$url\">";
            $placeholders[ '[/UNSUBSCRIBE]' ] = '</a>';
        }


        return $placeholders;
    }


    public function defaultEmailImprint()
    {
        $find = array( '<a>', '</a>' );
        $repl = array( '[UNSUBSCRIBE]', '[/UNSUBSCRIBE]' );

        $shortcode = str_replace( $find, $repl, _dgyou( '<a>Click here</a> to unsubscribe.' ) );

        return $shortcode;
    }


    //
    // private4
    //

    private function _queueActionsWhere( $where, $email_or_wp_user_id )
    {
        $is_upgrading = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
        if ($is_upgrading) {
            return;
        }

        /** @var ncore_ActionData $actionData */
        $actionData = $this->api->load->model( 'data/action' );
        $all = $actionData->getAll( $where );

        $this->_queueAction( $all, $email_or_wp_user_id );
    }

    function _queueAction( $action_list_or_action, $user_id, $is_test_run=false )
    {
        if (empty($action_list_or_action)) {
            return;
        }

        $action_list = is_object($action_list_or_action)
                     ? array( $action_list_or_action)
                     : $action_list_or_action;

        /** @var ncore_ActionOutQueue $actionOutQueue */
        $actionOutQueue = $this->api->load->model( 'queue/action_out' );

        /** @var digimember_UserProductData $user_product_model */
        $user_product_model = $this->api->load->model( 'data/user_product' );

        foreach ($action_list as $one)
        {
            if (!is_numeric( $user_id ) && !$is_test_run) {

                if (!isset( $wp_user_id)) {
                    $user = get_user_by( 'email', $user_id );
                    $wp_user_id = ncore_retrieve( $user, 'ID', false );
                }

                if ($wp_user_id) {
                    $resolved_user_id = $wp_user_id;
                }
                else {
                    continue;
                }
            }
            else {
                $resolved_user_id = $user_id;
            }

            $product_ids = $one->condition_product_ids_comma_seperated;

            if ($product_ids && $product_ids !== 'none' && !$is_test_run) {

                if (!$user_product_model->hasProduct( $resolved_user_id, $product_ids )) {
                    continue;
                }
            }

            $actionOutQueue->addJob( $resolved_user_id, $one, $is_test_run );
        }
    }


    private function _executeKlicktipp( $wp_user_id_or_email, $action )
    {
        try
        {
            $email = $this->_resolveUserToEmail( $wp_user_id_or_email );

            /** @var digimember_AutoresponderHandlerLib $lib */
            $lib = $this->api->load->library( 'autoresponder_handler' );

            $success = $lib->setTags( $action->klicktipp_ar_id, $email, $action->klicktipp_tags_add, $action->klicktipp_tags_remove );

            return $success
                   ? 'success'
                   : 'kt_no_contact';
        }
        catch (Exception $e)
        {
            $msg = $e->getMessage();

            $this->api->logError( 'ipn', _ncore( 'Failed to set tags for %s in KlickTipp: %s', $email, $msg ) );

            return 'error';
        }
    }

    private function _executeWebpush( $wp_user_id_or_email, $action )
    {
        $user_id = ncore_userId( $wp_user_id_or_email );
        if (!$user_id) {
            return 'webpush_no_contact';
        }

        /** @var digimember_WebpushLogic $webpushLogic */
        $webpushLogic = $this->api->load->model( 'logic/webpush' );
        /** @var digimember_WebpushMessageData $webpushMessageData */
        $webpushMessageData = $this->api->load->model( 'data/webpush_message' );
        try
        {
            $message = $webpushMessageData->get( $action->webpush_message_id );
            if (!$message) {
                return 'webpush_no_message';
            }

            $webpushLogic->sendNotificationToUser( $user_id, $message );
            return 'success';
        }

        catch (Exception $e)
        {
            $user = ncore_getUserById( $user_id );
            $name = ncore_retrieve( $user, 'user_login', 'User #'.$user_id );
            $this->api->logError( 'mail', _digi( 'Error sending web push message to %s: %s', $name, $e->getMessage()  ) );

            return 'error';
        }
    }


    public function doOptOutFromEmail( $wp_user_id_or_email='current', $do_optout = true )
    {
        /** @var ncore_UserSettingsData $model */
        $model = $this->api->load->model( 'data/user_settings' );

        $new_val = ncore_isTrue( $do_optout )
                 ? 'Y'
                 : false;

        $model->setForUser( $wp_user_id_or_email, 'has_action_email_optout', $new_val);
    }

    public function isOptedOutFromEmail( $wp_user_id_or_email='current' )
    {
        /** @var ncore_UserSettingsData $model */
        $model = $this->api->load->model( 'data/user_settings' );

        $has_opt_out = ncore_isTrue( $model->getForUser( $wp_user_id_or_email, 'has_action_email_optout', 'N' ) );

        return $has_opt_out;
    }

    private function _executeEmail( $wp_user_id_or_email, $action )
    {
        $email = $this->_resolveUserToEmail( $wp_user_id_or_email );
        if (!$email) {
            return 'email_no_contact';
        }


        /** @var ncore_MailerLib $mailer */
        $mailer = $this->api->load->library( 'mailer' );

        $has_opt_out = $this->isOptedOutFromEmail( $wp_user_id_or_email );
        if ($has_opt_out) {
            return 'email_optout';
        }

        $ph   = $this->placeholders( $wp_user_id_or_email );


        $body = wpautop( $action->email_body );

        /** @var digimember_BlogConfigLogic $blogConfigLogic */
        $blogConfigLogic = $this->api->load->model('logic/blog_config');
        $imprint = $blogConfigLogic->get( 'action_email_imprint' );
        if (!$imprint) {
            $imprint = $this->defaultEmailImprint();
        }
        $body .= wpautop( $imprint );

        $subject = str_ireplace( array_keys( $ph ), array_values( $ph ), $action->email_subject );
        $body    = str_ireplace( array_keys( $ph ), array_values( $ph ), $body );

        $mailer->to( $email );
        $mailer->subject( $subject );
        $mailer->html( $body );

        $success = $mailer->send();

        return $success
               ? 'success'
               : 'error';
    }


    private function _actionSignature( $email_or_wp_user_id, $action_id )
    {
        $user_id = $email_or_wp_user_id;

        /** @var ncore_UserSettingsData $model */
        $model = $this->api->load->model( 'data/user_settings' );

        $action_pw = $model->getForUser( $user_id, 'action_pw' );
        if (!$action_pw)
        {
            $this->api->load->helper( 'string' );
            $action_pw = ncore_randomString( 'alnum', 64 );
            $model->setForUser( $user_id, 'action_pw', $action_pw );
        }

        $secret = $action_id.'_'.$action_pw;

        $this->api->load->helper( 'encryption' );
        /** @noinspection PhpUnusedLocalVariableInspection */
        list( $algo, $signature ) = ncore_hash( $secret );

        return $signature;
    }

    private function _queuePausedUsingSiteActions()
    {
        /** @var ncore_ActionData $actionData */
        $actionData = $this->api->load->model( 'data/action' );
        $where = array();
        $where[ 'is_active' ] = 'Y';
        $where[ 'condition_type' ] = 'paused';
        $actions = $actionData->getAll( $where );
        if (!$actions) {
            return;
        }

        $min_paused_days = false;
        $max_paused_days = 1;

        foreach ($actions as $one)
        {
            if ($min_paused_days === false
                || $one->condition_paused_days<$min_paused_days)
            {
                $min_paused_days = $one->condition_paused_days;
            }

            if ($one->condition_paused_days>$max_paused_days)
            {
                $max_paused_days = $one->condition_paused_days;
            }
        }


        $last_login_before = ncore_dbDate( time()-86400*$min_paused_days );

        $grade_days = 30;
        $last_login_after  = ncore_dbDate( time()-86400*($max_paused_days+$grade_days) );

        $where = array();
        $where[ 'name'] = 'login';
        $where[ 'modified <='] = $last_login_before;
        $where[ 'modified >='] = $last_login_after;

        /** @var digimember_CounterData $model */
        $model = $this->api->load->model( 'data/counter' );
        $counts = $model->getAll( $where );

        $now = ncore_unixDate();
        foreach ($counts as $one_count)
        {
            $user_id = $one_count->user_id;

            $login_date = $one_count->modified;

            $paused_days = round( ($now-ncore_unixDate($login_date)) / 86400 );

            foreach ($actions as $one_action)
            {
                if ($one_action->condition_paused_days <= $paused_days
                    && $one_action->condition_paused_days >= $paused_days - $grade_days)
                {
                    $this->_queueAction( $one_action, $user_id );
                }
            }
        }

    }

    private function _queueNeverLoggedinActions()
    {
        /** @var ncore_ActionData $actionData */
        $actionData = $this->api->load->model( 'data/action' );
        $where = array();
        $where[ 'is_active' ] = 'Y';
        $where[ 'condition_type' ] = 'never_online';
        $actions = $actionData->getAll( $where );
        if (!$actions) {
            return;
        }

        $min_days = false;
        $max_days = 1;

        foreach ($actions as $one)
        {
            if ($min_days === false
                || $one->condition_never_online_days<$min_days)
            {
                $min_days = $one->condition_never_online_days;
            }

            if ($one->condition_never_online_days>$max_days)
            {
                $max_days = $one->condition_never_online_days;
            }
        }

        /** @var digimember_CounterData $model */
        $model = $this->api->load->model( 'data/counter' );
        $grace_days=30;
        $user_id_age_map = $model->getUserIdsNotLoggedIn( $min_days, $max_days+$grace_days );

        foreach ($user_id_age_map as $user_id => $age_in_days)
        {
            foreach ($actions as $one_action)
            {
                if ($one_action->condition_never_online_days <= $age_in_days
                    && $one_action->condition_never_online_days >= $age_in_days-$grace_days)
                {
                    $this->_queueAction( $one_action, $user_id );
                }
            }
        }
    }


    private function _resolveUserToEmail( $wp_user_id_or_email )
    {
        if (is_numeric($wp_user_id_or_email))
        {
            $user = ncore_getUserByid( $wp_user_id_or_email );
            return $user
                   ? $user->user_email
                   : false;
        }

        $is_email = is_string( $wp_user_id_or_email ) && strpos( $wp_user_id_or_email, '@' ) !== false;
        if ($is_email) {
            return $wp_user_id_or_email;
        }

        return ncore_retrieve( $wp_user_id_or_email, array( 'user_email', 'email' ), false );
    }

    private function unsubscribeKey( $user_obj_or_id )
    {
        /** @var ncore_UserSettingsData $userSettingsData */
        $userSettingsData = $this->api->load->model( 'data/user_settings' );

        $user_id = ncore_userid( $user_obj_or_id );
        if (!$user_id) {
            return '';
        }

        $key = $userSettingsData->getForUser( $user_id, 'action_unsub_key' );
        if (!$key) {
            $this->api->load->helper( 'string' );
            $key = ncore_randomString( 'password', 20 );

            $userSettingsData->setForUser( $user_id, 'action_unsub_key', $key );
        }

        return $user_id. '_' . $key;
    }

    private function emailUnsubscribeUrl( $user )
    {
        $key = $this->unsubscribeKey( $user );
        return ncore_addArgs( site_url(), array( 'dm_manage_subscriptions' => $key ) );
    }



}