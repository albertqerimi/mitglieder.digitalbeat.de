<style>
    #wpwrap {
        background: #FFFFFF;
    }
</style>

<div class='dm-certificate-preview-container'>
<?php
    foreach ($metas as $m):
        $url = str_replace( '__TYPE__', $m['type'], $base_url);
?>
<a href="<?=$url?>" class='dm-certificate-preview-item'>
    <div class='dm-certificate-preview-item-image-container'>
        <img class='dm-certificate-preview-item-image' alt="<?=$m['name']?>" title="<?=$m['name']?>" src="<?=$m['preview_image_url']?>" /><br />
    </div>
    <div class='dm-certificate-preview-item-button-container'>
        <button type="button" class='dm-btn dm-btn-primary dm-btn-fullwidth'><?=_digi('Select')?></button>
    </div>
</a>
<?php endforeach; ?>
</div>