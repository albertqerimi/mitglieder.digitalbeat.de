<?php

class ncoreFormLayout_table_feedback extends ncoreFormLayout_base
{
     public function renderHead( $section, $headline, $instructions, $collapsed = null )
     {
         $container_css = $this->setting( 'container_css' );

         if (empty($headline))
         {
             $container_css .= ' ncore_form_without_headline';
         }

         echo "<div class='dm-formbox $container_css'>";

         $anchor = $section
                 ? "<a name='$section'></a>"
                 : '';

        if ($headline)
        {
            echo "<div class='dm-formbox-headline'>".$headline.$anchor."</div>\n";
            $anchor = '';
        }

        foreach ($instructions as $one)
        {
            echo "<div class='dm-form-instructions'>$anchor$one</div>\n";
            $anchor = '';
        }

        echo $anchor, '<div class="dm-formbox-content">';
     }

     public function renderFoot()
     {
         echo '</div></div>';
     }


    public function renderSectionBegin()
    {

    }

    public function renderSectionEnd()
    {

    }

    public function renderHiddenHtml( $html )
    {
        echo '<div class="dm-formbox-item dm-row dm-hidden"><div class="dm-col-md-12">'.$html.'</div></div>';
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

        $hint_html = '';
        if ($hints)
        {
            $hint_html = '<label class="dm-input-label" for='.$input_id.'>'
                       . implode( '<br />', $hints )
                       . '</label>';
        }


        echo '<div class="dm-formbox-item dm-row ' . $row_css . '">';

        $inputOuterClass = $full_width ? 'dm-col-md-9 dm-col-sm-8 dm-col-xs-12' : 'dm-col-md-6 dm-col-sm-7 dm-col-xs-12';
        $inputOuterClass .= ' ' . $input_css;
        if ($have_label && !$omit_label)
        {
            if($row_layout == 'left') {

                echo '<div class="dm-column-input instruction info">'.ncore_paragraphs($label).'</div>';
            }
            else {
                echo '
                    <div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-12">
                        <label for='.$input_id.'>'.$label.'</label>
                    </div>
                    ';
            }

            echo '<div class="' . $inputOuterClass . '">';
        }
        else
        {
            $offset = $omit_label ? '' : ' dm-col-md-offset-3 dm-col-sm-offset-4';
            //$inputOuterClass = $omit_label ? 'dm-col-xs-12' : $inputOuterClass;
            echo '<div class="' . $inputOuterClass . $offset . '">';
        }

        echo '<div class="dm-input-outer">'.$input_html.$hint_html.'</div>';
        echo '</div></div>';
    }
}