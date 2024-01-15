<?php

class ncoreFormLayout_narrow_legacy extends ncoreFormLayout_base
{
     public function renderHead( $section, $headline, $instructions )
     {
         $anchor = $section
                 ? "<a name='$section'></a>"
                 : '';

        if ($headline)
        {
            echo "<h3>$headline$anchor</h3>\n";
            $anchor = '';
        }

        foreach ($instructions as $one)
        {
            echo "<p>$one$anchor</p>\n";
            $anchor = '';
        }

        echo $anchor;
     }

    public function renderSectionBegin()
    {
        echo "<div class='ncore ncore_user_form'>
";
    }

    public function renderSectionEnd()
    {
        echo "</div>
";
    }

    public function renderHiddenHtml( $html )
    {
        echo "<div class=\"ncore_hidden\">$html</div>";
    }

    public function renderRow( $data )
    {
        // $data:
        //    $have_label
        //    $label,
        //    $input_html,
        //    $input_id,
        //    $row_css,
        //    $label_css,
        //    $input_css,
        //    $hints,

        extract( $data );

        $hint_html = '';
        if ($hints)
        {
            $hint_html = '<div class="ncore_form_hint">'
                       . implode( '<br />', $hints )
                       . '</div>';
        }

        if ($have_label)
        {
            echo "
    <div class='ncore_form_label $row_css $label_css'>
        <label for='$input_id'>$label</label>
    </div>";
        }

        echo "
    <div class='ncore_form_input $row_css $input_css'>
        $input_html
        $hint_html
    </div>
";
    }
}