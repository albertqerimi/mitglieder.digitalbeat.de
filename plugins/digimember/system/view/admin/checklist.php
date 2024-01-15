
<div class='ncore_checklist_progressbar_label'>
        <?php echo _ncore('Setup progress:' ); ?>
</div>
<div class='checklist_progress_bar_container'>
    <div id='ncore_checklist_progressbar' style='width: <?php echo $progress; ?>%;' class='checklist_progress_bar_outer'>
        <div class='checklist_progress_bar_left'></div>
        <div class='checklist_progress_bar_middle'></div>
        <div class='checklist_progress_bar_right'></div>
    </div>
    <div class='checklist_progress_bar_right_outer'></div>
    <div class='checklist_progress_bar_percentage'><?php echo $progress; ?>%</div>
</div>
<div class='checklist_progress_bar_completed <?php if ($progress<100) echo 'ncore_hidden'; ?>'><?php echo _ncore('Setup complete'); ?></div>
<div class='ncore_checklist_progressbar_clear'>
</div>

<div class='ncore_big_checkbox_list_container'>
    <?php
        foreach ($checklist as $point)
        {
            echo "<div id='$point->id' class='ncore_big_checkbox_list'>",
                 $this->renderOnePoint( $point ),
                 "</div>\n";
        }
    ?>
</div>