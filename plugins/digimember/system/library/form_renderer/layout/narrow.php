<?php

class ncoreFormLayout_narrow extends ncoreFormLayout_base
{
    protected function haveLabelColons()
    {
        return false;
    }

    protected function init()
    {
        parent::init();

        static $initialized;
        if (!empty($initialized))
            return;
        $initialized = true;

        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model( 'logic/html' );

        $js_function = "
function ncore_narrowForm_setInputClass( obj, force_with_value )
{
    var haveValue = ncoreJQ(obj).val() != '' || force_with_value;
    if (haveValue) {
        ncoreJQ(obj).closest( 'div.ncore_form_row').removeClass('ncore_without_value').addClass('ncore_with_value');
    }
    else
    {
        ncoreJQ(obj).closest( 'div.ncore_form_row').removeClass('ncore_with_value').addClass('ncore_without_value');
    }

    var is_focused = ncoreJQ(obj).is(':focus');
    
    if (is_focused)
    {
        ncoreJQ(obj).closest( 'div.ncore_form_row').removeClass('ncore_without_focus').addClass('ncore_with_focus');
    }
    else
    {
        ncoreJQ(obj).closest( 'div.ncore_form_row').removeClass('ncore_with_focus').addClass('ncore_without_focus');
    }
};
";
        $htmlLogic->jsFunction( $js_function );

        $js_onload = "
ncoreJQ( 'div.ncore_input_text input[type=text], div.ncore_input_email input[type=text], div.ncore_input_password input[type=password]' ).on( 'focusout', function(){
    ncore_narrowForm_setInputClass( this, false );
});

ncoreJQ( 'div.ncore_input_text input[type=text], div.ncore_input_email input[type=text], div.ncore_input_password input[type=password]' ).on ( 'focusin keyup click', function(){
    ncore_narrowForm_setInputClass( this, true );
});

ncoreJQ( 'div.ncore_form_row.ncore_input_text, div.ncore_form_row.ncore_input_email, div.ncore_form_row.ncore_input_password' ).click(function() {
    ncoreJQ(this).find( 'input' ).focus();
});

window.setTimeout(
    function() {
        ncoreJQ( 'form.ncore_form_narrow' ).each( function( index, form ){
            var have_autofill = false;
            ncoreJQ( form ).find( '.ncore_input_text input[type=text], div.ncore_input_email input[type=text], div.ncore_input_password input[type=password]' ).each( function( i, v ) {
                if (ncoreJQ(v).val())
                {
                    have_autofill = true;
                }
            });
            if (have_autofill)
            {
                ncoreJQ( form ).find( '.ncore_input_text input[type=text], div.ncore_input_email input[type=text], div.ncore_input_password input[type=password]' ).each( function( i, v ) {
                    ncore_narrowForm_setInputClass( v, true );
                } );
            }
        });
    }, 200 
);

";

        $htmlLogic->jsOnLoad( $js_onload );
    }

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
        /** @var bool $have_value */

        extract( $data );

        $row_css .= $have_value
                  ? ' ncore_with_value'
                  : ' ncore_without_value';

        echo "<div class='ncore_form_row $row_css'>";

        $hint_html = '';
        if ($hints)
        {
            $hint_html = '<div class="ncore_form_hint">'
                       . implode( '<br />', $hints )
                       . '</div>';
        }

        if ($have_label && $label != 'omit')
        {
            echo "
    <div class='ncore_form_label $label_css'>
        <label for='$input_id'>$label</label>
    </div>";
        }

        echo "
    <div class='ncore_form_input $input_css'>
        $input_html
        $hint_html
    </div>
</div>
";
    }
}