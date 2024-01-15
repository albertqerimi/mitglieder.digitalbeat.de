

var ncore_tiny_mce_target_obj
var ncore_tiny_mce_target_type
var ncore_tiny_mce_insert_shortcode_split

function digimember_tinymce_handleShortcode( ed )
{
    ncore_tiny_mce_target_obj = ed;
    ncore_tiny_mce_target_type = 'editor';

    dmDialogAjax_FetchUrl( __ncore_tinymce_helper( 'ajax_dialog_url' ) );
}

function digimember_tinymce_quicktag( button, textarea, editor )
{
    ncore_tiny_mce_target_obj = textarea;
    ncore_tiny_mce_target_type = 'textarea';

    dmDialogAjax_FetchUrl( __ncore_tinymce_helper( 'ajax_dialog_url' ) );
}

function digimember_tinymce_insertShortcode( shortcode )
{
    switch (ncore_tiny_mce_target_type)
    {
        case 'gutenberg':
            ncore_tiny_mce_target_obj(shortcode);
            break;

        case 'editor':
            ncore_tiny_mce_target_obj.selection.setContent( ncore_tiny_mce_target_obj.selection.getContent() + shortcode );
            break;

        case 'textarea':
             var obj = ncoreJQ( ncore_tiny_mce_target_obj );

             var pos = obj.prop("selectionEnd")
             var text = obj.val()

             text = text.substring( 0, pos ) + shortcode + text.substring( pos )

             obj.val( text )
             break;
    }
}


function ncore_tinymce_register_parser( parser_function )
{
    if (typeof ncore_tinymce_parsers == 'undefined')
        ncore_tinymce_parsers = [];

    ncore_tinymce_parsers.push( parser_function );
}

function ncore_tinymce_register_content_renderer( render_function )
{
    if (typeof ncore_tinymce_content_renderers == 'undefined')
        ncore_tinymce_content_renderers = [];

    ncore_tinymce_content_renderers.push( render_function );
}

function digimember_tinymce_callbackShortcode( form_id )
{
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

        var br = ncore_tiny_mce_target_type == 'editor'
               ? "<br />\n"
               : "\n";

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
                    + ncore_tinymce_parseText( css_selector, 'hintForOrderID' )
                    + ncore_tinymce_parseText( css_selector, 'cancel_email' );

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

        /**
        if (contents == 'closetag')
        {
            shortcode += ' /]';
            shortcodeObj.shortcode = shortcode;
            shortcodeObj.parts.opentag = shortcode;
        }
        else if (contents)
        {
            shortcodeObj.parts.opentag = shortcode + "]";
            shortcodeObj.parts.content = contents;
            shortcodeObj.parts.closetag = "[/" + plugin_tag + ']';
            shortcode += "]" + br + contents + br + "[/" + plugin_tag + ']';
            shortcodeObj.shortcode = shortcode;
        }
        else
        {
            shortcodeObj.parts.opentag = shortcode + ']';
            shortcode += ']';
            shortcodeObj.shortcode = shortcode;
        }**/

        console.log(shortcodeObj);
        //digimember_tinymce_insertShortcode( shortcodeObj );
        digimember_tinymce_insertShortcode( shortcode );
    }

    catch( e )
    {
        alert( e )
    }
}


function ncore_tinymce_getValue( css_selector, name )
{
    var value = ncoreJQ( css_selector + 'select[name=ncore_'+name+']' ).val();

    if (value) {
        return value;
    }

    value = ncoreJQ( css_selector + 'select[input=ncore_'+name+']' ).val();

    if (value) {
        return value;
    }

    return false;
}




function ncore_tinymce_parseInt( css_selector, name )
{
    return ncore_tinymce_parseText( css_selector, name, null, true );
}

function ncore_tinymce_parseUrl( css_selector, name )
{
    var url = ncoreJQ( css_selector+' input[name=ncore_'+name+']' ).val();

    var is_void = !url || url == 'http://' || url == 'https://';
    if (is_void)
    {
        return '';
    }


    if (!__digimember_tinymce_validUrl( url ))
    {
        throw __ncore_tinymce_helper( 'tinymce_error_url' );
    }

    return ' ' + name + '="' + url + '"';
}

function ncore_tinymce_parsePage( css_selector, name )
{
    var page_id = ncoreJQ( css_selector+' select[name=ncore_'+name+']' ).val();
    if (page_id>0)
    {
        return ' ' + name + '=' + page_id;
    }
    return '';
}

function ncore_tinymce_parseText( css_selector, name, required_msg, skip_quotes )
{
    var value = ncoreJQ( css_selector+' input[name=ncore_'+name+']' ).val();

    if (value)
    {
        var quote = skip_quotes
                  ? ''
                  : (value.indexOf( '"' ) >= 0
                     ? "'"
                     : '"');

        return  ' ' + name + '=' + quote + value + quote;
    }

    if (required_msg)
    {
        throw required_msg;
    }

    return ''
}




function ncore_tinymce_parseSelect( css_selector, name, first_value )
{
    var value = ncoreJQ( css_selector+'select[name=ncore_'+name+']' ).val();

    if (value)
    {
        return ' ' + name + '=' + value;
    }

    if (first_value)
    {
        var checked = ncoreJQ( css_selector+' input[name=ncore_'+first_value+']' ).val();
        if (checked)
        {
            return ' ' + name + '=' + first_value;
        }
    }

    return '';
}

function ncore_tinymce_inputValue( css_selector, name )
{
    var input = ncoreJQ( css_selector+' input[name=ncore_'+name+']' )

    return input.val();
}


function ncore_tinymce_parseCheckbox( css_selector, name, value )
{
    // var cb = ncoreJQ( css_selector+' input[name=ncore_'+name+']' )
    // var checked = cb.attr( 'checked' )

    var checked = ncoreJQ( css_selector+' input[name=ncore_'+name+']' ).val();
    if (checked && checked != '0')
    {
        var have_value = typeof value != 'undefined'

        if (have_value)
        {
            return ' ' + value;
        }
        else
        {
            return ' ' + name;
        }
    }
    return ''
}

function ncore_tinymce_parseCheckboxList( css_selector, name, required_msg )
{
    return ncore_tinymce_parseText( css_selector, name, required_msg );
}


function __digimember_tinymce_validUrl( url ){

    return true
}

class ncore_shortcode_parser {
    constructor(shortcode) {
        this.shortCodeData = {
            'shortcode': shortcode,
            'elements': [],
            'hasopen': false,
            'hasclose': false,
            'hassplit': false,
            'hascontent': false,
            'innerContent': false,
        }
        this.findStart();
        this.findEnd();
        this.getContent();
        return this;
    }
    findStart() {
        let start = this.shortCodeData.shortcode.indexOf('[');
        if (start !== -1) {
            let end = this.shortCodeData.shortcode.indexOf(']');
            let tag = this.shortCodeData.shortcode.substring(start+1,end);
            if (!this.isCloseTag(tag)) {
                let type = 'open';
                const {newtag, attributes} = this.getTagAttributes(tag)
                this.addElement({
                    'tag': newtag,
                    'attributes': attributes.length > 0 ? attributes : false,
                    'type': type,
                    'pos': start,
                    'length': start+end,
                });
                this.shortCodeData.hasopen = true;
            }


        }
        return false;
    }
    findEnd() {
        let startElement = this.getStartElement();
        let endCode = '[/'+startElement.tag+']';
        let start = this.shortCodeData.shortcode.indexOf(endCode);
        if (start !== -1) {
            let end = start + endCode.length;
            let type = 'close';
            this.addElement({
                'tag': startElement.tag,
                'type': type,
                'pos': start,
                'length': endCode.length,
            });
            this.shortCodeData.hassplit = true;
            this.shortCodeData.hasclose = true;
            return true;
        }
        return false;
    }
    getContent() {
        if (this.hasSplit()) {
            let startElement = this.getStartElement();
            let endElement = this.getEndElement();
            let start = startElement.pos+startElement.length+1;
            let end = endElement.pos;
            let content = this.shortCodeData.shortcode.substring(start,end);
            if (content !== '') {
                this.addElement({
                    'tag': content,
                    'type': 'content',
                    'pos': start,
                    'length': content.length,
                });
                this.shortCodeData.hascontent = true;
            }

        }
        return false;
    }
    setInnerContent(content) {
        this.shortCodeData.innerContent = content;
    }
    setContent(contentString) {
        if (this.hasContent()) {
            let contentElement = this.getContentElement();
            this.updateElementByTag(contentElement.tag, contentString);
            return true;
        }
        if (this.hasStart()) {
            let startTag = this.getStartElement().tag;
            let startElement = this.getStartElement();
            let endElement = this.getEndElement();
            let newShortcode = this.getStartAsTag()
            newShortcode += contentString;
            newShortcode += this.hasEnd() ? this.getEndAsTag() : '[/'+this.getStartElement().tag+']';
            this.shortCodeData.shortcode = newShortcode;
            this.findStart();
            this.findEnd();
            this.getContent();
            return true;
        }
        return false;
    }
    getTagAttributes(tag) {
        let attributeArray = tag.split(' ');
        if (attributeArray.length > 1) {
            return {
                'newtag': attributeArray.shift(),
                'attributes': attributeArray
            };
        }
        return {
            'newtag': attributeArray[0],
            'attributes': []
        };
    }
    hasSplit() {
        return this.shortCodeData.hassplit;
    }
    hasStart() {
        return this.shortCodeData.hasopen;
    }
    hasEnd() {
        return this.shortCodeData.hasclose;
    }
    hasContent() {
        return this.shortCodeData.hascontent;
    }
    getStartElement(){
        return this.shortCodeData.elements.find(element => {
            return element.type === 'open';
        })
    }
    getStartAsTag(){
        let startElement = this.getStartElement();
        let startTag = '['+startElement.tag;
        for (let a = 0; a < startElement.attributes.length; a++) {
            startTag += ' '+startElement.attributes[a];
        }
        return startTag+']';
    }
    getEndAsTag(){
        let endElement = this.getEndElement();
        return '[/'+endElement.tag+']';
    }
    getEndElement(){
        return this.shortCodeData.elements.find(element => {
            return element.type === 'close';
        })
    }
    getContentElement() {
        return this.shortCodeData.elements.find(element => {
            return element.type === 'content';
        })
    }
    getContentAsString() {
        let contentElement = this.getContentElement();
        return contentElement.tag;
    }
    updateElementByTag(tag,updatetag) {
        return this.shortCodeData.elements.find(element => {
            if (element.tag === tag) {
                element.tag = updatetag
            }
        })
    }
    addElement(elementData) {
        this.shortCodeData.elements.push(elementData);
    }
    getDefaultElement() {
        return {
            'tag': '',
            'type': '',
            'pos': 0,
            'length': 0,
        }
    }
    isCloseTag(tag) {
        if (tag.substring(0,1) === '/') {
            return true;
        }
        return false;
    }

}


