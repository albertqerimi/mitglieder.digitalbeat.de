<?php

/**
 * Class digimember_AccessCustomFilters_Loader
 */
class digimember_CustomAccessFiltersLib extends ncore_Library
{
    /**
     * @var array
     */
    private $loadedFilters = [];
    /**
     * @var array
     */
    private $registeredFilters = [];

    /**
     * @param callable $callBack
     */
    public function registerFilters($callBack)
    {
        foreach ($this->getCustomAccessFilters() as $key) {
            if (key_exists($key, $this->registeredFilters)) {
                continue;
            }
            try {
                $instance = $this->loadCustomAccessFilter($key);
                if (!$instance) {
                    continue;
                }
                $instance->registerFilters($callBack);
                $this->registeredFilters[] = $key;
            } catch (Exception $e) {
                continue;
            }
        }
    }

    /**
     * @return array
     */
    protected function getCustomAccessFilters()
    {
        return apply_filters('digimember/custom_access/filters', [
            'thrive_architect',
            'post_password_hook',
        ]);
    }

    /**
     * @param string $filterName
     * @param bool   $forceReload
     *
     * @return bool|digimember_AccessCustomFilters_PluginBase
     * @throws Exception
     */
    private function loadCustomAccessFilter($filterName, $forceReload = false)
    {
        if (empty($filterName)) {
            return false;
        }
        $plugin =& $this->loadedFilters[$filterName];

        if (!isset($plugin) || $forceReload) {
            $all_types = $this->getCustomAccessFilters();
            $is_valid = in_array($filterName, $all_types);
            if (!$is_valid) {
                throw new Exception('Custom Access Filter not available: ' . $filterName);
            }

            $location = apply_filters('digimember/custom_access/filter_location', $filterName);
            $class_name = $this->loadPluginClass($filterName, $location == $filterName ? false : $location);

            if (empty($class_name) || !class_exists($class_name)) {
                trigger_error('Could not load class file for type "' . $filterName . '"');
                return false;
            }

            $plugin = new $class_name($this, $filterName, []);
        }

        return $plugin;
    }
}