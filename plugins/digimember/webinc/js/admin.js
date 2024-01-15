

function ncore_setupInputs()
{
    ncoreJQ('input.ncore_int_input').keyup(function () {
        this.value = this.value.replace(/[^0-9]/g,'');
    });
}

function ncore_setupFloats()
{
    ncoreJQ('input.ncore_float_input').keyup(function () {
        this.value = this.value.replace(/[^0-9\.\,]/g,'');
    });
}

function ncore_trim( string )
{
    if (typeof string != 'undefined')
    {
        return string.replace(/^\s+|\s+$/g,"")
    }

    return ''
}

function ncore_submitForm( form_id, action_url )
{
    var form_data = ncoreJQ('#'+form_id).serialize();

    var url = action_url
              ? action_url
              : ncoreJQ('#'+form_id).attr('action');

    var timestamp = new Date().getTime();
    var get_args = form_data + '&ajax=1&' + timestamp;
    url = ncore_addUrlArgs( url, get_args )

    ajax_wait_start();

    ncoreJQ.getJSON( url, ajax_callback );

    return false;
}


function ncore_encodeHtmlEntities( string )
{
    return string.replace( /\</g, '&lt;' ).replace( /\>/g, '&gt;' ).replace( /\&/g, '&amp;' );
}