<?php

class ncoreFormLayout_table_user extends ncoreFormLayout_base
{
    /**
     * @param $section
     * @param $headline
     * @param $instructions
     * @param null|boolean $collapsed
     */
    public function renderHead( $section, $headline, $instructions, $collapsed = null )
    {
        $container_css = $this->setting( 'container_css' );

        if (empty($headline))
        {
            $container_css .= ' ncore_form_without_headline';
        }

        $add_to_headline_tag = '';
        $add_to_headline = '';
        $add_to_inside = '';
        if (isset($collapsed)) {
            $collapse_id = ncore_id();
            $add_to_headline_tag = ' onclick="var $collapsible = ncoreJQ(\'div[data-collapse-section=\\\''.$collapse_id.'\\\']\').toggle(); if ($collapsible.is(\':visible\')) { ncoreJQ(this).find(\'span\').html(\'-\'); } else { ncoreJQ(this).find(\'span\').html(\'+\'); }" style="cursor: pointer;" ';
            $add_to_headline = '<span style="margin-right: 10px;">';
            $add_to_headline .= ($collapsed) ? '+' : '-';
            $add_to_headline .= '</span>';
            $add_to_inside = ' data-collapse-section="'.$collapse_id.'" style="';
            $add_to_inside .= ($collapsed) ? 'display: none;' : '';
            $add_to_inside .= '" ';
        }

        echo "<div class='ncore_form_section $container_css'>";

        $anchor = $section
            ? "<a name='$section'></a>"
            : '';

        if ($headline)
        {
            echo "<h3".$add_to_headline_tag.">".$add_to_headline.$headline.$anchor."</h3>\n";
            $anchor = '';
        }

        foreach ($instructions as $one)
        {
            echo "<p>$anchor$one</p>\n";
            $anchor = '';
        }

        echo $anchor, '<div class="ncore_inside"'.$add_to_inside.'>';
    }

    public function renderFoot()
    {
        echo '</div></div>';
    }


    public function renderSectionBegin()
    {
        $css = 'form-table';

        $css .= ncore_isAdminArea()
            ? ' ncore_admin'
            : ' ncore_user';

        echo "<table class='ncore ncore_form_table $css'>
<tbody>
";
    }

    public function renderSectionEnd()
    {
        echo "</tbody>
</table>
";
    }

    public function renderHiddenHtml( $html )
    {
        echo "<tr class=\"ncore_hidden\"><td>$html</td></tr>";
    }


    public function renderRow( $data )
    {
        /** @var bool $have_label */
        /** @var string $label */
        /** @var string $input_html  */
        /** @var string $input_id  */
        /** @var string $row_css  */
        /** @var string $label_css  */
        /** @var string $input_css  */
        /** @var string[] $hints  */
        /** @var bool $full_width */
        /** @var bool $omit_label */

        extract( $data );

        $css_attribute  = ncore_attribute( 'class', $row_css );
        $label_css_attr = ncore_attribute( 'class', $label_css );
        $input_css_attr = ncore_attribute( 'class', $input_css );


        $hint_html = '';
        if ($hints)
        {
            $hint_html = '<div class="ncore_form_hint">'
                . implode( '<br />', $hints )
                . '</div>';
        }


        echo "<tr $css_attribute>
";

        if ($have_label && $label != 'omit')
        {
            echo
            "    <th scope='row' $label_css_attr>
        <label for='$input_id'>$label</label>
    </th>
    <td $input_css_attr>
";
        }
        else
        {
            echo
            "    <td colspan='2' class='ncore_without_label $input_css'>
";
        }

        echo
        "        <div class='ncore_form_input'>$input_html</div>
         $hint_html
         <div class='ncore_clear'></div>
    </td>
</tr>";
    }
}