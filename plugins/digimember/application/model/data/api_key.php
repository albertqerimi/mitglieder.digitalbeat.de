<?php

/**
 * Class digimember_ApiKeyData
 */
class digimember_ApiKeyData extends ncore_BaseData
{
    /**
     * @return string
     */
    protected function sqlBaseTableName()
    {
        return 'api_key';
    }

    protected function sqlTableMeta()
    {
        $columns = [
            'key' => 'string[32]',
            'scope' => 'string[20]',
            'is_active' => ['type' => 'yes_no_bit', 'default' => 'Y'],
            'user_id' => 'int[8]',
        ];

        $indexes = [
            'key',
        ];

        $meta = [
            'columns' => $columns,
            'indexes' => $indexes,
        ];

        return $meta;
    }

    protected function hasTrash()
    {
        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values['key'] = $this->generateKey();
        $values['is_active'] = 'Y';
        $values['scope'] = DM_API_KEY_SCOPE_ADMIN;
        $values['user_id'] = ncore_userId();

        return $values;
    }

    /**
     * Generates a completely random token
     *
     * @param int $tokenLen
     *
     * @return string
     */
    public function generateKey($tokenLen = 32)
    {
        if (@file_exists('/dev/urandom')) {
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 100) . uniqid(mt_rand(), true);
        } else {
            $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
        }
        return substr(hash('sha512', $randomData), 0, $tokenLen);
    }

    protected function hasModified()
    {
        return false;
    }
}
