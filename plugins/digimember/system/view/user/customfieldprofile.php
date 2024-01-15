<?php
/** @var ncore_UserFormController $this */
/** @var string $container_css */
/** @var string $form_id */
/** @var bool $have_required */

?>
<div class="ncore ncore_user_form_container <?=$container_css?>">
    <?php $this->renderFormMessages(); ?>
        <?php
        $this->renderFormInner();
        if ($have_required)
        {
            ?><div class='required_hint'><span class='required'>*</span> <?php echo _ncore( 'Required' ); ?></div><?php
        }
        ?>
<?php
echo $this->renderPageFootnotes();
?>
</div>