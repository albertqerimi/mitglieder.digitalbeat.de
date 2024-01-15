<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminWebpushMessageEditController extends ncore_AdminFormController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );

        /** @var digimember_WebpushLogic $webpushLogic */
        $webpushLogic = $this->api->load->model( 'logic/webpush' );
        $webpushLogic->notifySetupErrors();
    }

    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUsePushNotifications();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUsePushNotifications();
    }

    protected function pageHeadline()
    {
        return _digi( 'Web push notification message' );
    }

    protected function inputMetas()
    {
        /** @var digimember_WebpushMessageData $webpushMessageData */
        $webpushMessageData = $this->api->load->model( 'data/webpush_message' );
        $this->api->load->helper( 'html_input' );

        $placeholders = array_keys( $webpushMessageData->placeholders() );

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



        $id = $this->getElementId();

        $obj    = $webpushMessageData->get( $id );

        $metas = array();

        $metas[] = array(
            'name'              => 'name',
            'section'           => 'general',
            'type'              => 'text',
            'label'             => _ncore('Name' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );

        $metas[] = array(
            'name'              => 'title',
            'section'           => 'message',
            'type'              => 'text',
            'label'             => _digi('Title' ),
            'rules'             => 'defaults|trim|required',
            'element_id'        => $id,
        );

        $metas[] = array(
            'name'              => 'icon_image_id',
            'section'           => 'message',
            'type'              => 'image',
            'label'             => _digi('Icon' ),
            'element_id'        => $id,
            'hint'              => _digi( 'Recommended size: %s x %s pixels.', 192, 192 ),
        );

        $metas[] = array(
            'name'              => 'message',
            'section'           => 'message',
            'type'              => 'textarea',
            'label'             => _digi('Message' ),
            'rules'             => 'defaults|trim|required',
            'simple_buttons'    => true,
            'rows'              => 10,
            'element_id'        => $id,
        );

        $metas[] = array(
            'section'           => 'message',
            'type'              => 'html',
            'label'             => _digi('Placeholder' ),
            'html'              => $placeholder_html,
            'element_id'        => $id,
            'hint' => _digi( 'For %s and %s', _digi( 'Title' ), _digi( 'Message' ) ),
        );



        $metas[] = array(
            'name'              => 'target_url',
            'section'           => 'message',
            'type'              => 'page_or_url',
            'label'             => _digi('Target URL' ),
            'hint'              => _digi( 'After the user clicks on the push notification, he is directed to this URL.' ),
            'element_id'        => $id,
        );

        $metas[] = array(
            'name'              => 'msg_image_id',
            'section'           => 'extended',
            'type'              => 'image',
            'label'             => _digi('Image below message' ),
            'element_id'        => $id,
            'hint'              => _digi( 'Recommended size: %s x %s pixels.', 600, 400 ) . ' - ' . _digi( 'use a 3:2 ratio for the image size' ),
        );

        $metas[] = array(
            'name'              => 'badge_image_id',
            'section'           => 'extended',
            'type'              => 'image',
            'label'             => _digi('Preview image' ),
            'element_id'        => $id,
            'hint'              => _digi( 'Preview image for small display.' )  . ' ' . _digi( 'Recommended size: %s x %s pixels.', 96, 96 ),
        );

        $this->api->load->helper( 'date' );

        $metas[] = array(
            'section'           => 'stats',
            'type'              => 'html',
            'label'             => _digi('Times sent' ),
            'rules'             => 'readonly',
            'html'             => ($obj ? $obj->count_sent : 0 ) . ($obj ? ' &nbsp; (' . _digi( 'started on %s', ncore_formatDateTime( $obj->count_started ) ) . ')' : ''),
        );

        $metas[] = array(
            'section'           => 'stats',
            'type'              => 'html',
            'label'             => _digi('Times shown' ),
            'rules'             => 'readonly',
            'html'             => ($obj ? $obj->count_shown: 0 ) . ' &nbsp; (' . number_format_i18n( ($obj ? $obj->quota_shown : 0), 1 ) . '%)',
        );

        $metas[] = array(
            'section'           => 'stats',
            'type'              => 'html',
            'label'             => _digi('Times clicked' ),
            'rules'             => 'readonly',
            'html'             => ($obj ? $obj->count_clicked: 0 ) . ' &nbsp; (' . number_format_i18n( ($obj ? $obj->quota_clicked: 0), 1 ) . '%)',
        );

        $metas[] = array(
            'section'           => 'stats',
            'type'              => 'html',
            'label'             => _digi('Total conversion' ),
            'rules'             => 'readonly',
            'html'             => number_format_i18n( ($obj ? $obj->quota_total: 0), 1 ) . '%',
        );

//        $metas[] = array(
//                'name' => 'is_active',
//                'section' => 'message',
//                'type' => 'yes_no_bit',
//                'label' => _ncore('Active' ),
//                'element_id' => $id,
//        );


        $metas[] = array(
            'name'              => 'send_to_what',
            'section'           => 'send',
            'type'              => 'select',
            'label'             => _digi('Send to' ),
            'options'           => array(
                'one'             => _digi( 'Single user' ),
                'all'             => _digi( 'All subscribers' ),
                'with_product'    => _digi( 'Users of one of these products' ),
                'without_product' => _digi( 'Users without any of these products' ),
            ),
            'element_id'        => $id,
            'html_id'           => 'dm_send_to_what',
        );

        /** @var digimember_ProductData $productData */
        $productData = $this->api->load->model( 'data/product' );
        $product_options = $productData->options();
        $metas[] = array(
                'name' => 'send_to_product_ids',
                'section' => 'send',
                'type' => 'checkbox_list',
                'options' => $product_options,
                'label' => _ncore('Products' ),
                'element_id' => $id,
                'have_all' => true,
                'row_size' => 1,
                'depends_on'        => array( 'send_to_what' => array( 'with_product', 'without_product' ) ),
            );


        $metas[] = array(
            'name'              => 'send_to_user_id',
            'section'           => 'send',
            'type'              => 'user_id',
            'label'             => _digi('Recipient user' ),
            'element_id'        => $id,
            'depends_on'        => array( 'send_to_what' => 'one' ),
            'default'           => ncore_userId(),
        );

        return $metas;
    }

    protected function buttonMetas()
    {
        $msg = _digi( 'The push notification is now sent to ALL subscripers.|This cannot be stopped or undone.|Continue?' );

        $msg = str_replace( array( "'", '|' ), array( "\\'", "\\n\\n" ), $msg );

        $js = "
var what = ncoreJQ( '#dm_send_to_what' ).val();

if (what != 'one')
    return confirm( '$msg' );
else
    return true;
";

        $metas = parent::buttonMetas();

        $metas[] = array(
            'type' => 'onclick',
            'name' => 'send',
            'label' => _digi( 'Send now' ),
            'primary' => false,
            'javascript' => $js,
        );

        $metas[] = array(
            'type' => 'submit',
            'name' => 'reset_stats',
            'label' => _digi( 'Reset statistic' ),
            'primary' => false,
            'confirm' => _digi( 'The numbers are resetted to 0.|Continue?' ),
        );

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model( 'logic/link' );
        $link = $linkLogic->adminPage( 'webpush' );
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
                            'headline'     => _ncore( 'General' ),
                            'instructions' => '',
            ),
            'message' =>  array(
                            'headline'     => _ncore( 'Message' ),
                            'instructions' => '',
            ),
            'send' =>  array(
                            'headline'     => _ncore( 'Send' ),
                            'instructions' => '',
            ),

            'stats' =>  array(
                            'headline'     => _ncore( 'Statistics' ),
                            'instructions' => '',
            ),

            'extended' =>  array(
                            'headline'     => _digi( 'For %s only', 'Google Chrome' ),
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
        /** @var digimember_WebpushMessageData $model */
        $model = $this->api->load->model( 'data/webpush_message' );

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            $obj = $model->get( $id );
        }
        else
        {
            $obj = $model->emptyObject();
        }

        if (!$obj)
        {
            $this->formDisable( _ncore( 'The element has been deleted.' ) );
            return false;
        }

        return $obj;
    }

    protected function setData( $id, $data )
    {
        /** @var digimember_WebpushMessageData $model */
        $model = $this->api->load->model( 'data/webpush_message' );

        $must_reload = false;

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            $modified = $model->update( $id, $data );
        }
        else
        {
            $id = $model->create( $data );

            $this->setElementId( $id );

            $modified = (bool) $id;
        }

        $obj = $model->get( $id );

        $must_reset = $obj && $this->form()->isPosted( 'reset_stats' );
        if ($must_reset)
        {
            $model->resetStats( $obj );
            ncore_resetFlashMessages();
            ncore_flashMessage( NCORE_NOTIFY_SUCCESS, _digi( 'The statistics have been reset.'  ));
            $must_reload = true;
        }

        $must_send = $obj && $this->form()->isPosted( 'send' );
        if ($must_send)
        {
            try
            {
                /** @var digimember_WebpushQueue $webpushQueue */
                $webpushQueue = $this->api->load->model( 'queue/webpush' );
                $count = $webpushQueue->addJob( $id );

                $msg = $count == 1
                     ? _digi( 'Push notification sent to %s user.', 1  )
                     : _digi( 'Push notification sent to %s users.', $count  );

                $type = $count >= 1
                       ? NCORE_NOTIFY_SUCCESS
                       : NCORE_NOTIFY_WARNING;

                ncore_flashMessage( $type, $msg );
            }
            catch (Exception $e)
            {
                ncore_flashMessage( NCORE_NOTIFY_ERROR, $e->getMessage() );
            }
        }


        if ($obj && $must_reload)
        {
            $url = ncore_currentUrl();
            $url = ncore_removeArgs( $url, array( 'id' ), '&', false );
            $url = ncore_addArgs( $url, array( 'id' => $obj->id ), '&', false );

            ncore_redirect( $url );
        }


        return $modified;
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
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );

        $tabs = array();

        $tabs[ 'edit' ] = array(
            'url'   => $model->adminPage( 'webpush'),
            'label' => _ncore( 'Messages' ),
        );

        $tabs[ 'subscriptions' ] = array(
            'url'   => $model->adminPage( 'webpush', array( 'subscriptions' => 'show' ) ),
            'label' => _digi( 'Subscriptions' ),
        );

        $tabs[ 'settings' ] = array(
            'url'   => $model->adminPage( 'webpush', array( 'settings' => 'edit' ) ),
            'label' => _ncore( 'Settings' ),
        );

        return $tabs;
    }
}