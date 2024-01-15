<?php

/**
 * Class digimember_CustomAccessFilters_PluginThriveArchitect
 */
class digimember_CustomAccessFilters_PluginThriveArchitect extends digimember_AccessCustomFilters_PluginBase
{
    private static $TAG = 'DIGIMEMBER';

    /**
     * @return callable[]
     */
    protected function getFilters()
    {
        return [
            [$this, 'tvaRegisterMembershipPlugin'],
            [$this, 'tvaRegisterIsPostProtected'],
            [$this, 'tvaReplaceMediaPostMeta'],
        ];
    }

    /**
     * @param callable $callback
     */
    public function tvaReplaceMediaPostMeta($callback)
    {
        add_filter('get_post_metadata', function ($null, $object_id, $meta_key, $single) use ($callback) {
            if ($meta_key != 'tva_post_media') {
                return $null;
            }
            list($postId, $isPostBlocked) = call_user_func($callback);

            if (!$isPostBlocked || $object_id != $postId) {
                return $null;
            }
            return '';
        }, 10, 4);
    }

    /**
     * @param callable $callback
     */
    public function tvaRegisterMembershipPlugin($callback)
    {
        add_filter('tva_has_membership_plugin', function ($memberships) {
            foreach ($memberships as $membership) {
                if ($membership['tag'] == static::$TAG) {
                    return $memberships;
                }
            }
            return array_merge($memberships, [[
                'tag' => static::$TAG,
                'membership_levels' => [],
                'bundles' => [],
            ]]);
        });
    }

    /**
     * @param callable $callback
     */
    public function tvaRegisterIsPostProtected($callback)
    {
        add_filter('tva_is_post_protected', function ($allowed, $post, $tag, $excluded) use ($callback) {
            list($postId, $isPostBlocked) = call_user_func($callback);

            if (!$isPostBlocked || $tag != static::$TAG || $post != $postId) {
                return $allowed;
            }
            return true;
        }, 10, 4);
    }
}