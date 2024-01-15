<?php
/** @var array $data */
?>

<style>
    #wpwrap {
        background: #FFFFFF;
    }
</style>

<div id="dm-advanced-forms-backend"></div>
<script>
    var advancedformsData = {
        formId: '<?=$data['formId']?>',
        locale: '<?=$data['locale']?>',
        translations: '<?=$data['translations']?>',
        ajaxUrl: '<?=$data['ajax_url']?>',
        avatarHtmlCode: '<?=$data['avatar_html_code']?>',
    };
</script>