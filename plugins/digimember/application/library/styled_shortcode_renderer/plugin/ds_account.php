<?php
define('DS_ACCOUNT__ELEMENT__SAVE_BUTTON', 'ds-account-save-button');
define('DS_ACCOUNT__ELEMENT__EXPORT_BUTTON', 'ds-account-export-button');
define('DS_ACCOUNT__ELEMENT__DELETE_BUTTON', 'ds-account-delete-button');
define('DS_ACCOUNT__ELEMENT__FORM', 'ds-account-form');
define('DS_ACCOUNT__ELEMENT__PASSWORD_STRENGTH', 'passwordStrength');

define('DS_ACCOUNT__PLACEHOLDER__USERNAME', '%%USERNAME%%');
define('DS_ACCOUNT__PLACEHOLDER__DISPLAYNAME', '%%DISPLAYNAME%%');
define('DS_ACCOUNT__PLACEHOLDER__USERID', '%%USERID%%');
define('DS_ACCOUNT__PLACEHOLDER__PASSWORD', '%%FIELD_PASSWORD%%');
define('DS_ACCOUNT__PLACEHOLDER__NEW_PASSWORD', '%%FIELD_NEW_PASSWORD%%');
define('DS_ACCOUNT__PLACEHOLDER__REPEAT_PASSWORD', '%%FIELD_REPEAT_PASSWORD%%');
define('DS_ACCOUNT__PLACEHOLDER__FIELD_DISPLAY_NAME', '%%FIELD_DISPLAY_NAME%%');
define('DS_ACCOUNT__PLACEHOLDER__PASSWORD_STRENGTH', '%%PASSWORD_STRENGTH%%');

define('DS_ACCOUNT__JS__MSG__EXPORT', '%%MSG_EXPORT%%');

define('DS_ACCOUNT__FIELD__PASSWORD', 'current_password');
define('DS_ACCOUNT__FIELD__NEW_PASSWORD', 'new_password');
define('DS_ACCOUNT__FIELD__REPEAT_PASSWORD', 'repeat_password');
define('DS_ACCOUNT__FIELD__FIELD_DISPLAY_NAME', 'display_name');

define('DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_NONE', 'passwordStrengthLabelNone');
define('DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_BAD', 'passwordStrengthLabelBad');
define('DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_MISMATCH', 'passwordStrengthLabelMismatch');
define('DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_WEAK', 'passwordStrengthLabelWeak');
define('DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_GOOD', 'passwordStrengthLabelGood');
define('DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_STRONG', 'passwordStrengthLabelStrong');

/**
 * Class digimember_StyledShortcodeRenderer_PluginBase
 */
class digimember_StyledShortcodeRenderer_PluginDsAccount extends digimember_StyledShortcodeRenderer_PluginBase
{
    /**
     * @inheritDoc
     */
    public function render($shortcode)
    {
        if (!ncore_isLoggedIn()) {
            return '';
        }
        $parentRender = parent::render($shortcode);

        // Parsing
        $decodedHtml = $this->getSectionHtml('widget');

        // Rendering
        $decodedHtml = $this->replaceInText($decodedHtml);

        return $parentRender . $decodedHtml . $this->getSectionHtml(DS_SHORTCODE__SECTION__GLOBAL);
    }

    /**
     * @return array
     */
    protected function getReplacements()
    {
        $userId = ncore_userId();
        /** @var stdClass $userData */
        $userData = $this->getUserData($userId);

        $confirm_msg = _dgyou('All your personal data stored on %s will be exported and copied to your local computer.', site_url())
            . '|' . _dgyou('You will find the data in your local download folder on your computer.');
        return array_merge(
            parent::getReplacements(),
            [
                'DS_ACCOUNT__ELEMENT__SAVE_BUTTON' => DS_ACCOUNT__ELEMENT__SAVE_BUTTON,
                'DS_ACCOUNT__ELEMENT__EXPORT_BUTTON' => DS_ACCOUNT__ELEMENT__EXPORT_BUTTON,
                'DS_ACCOUNT__ELEMENT__DELETE_BUTTON' => DS_ACCOUNT__ELEMENT__DELETE_BUTTON,
                DS_ACCOUNT__ELEMENT__FORM => $this->shortcodeTempId . DS_ACCOUNT__ELEMENT__FORM,
                DS_ACCOUNT__PLACEHOLDER__USERID => $userId,
                DS_ACCOUNT__PLACEHOLDER__USERNAME => ncore_userName(),
                DS_ACCOUNT__PLACEHOLDER__DISPLAYNAME => ncore_retrieve($userData, 'display_name', ''),
                DS_ACCOUNT__JS__MSG__EXPORT => $confirm_msg,
                DS_ACCOUNT__PLACEHOLDER__PASSWORD => DS_ACCOUNT__FIELD__PASSWORD,
                DS_ACCOUNT__PLACEHOLDER__NEW_PASSWORD => DS_ACCOUNT__FIELD__NEW_PASSWORD,
                DS_ACCOUNT__PLACEHOLDER__REPEAT_PASSWORD => DS_ACCOUNT__FIELD__REPEAT_PASSWORD,
                DS_ACCOUNT__PLACEHOLDER__FIELD_DISPLAY_NAME => DS_ACCOUNT__FIELD__FIELD_DISPLAY_NAME,
                'DS_ACCOUNT__CODE__DELETE_ACCOUNT' => $this->renderDeleteDialog(),
                DS_ACCOUNT__PLACEHOLDER__PASSWORD_STRENGTH => $this->renderPasswordStrengthField(),
            ]
        );
    }

    /**
     * @return string
     */
    private function renderPasswordStrengthField()
    {
        $userId = ncore_userId();
        $userName = ncore_userName();

        $this->api->load->helper('html_input');

        $values = ncore_retrieve($this->getValues(), DS_ACCOUNT__ELEMENT__PASSWORD_STRENGTH, []);

        return ncore_renderPasswordIndicator(ncore_id(), [
            'name' => 'new_password_strength',
            'section' => 'account',
            'type' => 'password_indicator',
            'label' => '',
            'element_id' => $userId,

            'password_input' => DS_ACCOUNT__FIELD__NEW_PASSWORD,
            'password2_input' => DS_ACCOUNT__FIELD__REPEAT_PASSWORD,
            'username_value' => $userName,
            'css' => 'digimember_row_password',

            'label_none' => ncore_retrieve($values, DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_NONE),
            'label_bad' => ncore_retrieve($values, DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_BAD),
            'label_mismatch' => ncore_retrieve($values, DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_MISMATCH),
            'label_weak' => ncore_retrieve($values, DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_WEAK),
            'label_good' => ncore_retrieve($values, DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_GOOD),
            'label_strong' => ncore_retrieve($values, DS_ACCOUNT__ATTRIBUTE__PASSWORD_STRENGTH_STRONG),
        ]);
    }

    /**
     * @return string
     */
    private function renderDeleteDialog()
    {
        $buttonLabel = _digi('Delete account');
        $confirm_msg = '<strong>' . _digi('Important') . '</strong>: ' . _dgyou('Your account and your data will be irrecoverably erased.')
            . '|' . _dgyou('You will NOT be able to access any content of this site any more.')
            . '|' . _dgyou('You renounce on all claims the site owner.')
            . '|' . _dgyou('If you want to continue, enter the text %s below and click on %s.', '<strong>' . $this->_confirmText() . '</strong>', $buttonLabel);

        $meta = [
            'type' => 'form',
            'cb_form_id' => $this->shortcodeTempId . DS_ACCOUNT__ELEMENT__FORM,
            'message' => ncore_paragraphs($confirm_msg, ['also_include_first_parapraphend' => true]),
            'title' => _dgyou('Delete your account'),
            'label_ok' => $buttonLabel,
            'confirm_ok' => _digi('This is the last warning.') . '|' . _dgyou('Your account and your data will be irrecoverably deleted.') . '|' . _digi('This cannot be undone - by nobody, not even by our support team.') . '|' . _dgyou('You will not be able to access this site any more.') . '|' . _ncore('Continue?'),
            'width' => '500px',
            'form_sections' => [],
            'form_inputs' => [
                [
                    'name' => 'delete_confirm_message',
                    'type' => 'text',
                    'label' => 'omit',
                    'rules' => 'required',
                    'full_width' => true,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'delete_confirm_send',
                    'value' => 1,
                ]
            ],
        ];

        /** @var ncore_AjaxLib $ajax */
        $ajax = $this->api->load->library('ajax');

        $dialog = $ajax->dialog($meta);

        $javascript = $dialog->showDialogJs();

        return $javascript . '; return false;';
    }

    private function _confirmText()
    {
        return _digi('DELETE ACCOUNT');
    }

    protected function handleRequest()
    {
        parent::handleRequest();

        $password1 = ncore_retrieve($_POST, DS_ACCOUNT__FIELD__NEW_PASSWORD);
        $password2 = ncore_retrieve($_POST, DS_ACCOUNT__FIELD__REPEAT_PASSWORD);
        $userId = ncore_retrieve($_POST, 'user_login');

        if ($password1) {
            $match = $password1 == $password2;
            if ($match) {
                /** @var digimember_UserData $model */
                $model = $this->api->load->model('data/user');
                $model->setPassword($userId, $password1, $is_generated_password = false);

                $msg = _dgyou('Your password has been changed.');
                $msg_esc = str_replace("'", "\\'", $msg);

                $url = ncore_currentUrl();

                /** @var ncore_OneTimeLoginData $model */
                $model = $this->api->load->model('data/one_time_login');
                $redirect_url = $model->setOneTimeLogin($userId, $url);

                $js = "alert('$msg_esc'); location.href=\"$redirect_url\"; ";

                /** @var ncore_HtmlLogic $model */
                $model = $this->api->load->model('logic/html');
                $model->jsOnLoad($js);
            } else {
                $this->formError(_dgyou('The passwords do not match.'));
            }
        }

        $user = ncore_getUserById($userId);

        $display_name = ncore_retrieve($_POST, DS_ACCOUNT__FIELD__FIELD_DISPLAY_NAME);
        if ($display_name && $display_name != $user->display_name) {
            $is_free = $this->userNameIsFree($userId, $display_name);
            if ($is_free) {
                wp_update_user((object)['ID' => $userId, 'display_name' => $display_name]);
            } else {
                $this->formError(_dgyou('Unfortunately the display name is already taken.'));
            }
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

        $dm_export_personal_data = ncore_retrieve($_POST, 'dm_export_personal_data');
        if ($dm_export_personal_data) {
            $this->api->load->helper('string');

            $key = ncore_randomString('alnum', 32);
            /** @var ncore_SessionLogic $sessionLogic */
            $sessionLogic = $this->api->load->model('logic/session');
            $sessionLogic->set('gdpr_download', $key);

            /** @var digimember_LinkLogic $linkLogic */
            $linkLogic = $this->api->load->model('logic/link');
            $xml_url = $linkLogic->ajaxUrl($this, 'download_personal_data', ['format' => 'xml', 'key' => $key]);
            $txt_url = $linkLogic->ajaxUrl($this, 'download_personal_data', ['format' => 'txt', 'key' => $key]);

            $msg = '<div style="text-align: left">' . _digi('The report has been created:')
                . '<ul><li><strong><a>' . _digi('Download TEXT file') . '</a></strong> - ' . _digi('easy to read') . '</li><li><strong><a>' . _digi('Download XML file') . '</a></strong> - ' . _digi('for data transfer') . '</li></ul>'
                . _dgyou('After download the report is in your local download folder on your computer.')
                . '|' . _digi('<strong>Note:</strong> For technical reasons it is not possible to import data <strong>into</strong> this membership site.') . '</div>';

            $msg = ncore_paragraphs(ncore_linkReplace($msg, $txt_url, $xml_url));

            ncore_flashMessage(NCORE_NOTIFY_SUCCESS, $msg);
        }
    }

    /**
     * @param $userId
     *
     * @return bool|int|string
     */
    private function getUserData($userId)
    {
        $user = ncore_getUserById($userId);

        if ($user) {
            /** @var digimember_UserData $model */
            $model = $this->api->load->model('data/user');
            /** @noinspection PhpUnusedLocalVariableInspection */
            list ($fb_user_id, $fb_auth_token, $fb_scopes, $fb_is_posting_active) = $model->getFbUserDataByWpUserId($userId);

            $user->fb_is_posting_active = ncore_isTrue($fb_is_posting_active);
        }
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function baseName()
    {
        return 'user/account_editor';
    }

    /**
     * @inheritDoc
     */
    protected function validation()
    {
        /** @var ncore_BusinessLogic $business */
        $business = $this->api->load->model('logic/business');
        $pw_min_length = $business->passwordMinLength();

        return [
            DS_ACCOUNT__FIELD__PASSWORD => 'required|current_password',
            DS_ACCOUNT__FIELD__NEW_PASSWORD => 'min_length[' . $pw_min_length . ']',
            DS_ACCOUNT__FIELD__FIELD_DISPLAY_NAME => 'min_length[3]',
        ];
    }

    /**
     * @param $user_id
     * @param $name
     *
     * @return bool
     */
    private function userNameIsFree($user_id, $name)
    {
        global $wpdb;
        $user_id = ncore_washInt($user_id);
        $name_esc = esc_sql($name);
        $sql = "SELECT 1 FROM " . $wpdb->prefix . "users WHERE ID != $user_id AND display_name=\"$name_esc\"";
        return !(bool)$wpdb->get_results($sql, OBJECT);
    }

    private function deleteAccount()
    {
        try {
            /** @var digimember_UserData $model */
            $model = $this->api->load->model('data/user');
            $model->deleteWpAccount();

            ncore_redirect(site_url());
        } catch (Exception $e) {
            $this->formError($e->getMessage());
        }
    }
}