<?php

/**
 * Class digimember_AccessCustomFilters_PluginBase
 */
abstract class digimember_AccessCustomFilters_PluginBase extends ncore_Plugin
{
    /**
     * @return callable[]
     */
    abstract protected function getFilters();

    /**
     * @param callable $callback
     */
    public function registerFilters($callback)
    {
        foreach ($this->getFilters() as $filter) {
            call_user_func_array($filter, [$callback]);
        }
    }
}