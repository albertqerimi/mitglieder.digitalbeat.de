<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminActionEditController extends ncore_AdminFormController
{
    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUseActions();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUseActions();
    }

    protected function pageHeadline()
    {
        return _ncore( 'Actions' );
    }

    protected function tabs()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $tabs = array();

        $tabs[ 'list' ] = array(
            'url' => $model->adminPage( 'actions'  ),
            'label' => _ncore( 'Edit' ),
        );

        $tabs[ 'log' ] = array(
            'url' => $model->adminPage( 'actions', array( 'log' => 'all' ) ),
            'label' => _ncore( 'Log' ),
        );

        $tabs[ 'settings' ] = array(
             'url'  => $model->adminPage( 'actions', array( 'cfg' => 'all' ) ),
            'label' => _ncore( 'Settings' ),
        );

        return $tabs;
    }


    protected function inputMetas()
    {
        $api = $this->api;

        /** @var ncore_ActionData $actionData */
        $actionData = $api->load->model( 'data/action' );
        $api->load->model( 'data/autoresponder' );
        /** @var digimember_WebpushMessageData $webpushMessageData */
        $webpushMessageData = $api->load->model( 'data/webpush_message' );
        /** @var digimember_ProductData $productData */
        $productData = $api->load->model( 'data/product' );
        /** @var digimember_ActionLogic $actionLogic */
        $actionLogic = $api->load->model( 'logic/action' );

        /** @var digimember_AutoresponderHandlerLib $lib */
        $lib = ncore_api()->load->library( 'autoresponder_handler' );

        $id = $this->getElementId();

        $product_options       = $productData->options();
        $autoresponder_options = $lib->actionSupportAutoresponderOptions( $allow_null = false );
        $webpush_msg_options   = $webpushMessageData->options();

        $autoresponder_id   = false;

        if ($autoresponder_options && is_array($autoresponder_options))
        {
            $autoresponder_ids = array_keys( $autoresponder_options );

            $action = $id > 0
                    ? $actionData->get( $id )
                    : false;

            $autoresponder_id = @$action->klicktipp_ar_id;

            $pos = array_search( $autoresponder_id, $autoresponder_ids );

            $is_not_found = $pos === false;
            if ($is_not_found) {
                $pos = 0;
            }

            $autoresponder_id = $autoresponder_ids[$pos];
        }

        $metas = array();

        $metas[] = array(
            'name' => 'name',
            'section' => 'general',
            'type' => 'text',
            'label' => _ncore('Name' ),
            'rules' => 'defaults|trim|required',
            'element_id' => $id,
        );

        $metas[] = array(
                'name' => 'id',
                'section' => 'general',
                'type' => 'int',
                'label' => _ncore('Id' ),
                'element_id' => $id,
                'rules' => 'readonly',
            );

        $metas[] = array(
                'name' => 'is_active',
                'section' => 'general',
                'type' => 'yes_no_bit',
                'label' => _ncore('Active' ),
                'element_id' => $id,
            );


        $metas[] = array(
                'name' => 'condition_type',
                'section' => 'condition',
                'type' => 'select',
                'options' => $actionLogic->conditionOptions(),
                'label' => _ncore('Action will be triggered' ),
                'element_id' => $id,
                'html_id' => 'dm_select_condition_type',
            );

        $cond_prefix = '&hellip;'; // '&bull; ';

        $label = _ncore( 'if logging in for the [N]. time' );
        list( $label, $unit ) = explode( '[N]', $label );

        $metas[] = array(
                'name' => 'condition_login_count',
                'section' => 'condition',
                'type' => 'int',
                'label' => $cond_prefix.$label,
                'unit'  => $unit,
                'element_id' => $id,
                'depends_on' => array( 'condition_type' => 'login' ),
                'hint' => _ncore( 'Enter 1 to trigger the action when the user does his first login.' )
                        . '<br />'
                        . _ncore( 'Logins only will be counted (roughly) once a day.' ),
            );

        $label = _ncore( 'if logging in after [N] days' );
        list( $label, $unit ) = explode( '[N]', $label );

        $metas[] = array(
                'name' => 'condition_login_after_days',
                'section' => 'condition',
                'type' => 'int',
                'label' => $cond_prefix.$label,
                'unit'  => $unit,
                'element_id' => $id,
                'depends_on' => array( 'condition_type' => 'login' ),
                'hint' => _ncore( 'Enter 14 to trigger the action, if the user has paused using your site for 14 (or more) days.' ),
            );

        $label = _ncore( 'if not logged in for [N] days' );
        list( $label, $unit ) = explode( '[N]', $label );

        $metas[] = array(
                'name' => 'condition_paused_days',
                'section' => 'condition',
                'type' => 'int',
                'label' => $cond_prefix.$label,
                'unit'  => $unit,
                'element_id' => $id,
                'rules' => 'required|greater_equal[1]',
                'depends_on' => array( 'condition_type' => 'paused' ),
                'hint' => _ncore( 'Enter 7 to trigger the action, after the user has not logged in for a week.' )
            );

        $label = _ncore( 'if never was online within [N] days after account creation' );
        list( $label, $unit ) = explode( '[N]', $label );

        $metas[] = array(
                'name' => 'condition_never_online_days',
                'section' => 'condition',
                'type' => 'int',
                'label' => $cond_prefix.$label,
                'unit'  => $unit,
                'element_id' => $id,
                'rules' => 'required|greater_equal[1]',
                'depends_on' => array( 'condition_type' => 'never_online' ),
                'hint' => _ncore( 'Enter 21 to trigger the action, after the user has not logged in within three weeks after account creation.' )
            );


        $metas[] = array(
                'name' => 'condition_page',
                'section' => 'condition',
                'type' => 'page',
                'label' => _ncore('Page' ),
                'element_id' => $id,
                'depends_on' => array( 'condition_type' => 'page_view' ),
                'rules' => 'required',
                'allow_null' => false,
            );

        $metas[] = array(
                'name' => 'condition_page_view_time',
                'section' => 'condition',
                'type' => 'int',
                'label' => $cond_prefix._ncore('after viewing the page for' ),
                'element_id' => $id,
                'depends_on' => array( 'condition_type' => 'page_view' ),
                'unit' => _ncore('seconds' ),
            );



        $metas[] = array(
                'name' => 'condition_prd_expired_before',
                'section' => 'condition',
                'type' => 'select',
                'label' => $cond_prefix._ncore('when the product access' ),
                'element_id' => $id,
                'depends_on' => array( 'condition_type' => 'prd_expired' ),
                'options' => array( 'N' => _ncore( 'will expire in' ), 'Y' => _ncore('is expired for') ),
                'in_row_with_next' => true,
                'hint' => _ncore( 'Only for products, where %s is 1 day or longer.', '<strong>'._ncore('Days of access').'</strong>'),
            );

        $metas[] = array(
                'name' => 'condition_prd_expired_days',
                'section' => 'condition',
                'type' => 'int',
                'label' => 'none',
                'element_id' => $id,
                'depends_on' => array( 'condition_type' => 'prd_expired' ),
                'unit' => _ncore('days.' ),
            );



        $metas[] = array(
                'name' => 'condition_product_ids_comma_seperated',
                'section' => 'condition',
                'type' => 'checkbox_list',
                'options' => $product_options,
                'label' => _ncore('For owner of these products only' ),
                'element_id' => $id,
                'have_all' => true,
                'row_size' => 1,
                'all_label' => _ncore( 'Any product' ),
            );

        $condition_type_days_map  = $actionLogic->actionExpireDays( 'all' );
        $fallback_days            = $actionLogic->actionExpireDays( 'fallback' );

        $repeat_hint = _digi( 'This action will be triggered for each user not more than once in %s days.', "<span class='dm_action_expire_days'>$fallback_days</span>" );

        $js = "ncoreJQ( '#dm_select_condition_type' ).change(function() {
    var val=ncoreJQ(this).val();
    var days = $fallback_days;
    switch (val) {
    ";
            foreach ($condition_type_days_map as $type => $days)
            {
                $js .= "case '$type': days=$days; break;
    ";
            }
            $js .= "
    }
    ncoreJQ( '.dm_action_expire_days' ).html( days );
});
ncoreJQ( '#dm_select_condition_type' ).trigger('change');";

        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model( 'logic/html' );
        $htmlLogic->jsOnLoad( $js );

        $metas[] = array(
                    'section' => 'condition',
                    'type' => 'html',
                    'label' => _ncore( 'Note' ),
                    'element_id' => $id,
                    'html' =>  $repeat_hint,
                );

        if ($webpush_msg_options)
        {
            $metas[] = array(
                'name' => 'webpush_is_active',
                'section' => 'webpush',
                'type' => 'yes_no_bit',
                'label' => _digi( 'Send Webpush notification' ),
                'element_id' => $id,
            );
            $metas[] = array(
                'name' => 'webpush_message_id',
                'section' => 'webpush',
                'type' => 'select',
                'label' => _digi( 'Webpush message' ),
                'element_id' => $id,
                'options' => $webpush_msg_options,
                'depends_on' => array( 'webpush_is_active' => 'Y' ),
            );
        }
        else
        {
            /** @var digimember_LinkLogic $linkLogic */
            $linkLogic = $this->api->load->model( 'logic/link' );
            $url = $linkLogic->adminPage( 'digimember_webpush' );
            $html = ncore_linkReplace(_digi( 'Please <a>setup webpush messages</a> first.', "<strong>$label</strong>"), $url );

            $metas[] = array(
                'section' => 'webpush',
                'element_id' => $id,
                'type' => 'html',
                'label' => _ncore('Add/Remove tag' ),
                'html' => $html,
            );
        }

        $this->api->load->helper( 'html_input' );
        $placeholders = array_keys( $actionLogic->placeholders() );
        $placeholder_html = '';
        foreach ($placeholders as $placeholder)
        {
            $placeholder_html .= '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-md-3 dm-col-xs-12 dm-placeholder-name dm-color-coral">' . $placeholder . '</div>
    <div class="dm-col-md-9 dm-col-md-offset-0 dm-col-xs-offset-1 dm-col-xs-11"></div>
</div>
';
        }




        $metas[] = array(
            'name' => 'email_is_active',
            'section' => 'email',
            'type' => 'yes_no_bit',
            'label' => _digi( 'Send email notification' ),
            'element_id' => $id,
        );


        $metas[] = array(
            'name' => 'email_subject',
            'section' => 'email',
            'type' => 'text',
            'label' => _digi( 'Subject' ),
            'element_id' => $id,
            'depends_on' => array( 'email_is_active' => 'Y' ),
            'rules' => 'required',
        );

        $metas[] = array(
            'name' => 'email_body',
            'section' => 'email',
            'type' => 'htmleditor',
            'label' => _digi( 'Message' ),
            'element_id' => $id,
            'depends_on' => array( 'email_is_active' => 'Y' ),
            'rows' => 10,
            'rules' => 'required',
        );
        $metas[] = array(
            'section'           => 'email',
            'type'              => 'html',
            'label'             => _digi('Placeholder' ),
            'html'              => $placeholder_html,
            'element_id'        => $id,
            'tooltip' => _digi( 'For %s and %s', _digi( 'Subject' ), _digi( 'Message' ) ),
            'depends_on' => array( 'email_is_active' => 'Y' ),
        );
        $metas[] = array(
            'name' => 'email_is_sent_if_push_is_sent',
            'section' => 'email',
            'type' => 'yes_no_bit',
            'label' => _digi( 'Sent if webpush was successfull' ),
            'element_id' => $id,
            'depends_on' => array( 'email_is_active' => 'Y' ),
        );

       if ($autoresponder_options)
       {
            $metas[] = array(
                'name' => 'klicktipp_is_active',
                'section' => 'autoresponder',
                'type' => 'yes_no_bit',
                'label' => _digi( 'Set autoresponder tag' ),
                'element_id' => $id,
            );

            $autoresponder_label = ncore_retrieve( $autoresponder_options, $autoresponder_id, _ncore('Autoresponder') );

            $autoresponder_id = ncore_washInt( $autoresponder_id );
            $metas[] = array(
                'name' => 'klicktipp_ar_id',
                'section' => 'autoresponder',
                'type' => 'select',
                'label' => _ncore('Autoresponder connection' ),
                'element_id' => $id,
                'depends_on' => array( 'klicktipp_is_active' => 'Y' ),
                'options' => $autoresponder_options,
                'onchange' => 'if('.$autoresponder_id.'!=ncoreJQ(this).val()) alert("'
                               . _ncore( 'Important: Click on %s below to reload the %s tags.', $autoresponder_label, "'".$this->saveButtonLabel() . "'" )
                               . '"); return true;',
            );


            $metas[] = array(
                        'name' => 'klicktipp_tags_add',
                        'element_id' => $id,
                        'section' => 'autoresponder',
                        'type' => 'autoresponder_tag_list',
                        'label' => _digi3( 'Add tags' ),
                        'invalid_label' => _digi3( 'Invalid tag id #%s', '[VALUE]' ),
                        'rules' => '',
                        'seperator' => '<br />',
                        'depends_on' => array( 'klicktipp_is_active' => 'Y' ),
                        'autoresponder' => $autoresponder_id,
            );
            $metas[] = array(
                        'name' => 'klicktipp_tags_remove',
                        'element_id' => $id,
                        'section' => 'autoresponder',
                        'type' => 'autoresponder_tag_list',
                        'label' => _digi3( 'Remove tags' ),
                        'invalid_label' => _digi3( 'Invalid tag id #%s', '[VALUE]' ),
                        'rules' => '',
                        'seperator' => '<br />',
                        'depends_on' => array( 'klicktipp_is_active' => 'Y' ),
                        'autoresponder' => $autoresponder_id,
            );
        }
        else
        {
            $this->api->load->model( 'logic/link' );
            $lib = $this->api->load->library( 'autoresponder_handler' );
            $label = $lib->renderActionSupportTypeList( 'or' );

            $url = $this->api->link_logic->adminPage( 'digimember_newsletter' );
            $html = ncore_linkReplace(_ncore( 'Please <a>setup an autoresponder</a> of type %s first.', "<strong>$label</strong>"), $url );

            $metas[] = array(
                'section' => 'autoresponder',
                'element_id' => $id,
                'type' => 'html',
                'label' => _ncore('Add/Remove tag' ),
                'html' => $html,
            );
        }


        return $metas;
    }

    protected function buttonMetas()
    {
        $id = $this->getElementId();

        $metas = parent::buttonMetas();

        $metas[] = array(
            'type' => 'submit',
            'name' => 'test',
            'label' => _digi( 'Test' ),
            'primary' => false,
        );

        $link = $this->api->link_logic->adminPage( 'actions' );

        $metas[] = array(
                'type' => 'link',
                'label' => _ncore('Back'),
                'url' => $link,
                );

        return $metas;
    }

    protected function sectionMetas()
    {
        return array(
            'general' =>  array(
                            'headline' => _ncore('Settings'),
                            'instructions' => '',
            ),
            'condition' =>  array(
                            'headline' => _ncore('Condition'),
                            'instructions' => '',
            ),
            'autoresponder' =>  array(
                            'headline' => _ncore('Autoresponder action'),
                            'instructions' => '',
            ),
            'webpush' =>  array(
                            'headline' => _digi('Webpush notification'),
                            'instructions' => '',
            ),
            'email' =>  array(
                            'headline' => _digi('Send email'),
                            'instructions' => '',
            ),
        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        $model = $this->api->load->model( 'data/action' );
        $lib = ncore_api()->load->library( 'autoresponder_handler' );

        $have_id = is_numeric( $id ) && $id > 0;

        $obj = $have_id
             ? $model->get( $id )
             : $model->emptyObject();

        if (!$obj) {
            $this->formDisable( _ncore( 'The element has been deleted.' ) );
            return false;
        }

        $autoresponder_options = $lib->actionSupportAutoresponderOptions();

        if ($autoresponder_options && is_array($autoresponder_options))
        {
            $autoresponder_ids = array_keys( $autoresponder_options );

            $autoresponder_id = @$obj->klicktipp_ar_id;

            $pos = array_search( $autoresponder_id, $autoresponder_ids );

            $is_not_found = $pos === false;
            if ($is_not_found) {
                if ($have_id) {
                    $data = array(
                        'klicktipp_ar_id' => $autoresponder_ids[0]
                    );
                    $is_modified = $model->update($obj, $data);
                    if ($is_modified) {
                        $this->formWarning( _digi( 'The autoresponder connection of this action has been deleted and replaced. Please check the autoresponder setting below. If you experienced problems with setting tags, please try again now.' ) );
                    }

                }
                else {
                    $obj->klicktipp_ar_id = $autoresponder_ids[0];
                }
            }
        }

        return $obj;
    }

    protected function setData( $id, $data )
    {
        $model = $this->api->load->model( 'data/action' );

        $have_id = is_numeric( $id ) && $id > 0;

        $subdata = array();

        if ($have_id)
        {
            $is_modified = $model->update( $id, $data );
        }
        else
        {
            $id = $model->create( $data );

            $this->setElementId( $id );

            $is_modified = (bool) $id;
        }

        $must_test = $id > 0 && $this->form()->isPosted( 'test' );
        if ($must_test) {

            $action  = $model->get( $id );
            $user_id = ncore_userId();

            $this->api->load->model( 'logic/action' );
            $this->api->action_logic->queueAction( $action, $user_id, $is_test_run=true );
            ncore_flashMessage( NCORE_NOTIFY_SUCCESS, _digi( 'The action has been triggered. Please observe the resulting action.'  ));
        }


        return $is_modified;
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
}