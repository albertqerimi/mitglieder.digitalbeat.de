<?php
/** @var array $data */
?>

<style>
    #wpwrap {
        background: #FFFFFF;
    }
</style>

<div id="csv-import-app"></div>
<script>
    var csvImportData = {
        locale: '<?=$data['locale']?>',
        ajaxUrl: '<?=$data['ajax_url']?>',
    };
</script>