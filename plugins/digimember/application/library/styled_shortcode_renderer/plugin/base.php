<?php

define('DS_SHORTCODE__PLACEHOLDER__SCRIPTS', '%%SCRIPTS%%');
define('DS_SHORTCODE__PLACEHOLDER__SHORTCODE_ID', '%%SHORTCODE_ID%%');
define('DS_SHORTCODE__PLACEHOLDER__FORM_ACTION', '%%FORM_ACTION%%');
define('DS_SHORTCODE__PLACEHOLDER__XSS', '%%XSS%%');

define('DS_SHORTCODE__ELEMENT_ROOT', 'ds-styled-root');

define('DS_SHORTCODE__ELEMENT__GLOBAL', 'global');
define('DS_SHORTCODE__SECTION__GLOBAL', 'global');

/**
 * Class digimember_StyledShortcodeRenderer_PluginBase
 */
abstract class digimember_StyledShortcodeRenderer_PluginBase extends ncore_Plugin
{
    /** @var string */
    protected $shortcodeTempId;

    /** @var int */
    protected $version;

    /** @var string */
    protected $tag;

    /** @var bool */
    private $scriptMode = false;

    /** @var array */
    protected $formErrors = [];

    /** @var stdClass */
    protected $shortcode;

    /** @var string[] */
    protected $sections = null;

    /** @var string */
    protected $html;

    /**
     * @param stdClass $shortcode
     *
     * @return string
     * @throws Exception
     */
    public function render($shortcode)
    {
        $version = ncore_retrieve($shortcode, 'version');
        if (!$version) {
            throw new Exception(_digi('Version not present for current shortcode'));
        }
        $this->version = (int)$version;

        $tag = ncore_retrieve($shortcode, 'tag');
        if (!$tag) {
            throw new Exception(_digi('Tag not present for current shortcode'));
        }
        $this->tag = $tag;
        $this->shortcode = $shortcode;

        if ($this->isPosted()) {
            $errors = $this->validate();
            if (empty($errors)) {
                $this->handleRequest();
            }
            $errors = array_merge($errors, $this->formErrors);
            foreach ($errors as $error) {
                if ($error) {
                    ncore_flashMessage(NCORE_NOTIFY_ERROR, $error);
                }
            }
        }

        return ncore_renderFlashMessages();
    }

    /**
     * @return string
     */
    protected function getHtml()
    {
        if (!$this->html) {
            $this->html = utf8_encode(base64_decode($this->shortcode->html));
        }
        return $this->html;
    }

    /**
     * @return string[]
     */
    protected function getSections()
    {
        if ($this->sections === null) {
            $this->sections = [];
            $html = $this->getHtml();
            $matches = [];
            preg_match_all('/---SECTION_BEGIN_([0-9a-zA-Z]*)---(.*)---SECTION_END_([0-9a-zA-Z]*)---/msU', $html, $matches);
            foreach (ncore_retrieve($matches, 1, []) as $i => $section) {
                $this->sections[$section] = ncore_retrieve(ncore_retrieve($matches, 2, []), $i, '');
            }
            foreach (ncore_retrieve($matches, 0, []) as $i => $sectionHtml) {
                $html = str_replace($sectionHtml, '', $html);
            }
            $this->sections[DS_SHORTCODE__SECTION__GLOBAL] = $html;
        }

        return $this->sections;
    }

    /**
     * @param string $section
     *
     * @return string
     */
    protected function getSectionHtml($section)
    {
        $sections = $this->getSections();
        return ncore_retrieve($sections, $section, '');
    }

    /**
     * @return array
     */
    protected function getValues()
    {
        if (!$this->shortcode) {
            return [];
        }
        static $cache = [];
        $values =& $cache[$this->shortcodeTempId];
        $values = json_decode($this->shortcode->values, true, JSON_NUMERIC_CHECK);
        return $values;
    }

    /**
     * @param string     $key
     *
     * @param null|mixed $default
     *
     * @return null|mixed
     */
    protected function getGlobalValue($key, $default = null)
    {
        $globalValues = ncore_retrieve($this->getValues(), DS_SHORTCODE__ELEMENT__GLOBAL, []);
        return (isset($globalValues[$key])) ? $globalValues[$key] : $default;
    }

    /**
     * @param string     $element
     * @param string     $key
     * @param null|mixed $default
     *
     * @return mixed|null
     */
    protected function getValue($element, $key, $default = null)
    {
        $elementValues = ncore_retrieve($this->getValues(), $element, []);
        return (isset($elementValues[$key])) ? $elementValues[$key] : $default;
    }

    /**
     * @return string
     */
    protected function getShortcodeTempId()
    {
        if (!$this->shortcodeTempId) {
            $this->shortcodeTempId = ncore_id('short');
        }
        return $this->shortcodeTempId;
    }

    protected function handleRequest()
    {
    }

    /**
     * @param string $text
     *
     * @return string
     */
    protected function replaceInText($text)
    {
        $replacements = $this->getReplacements();
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * @return array
     */
    protected function getReplacements()
    {
        return [
            DS_SHORTCODE__PLACEHOLDER__SCRIPTS => $this->renderScripts(),
            DS_SHORTCODE__PLACEHOLDER__SHORTCODE_ID => $this->getShortcodeTempId(),
            DS_SHORTCODE__PLACEHOLDER__FORM_ACTION => $this->getFormActionUrl(),
            DS_SHORTCODE__PLACEHOLDER__XSS => $this->getXssPrevention(),
            'DS_SHORTCODE__ELEMENT_ROOT' => DS_SHORTCODE__ELEMENT_ROOT,
            'DS_SHORTCODE__PLACEHOLDER__IS_POSTED' => $this->isPosted() ? 'true' : 'false',
        ];
    }

    /**
     * @return string
     */
    private function getFormActionUrl()
    {
        $this->api->load->helper('url');
        return ncore_currentUrl();
    }

    /**
     * @return string
     */
    private function getXssPrevention()
    {
        $this->api->load->helper('xss_prevention');

        return ncore_XssPasswordHiddenInput() . "\n" . '<input type="hidden" name="ds_shortcode_ident" value="' . $this->baseName() . '" />';
    }

    /**
     * @return bool
     */
    protected function isPosted()
    {
        $this->api->load->helper('xss_prevention');

        if (empty($_POST)
            || !is_array($_POST)
            || !count($_POST)
            || empty($_POST[ncore_XssVariableName()])
        ) {
            return false;
        }

        if (ncore_isLoggedIn()) {
            if (!ncore_XssPasswordVerified()) {
                return false;
            }
        }

        if ($this->baseName()) {
            if (ncore_retrieve($_POST, 'ds_shortcode_ident', '') !== $this->baseName()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    protected function renderScripts()
    {
        if ($this->scriptMode) {
            return '';
        }
        $this->scriptMode = true;

        $fileName = $this->tag . '_v' . $this->version . '.js';

        $app_dir = $this->api->appDir();
        $path = $app_dir . '/library/styled_shortcode_renderer/plugin/' . $fileName;
        $jsContents = ncore_readFileContents($path);
        if ($jsContents) {
            $jsContents = $this->replaceInText($jsContents);
        }
        $this->scriptMode = false;

        return '<script>' . $jsContents . '</script>';
    }

    /**
     * @return array
     */
    protected function validate()
    {
        $errors = [];
        /** @var ncore_RuleValidatorLib $validator */
        $validator = $this->api->load->library('rule_validator');

        foreach ($this->validation() as $key => $rules) {
            $value = isset($_POST[$key]) ? $_POST[$key] : false;
            if ($value !== false) {
                $error = $validator->validate($key, $value, $rules);
                if ($error) {
                    $errors[$key] = $error;
                }
            }
        }
        return $errors;
    }

    /**
     * @param string $error
     */
    protected function formError($error)
    {
        $this->formErrors[] = $error;
    }

    /**
     * @return string
     */
    abstract public function baseName();

    /**
     * @return array
     */
    abstract protected function validation();
}