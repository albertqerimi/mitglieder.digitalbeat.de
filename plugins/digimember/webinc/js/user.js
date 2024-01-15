function ncore_getElementsByClass( searchClass, domNode, tagName) {
    if (domNode == null) domNode = document;
    if (tagName == null) tagName = '*';
    var el = new Array();
    var tags = domNode.getElementsByTagName(tagName);
    var tcl = " "+searchClass+" ";
    for(i=0,j=0; i<tags.length; i++) {
        var test = " " + tags[i].className + " ";
        if (test.indexOf(tcl) != -1)
            el[j++] = tags[i];
    }
    return el;
}

function ncore_setupJsForAllInputTypes()
{
    ncore_initCheckbox();
    ncore_initCheckboxList();
    ncore_initTimeSelector();
    ncore_initSelectableImageList();
}

function ncore_setupJsInputColorPicker() {

    var obj = ncoreJQ('input[data-ncore-colorpicker]');

    if (typeof obj.wpColorPicker != 'undefined')
    {
        obj.wpColorPicker();
    }
}


function ncore_initSelectableImageList()
{
    ncoreJQ('.ncore-selectable-image-list .list-item[data-tooltip]').each(function() {
                var $this = ncoreJQ(this);
                var tt = $this.attr('data-tooltip');
                $this.tooltip({
                    items: '.ncore-selectable-image-list .list-item',
                    content: function() { return tt; },
                    tooltipClass: 'ncore_jquery-ui-tooltip'
                });
            });

    ncoreJQ('.ncore-selectable-image-list .list-row .list-item').on('click.ncore',function() {
                var $this = ncoreJQ(this);
                var name = $this.attr('data-name');
                var value = $this.attr('data-value');

                ncoreJQ('input[name="' + name + '"]').val(value).change();

                $this.parent().parent().find('.list-item').removeClass('selected');
                $this.addClass('selected');
            });

    ncoreJQ('.ncore-selectable-image-list .list-item.selected').click();
}

function ncore_initTimeSelector()
{
    ncoreJQ('select[data-time-select]').on('change.ncore',function() {
                var $this = ncoreJQ(this);
                var name = $this.attr('data-name');
                var $hidden = ncoreJQ('input[data-name="' + name + '"]');

                var hours = ncoreJQ('select[data-time-select="hours"][data-name="' + name + '"]').val();
                var minutes = ncoreJQ('select[data-time-select="minutes"][data-name="' + name + '"]').val();
                var seconds = ncoreJQ('select[data-time-select="seconds"][data-name="' + name + '"]').val();

                var str = '';
                if ($hidden.attr('data-time-hours') === '1') {
                    hours = (parseInt(hours) < 10) ? '0' + hours : hours;
                    str += hours;
                    if ($hidden.attr('data-time-seconds') === '1' || $hidden.attr('data-time-minutes') === '1') {
                        str += ':';
                    }
                }
                if ($hidden.attr('data-time-minutes') === '1') {
                    minutes = (parseInt(minutes) < 10) ? '0' + minutes : minutes;
                    str += minutes;
                    if ($hidden.attr('data-time-seconds') === '1') {
                        str += ':';
                    }
                }
                if ($hidden.attr('data-time-seconds') === '1') {
                    seconds = (parseInt(seconds) < 10) ? '0' + seconds : seconds;
                    str += seconds;
                }

                ncoreJQ('input[name="' + name + '"]').val(str);
            });
    ncoreJQ('input[data-time-select]').on('change.ncore',function() {
                var $this = ncoreJQ(this);
                var name = $this.attr('data-name');
                var value = $this.val();
                var hours = $this.attr('data-time-hours');
                var minutes = $this.attr('data-time-minutes');
                var seconds = $this.attr('data-time-seconds');

                var spl = value.split(':');

                if (hours === '1' && minutes === '1' && seconds === '1') {
                    ncoreJQ('select[data-time-select="hours"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[0],'0'));
                    ncoreJQ('select[data-time-select="minutes"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[1],'0'));
                    ncoreJQ('select[data-time-select="seconds"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[2],'0'));
                }
                else if (hours === '1' && minutes === '1' && seconds === '0') {
                    ncoreJQ('select[data-time-select="hours"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[0],'0'));
                    ncoreJQ('select[data-time-select="minutes"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[1],'0'));
                }
                else if (hours === '0' && minutes === '1' && seconds === '1') {
                    ncoreJQ('select[data-time-select="minutes"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[0],'0'));
                    ncoreJQ('select[data-time-select="seconds"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[1],'0'));
                }
                else if (hours === '1' && minutes === '0' && seconds === '0') {
                    ncoreJQ('select[data-time-select="seconds"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[0],'0'));
                }
                else if (hours === '0' && minutes === '1' && seconds === '0') {
                    ncoreJQ('select[data-time-select="minutes"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[0],'0'));
                }
                else if (hours === '0' && minutes === '0' && seconds === '1') {
                    ncoreJQ('select[data-time-select="seconds"][data-name="' + name + '"]').val(ncore.helpers.string.ltrim(spl[0],'0'));
                }
            });
    ncoreJQ('select[data-time-select]').change();
}

function ncore_initCheckbox()
{
    ncoreJQ( 'input.ncore_checkbox').off('change').on( 'change', function() {

        var checkbox_postname = ncoreJQ( this ).attr( 'name' );

        // checkbox_ -> 9 chars
        var postname = checkbox_postname.substring( 9 );

        var checked = this.checked;

        var value;

        if (checked)
        {
            value = ncoreJQ( this ).attr( 'data-value-checked' );
            if (!value) {
                value = 1;
            }
        }
        else
        {
            value = ncoreJQ( this ).attr( 'data-value-unchecked' );
            if (!value) {
                value = 0;
            }
        }

        var obj = ncoreJQ(this).parent('span.ncore_checkbox').find( 'input[name="' + postname + '"]'); // in case of error, replace parent() by parents()
        obj.val( value );
        obj.trigger( 'change' );
    } );
}




function ncore_initCheckboxList()
{
    ncoreJQ( 'input.ncore_checkbox_list').off('change').on( 'change', function() {

        var checkbox_postname = ncoreJQ( this ).attr( 'name' );

        // checkbox_ -> 9 chars
        var postname = checkbox_postname.substring( 9 );

        var all_checked = false;

        var father = ncoreJQ(this).parentsUntil('div.ncore_checkbox_list').parent();
        var checkboxes = father.find('input.ncore_checkbox_list[name="' + checkbox_postname + '"]' );

        checkboxes.each( function( index, obj ) {
            if (obj.checked && obj.value=='all')
            {
                all_checked = true;
            }
        } );

        var value='';
        checkboxes.each( function( index, obj ) {

            if (obj.checked) {
                if (value) value += ',';
                value += obj.value;
            }

            var css_checked = obj.checked || all_checked;
            if (css_checked) {
                ncoreJQ(obj).parent().removeClass('ncore_unchecked');
                ncoreJQ(obj).parent().addClass('ncore_checked');
            }
            else
            {
                ncoreJQ(obj).parent().removeClass('ncore_checked');
                ncoreJQ(obj).parent().addClass('ncore_unchecked');
            }
        } );

        var obj = father.find( 'input[name="' + postname + '"]');
        obj.val( value );
        obj.trigger( 'change' );
    } );

    ncoreJQ('div.ncore_checkbox_list input[type="hidden"]').off('change').on('change',function() {
        var $this = ncoreJQ(this);
        var value = $this.val();

        var spl = value.split(',');
        $this.parent().find('input[type="checkbox"]').prop('checked',false).parent().addClass('ncore_unchecked').removeClass('ncore_checked');
        for (var i in spl) {
            $this.parent().find('input[type="checkbox"][value="' + spl[i] + '"]').prop('checked',true).parent().addClass('ncore_checked').removeClass('ncore_unchecked');
        }
    });
}

function ncore_windowOpenPosition( width, height )
{
    var specs = 'height=' + height + ',width=' + width;

    var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName('body')[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight|| e.clientHeight|| g.clientHeight,

        l = w.screenLeft || w.screenX,
        t = w.screenTop || w.screenY;

    var left = l + Math.round((x-width)/2);
    var top  = t + Math.round((y-height)/2);

    specs += ',left=' + left + ',top=' + top;

    return specs;

}

function ncore_urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}


function ncore_canShowByCookieCount( basename, max_count, max_days, do_increase_counter )
{
    var is_initialized = parseInt( ncore_readCookie( 'dm_' + basename + '_initialized' ) );
    var counts_so_far  = parseInt( ncore_readCookie( 'dm_' + basename + '_count' ) );

    if (isNaN(is_initialized)) {
        is_initialized = 0;
    }
    if (isNaN(counts_so_far)) {
        counts_so_far = 0;
    }

    var must_reset = !is_initialized;

    if (must_reset)
    {
        ncore_createCookie( 'dm_' + basename + '_initialized', 1, max_days );
        ncore_createCookie( 'dm_' + basename + '_count',       0, max_days+1 );

        return true;
    }

    var can_show = counts_so_far < max_count;

    if (can_show && typeof do_increase_counter != 'undefined' && do_increase_counter)
    {
        ncore_createCookie( 'dm_' + basename + '_count', counts_so_far+1, max_days+1 );
    }

    return can_show;
}



function ncore_createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function ncore_readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function ncore_eraseCookie(name) {
    createCookie(name,"",-1);
}

function ncore_copyShortcodeToClipboard(type, id) {
    var shortcode = '[ds_'+type+' id="'+id+'"]';
    if (!navigator.clipboard) {
        ncore_fallbackCopyTextToClipboard(shortcode, id);
        return;
    }
    navigator.clipboard.writeText(shortcode).then(function() {}, function(err) {});
}

function ncore_fallbackCopyTextToClipboard(text, id) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
    } catch (err) {}
    document.body.removeChild(textArea);
}

function ncore_fallbackCopyValueToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
    } catch (err) {}
    document.body.removeChild(textArea);
}

function ncore_copyTooltipInputToClipboard(e, linkElement) {
    e.preventDefault();
    var elementValue = linkElement.firstChild.value;
    if (!navigator.clipboard) {
        ncore_fallbackCopyValueToClipboard(elementValue);
        return;
    }
    navigator.clipboard.writeText(elementValue).then(function() {}, function(err) {});
}

function ncore_switchElementAttribute(element, attribute, change, back = false, timer = false) {
    element.setAttribute(attribute, change);
    if (back && timer) {
        setTimeout(() => {
            element.setAttribute(attribute, back);
        }, timer);
    }
}

function ncore_switchElementTooltip(element, attribute, change, back = false, timer = false) {
    element.setAttribute(attribute, change);
    if (back && timer) {
        setTimeout(() => {
            element.setAttribute(attribute, back);
        }, timer);
    }
}

