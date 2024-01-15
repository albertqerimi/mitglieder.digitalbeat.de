
<div class='dm-adminpage-table'>

<?php
    foreach ($messages as $one)
    {
        /** @var string $type */
        /** @var string $text */
        /** @var string|null $action */
        extract($one);
        echo ncore_htmlAlert($type, $text, $type, '', isset($action) ? $action : '').'<br />';
    }

    if (empty($is_table_hidden))
    {
        $table->view();
    }

    if ($below_table_html)
    {
        echo '
<div class="dm-tabs-content dm-form-instructions">
    <div class="dm-tabs-tab visible">
        <p class="dm-text">
            ' . $below_table_html . '        
        </p>
    </div>
</div>        
';
    }
?>

</div>