<?php
/** @var ncore_UserFormController $this */
/** @var string $container_css */
/** @var string $form_id */
/** @var bool $have_required */

?>
<div class="ncore ncore_user_form_container <?=$container_css?>">
    <?php $this->renderFormMessages(); ?>
    <form action="<?php echo $action;?>" method="post" id="<?php echo $form_id; ?>" name="<?php echo $form_id; ?>" class="<?php echo 'ncore_user_form', (empty($form_css)?'':" $form_css"); ?>">
        <?php
        $this->renderFormInner();
        if ($have_required)
        {
            ?><div class='required_hint'><span class='required'>*</span> <?php echo _ncore( 'Required' ); ?></div><?php
        }
        echo "<div class='ncore ncore_user ncore_form_buttons'>";
        $this->renderFormButtons();
        echo "</div>";
        ?>
    </form>

<?php
echo $this->renderPageFootnotes();
?>
</div>