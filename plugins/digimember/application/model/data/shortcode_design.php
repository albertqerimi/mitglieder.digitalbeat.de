<?php

/**
 * Class digimember_ShortcodeDesignData
 */
class digimember_ShortcodeDesignData extends ncore_BaseData
{
    /**
     * @return string
     */
    protected function sqlBaseTableName()
    {
        return 'shortcode_design';
    }

    protected function sqlTableMeta()
    {
        $columns = [
            'name' => 'string[256]',
            'tag' => 'string[100]',
            'values' => 'text',
            'template' => 'string[100]',
            'html' => 'text',
            'version' => ['type' => 'int', 'default' => 1],
        ];

        $indexes = [];

        return [
            'columns' => $columns,
            'indexes' => $indexes,
        ];
    }

    protected function hasTrash()
    {
        return false;
    }
}
