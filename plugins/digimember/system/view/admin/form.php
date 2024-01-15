<?php
/** @var ncore_AdminFormController $this */
/** @var string $container_css */
/** @var string $form_id */
/** @var bool $have_required */
?>

<style>
    #wpwrap {
        background: #FFFFFF;
    }
</style>

<div class="dm-tabs-content">
    <div class="dm-tabs-tab visible">
        <form action="<?php echo $action;?>" method="post" id="<?php echo $form_id; ?>" name="<?php echo $form_id; ?>" class="<?php echo 'ncore_admin_form', (empty($form_css)?'':" $form_css"); ?>">
            <?php
                $this->renderFormInner();

                echo "<div class='ncore ncore_admin dm-form-button-pane'>";
                $this->renderFormButtons();
                echo "</div>";
            ?>
        </form>
    </div>
</div>

<?php
    echo $this->renderPageFootnotes();
?>