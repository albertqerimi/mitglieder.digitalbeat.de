var dmShortcodes = {
    cacheElements: function cacheElements() {
        var shortcodesDialogLink = jQuery('#show-shortcodes-dialog');
        this.cache = {
            shortcodesDialogLink: shortcodesDialogLink,
        };
    },
    bindEvents: function bindEvents() {
        var self = this;
        self.cache.shortcodesDialogLink.on('click', function (event) {
            event.preventDefault();
            self.showModal();
        });
    },
    showModal: function showModal() {
        dmDialogAjax_FetchUrl(__ncore_shortcodes('ajax_shortcodes_dialog_url'));
    },
    modalCallback: function modalCallback(form_id) {
        try
        {
            var plugin_tag = ncoreJQ( '#'+form_id+' select[name=ncore_shortcode]' ).val();

            var css_selector = '#'+form_id+' .ncore_shortcode_' + plugin_tag + ' ';

            var shortcode = '[' + plugin_tag;

            var pos = plugin_tag.indexOf( '_' )

            var tag = plugin_tag.substr( pos+1 )

            var contents = '';

            var shortcodeHasSplit = false;

            var shortcodeObj = {
                shortcode: '',
                parts: {
                    opentag: '',
                    closetag: '',
                    content: ''
                },
            };

            var br = "\n";

            shortcode += ncore_tinymce_parseText( css_selector, 'button_bg' )
                + ncore_tinymce_parseText( css_selector, 'button_fg' )
                + ncore_tinymce_parseSelect( css_selector, 'button_radius' )
                + ncore_tinymce_parseText( css_selector, 'button_text' );

            switch (tag)
            {
                case 'account':
                    shortcode += ncore_tinymce_parseCheckbox( css_selector, 'hide_display_name' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'first_name' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'last_name' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'custom_fields' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'delete_button' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'data_export_button' );

                    break;

                case 'autojoin':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'autoresponder' )
                        + ncore_tinymce_parseCheckboxList( css_selector, 'product' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'do_login' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'show_errors' );

                    var has_contents = ncoreJQ( css_selector+' select[name=ncore_has_contents]' ).val() == 'yes';

                    if (has_contents)
                    {
                        contents = __ncore_tinymce_helper( 'tinymce_autojoin_hint' ) + br + br
                            + __ncore_tinymce_helper( 'tinymce_label_username' )
                            + " [username]" + br
                            + __ncore_tinymce_helper( 'tinymce_label_password' )
                            + " [password]";
                    }
                    else
                    {
                        contents = 'closetag';
                    }
                    break;

                case 'counter':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'product' )
                        + ncore_tinymce_parseInt( css_selector, 'start' );
                    break;

                case 'days_left':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'product' );
                    break;

                case 'download':
                    shortcode += ncore_tinymce_parseUrl( css_selector, 'url' )
                        + ncore_tinymce_parseUrl( css_selector, 'text' )
                        + ncore_tinymce_parseUrl( css_selector, 'img' );

                    break;

                case 'downloads_left':
                    shortcode += ncore_tinymce_parseUrl( css_selector, 'url' );
                    break;

                case 'digistore_download':
                    shortcode += ncore_tinymce_parseCheckboxList( css_selector, 'product' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'show_texts' )
                        + ncore_tinymce_parseSelect( css_selector, 'icon' );
                    break;

                case 'login':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'type' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'hidden_if_logged_in' )
                        + ncore_tinymce_parseSelect( css_selector, 'facebook' )
                        + ncore_tinymce_parseCheckboxList( css_selector, 'fb_product' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'stay_on_same_page' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'redirect_if_logged_in' )
                        + ncore_tinymce_parseUrl( css_selector, 'url' )
                        + ncore_tinymce_parseText( css_selector, 'dialog_headline' )
                        + ncore_tinymce_parseUrl( css_selector, 'signup_url' )
                        + ncore_tinymce_parseText( css_selector, 'signup_msg' )
                        + ncore_tinymce_parseSelect( css_selector, 'style' )
                    break;

                case 'logout':
                    var arg = ncore_tinymce_parsePage( css_selector, 'page' );
                    if (!arg)
                    {
                        arg = ncore_tinymce_parseUrl( css_selector, 'url' );
                    }
                    shortcode += arg;

                    break;

                case 'menu':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'what' )
                        + ncore_tinymce_parseSelect( css_selector, 'depth' );
                    break;

                case 'lecture_buttons':
                    shortcode += ncore_tinymce_parseCheckbox( css_selector, '2nd_level' )
                        + ncore_tinymce_parseSelect( css_selector, 'color' )
                        + ncore_tinymce_parseText( css_selector, 'bg' )
                        + ncore_tinymce_parseSelect( css_selector, 'round' )
                        + ncore_tinymce_parseSelect( css_selector, 'product' )
                        + ncore_tinymce_parseSelect( css_selector, 'align' );
                    break;

                case 'subscriptions':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'show' );
                    break;

                case 'webpush':
                    shortcode += ncore_tinymce_parseCheckbox( css_selector, 'optout' );
                    break;

                case 'exam':
                case 'exam_certificate':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'id' );
                    break;


                case 'lecture_progress':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'for' )
                        + ncore_tinymce_parseText( css_selector, 'color' )
                        + ncore_tinymce_parseText( css_selector, 'bg' )
                        + ncore_tinymce_parseSelect( css_selector, 'round' )
                        + ncore_tinymce_parseSelect( css_selector, 'product' );
                    break;

                case 'firstname':
                case 'lastname':
                    shortcode += ncore_tinymce_parseCheckboxList( css_selector, 'space' );
                    break;


                case 'give_product':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'product' )
                        + ncore_tinymce_parseText( css_selector, 'order_id' );
                    break;

                case 'password':
                    shortcode += ncore_tinymce_parseText( css_selector, 'no_pw_text' );
                    break;



                case 'signup':
                    shortcode += ncore_tinymce_parseCheckboxList(
                        css_selector,
                        'product',
                        '' // __ncore_tinymce_helper( 'tinymce_error_product_required' )
                        )
                        + ncore_tinymce_parseSelect( css_selector, 'type' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'first_name' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'last_name' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'custom_fields' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'login' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'hideform' )
                        + ncore_tinymce_parseSelect( css_selector, 'facebook' )
                        + ncore_tinymce_parseText( css_selector, 'confirm' );

                    var have_captcha = ncore_tinymce_inputValue( css_selector, 'recaptcha_active') == 'Y';
                    if (have_captcha)
                    {
                        shortcode += ncore_tinymce_parseText( css_selector, 'recaptcha_key' )
                            + ncore_tinymce_parseText( css_selector, 'recaptcha_secret' );

                    }
                    break;
                case 'cancel_form':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'type' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'first_name' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'last_name' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'type_reason' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'cancellation_date' )
                        + ncore_tinymce_parseText( css_selector, 'cancel_email' )
                        + ncore_tinymce_parseText( css_selector, 'hintForOrderID' );

                    var have_captcha = ncore_tinymce_inputValue( css_selector, 'recaptcha_active') == 'Y';
                    if (have_captcha)
                    {
                        shortcode += ncore_tinymce_parseText( css_selector, 'recaptcha_key' )
                            + ncore_tinymce_parseText( css_selector, 'recaptcha_secret' );

                    }
                    break;

                case 'upgrade':
                    shortcode += ncore_tinymce_parseText( css_selector, 'id' )
                        + ncore_tinymce_parseUrl( css_selector, 'text' )
                        + ncore_tinymce_parseUrl( css_selector, 'confirm' )
                        + ncore_tinymce_parseUrl( css_selector, 'img' );
                    break;

                case 'renew':
                case 'receipt':
                case 'buyer_to_affiliate':
                    shortcode += ncore_tinymce_parseCheckboxList( css_selector, 'product' )
                        + ncore_tinymce_parseUrl( css_selector, 'text' )
                        + ncore_tinymce_parseUrl( css_selector, 'confirm' )
                        + ncore_tinymce_parseUrl( css_selector, 'img' );
                    break;


                case 'webinar':
                    shortcode += ncore_tinymce_parseUrl( css_selector, 'url' )
                        + ncore_tinymce_parseInt( css_selector, 'width' )
                        + ncore_tinymce_parseInt( css_selector, 'height' );
                    break;

                case 'if':
                    shortcode += ncore_tinymce_parseCheckboxList( css_selector, 'has_product' )
                        + ncore_tinymce_parseCheckboxList( css_selector, 'has_not_product' )
                        + ncore_tinymce_parseSelect( css_selector, 'logged_in' )
                        + ncore_tinymce_parseSelect( css_selector, 'mode' );

                    contents = __ncore_tinymce_helper('tinymce_if_hint');
                    break;
                case 'customfield':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'customfield' );
                    shortcode += ncore_tinymce_parseCheckboxList( css_selector, 'space' );
                    break;
                case 'forms':
                    shortcode += ncore_tinymce_parseSelect( css_selector, 'id' );
                    break;
                case 'cancel':
                    shortcode += ncore_tinymce_parseCheckboxList( css_selector, 'product' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'use_generic' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'show_product_name' )
                        + ncore_tinymce_parseCheckbox( css_selector, 'show_order_id' )
                        + ncore_tinymce_parseSelect( css_selector, 'style' );
                    break;
            }

            if (typeof ncore_tinymce_parsers != 'undefined')
            {
                for (var i in ncore_tinymce_parsers) {
                    var fct_name = ncore_tinymce_parsers[i];
                    var fct      = window[ fct_name ];
                    if (typeof fct == 'function')
                    {
                        shortcode += fct( css_selector, tag );
                    }
                    else
                    {
                        alert( "Ncore Tinymce Error: " + fct_name + " is not a function" );
                    }
                }
            }

            if (typeof ncore_tinymce_content_renderers != 'undefined')
            {
                for (var i in ncore_tinymce_content_renderers) {
                    var fct_name = ncore_tinymce_content_renderers[i];
                    var fct      = window[ fct_name ];
                    if (typeof fct == 'function')
                    {
                        if (contents) contents += br;
                        contents += fct( css_selector, tag );
                    }
                    else
                    {
                        alert( "Ncore Tinymce Error: " + fct_name + " is not a function" );
                    }
                }
            }

            if (contents == 'closetag')
            {
                shortcode += ' /]';
            }
            else if (contents)
            {
                shortcode += "]" + br + contents + br + "[/" + plugin_tag + ']';
            }
            else
            {
                shortcode += ']';
            }
            this.addShortcode(shortcode)
        }
        catch (e) {
            console.log('fehler beim einfügen')
        }
    },
    listShortcodes: function listShortcodes() {
        ncoreJQ.ajax({
            url: __ncore_shortcodes('ajax_shortcodes_list_url'),
            method: "GET",
        }).done(function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                console.log(data.success);
                var list = ncoreJQ( '#'+data.target_div_id );
                list.html(data.html);
            }
        });
    },
    addShortcode: function addShortcode(shortcode) {
        var dmshortcodesClass = this;
        var newShortcode = {
            "shortcode": shortcode,
        }
        ncoreJQ.ajax({
            url: __ncore_shortcodes('ajax_shortcodes_add_url'),
            method: "POST",
            data: newShortcode,
        }).done(function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                console.log(data.success);
                var list = ncoreJQ( '#'+data.target_div_id );
                list.prepend(data.html);
            }
            dmshortcodesClass.listShortcodes();
        });
    },
    init: function init() {
        this.cacheElements();
        this.bindEvents();
        this.listShortcodes();
    }
};

jQuery(function () {
    dmShortcodes.init();
});


