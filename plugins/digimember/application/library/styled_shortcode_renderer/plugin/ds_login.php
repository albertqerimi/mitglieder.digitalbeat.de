<?php
define('DS_LOGIN__ATTRIBUTE__LAYOUT_TYPE', 'layoutType');
define('DS_LOGIN__ATTRIBUTE__HIDDEN_IF_LOGGEDIN', 'hiddenIfLoggedIn');
define('DS_LOGIN__ATTRIBUTE__STAY_ON_SAME_PAGE', 'stayOnSamePage');
define('DS_LOGIN__ATTRIBUTE__REDIRECT_URL', 'redirectUrl');
define('DS_LOGIN__ATTRIBUTE__REDIRECT_IF_LOGGEDIN', 'redirectIfLoggedIn');
define('DS_LOGIN__ATTRIBUTE__REGISTER_URL', 'registerUrl');
define('DS_LOGIN__ATTRIBUTE__LOGOUT_URL', 'logoutUrl');

define('DS_LOGIN__SECTION__BUTTON', 'button');
define('DS_LOGIN__SECTION__WIDGET', 'widget');
define('DS_LOGIN__SECTION__LOGGEDIN', 'loggedIn');

define('DS_LOGIN__ELEMENT__DIALOG_BUTTON', 'ds-login-dialog-button');
define('DS_LOGIN__ELEMENT__WIDGET_HEADLINE', 'ds-login-headline');
define('DS_LOGIN__ELEMENT__LINK_FORGOT_PASSWORD', 'ds-login-link-forgot-password');
define('DS_LOGIN__ELEMENT__BUTTON_LOGIN', 'ds-login-button-login');

define('DS_LOGIN__PLACEHOLDER__LINK_REGISTER', '%%LINK_REGISTER%%');
define('DS_LOGIN__PLACEHOLDER__LINK_LOGOUT', '%%LINK_LOGOUT%%');
define('DS_LOGIN__PLACEHOLDER__TITLE_CLOSE', '%%TITLE_CLOSE%%');
define('DS_LOGIN__PLACEHOLDER__FIELD_USERNAME', '%%FIELD_USERNAME%%');
define('DS_LOGIN__PLACEHOLDER__FIELD_PASSWORD', '%%FIELD_PASSWORD%%');
define('DS_LOGIN__PLACEHOLDER__FIELD_REMEMBER', '%%FIELD_REMEMBER%%');
define('DS_LOGIN__PLACEHOLDER__ERROR_DIV_ID', '%%ERROR_DIV_ID%%');
define('DS_LOGIN__PLACEHOLDER__FIRST_NAME', '%%FIRST_NAME%%');
define('DS_LOGIN__PLACEHOLDER__AVATAR_HTML_CODE', '%%AVATAR_HTML_CODE%%');

define('DS_LOGIN__FIELD_USERNAME', 'username');
define('DS_LOGIN__FIELD_PASSWORD', 'password');
define('DS_LOGIN__FIELD_REMEMBER', 'remember');

/**
 * Class digimember_StyledShortcodeRenderer_PluginDsLogin
 *
 * @method digimember_StyledShortcodeRendererLib parent()
 */
class digimember_StyledShortcodeRenderer_PluginDsLogin extends digimember_StyledShortcodeRenderer_PluginBase
{
    /** @var string */
    private $dialogId;

    /**
     * @inheritDoc
     */
    public function baseName()
    {
        return 'user/login_form';
    }

    /**
     * @param stdClass $shortcode
     *
     * @return string
     * @throws Exception
     */
    public function render($shortcode)
    {
        $parentRender = parent::render($shortcode);
        $this->dialogId = ncore_id('dlg');

        if (ncore_isLoggedIn()) {
            if ($this->getGlobalValue(DS_LOGIN__ATTRIBUTE__HIDDEN_IF_LOGGEDIN) === true) {
                return '';
            } else {
                return $this->replaceInText($parentRender . $this->getSectionHtml(DS_LOGIN__SECTION__LOGGEDIN) . $this->getSectionHtml(DS_SHORTCODE__SECTION__GLOBAL));
            }
        }


        switch ($this->layoutType()) {
            case DS_LOGIN__SECTION__WIDGET:
                $html = $this->renderWidget();
                break;
            case DS_LOGIN__SECTION__BUTTON:
            default:
                $html = $this->renderButton();
        }

        return $this->replaceInText($parentRender . $html . $this->getSectionHtml(DS_SHORTCODE__SECTION__GLOBAL));
    }

    /**
     * @return array
     */
    protected function getReplacements()
    {
        /** @var digimember_LinkLogic $linkModel */
        $linkModel = $this->api->load->model('logic/link');
        $logoutUrl = $this->getGlobalValue(DS_LOGIN__ATTRIBUTE__LOGOUT_URL);

        return array_merge(
            parent::getReplacements(),
            [
                'DS_LOGIN__ATTRIBUTE__LAYOUT_TYPE' => $this->layoutType(),
                'DS_LOGIN__ELEMENT__DIALOG_BUTTON' => DS_LOGIN__ELEMENT__DIALOG_BUTTON,
                'DS_LOGIN__ELEMENT__WIDGET_HEADLINE' => DS_LOGIN__ELEMENT__WIDGET_HEADLINE,
                'DS_LOGIN__SECTION__BUTTON' => DS_LOGIN__SECTION__BUTTON,
                'DS_LOGIN__SECTION__WIDGET' => DS_LOGIN__SECTION__WIDGET,
                'DS_LOGIN__ELEMENT__LINK_FORGOT_PASSWORD' => DS_LOGIN__ELEMENT__LINK_FORGOT_PASSWORD,
                'DS_LOGIN__ELEMENT__BUTTON_LOGIN' => DS_LOGIN__ELEMENT__BUTTON_LOGIN,
                DS_LOGIN__PLACEHOLDER__ERROR_DIV_ID => ncore_id(),
                '%%DIALOG_ID%%' => $this->dialogId,
                DS_LOGIN__PLACEHOLDER__LINK_REGISTER => $this->getGlobalValue(DS_LOGIN__ATTRIBUTE__REGISTER_URL),
                DS_LOGIN__PLACEHOLDER__TITLE_CLOSE => _ncore('Close'),
                'DS_LOGIN__CODE__FORGOT_PASSWORD' => $this->renderForgotPasswordDialog(),
                'DS_LOGIN__CODE__AJAX_LOGIN' => $this->renderAjaxLoginJavascript(),
                DS_LOGIN__PLACEHOLDER__FIELD_USERNAME => DS_LOGIN__FIELD_USERNAME,
                DS_LOGIN__PLACEHOLDER__FIELD_PASSWORD => DS_LOGIN__FIELD_PASSWORD,
                DS_LOGIN__PLACEHOLDER__FIELD_REMEMBER => DS_LOGIN__FIELD_REMEMBER,
                DS_LOGIN__PLACEHOLDER__FIRST_NAME => ncore_userFirstName(),
                DS_LOGIN__PLACEHOLDER__LINK_LOGOUT => $linkModel->logoff(!$logoutUrl ? 'auto' : $logoutUrl),
                DS_LOGIN__PLACEHOLDER__AVATAR_HTML_CODE => ncore_userImage(ncore_userId(), 64),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function validation()
    {
        return [

        ];
    }

    /**
     * @return string
     */
    private function renderWidget()
    {
        return $this->getSectionHtml(DS_LOGIN__SECTION__WIDGET);
    }

    /**
     * @return string
     */
    private function renderAjaxLoginJavascript()
    {
        /** @var digimember_UserLoginFormController $userFormController */
        $userFormController = $this->api->load->controller('user/login_form');

        $args = [];
        $redirect_url = $this->redirectUrl();
        if ($redirect_url) {
            $args['redirect_url'] = $redirect_url;
        }
        $args['current_url'] = ncore_currentUrl();

        return $userFormController->renderAjaxJs('login', $args, 'data');
    }

    /**
     * @return string
     */
    private function redirectUrl()
    {
        $url = $this->getGlobalValue(DS_LOGIN__ATTRIBUTE__REDIRECT_URL, '');

        $url = str_replace('&amp;', '&', $url);

        if ($this->getGlobalValue(DS_LOGIN__ATTRIBUTE__STAY_ON_SAME_PAGE) === true) {
            $url = ncore_currentUrl();
        }

        return $url;
    }

    /**
     * @return string
     */
    private function renderButton()
    {
        $html = $this->getSectionHtml(DS_LOGIN__SECTION__BUTTON);

        $styleAttr = $this->isPosted() ? '' : 'style="display:none;"';

        $html .= '<div id="' . $this->dialogId . '" class="" ' . $styleAttr . '>';
        $html .= $this->renderWidget();
        $html .= '</div>';

        return $html;
    }

    private function renderForgotPasswordDialog()
    {
        $meta = [
            'type' => 'form',
            'ajax_dlg_id' => 'ajax_forgotton_pw_dlg',
            'cb_controller' => 'user/login_form',
            'message' => _dgyou('Enter your email address. Only after you clicked the link in the confirmation email, we will create a new password for you.'),
            'title' => _dgyou('Set new password'),
            'width' => '500px',
            'form_sections' => [],
            'form_inputs' => [
                [
                    'name' => 'email',
                    'type' => 'text',
                    'label' => _dgyou('Email'),
                    'label_css' => 'ncore_texttoken',
                    'rules' => 'defaults|email',
                    'full_width' => true,
                ],
                [
                    'name' => 'redir_url',
                    'type' => 'hidden',
                    'default' => ncore_currentUrl(),
                ],
            ],
        ];

        /** @var ncore_AjaxLib $ajax */
        $ajax = $this->api->load->library('ajax');

        $dialog = $ajax->dialog($meta);

        $javascript = $dialog->showDialogJs();

        return $javascript . '; return false;';
    }

    /**
     * @return string
     */
    private function layoutType()
    {
        return $this->getGlobalValue(DS_LOGIN__ATTRIBUTE__LAYOUT_TYPE, DS_LOGIN__SECTION__WIDGET);
    }
}