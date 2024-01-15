<?php

$load->controllerBaseClass( 'user/form' );

class digimember_UserAccountEditorController extends ncore_UserFormController
{
    public function init( $settings=array() )
    {
        if ($settings && is_array($settings)) {
            $this->hide_display_name = in_array('hide_display_name', $settings) || ncore_retrieve($settings, 'hide_display_name', 0) == 1;
            $this->show_delete_button = in_array('delete_button', $settings) || ncore_retrieve($settings, 'delete_button', 0) == 1;
            $this->show_data_export_button = in_array('data_export_button', $settings) || ncore_retrieve($settings, 'data_export_button', 0) == 1;
            $this->show_custom_fields = in_array('custom_fields', $settings) || ncore_retrieve($settings, 'custom_fields', 0) == 1;
            $this->show_first_name = in_array('first_name', $settings) || ncore_retrieve($settings, 'first_name', 0) == 1;
            $this->show_last_name = in_array('last_name', $settings) || ncore_retrieve($settings, 'last_name', 0) == 1;
        } else {
            $this->hide_display_name = $settings === 'hide_display_name';
            $this->show_delete_button = $settings === 'delete_button';
            $this->show_data_export_button = $settings === 'data_export_button';
            $this->show_custom_fields = $settings === 'custom_fields';
            $this->show_first_name = $settings === 'first_name';
            $this->show_last_name = $settings === 'last_name';
        }

        $settings[ 'container_css' ] = 'dm_account';

        parent::init( $settings );
    }

	public function setPasswordChangeRedirectUrl( $url )
	{
		$this->password_change_redirect_url = $url;
	}

	protected function pageHeadline()
	{
		return _dgyou( 'Your %s Account', $this->api->pluginDisplayName() );
	}

    protected function formSuccessMessage()
    {
        return _dgyou('Your changes have been saved.');
    }

    protected function saveButtonLabel()
    {
        return _dgyou('Save Changes');
    }


	protected function inputMetas()
	{
        $user_id   = ncore_userId();
		$user_name = ncore_userName();

        $userSettings = $this->api->load->model( 'data/user_settings' );

        $customFieldsObj = $this->api->load->model('data/custom_fields');

        $customFields = $customFieldsObj->getCustomFieldsBySection('account');

        $customFieldsValues = array();

        foreach ($customFields as $customField) {
            $customFieldsValues[$customField->name] = $userSettings->get($customField->name, 'Platzhalter '.$customField->name);
        }

        /** @var ncore_BusinessLogic $business */
		$business = $this->api->load->model( 'logic/business' );
		$pw_min_length = $business->passwordMinLength();

        $metas = array(

		   array(
				'name' => 'user_login',
				'section' => 'account',
				'type' => 'text',
				'label' => _dgyou('Username' ),
				'rules' => 'readonly',
				'tooltip' => _dgyou( 'You use your username and your password to sign in.' ),
				'element_id' => $user_id,
                'default' => $user_name,
                'css' => 'digimember_row_login',
		   ),

           array(
                'name' => 'current_password',
                'section' => 'account',
                'type' => 'password',
                'size' => 32,
                'label' => _digi('Current password' ),
                'rules' => "required|current_password",
                'element_id' => $user_id,
                'tooltip' => _dgyou( 'Please enter your current password.|This is required for security reasons for any change you make here.' ),
            ),

		   array(
				'name' => 'new_password',
				'section' => 'account',
				'type' => 'password',
				'size' => 32,
				'label' => _dgyou('New password' ),
				'rules' => "defaults|min_length[$pw_min_length]",
				'tooltip' => _dgyou( 'Leave it blank to keep your password.<p>Or enter your new password.' ) . '<p>' . $business->newPasswordHint('<p>'),
				'element_id' => $user_id,
                'css' => 'digimember_row_password',
			),

		   array(
				'name' => 'new_password2',
				'section' => 'account',
				'type' => 'password',
				'size' => 32,
				'label' => _dgyou('Retype password' ),
				'tooltip' => _dgyou( 'Repeat your new password.' ),
				'element_id' => $user_id,
                'css' => 'digimember_row_password',
			),

		   array(
				'name' => 'new_password_strength',
				'section' => 'account',
				'type' => 'password_indicator',
				'label' => '',
				'element_id' => $user_id,

				'password_input' => 'new_password',
				'password2_input' => 'new_password2',
				'username_value' => $user_name,
                'css' => 'digimember_row_password',
			),
        );

        if ($this->show_first_name)
        {
            $metas[] = array(
                'name' => 'first_name',
                'section' => 'account',
                'type' => 'text',
                'label' => _dgyou('Firstname'),
                'rules' => "defaults|min_length[3]",
                'tooltip' => _dgyou('Please enter your firstname.'),
                'element_id' => $user_id,
                'css' => 'digimember_row_display_name',
            );
        }
        if ($this->show_last_name)
        {
            $metas[] = array(
                'name' => 'last_name',
                'section' => 'account',
                'type' => 'text',
                'label' => _dgyou('Lastname'),
                'rules' => "defaults|min_length[3]",
                'tooltip' => _dgyou('Please enter your lastname.'),
                'element_id' => $user_id,
                'css' => 'digimember_row_display_name',
            );
        }

        if (!$this->hide_display_name)
        {
           $metas[] = array(
				'name' => 'display_name',
				'section' => 'account',
				'type' => 'text',
				'label' => _dgyou('Display name' ),
				'rules' => "defaults|min_length[3]",
				'tooltip' => _dgyou( 'This name is displayed, when you write comments or posts.' ),
				'element_id' => $user_id,
                'css' => 'digimember_row_display_name',
    		);
        }



        if($this->show_custom_fields) {
            if (count($customFields) > 0) {
                foreach ($customFields as $customField) {
                    if ($customField->visible === 'Y') {
                        $metas[] = $customFieldsObj->getMeta($user_id, $customField);
                    }
                }
            }
        }

        return $metas;
	}

	protected function sectionMetas()
	{
		return array(
			'account' =>  array(
							'headline' => '',
							'instructions' => '',
						  ),
		);
	}

	protected function buttonMetas()
	{
		$metas = parent::buttonMetas();

        if ($this->show_data_export_button)
        {
            $confirm_msg = _dgyou( 'All your personal data stored on %s will be exported and copied to your local computer.', site_url() )
               . '|' . _dgyou( 'You will find the data in your local download folder on your computer.' );

            $metas[] = array(
                'type'    => 'submit',
                'name'    => 'dm_export_personal_data',
                'label'   => _digi( 'Personal data report' ),
                'class'   => 'dm_data_export_button',
                'confirm' => $confirm_msg,
            );
        }


        if ($this->show_delete_button)
        {
            $form_id = $this->formId();

            $button_label = _digi( 'Delete account' );

            $confirm_msg = '<strong>'._digi('Important').'</strong>: '._dgyou( 'Your account and your data will be irrecoverably erased.' )
               . '|' . _dgyou( 'You will NOT be able to access any content of this site any more.' )
               . '|' . _dgyou( 'You renounce on all claims the site owner.' )
               . '|' . _dgyou( 'If you want to continue, enter the text %s below and click on %s.', '<strong>'. $this->_confirmText() . '</strong>', $button_label );

            $metas[] = array(
                'type' => 'ajax',
                'name' => 'delete',
                'label' => _digi( 'Delete account' ),
                'class' => 'dm_account_delete_button',

                 'ajax_meta' => array(
                        'type' => 'form',
                        'cb_form_id' => $form_id,
                        'message' => ncore_paragraphs( $confirm_msg, array( 'also_include_first_parapraphend' => true ) ),
                        'title' => _dgyou( 'Delete your account' ),
                        'label_ok' => $button_label,
                        'confirm_ok' => _digi( 'This is the last warning.' ) . '|' . _dgyou( 'Your account and your data will be irrecoverably deleted.').'|'._digi('This cannot be undone - by nobody, not even by our support team.').'|'._dgyou('You will not be able to access this site any more.').'|'._ncore('Continue?' ),
                        'width' => '500px',
                        'form_sections' => array(
                        ),
                        'form_inputs' => array(
                            array(
                                'name' => 'delete_confirm_message',
                                'type' => 'text',
                                'label' => 'omit',
                                'rules' => 'required',
                                'full_width' => true,
                            ),
                            array(
                                'type' => 'hidden',
                                'name' => 'delete_confirm_send',
                                'value' => 1,
                            ),
                     ),
                ),
            );
        }


		return $metas;
	}

	protected function editedElementIds()
	{
		$user_id = ncore_userId();

		return array( $user_id );
	}

    protected function formSettings()
    {
        return array(
             'layout' => 'table_user',
             'hide_required_hint' => true,
        );
    }


	protected function handleRequest()
	{
		parent::handleRequest();

        if ($this->haveFormErrors())
        {
            return;
        }

        if (ncore_retrieve($_POST, 'ncore_delete_confirm_send') === '1') {
            $delete_confirm_password = ncore_retrieve( $_POST, 'ncore_delete_confirm_message' );
            if (($delete_confirm_password && $delete_confirm_password != $this->_confirmText()) || $delete_confirm_password == '')
            {
                $delete_confirm_password = false;

                ncore_flashMessage( NCORE_NOTIFY_ERROR, _digi( 'The confirmation text was not correct.' ) );
            }
            if ($delete_confirm_password) {
                $this->deleteAccount();
            }
        }

        $dm_export_personal_data = ncore_retrieve( $_POST, 'dm_export_personal_data' );
        if ($dm_export_personal_data)
        {
            $this->api->load->helper( 'string' );

            $key = ncore_randomString( 'alnum', 32 );
            /** @var ncore_SessionLogic $sessionLogic */
            $sessionLogic = $this->api->load->model('logic/session');
            $sessionLogic->set( 'gdpr_download', $key );

            /** @var digimember_LinkLogic $linkLogic */
            $linkLogic = $this->api->load->model('logic/link');
            $xml_url = $linkLogic->ajaxUrl( $this, 'download_personal_data', array( 'format' => 'xml', 'key' => $key ) );
            $txt_url = $linkLogic->ajaxUrl( $this, 'download_personal_data', array( 'format' => 'txt', 'key' => $key ) );

            $msg = _digi( 'The report has been created:' )
                   . '<ul><li><strong><a>' . _digi( 'Download TEXT file' ) . '</a></strong> - ' . _digi( 'easy to read') . '</li><li><strong><a>' . _digi( 'Download XML file' ) . '</a></strong> - '._digi('for data transfer').'</li></ul>'
                   . _dgyou( 'After download the report is in your local download folder on your computer.' )
                   . '|' . _digi( '<strong>Note:</strong> For technical reasons it is not possible to import data <strong>into</strong> this membership site.' );

            $msg = ncore_paragraphs( ncore_linkReplace( $msg, $txt_url, $xml_url ) );

            ncore_flashMessage( NCORE_NOTIFY_SUCCESS, $msg );
        }
	}

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function handle_download_personal_data()
    {
        $format = ncore_retrieveGET( 'format', 'txt' );
        $key    = ncore_retrieveGET( 'key' );

        $this->api->load->model( 'logic/session' );
        $is_valid = $key && $key === $this->api->session_logic->get( 'gdpr_download' );

        if ($is_valid) {
            $this->api->load->model( 'logic/gdpr' );
            list( $filename, $content_type ) = $this->api->gdpr_logic->filename( $format );

            $report = $this->api->gdpr_logic->download_personal_data_report( 'current', $format );
        }
        else
        {
            $content_type = 'text/plain';
            $filename = _digi( 'error.txt' );
            $report = _digi( 'ERROR: Link is expired - please try again!' );
        }

        header( "Content-type: $content_type" );
        header( 'Content-Disposition: attachment; filename="'.$filename.'"' );

        echo $report;

        exit;
    }

    protected function handleAjaxEvent( $event, $response )
    {
        $handler = "handle_$event";
        if (method_exists( $this, $handler )) {
            $this->$handler( $response );
        }
    }

	protected function getData( $user_id )
	{
		$user = ncore_getUserById( $user_id );

        if ($user)
        {
            /** @var digimember_UserData $model */
            $model = $this->api->load->model( 'data/user' );
            /** @noinspection PhpUnusedLocalVariableInspection */
            list( $fb_user_id, $fb_auth_token, $fb_scopes, $fb_is_posting_active ) = $model->getFbUserDataByWpUserId( $user_id );

            $user->fb_is_posting_active = ncore_isTrue( $fb_is_posting_active );

            $user->first_name = get_user_meta($user_id,'first_name', true);
            $user->last_name = get_user_meta($user_id,'last_name', true);

            //TODO move that block into the customfieldsmodel?
            $customFieldsModel = $this->api->load->model( 'data/custom_fields' );
            $userSettingsModel = $this->api->load->model( 'data/user_settings' );
            $customFields = $customFieldsModel->getCustomFieldsBySection('account');
            foreach ($customFields as $customField) {
                $value = $userSettingsModel->get('customfield_'.$customField->id);
                $fieldname = $customField->name;
                $user->$fieldname = $value;
            }


        }

		return $user;
	}

	protected function setData( $user_id, $data )
	{
		$password1 = ncore_retrieve( $data, 'new_password' );
		$password2 = ncore_retrieve( $data, 'new_password2' );

		$modified = false;
        $userMetaModified = false;

		if ($password1)
		{
			$match = $password1 == $password2;
			if ($match)
			{
			    /** @var digimember_UserData $model */
                $model = $this->api->load->model ('data/user');
                $model->setPassword( $user_id, $password1, $is_generated_password=false );

				$msg = _dgyou( 'Your password has been changed.' );
				$msg_esc = str_replace( "'", "\\'", $msg );

				$url = ncore_currentUrl();

				/** @var ncore_OneTimeLoginData $model */
				$model = $this->api->load->model( 'data/one_time_login' );
				$redirect_url= $model->setOneTimeLogin( $user_id, $url );

				$js = "alert('$msg_esc'); location.href=\"$redirect_url\"; ";

				/** @var ncore_HtmlLogic $model */
				$model = $this->api->load->model( 'logic/html' );
				$model->jsOnLoad( $js );
			}
			else
			{
				$this->formError( _dgyou( 'The passwords do not match.' ) );
			}
		}

		$user = ncore_getUserById( $user_id );

		$display_name = ncore_retrieve( $data, 'display_name' );
		if ($display_name && $display_name!=$user->display_name)
		{
			$is_free = $this->userNameIsFree( $user_id, $display_name );
			if ($is_free)
			{
				wp_update_user( (object) array( 'ID' => $user_id, 'display_name'=> $display_name ) );
				$modified = true;
			}
			else
			{
				$this->formError( _dgyou( 'Unfortunately the display name is already taken.' ) );
			}
		}

        $first_name = ncore_retrieve( $data, 'first_name', false );
		if ($first_name) {
            $current_first_name = get_user_meta( $user_id, 'first_name', true );

            if ($first_name!=$current_first_name)
            {
                update_user_meta($user_id,'first_name',$first_name);
                apply_filters('digimember_ipn_push_user_name', $user_id);
                $userMetaModified = true;
            }
        }

        $last_name = ncore_retrieve( $data, 'last_name', false );
        if ($last_name) {
            $current_last_name = get_user_meta( $user_id, 'last_name', true );

            if ($last_name!=$current_last_name)
            {
                update_user_meta($user_id,'last_name', $last_name);
                apply_filters('digimember_ipn_push_user_name', $user_id);
                $userMetaModified = true;
            }
        }



        $have_fb_setting = isset( $data['fb_is_posting_active'] );
        if ($have_fb_setting)
        {
            /** @var digimember_UserData $model */
            $model = $this->api->load->model( 'data/user' );
            $model->setFbPosting( $user_id, $data['fb_is_posting_active'] );
        }

        $customFieldsUpdated = apply_filters('digimember_cf_update_data', $user_id, $data);
        if ($customFieldsUpdated) {
            apply_filters('digimember_ipn_push_arcf_links', $user_id);
        }
        $modified = $modified ? $modified : $customFieldsUpdated;
        return $modified;
	}


	private $password_change_redirect_url = '';
    private $hide_display_name            = false;
    private $show_delete_button           = false;
    private $show_data_export_button      = false;
    private $show_custom_fields           = false;
    private $show_first_name              = false;
    private $show_last_name               = false;

    private function userNameIsFree( $user_id, $name )
    {
        global $wpdb;
        $user_id = ncore_washInt( $user_id);
        $name_esc = esc_sql( $name );
        $sql = "SELECT 1 FROM " . $wpdb->prefix . "users WHERE ID != $user_id AND display_name=\"$name_esc\"";
        return ! (bool) $wpdb->get_results( $sql, OBJECT);
    }


    private function deleteAccount()
    {
        try {
            /** @var digimember_UserData $model */
            $model = $this->api->load->model( 'data/user' );
            $model->deleteWpAccount();

            ncore_redirect( site_url() );
        }
        catch (Exception $e)
        {
            $this->formError( $e->getMessage() );
        }
    }



    private function _confirmText()
    {
        return _digi( 'DELETE ACCOUNT' );
    }

}
