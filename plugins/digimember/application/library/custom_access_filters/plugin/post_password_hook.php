<?php

/**
 * Class digimember_CustomAccessFilters_PluginPostPasswordHook
 */
class digimember_CustomAccessFilters_PluginPostPasswordHook extends digimember_AccessCustomFilters_PluginBase
{

    /**
     * @return callable[]
     */
    protected function getFilters()
    {
        return [
            [$this, 'registerPasswordHook'],
        ];
    }

    /**
     * @param int $postId
     *
     * @return bool
     */
    private function isElementorPost($postId)
    {
        if (class_exists('\Elementor\Plugin')) {
            return \Elementor\Plugin::$instance->db->is_built_with_elementor($postId);
        }
        return false;
    }

    /**
     * @param callable $callback
     */
    public function registerPasswordHook($callback)
    {
        add_filter('post_password_required', function ($null) use ($callback) {
            list($postId, $isPostBlocked) = call_user_func($callback);
            if ($isPostBlocked) {
                if (
                    // Elementor
//                    $this->isElementorPost($postId)
//                    ||
                    // Goodlayers
                    wp_get_theme()->get('Author') == 'Goodlayers'
                    ||
                    // YOOtheme
                    wp_get_theme()->get('Name') == 'YOOtheme'
                ) {
                    return true;
                }
            }
            return $null;
        }, 10);
    }
}