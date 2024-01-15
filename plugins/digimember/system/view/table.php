<style>
    #wpwrap {
        background: #FFFFFF;
    }
</style>
<div class='ncore_data_table'>

<form id='ncore_table' method='get' action='<?php echo $form_action; ?>'>

    <?php
        foreach ($keep_get_vars as $k => $v)
        {
             echo ncore_htmlHiddenInput($k, $v );
        }
    ?>

    <?php if ($views): ?>
        <div class="dm-tabs">
            <ul class="dm-tabs-header">

                <?php
                $selected = ncore_retrieveGET('view', count($views) ? ncore_retrieve($views[0], 'css') : '');
                foreach ($views as $one)
                {
                    extract($one);
                    /** @var string $css */
                    /** @var string $link */
                    /** @var string $suffix */
                    ?>
                    <li class="dm-tabs-header-item <?=$css?><?= $selected == $css ? ' selected' : ''?>"><?=$link?></li>
                    <?php
                }
                ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="dm-tabs-content"><div class="dm-tabs-tab visible">

    <div class="dm-table-toolbar">
        <?php if ($have_search): ?>
            <div class="dm-table-toolbar-searchbox">
                <label class="screen-reader-text" for="ncore-search-input"><?php echo $search_label, _ncore(': '); ?></label>
                <div class="dm-input-group dm-input-dense">
                    <input class="dm-input" id="ncore-search-input" type="search" value="<?php echo $search_value;?>" name="search">
                    <input class="dm-btn dm-btn-primary dm-input-button" id="search-submit" class="button" type="submit" value="<?php echo $search_label; ?>" name="">
                </div>
            </div>
        <?php endif; ?>

        <?php if ($top_actions): ?>
            <?php foreach ($top_actions as $one): ?>
                <div class="<?php echo $one->css;?>"><?php echo $one->html;?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <table <?php echo $table_css_attr ?>>
    <thead>
        <tr scope='row'>
            <?php $table->renderHeader(); ?>
        </tr>
    </thead>

    <?php if ($table->showFooter()): ?>

        <tfoot>
            <tr scope='row'>
                <?php $table->renderFooter(); ?>
            </tr>
        </tfoot>
    <?php endif; ?>

    <tbody id='the-list'>
    <?php

        if ($rows)
        {
            foreach ($rows as $row)
            {
                $table->renderRow( $row );
            }
        }
        else
        {
            $table->renderNoRowsMessage();
        }

    ?>
    </tbody>

    </table>

    <?php if ($bottom_actions && $table->showFooter()): ?>
        <div class="dm-table-toolbar">
            <?php foreach ($bottom_actions as $one): ?>
                <div class="<?php echo $one->css;?>"><?php echo $one->html;?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    </div></div>

</form>

</div>
