<?php
/** @var array $data */
?>

<style>
    #wpwrap {
        background: #FFFFFF;
    }
</style>

<div id="dm-shortcode-designer-app"></div>
<script>
    var shortcodeDesignerData = {
        locale: '<?=$data['locale']?>',
        ajaxUrl: '<?=$data['ajax_url']?>',
        avatarHtmlCode: '<?=$data['avatar_html_code']?>',
    };
</script>