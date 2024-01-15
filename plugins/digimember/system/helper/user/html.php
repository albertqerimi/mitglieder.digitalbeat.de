<?php

function ncore_renderButtonStyle( $button, $prefix='' )
{
    $style = '';

    $color = ncore_retrieve( $button, array( $prefix.'fg_color', $prefix.'fg' ) );
    if ($color)
    {
        $style .= "color: $color;";
    }

    $color = ncore_retrieve( $button, array( $prefix.'bg_color', $prefix.'bg' ) );
    if ($color)
    {
        $style .= "background-color: $color;";
    }

    $radius = ncore_retrieve( $button, $prefix.'radius', false );
    if ($radius !== false)
    {
        $style .= "border-radius: ${radius}px;";
    }

    return $style;
}

function ncore_imgUrl( $img_path, $api=null )
{
    $path   = "/webinc/image/$img_path";
    $url    = empty($api)
        ? ncore_api()->pluginUrl( $path )
        : $api->pluginUrl( $path );

    return $url;
}

function ncore_icon( $icon, $tooltip = '', $api=null, $attr = array() )
{
    // see https://api.jqueryui.com/theming/icons/
    //    $ui_icons = [
    //        'view_page' => 'ui-icon-arrowreturnthick-1-e',
    //    ];

    //    $ui_icon = ncore_retrieve( $ui_icons, $icon );

    //    if ($ui_icon)
    //    {
    //        ncore_addCssClass( $attr, "ncore_ui_icon ui-icon $ui_icon" );

    //        $attr_text = ncore_renderAttributes( $attr );

    //        $html = "<span $attr_text title=\"$tooltip\"></span>";
    //    }
    //    else
    {
        $url = ncore_imgUrl( "icon/$icon.png", $api );

        $attr[ 'src' ] = $url;

        ncore_addCssClass( $attr, "ncore_icon digimember_$icon ncore_$icon" );

        $attr_text = ncore_renderAttributes( $attr );

        $html = "<img $attr_text title=\"$tooltip\" />";
    }

    return $html;
}

function ncore_attribute( $tag, $value )
{
    if (is_array($value))
    {
        $value = implode( ' ', $value );
    }

    $value = trim($value);

    $find = array( '"'     );
    $repl = array( '&quot' );

    $value = str_replace( $find, $repl, $value );
    $tag = ncore_washText( $tag );

    $have_value = (bool) $value || $value === '0';

    return $have_value
        ? " $tag=\"$value\" "
        : '';
}

function ncore_mergeAttributes( $attr1, $attr2 )
{
    $seperators = array(
        'class' => ' ',
    );

    if (!$attr1)
    {
        $attr1 = array();
    }
    if (!$attr2)
    {
        $attr2 = array();
    }

    $result = $attr1;
    foreach ($attr2 as $key => $value)
    {
        if (isset($result[$key]))
        {
            $seperator = ncore_retrieve( $seperators, $key, ' ' );
            $result[$key] .= $seperator . $value;
        }
        else
        {
            $result[$key] = $value;
        }

    }
    return $result;
}

function ncore_addCssClass( &$attributes, $new_css_class, $key = 'class' )
{
    $class = ncore_retrieve( $attributes, $key );

    $class .= " $new_css_class";

    $attributes[ $key ] = trim( $class );
}

function ncore_renderAttributes( $attributes )
{
    $use_jquery_handler = ncore_retrieveAndUnset( $attributes, 'use_jquery_handler', false );
    $onclick_js  = ncore_retrieveAndUnset( $attributes, 'onclick' );
    $onchange_js = ncore_retrieveAndUnset( $attributes, 'onchange' );
    $raw_attr    = ncore_retrieveAndUnset( $attributes, 'raw' );

    $have_js = false;

    if ($onclick_js)
    {
        $have_js = true;
        $onclick_js .= ';';
    }
    $onclick_js .= 'return true;';

    $confirm_keys = array( 'confirm2', 'confirm' );
    foreach ($confirm_keys as $key)
    {
        $confirm = ncore_retrieveAndUnset( $attributes, $key );
        unset( $attributes[ $confirm ] );

        if ($confirm)
        {
            $have_js = true;
            $confirm = str_replace( "|", "\\n\\n", $confirm );
            $onclick_js = "if (confirm(\"$confirm\")) { $onclick_js }";
            $use_jquery_handler = true;
        }
    }

    if ($have_js)
    {
        $onclick_js .= '; return false;';

        if ($use_jquery_handler)
        {
            $input_id  = ncore_addId( $attributes, 'js_button' );

            $js = "ncoreJQ( '#$input_id' ).click( function(){ $onclick_js; return false; } );";

            ncore_addJsOnLoad( $js );
        }
        else
        {
            $attributes[ 'onload' ] = $onclick_js;
        }
    }

    if ($onchange_js)
    {
        $input_id  = ncore_addId( $attributes, 'js_change' );

        $js = "ncoreJQ( '#$input_id' ).change( function(){ $onchange_js; return true; } );";

        ncore_addJsOnLoad( $js );
    }




    $attr_tags = '';

    $tooltip = ncore_retrieveAndUnset( $attributes, 'tooltip' );
    if ($tooltip)
    {
        $tooltip_attr = ncore_tooltipAttr( $tooltip );
        $attributes = ncore_mergeAttributes( $attributes, $tooltip_attr );
    }

    foreach ($attributes as $tag => $value)
    {
        $attr_tags .= ' ' . ncore_attribute( $tag, $value );
    }

    $attr_tags  .= ' ' . $raw_attr;


    return $attr_tags ;
}

function ncore_htmlLink( $url, $label, $attributes=array() )
{
    $as_popup = ncore_retrieveAndUnset( $attributes, 'as_popup', false );

    if ($as_popup && empty($attributes['target'])) {
        $attributes['target'] = '_blank';
    }

    $attr_html = ncore_renderAttributes(  $attributes );

    $is_absolute_url = $url && $url[0] == '/';
    $has_protocol = strpos( $url, '://' ) !== false;

    if (!$is_absolute_url && !$has_protocol)
    {
        $url = 'http://'.$url;
    }

    return "<a href='$url' $attr_html>$label</a>";
}


function ncore_tooltipIcon()
{
    // return "<span class='ncore_tooltip_icon'>?</span>";

    $attr = array();

    $attr['class'] = 'ncore_tooltip_icon';

    return ncore_icon( 'tooltip', $tooltip='', $api=null, $attr );
}

function ncore_tooltip( $tooltip_text, $inner_html='', $attributes=array() )
{
    if (!$tooltip_text)
    {
        return $inner_html;
    }

    $tooltip_attr = ncore_tooltipAttr( $tooltip_text );
    $attributes   = ncore_mergeAttributes( $attributes, $tooltip_attr );

    $html_attr = ncore_renderAttributes( $attributes );

    $tag = ncore_retrieveAndUnset( $attributes, 'tag', 'div' );

    return "<$tag $html_attr>$inner_html</$tag>";


}

function ncore_tooltipAttr( $tooltip_text )
{
    static $js_onload_included;

    $tt_contents_id = ncore_id( 'digi_tt' );

    $css = 'ncore_with_tooltip';

    $html = ncore_api()->load->model( 'logic/html' );

    if ( !isset( $js_onload_included ) )
    {
        $js_onload_included = true;

        //TODO PHPStan always false. dicover later
        $debug_options = 0&&NCORE_DEBUG
            ? 'hide: 60000,'
            : '';

        $js_onload =
            "
			ncoreJQ('.$css').dmTooltip(
				{
                    $debug_options
				});";

        $html->jsOnLoad($js_onload);
    }

    $tooltip_text = ncore_paragraphs( $tooltip_text, array( 'also_include_first_parapraphend' => true ) );

    $attributes = array();

    $attributes['data-title'] = $tooltip_text;
    $attributes['class'] = $css;

    return $attributes;
}


function ncore_AdminTabs( $metas )
{
    if (!$metas)
    {
        return '';
    }

    ncore_api()->load->helper( 'array' );

    $metas = array_values( $metas );

    $have_selected = (bool) ncore_elementsWithKey( $metas, 'selected', true );
    if (!$have_selected)
    {
        $metas[0]['selected'] = true;
    }

    $html = '<div class="ncore_admin_tabs">
		<ul>';

    foreach ($metas as $index => $meta)
    {
        $label    = ncore_retrieve( $meta, 'label', 'TAB_LABEL_MISSING' );
        $div_id   = ncore_retrieve( $meta, 'div_id' );
        $url      = ncore_retrieve( $meta, 'url', '#' );
        $selected = ncore_retrieve( $meta, 'selected', false );

        $css = '';
        if ($selected)
        {
            $css .= ' selected';
        }
        if ($div_id)
        {
            $css .= ' onclick';
        }

        $rel_attr = $div_id
            ? "rel='$div_id'"
            : '';

        $html .= "<li class='$css'>
					<a href='$url' $rel_attr>$label</a>
				 </li>";
    }

    $html .= '
		</ul>
	</div>';

    $js = "ncoreJQ('div.ncore_admin_tabs > ul > li.onclick').each(function(){

		var _this = ncoreJQ(this);
		_this.click(function(e){
			var old_id = _this.parent().find('.selected a').attr('rel');
			var new_id = _this.find('a').attr('rel');

			ncoreJQ('#' + old_id).hide();
			ncoreJQ('#' + new_id).show();
			_this.parent().find('.selected').removeClass('selected');
			_this.addClass('selected');

			e.preventDefault();
		});
	});";

    $model = ncore_api()->load->model( 'logic/html' );
    $model->jsOnLoad( $js );

    return $html;
}

function ncore_isFirst( $what )
{
    global $ncore_is_first;

    $is_first = empty( $ncore_is_first[ $what ] );

    $ncore_is_first[ $what ] = true;

    return $is_first;
}

function ncore_resetFirst( $what )
{
    global $ncore_is_first;
    unset( $ncore_is_first[ $what ] );
}

function ncore_paragraphs( $text_or_lines, $settings=array())
{
    if (!$text_or_lines)
    {
        return '';
    }

    $use_double_linebreaks = ncore_retrieve( $settings, 'use_double_linkbreaks', false );

    $begin = ncore_retrieve( $settings, 'begin',  '<p>' );
    $end   = ncore_retrieve( $settings, 'end',    '</p>' );

    $do_also_include_first_parapraph = ncore_retrieve( $settings, 'also_include_first_parapraphend', false );

    if ($use_double_linebreaks)
    {
        $lines = is_array( $text_or_lines )
            ? $text_or_lines
            : explode( "\n", $text_or_lines );

        $had_line_break = true;

        $current_index = 0;
        $parts = array();

        foreach ($lines as $index => $line)
        {
            $line = trim( $line );

            $is_line_break = $line == '';
            if ($is_line_break) {

                if ($parts[$current_index] && !$had_line_break) {
                    $current_index++;
                }

                $had_line_break = true;
                continue;
            }
            else
            {
                $had_line_break = false;
            }

            if (empty( $parts[ $current_index ] ))
            {
                $parts[ $current_index ] = $line;
            }
            else
            {
                $parts[ $current_index ] .= ' ' . $line;
            }
        }
        $text_or_lines = implode( '|', $parts );
    }

    if (!empty($parts))
    {
        //ok
    }
    elseif (is_array($text_or_lines))
    {
        $parts = $text_or_lines;
    }
    else
    {
        $parts = explode( '|', $text_or_lines );
    }

    if ($do_also_include_first_parapraph) {
        $text = '';
    }
    else
    {
        $text = $parts[0];
        unset( $parts[0] );
    }

    if ($parts)
    {

        $text .= $begin . implode( "$end$begin", $parts ) . $end;
    }

    return $text;
}


function ncore_addId( &$attributes, $basename='html' )
{
    // When html elements are added in ajax calls, prevent duplicate use of the same id.
    // This is ugly, but duplicate ids do not work well is jquery.
    $is_ajax = defined( 'NCORE_IS_AJAX' ) && NCORE_IS_AJAX;

    if ($is_ajax  || empty( $attributes['id'] ))
    {
        $id = ncore_id( $basename );
        $attributes['id'] = $id;
    }
    else
    {
        $id = $attributes['id'] ;
    }

    return $id;
}


function ncore_setJsChange( $selector, $js_onchange )
{
    $api = ncore_api();
    $model = $api->load->model ('logic/html' );
    $model->jsChange( $selector, $js_onchange );
}

function ncore_addJsOnLoad( $js_onload )
{
    $api = ncore_api();
    $model = $api->load->model ('logic/html' );
    $model->jsOnLoad( $js_onload );
}




function ncore_renderHtmlList( $list, $type='and', $prefix='', $suffix='' )
{
    if (empty($list)) {
        return '';
    }

    if (count($list)==1) {
        $last = end( $list );
        return "$prefix$last$suffix";
    }

    switch ($type)
    {
        //        case 'ol':
        //        case 'ul':
        //            $html = "<$type class='htmllist'>";
        //            foreach ($list as $one)
        //            {
        //                $html .= "<li>$prefix$one$suffix</li>";
        //            }
        //            $html .= "</$type>";
        //            return $html;

        case 'and':
        case 'or':

            $last = array_pop( $list );

            //TODO PHPStan list is always true
            $html = $list
                ? $prefix . implode( "$suffix, $prefix", $list ) . $suffix
                . ' '
                . ($type == 'and' ? _ncore( 'and' ) : _ncore( 'or' ) )
                . ' '
                : '';

            $html .= "$prefix$last$suffix";

            return $html;
    }
}


function ncore_FileTypeIconUrl( $type, $size='large' )
{
    $sizes = array(
        's'  => '16px',
        'm'  => '32px',
        'l'  => '48px',
    );

    $valid_types = ' aac ai aiff avi bmp c cpp css dat dmg doc dotx dwg dxf eps'
        . ' exe flv gif h hpp html ics iso java jpg js key less mid mp3'
        . ' mp4 mpg odf ods odt otp ots ott pdf php png ppt psd py qt'
        . ' rar rb rtf sass scss sql tga tgz tiff txt wav xls xlsx xml'
        . ' yml zip ';

    if (NCORE_DEBUG) {
        assert( $valid_types[0] == ' ' );
        assert( substr( $valid_types, -1 ) == ' ' );
    }

    $size = $size[0];

    $dir = empty( $sizes[ $size ] )
        ? $sizes[ 's' ]
        : $sizes[ $size ];

    $type = strtolower( $type );

    $is_type_valid = strpos( $valid_types, " $type " ) !== false;

    if (!$is_type_valid)
    {
        $type = '_page'; // '_blank' or '_page'
    }

    $url = "file_types/$dir/$type.png";

    return ncore_imgUrl( $url );
}