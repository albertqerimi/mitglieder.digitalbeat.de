<?php

class ncoreFormLayout_widget_editor extends ncoreFormLayout_base
{

     public function renderHead( $section, $headline, $instructions )
     {
        /*
        $anchor = $section
                 ? "<a name='$section'></a>"
                 : '';

        $details_open = $this->setting( 'details_open', false );

        if ($details_open)
        {
            $open_style  = '';
            $close_style = 'style="display: none;"';
        }
        else
        {
            $open_style  = 'style="display: none;"';
            $close_style = '';
        }

        echo '<div><p>', $anchor;

        if ($headline)
        {
            echo "<strong>$headline</strong> - \n";
        }

        $label_open  = _ncore( 'Show details' );
        $label_close = _ncore( 'Hide details' );

        $js_init = 'ncore_setupJsForAllInputTypes();';

        $js_open  = "ncoreJQ(this).hide();ncoreJQ(this).parent().children('.ncore_toggler_hide_button').show();ncoreJQ(this).parent().parent().children('.ncore_section_details').slideDown('slow'); $js_init return false;";
        $js_close = "ncoreJQ(this).hide();ncoreJQ(this).parent().children('.ncore_toggler_show_button').show();ncoreJQ(this).parent().parent().children('.ncore_section_details').slideUp('slow');return false;";

        echo "<a href='#' class='ncore_toggler_show_button' onclick=\"$js_open\" $close_style>$label_open</a><a href='#' class='ncore_toggler_hide_button' onclick=\"$js_close\" $open_style>$label_close</a>";

        echo "</p><div class='ncore_section_details' $open_style>";
        */

        if ($headline) {
            echo '<h3>', $headline, '</h3>';
        }

        foreach ($instructions as $one)
        {
            echo "<p>$one</p>\n";
        }
     }

    public function renderSectionBegin()
    {
    }

    public function renderSectionEnd()
    {
        // echo "</div></div>";
    }

    public function renderHiddenHtml( $html )
    {
        echo "<div class=\"ncore_hidden\">$html</div>";
    }

    public function renderRow( $data )
    {
        // $data:
        /** @var bool $have_label */
        /** @var string $label */
        /** @var string $input_html */
        /** @var string $input_id */
        /** @var string $row_css */
        /** @var string $label_css */
        /** @var string $input_css */
        /** @var string[] $hints */
        /** @var bool $single_row */

        extract( $data );

        $hint_html = '';
        if ($hints)
        {
            $hint_html = '<p class="dm-input-label">'
                       . implode( '<br />', $hints )
                       . '</p>';
        }

        if ($have_label)
        {
            if ($single_row)
            {
                echo '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-xs-6"><label for="' . $input_id . '">' . $label . '</label></div>
    <div class="dm-col-xs-6"><div class="dm-input-outer">' . $input_html . $hint_html . '</div></div>
</div>
';
            }
            else
            {
                echo '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-xs-12"><label for="' . $input_id . '">' . $label . '</label></div>
    <div class="dm-col-xs-12"><div class="dm-input-outer">' . $input_html . $hint_html . '</div></div>
</div>
';
            }
        }
        else
        {
            echo '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-xs-12"><div class="dm-input-outer">' . $input_html . $hint_html . '</div></div>
</div>
';
        }
    }
}