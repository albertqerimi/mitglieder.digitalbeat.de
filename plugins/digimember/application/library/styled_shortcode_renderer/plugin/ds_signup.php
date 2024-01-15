<?php
define('DS_SIGNUP__ATTRIBUTE__LAYOUT_TYPE', 'layoutType');
define('DS_SIGNUP__ATTRIBUTE__PRODUCTS', 'products');
define('DS_SIGNUP__ATTRIBUTE__AUTOMATIC_LOGIN', 'automaticLogin');
define('DS_SIGNUP__ATTRIBUTE__HIDE_FIRST_NAME', 'hideFirstName');
define('DS_SIGNUP__ATTRIBUTE__HIDE_LAST_NAME', 'hideLastName');
define('DS_SIGNUP__ATTRIBUTE__HIDE_FORM_AFTER_SUBMIT', 'hideFormAfterSubmit');
define('DS_SIGNUP__ATTRIBUTE__USE_RECAPTCHA', 'useRecaptcha');
define('DS_SIGNUP__ATTRIBUTE__RECAPTCHA_WEBSITE_KEY', 'recaptchaWebsiteKey');
define('DS_SIGNUP__ATTRIBUTE__RECAPTCHA_SECRET_KEY', 'recaptchaSecretKey');
define('DS_SIGNUP__ATTRIBUTE__REQUIRE_CHECKBOX', 'requireCheckbox');
define('DS_SIGNUP__ATTRIBUTE__ELEMENT_CHECKBOX_CONTAINER', 'widget_containerCheckbox');
define('DS_SIGNUP__ATTRIBUTE__CONTAINER_HIDDEN', 'cnt_hidden');

define('DS_SIGNUP__SECTION__BUTTON', 'button');
define('DS_SIGNUP__SECTION__WIDGET', 'widget');

define('DS_SIGNUP__ELEMENT__DIALOG_BUTTON', 'ds-signup-dialog-button');
define('DS_SIGNUP__ELEMENT__WIDGET_HEADLINE', 'ds-signup-headline');
define('DS_SIGNUP__ELEMENT__BUTTON_SIGNUP', 'ds-signup-button-signup');

define('DS_SIGNUP__PLACEHOLDER__FIELD_EMAIL', '%%FIELD_EMAIL%%');
define('DS_SIGNUP__PLACEHOLDER__FIELD_FIRST_NAME', '%%FIELD_FIRST_NAME%%');
define('DS_SIGNUP__PLACEHOLDER__FIELD_LAST_NAME', '%%FIELD_LAST_NAME%%');
define('DS_SIGNUP__PLACEHOLDER__FIELD_CHECKBOX', '%%FIELD_CHECKBOX%%');
define('DS_SIGNUP__PLACEHOLDER__TITLE_CLOSE', '%%TITLE_CLOSE%%');
define('DS_SIGNUP__PLACEHOLDER__ERRORS', '%%ERRORS%%');
define('DS_SIGNUP__PLACEHOLDER__RECAPTCHA', '%%RECAPTCHA%%');
define('DS_SIGNUP__PLACEHOLDER__REQUIRE_CHECKBOX_TEXT', '%%REQUIRE_CHECKBOX_TEXT%%');

define('DS_SIGNUP__FIELD_EMAIL', 'email');
define('DS_SIGNUP__FIELD_FIRST_NAME', 'first_name');
define('DS_SIGNUP__FIELD_LAST_NAME', 'last_name');
define('DS_SIGNUP__FIELD_CHECKBOX', 'is_confirmed');

/**
 * Class digimember_StyledShortcodeRenderer_PluginDsSignup
 */
class digimember_StyledShortcodeRenderer_PluginDsSignup extends digimember_StyledShortcodeRenderer_PluginBase
{
    /** @var string */
    private $dialogId;

    /** @var string */
    private $errors = '';

    /**
     * @var bool
     */
    private $isFormVisible = true;

    /**
     * @inheritDoc
     */
    public function baseName()
    {
        return 'user/signup_form';
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
        if (trim($parentRender)) {
            $this->errors = $parentRender;
        }
        $this->dialogId = ncore_id('dlg');

        if ($this->isFormVisible) {
            switch ($this->layoutType()) {
                case DS_SIGNUP__SECTION__BUTTON:
                    $html = $this->renderButton();
                    break;
                case DS_SIGNUP__SECTION__WIDGET:
                    $html = $this->renderWidget();
                    break;
                default:
                    $html = '';
            }
        } else {
            $html = $parentRender;
        }

        return $this->replaceInText($html . $this->getSectionHtml(DS_SHORTCODE__SECTION__GLOBAL));
    }

    /**
     * @return array
     */
    protected function getReplacements()
    {
        return array_merge(
            parent::getReplacements(),
            [
                'DS_SIGNUP__ATTRIBUTE__LAYOUT_TYPE' => $this->layoutType(),
                'DS_SIGNUP__ELEMENT__DIALOG_BUTTON' => DS_SIGNUP__ELEMENT__DIALOG_BUTTON,
                'DS_SIGNUP__ELEMENT__WIDGET_HEADLINE' => DS_SIGNUP__ELEMENT__WIDGET_HEADLINE,
                'DS_SIGNUP__ELEMENT__BUTTON_SIGNUP' => DS_SIGNUP__ELEMENT__BUTTON_SIGNUP,
                'DS_SIGNUP__SECTION__BUTTON' => DS_SIGNUP__SECTION__BUTTON,
                'DS_SIGNUP__SECTION__WIDGET' => DS_SIGNUP__SECTION__WIDGET,
                DS_SIGNUP__PLACEHOLDER__FIELD_CHECKBOX => DS_SIGNUP__FIELD_CHECKBOX,
                DS_SIGNUP__PLACEHOLDER__FIELD_EMAIL => DS_SIGNUP__FIELD_EMAIL,
                DS_SIGNUP__PLACEHOLDER__FIELD_FIRST_NAME => DS_SIGNUP__FIELD_FIRST_NAME,
                DS_SIGNUP__PLACEHOLDER__FIELD_LAST_NAME => DS_SIGNUP__FIELD_LAST_NAME,
                DS_SIGNUP__PLACEHOLDER__ERRORS => $this->errors,
                '%%DIALOG_ID%%' => $this->dialogId,
                DS_SIGNUP__PLACEHOLDER__TITLE_CLOSE => _ncore('Close'),
                DS_SIGNUP__PLACEHOLDER__RECAPTCHA => $this->renderRecaptcha(),
                'DS_SIGNUP__ATTRIBUTE__REQUIRE_CHECKBOX' => $this->isCheckboxRequired() ? 'true' : 'false',
                DS_SIGNUP__PLACEHOLDER__REQUIRE_CHECKBOX_TEXT => _dgyou('Please accept our terms and check the checkbox.'),
                'DS_SIGNUP__OLD_VALUES' => json_encode($this->isPosted() ? [
                    DS_SIGNUP__FIELD_EMAIL => ncore_retrieve($_POST, DS_SIGNUP__FIELD_EMAIL),
                    DS_SIGNUP__FIELD_FIRST_NAME => ncore_retrieve($_POST, DS_SIGNUP__FIELD_FIRST_NAME),
                    DS_SIGNUP__FIELD_LAST_NAME => ncore_retrieve($_POST, DS_SIGNUP__FIELD_LAST_NAME),
                ] : []),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function validation()
    {
        return [
            DS_SIGNUP__FIELD_CHECKBOX => $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__REQUIRE_CHECKBOX) === false ? '' : 'required',
            DS_SIGNUP__FIELD_LAST_NAME => $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__HIDE_LAST_NAME) === false ? 'required' : '',
            DS_SIGNUP__FIELD_FIRST_NAME => $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__HIDE_FIRST_NAME) === false ? 'required' : '',
            DS_SIGNUP__FIELD_EMAIL => 'email|required',
        ];
    }

    public function handleRequest()
    {
        parent::handleRequest();

        $email = ncore_retrieve($_POST, DS_SIGNUP__FIELD_EMAIL);
        $firstName = ncore_retrieve($_POST, DS_SIGNUP__FIELD_FIRST_NAME);
        $lastName = ncore_retrieve($_POST, DS_SIGNUP__FIELD_LAST_NAME);
        $isConfirmed = ncore_retrieve($_POST, DS_SIGNUP__FIELD_CHECKBOX);

        if ($this->isCheckboxRequired() && !$isConfirmed) {
            $this->formError(_dgyou('Please accept our terms and check the checkbox.'));
            return;
        }
        if ($this->isCheckboxRequired()) {
            $text = ncore_retrieve(ncore_retrieve($this->getValues(), 'widget_labelCheckbox', []), 'text', '');
            $this->api->log('privacy', _digi('User %s with IP %s has accepted our text: %s'), $email, ncore_clientIp(), $text);
        }

        if ($this->useRecaptcha()) {
            list($recaptchaKey, $recaptchaSecret) = $this->recaptchaData();

            $response = ncore_retrieve($_POST, 'g-recaptcha-response');

            $args = [];
            $args['body'] = 'secret=' . $recaptchaSecret . '&response=' . $response;

            $url = 'https://www.google.com/recaptcha/api/siteverify';

            $result = wp_remote_post($url, $args);

            $json = ncore_retrieve($result, 'body');
            $result = $json
                ? @json_decode($json)
                : false;

            $is_valid = ncore_retrieve($result, 'success', false);

            if (!$is_valid) {
                $this->formError(_dgyou('Please prove that you are human and not a bot.'));
                return;
            }
        }

        $products = $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__PRODUCTS, []);
        $product_ids = join(',', $products);

        if (!$product_ids) {
            $product_ids = 'none';
        }
        $address = [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];

        $do_perform_login = $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__AUTOMATIC_LOGIN) === true;
        $hide_after_signup = $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__HIDE_FORM_AFTER_SUBMIT) === true;

        $user_id = ncore_getUserIdByEmail($email);
        $hadAccount = $user_id > 0;
        $hadAdminAccount = false;

        if ($hadAccount) {
            $hadAdminAccount = ncore_canAdmin($user_id);
        } else {
            /** @var ncore_IpLockData $model */
            $model = $this->api->load->model('data/ip_lock');
            $is_locked = $model->isLocked('signup', 10, 86400);
            if ($is_locked) {
                $this->formError(_dgyou('Sorry, you have created too many accounts already.'));
                return;
            }
        }

        try {
            /** @var digimember_PaymentHandlerLib $library */
            $library = $this->api->load->library('payment_handler');
            $welcomeMsgSent = $library->signUp($email, $product_ids, $address, $do_perform_login);

            if ($hide_after_signup) {
                $this->isFormVisible = false;
            }

            if (!$hadAccount && !$hadAdminAccount && !$welcomeMsgSent) {
                ncore_flashMessage(NCORE_NOTIFY_ERROR, _dgyou('The registration could not be carried out.'));
            }
            else {
                ncore_flashMessage(NCORE_NOTIFY_SUCCESS, $this->successMessage($email, $hadAccount, $hadAdminAccount, $welcomeMsgSent));
            }

        } catch (Exception $e) {
            $this->formError($e->getMessage());
            return;
        }
    }

    /**
     * @return bool
     */
    private function isCheckboxRequired()
    {
        $confirm = $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__REQUIRE_CHECKBOX) !== false;
        $isCheckboxHidden = $this->getValue(DS_SIGNUP__ATTRIBUTE__ELEMENT_CHECKBOX_CONTAINER, DS_SIGNUP__ATTRIBUTE__CONTAINER_HIDDEN) === true;
        return $confirm && !$isCheckboxHidden;
    }

    /**
     * @param string $email
     * @param bool   $hadAccount
     * @param bool   $hadAdminAccount
     * @param bool   $welcomeMsgSent
     *
     * @return string|void
     */
    private function successMessage($email, $hadAccount, $hadAdminAccount, $welcomeMsgSent)
    {
        if ($hadAdminAccount && ncore_canAdmin()) {
            return _digi('The email address %s belongs to an admin account. For testing purposes, please use a NON admin email address, because for admins wordpress looks and behaves different than for regular users.', "<em>$email</em>");
        } else if (!$welcomeMsgSent) {
            /** @var digimember_BlogConfigLogic $config */
            $config = $this->api->load->model('logic/blog_config');
            $login_url = $config->loginUrl();

            $msg = _dgyou('There is already an account for the email address %s.', "<em>$email</em>");
            if ($login_url) {
                $msg .= ' ' . ncore_linkReplace(_dgyou('<a>Click here to login.</a>'), $login_url);
            }
            return $msg;
        } else if ($hadAccount) {
            return _dgyou('There is already an account for the email address %s. We have re-sent the confirmation email to this email address.', "<em>$email</em>");
        } else {
            return _dgyou('Your account has been created. We have send your password to %s. If you don\'t receive the email within 10 minutes, please check your spam folder.', "<em>$email</em>");
        }
    }

    /**
     * @return string
     */
    private function renderWidget()
    {
        return $this->getSectionHtml(DS_SIGNUP__SECTION__WIDGET);
    }

    /**
     * @return string
     */
    private function renderButton()
    {
        $html = $this->getSectionHtml(DS_SIGNUP__SECTION__BUTTON);

        $styleAttr = $this->isPosted() ? '' : 'style="display:none;"';

        $html .= '<div id="' . $this->dialogId . '" class="" ' . $styleAttr . '>';
        $html .= $this->renderWidget();
        $html .= '</div>';

        return $html;
    }

    /**
     * @return bool
     */
    private function useRecaptcha()
    {
        $useRecaptcha = $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__USE_RECAPTCHA);
        list($recaptchaKey, $recaptchaSecret) = $this->recaptchaData();

        return !($useRecaptcha !== true || !trim($recaptchaKey) || !trim($recaptchaSecret));
    }

    /**
     * @return array
     */
    private function recaptchaData()
    {
        $recaptchaKey = $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__RECAPTCHA_WEBSITE_KEY);
        $recaptchaSecret = $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__RECAPTCHA_SECRET_KEY);

        return [$recaptchaKey, $recaptchaSecret];
    }

    private function renderRecaptcha()
    {
        if (!$this->useRecaptcha()) {
            return '';
        }
        list($recaptchaKey) = $this->recaptchaData();

        /** @var ncore_HtmlLogic $html */
        $html = ncore_api()->load->model('logic/html');
        $html->includeJs('https://www.google.com/recaptcha/api.js?onload=ncoreCaptchaCallback&render=explicit');
        $fct = 'function ncoreCaptchaCallback() {
    ncoreJQ(".ncore_repatcha").each(function(i,o) {
        var id=ncoreJQ(o).attr("id");
        grecaptcha.render(id, {
          "sitekey" : "' . $recaptchaKey . '",
        });
    });
}';
        $html->jsFunction($fct);

        return '<div style="width: 100%; display: flex; justify-content: center;" class="ncore_repatcha" id="' . ncore_id() . '"></div>';
    }

    /**
     * @return string
     */
    private function layoutType()
    {
        return $this->getGlobalValue(DS_SIGNUP__ATTRIBUTE__LAYOUT_TYPE, DS_SIGNUP__SECTION__WIDGET);
    }
}