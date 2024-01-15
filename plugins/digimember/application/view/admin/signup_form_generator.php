<style>
    .dm-form-button-pane {
        justify-content: flex-end;
        max-width: calc(75% - 25px);
    }
    .dm-tabs-content:last-of-type {
        margin-top: -1px;
        border-top: 0;
    }
</style>
<?php
require DIGIMEMBER_DIR . '/system/view/admin/form.php';
?>

<div class="dm-tabs-content">
    <div class="dm-tabs-tab visible">
        <div class="dm-formbox">
            <div class="dm-formbox-content">
                <div class="dm-formbox-item dm-row dm-middle-xs">
                    <div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-12">
                        <label for="dm_code_display_id">
                            <?=_digi('Sign up form code')?>
                        </label>
                    </div>
                    <div class="dm-col-md-6 dm-col-sm-7 dm-col-xs-12 dm-column-input">
                        <div class="dm-input-outer">
                            <?=ncore_htmlTextInputCode( '', array( 'id' => 'dm_code_display_id', 'rows' => 8, 'cols' => 80 ) )?>
                            <div class="dm-input-label">
                                <p><?=_digi( 'Use this code for form generators or HTML elements within your page builder.')?></p>
                                <p><?=_digi( 'To create signup forms directly in Wordpress Pages and Posts, use the %s shortcode.', '<strong>[ds_signup]</strong>' )?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
function dm_renderCode()
{
    ncoreJQ( '#dm_code_display_id' ).html( '' );

    var url = '<?=$form_action_url?>';

    var product_ids = ncoreJQ( '#dm_signup_form_generator' ).find( 'input[name=ncore_product_id]' ).val();

    if (!product_ids) {
        alert( "<?=_digi( 'Please select at least one Product.' ) ?> " );
        return;
    }

    url = url.replace( 'PRODUCT_IDS', product_ids );



    var do_redirect = ncoreJQ( '#dm_signup_form_generator' ).find( 'select[name=ncore_do_autologin]' ).val() === 'N';

    if (do_redirect)
    {
        var redirect_url = ncoreJQ( '#dm_signup_form_generator' ).find( 'input[name=ncore_thankyou_page_url]' ).val();

        url = url.replace( 'REDIRECT_URL', encodeURIComponent(redirect_url) );
    }
    else
    {
        url = url.replace( 'REDIRECT_URL', '' );
    }


    var code = '<form action="' + url + '" method="POST">\n';


    code += "<?=_digi('Email:')?> <input type=\"text\" name=\"email\">\n";

    code += "<?=_digi('First name:')?> <input type=\"text\" name=\"firstname\">\n";

    code += "<?=_digi('Last name:')?> <input type=\"text\" name=\"lastname\">\n";

    code += "<button><?=_digi('Signup')?></button>\n";

    code += '</form>';

    ncoreJQ( '#dm_code_display_id' ).html( code );
}


</script>