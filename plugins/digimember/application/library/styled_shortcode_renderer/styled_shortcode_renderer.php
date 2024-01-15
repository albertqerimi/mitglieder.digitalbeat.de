<?php

/**
 * Class digimember_StyledShortcodeRendererLib
 */
class digimember_StyledShortcodeRendererLib extends ncore_Library
{
    /** @var array */
    protected $loadedShortcodes = [];

    /**
     * @param int $id
     *
     * @return string
     * @throws Exception
     */
    public function renderShortcode($id) {
        /** @var digimember_ShortcodeDesignData $model */
        $model = $this->api->load->model('data/shortcode_design');
        /** @var bool|stdClass $shortcode */
        $shortcode = $model->get($id);

        if ($shortcode === false) {
            return _digi('Warning: Could not find styled shortcode for id "' . $id . '".');
        }

        /** @var digimember_StyledShortcodeRenderer_PluginBase $shortcodeClass */
        $shortcodeClass = $this->getShortcodeClass($shortcode->tag);
        if ($shortcodeClass == false) {
            return _digi('Warning: Could not find shortcode renderer for tag "' . $shortcode->tag . '".');
        }

        return $shortcodeClass->render($shortcode);
    }

    /**
     * @param string $shortcodeName
     * @param bool   $forceReload
     * @return bool|digimember_AccessCustomFilters_PluginBase
     * @throws Exception
     */
    private function getShortcodeClass($shortcodeName, $forceReload = false)
    {
        if (empty($shortcodeName)) {
            return false;
        }
        $plugin =& $this->loadedShortcodes[$shortcodeName];

        if (!isset($plugin) || $forceReload) {
            $class_name = $this->loadPluginClass($shortcodeName);

            if (empty($class_name) || !class_exists($class_name)) {
                trigger_error('Could not load class file for type "' . $shortcodeName . '"');
                return false;
            }

            $plugin = new $class_name($this, $shortcodeName, []);
        }

        return $plugin;
    }
}