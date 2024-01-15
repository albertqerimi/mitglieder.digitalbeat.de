<?php


function ncore_htmlSelectableImageList($postname,$options,$selected_value,$settings = array(),$attributes = array()) {

    ncore_setupJsInput( 'ncore_initSelectableImageList' );

    $attributes['data-name'] = $postname;

    $max_items_per_row = ncore_retrieve($settings,'max_items_per_row',2);
    $default_image = ncore_retrieve($settings,'default_image','http://placehold.it/300x300');
    $links_new_window = (ncore_retrieve($settings,'links_new_window',true) === true) ? ' target="_blank"' : '';

    $html = '<div class="ncore-selectable-image-list">'."\n";

    $x = 0;
    for ($i=0;$i<count($options);$i++) {
        if ($x == 0 || $x >= $max_items_per_row) {
            if ($x > 0) {
                $html .= '</div>'."\n";
            }
            $html .= '<div class="list-row row-length-'.$max_items_per_row.'">'."\n";
            $x = 0;
        }

        $is_selected = ($options[$i]['value'] == $selected_value) ? ' selected' : '';
        $html .= '<div class="list-item'.$is_selected.'" data-name="'.$postname.'" data-value="'.$options[$i]['value'].'" data-tooltip="'.$options[$i]['tooltip'].'">'."\n";

        $image = ($options[$i]['image'] != '') ? $options[$i]['image'] : $default_image;
        $html .= '<img src="'.$image.'" alt="'.$options[$i]['label'].'">'."\n";
        $html .= '<p>'."\n";
        if ($options[$i]['link'] != '') {

            $html .= '<a href="'.$options[$i]['link'].'"'.$links_new_window.'>'.$options[$i]['label'].'</a>'."\n";
        }
        else {
            $html .= $options[$i]['label']."\n";
        }
        $html .= '</p>'."\n";

        $html .= '</div>'."\n";

        $x++;
    }

    $html .= '</div>'."\n";
    $html .= '</div>'."\n";

    $html .= ncore_htmlHiddenInput($postname,$selected_value,$attributes);
    $html .= "\n";

    return $html;
}

function ncore_htmlTimeSelector($postname,$value,$attributes = array(),$settings = array()) {

    ncore_setupJsInput( 'ncore_initTimeSelector' );

    $attributes['data-name'] = $postname;
    $attr_tags = ncore_renderAttributes($attributes);

    $value_hours = '';
    $value_minutes = '';
    $value_seconds = '';
    if ($settings['hours'] && $settings['minutes'] && !$settings['seconds']) {
        $valid_value = preg_match('/([0-9]{1,2})\:([0-9]{1,2})/',$value,$value_matches);
        if ($valid_value === 1) {
            $value_hours = $value_matches[1];
            $value_minutes = $value_matches[2];
        }
        else {
            $value = '';
        }
    }
    elseif (!$settings['hours'] && $settings['minutes'] && $settings['seconds']) {
        $valid_value = preg_match('/([0-9]{1,2})\:([0-9]{1,2})/',$value,$value_matches);
        if ($valid_value === 1) {
            $value_minutes = $value_matches[1];
            $value_seconds = $value_matches[2];
        }
        else {
            $value = '';
        }
    }
    elseif ($settings['hours'] && $settings['minutes'] && $settings['seconds']) {
        $valid_value = preg_match('/([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})/',$value,$value_matches);
        if ($valid_value === 1) {
            $value_hours = $value_matches[1];
            $value_minutes = $value_matches[2];
            $value_seconds = $value_matches[3];
        }
        else {
            $value = '';
        }
    }
    elseif ($settings['hours'] && !$settings['minutes'] && !$settings['seconds']) {
        $valid_value = preg_match('/([0-9]{1,2})/',$value,$value_matches);
        if ($valid_value === 1) {
            $value_hours = $value_matches[1];
        }
        else {
            $value = '';
        }
    }
    elseif (!$settings['hours'] && $settings['minutes'] && !$settings['seconds']) {
        $valid_value = preg_match('/([0-9]{1,2})/',$value,$value_matches);
        if ($valid_value === 1) {
            $value_minutes = $value_matches[1];
        }
        else {
            $value = '';
        }
    }
    elseif (!$settings['hours'] && !$settings['minutes'] && $settings['seconds']) {
        $valid_value = preg_match('/([0-9]{1,2})/',$value,$value_matches);
        if ($valid_value === 1) {
            $value_seconds = $value_matches[1];
        }
        else {
            $value = '';
        }
    }
    else {
        $value = '';
    }

    $range_hours = range(0,23);
    $range_minutes = range(0,59);
    $range_seconds = range(0,59);

    for ($i=0;$i<10;$i++) {
        $range_hours[$i] = '0'.$range_hours[$i];
        $range_minutes[$i] = '0'.$range_minutes[$i];
        $range_seconds[$i] = '0'.$range_seconds[$i];
    }

    $html = '';

    if ($settings['hours']) {
        $html .= ncore_htmlSelect($postname.'_hours',$range_hours,$value_hours,array_merge($attributes,array('data-time-select'=>'hours')));
        if ($settings['minutes'] || $settings['seconds']) {
            $html .= ':';
        }
    }
    if ($settings['minutes']) {
        $html .= ncore_htmlSelect($postname.'_minutes',$range_minutes,$value_minutes,array_merge($attributes,array('data-time-select'=>'minutes')));
        if ($settings['seconds']) {
            $html .= ':';
        }
    }
    if ($settings['seconds']) {
        $html .= ncore_htmlSelect($postname.'_seconds',$range_seconds,$value_seconds,array_merge($attributes,array('data-time-select'=>'seconds')));
        $html .= "\n";
    }
    $html .= ncore_htmlHiddenInput($postname,$value,array_merge($attributes,array('data-time-select'=>'value','data-time-hours'=>($settings['hours']) ? 1 : 0,'data-time-minutes'=>($settings['minutes']) ? 1 : 0,'data-time-seconds'=>($settings['seconds']) ? 1 : 0)));
    $html .= "\n";

    return $html;
}
function ncore_htmlUnorderedList($postname, $options = array(), $attributes = array()) {
    $attributes['data-name'] = $postname;

    ncore_addCssClass($attributes,'ncore-list-group');

    $attr_tags = ncore_renderAttributes($attributes);

    $html = 	'<ul '.$attr_tags.'>'
        ."\n"
        .ncore_htmlListOptions($options)
        .'</ul>'
        ."\n";
    return $html;
}
function ncore_htmlListOptions($options,$attributes = array()) {
    ncore_addCssClass($attributes,'ncore-list-group-item');
    $attr_tags = ncore_renderAttributes($attributes);

    $html = '';
    foreach ($options as $value=>$label) {
        $html .= '<li data-value="'.$value.'" '.$attr_tags.'>'.$label.'</li>'."\n";
    }
    return $html;
}





function ncore_htmlSelectNullEntryLabel( $null_entry_label )
{
    $null_entry_label = is_string( $null_entry_label )
        ? $null_entry_label
        : _ncore( 'Please select' );

    return "($null_entry_label...)";

}
function ncore_htmlSelect( $postname, $options, $selected_value, $attributes=array() )
{
    ncore_addCssClass($attributes,'ncore_select_input');

    $attributes['name'] = $postname;
    $attr_tags = ncore_renderAttributes( $attributes );

    $html = "<select $attr_tags>\n"
        . ncore_htmlSelectOptions( $options, $selected_value )
        . "</select>\n";

    return $html;
}

function ncore_htmlSelectOptions( $options, $selected_value=false )
{
    $html = '';

    $have_opt_group = false;

    $is_first = true;

    foreach ($options as $value => $label)
    {
        $is_opt_group = ncore_stringStartsWith( $value, 'optgroup' );
        if ($is_opt_group && $have_opt_group)
        {
            $html .= "</optgroup>";
            $have_opt_group = false;
        }

        if ($is_opt_group)
        {
            $html .= "<optgroup label=\"$label\">";
            $have_opt_group = true;
            continue;
        }

        $is_selected = $selected_value == $value;

        $selected_attr = $is_selected
            ? 'selected="selected"'
            : '';

        $html .= "<option value='$value' $selected_attr>$label</option>\n";
    }

    if ($have_opt_group)
    {
        $html .= "</optgroup>";
    }

    return $html;
}

function ncore_htmlCheckbox( $postname, $checked, $label='', $attributes=array(), $hidden_input_attributes=array(), $label_attributes=array() )
{
    ncore_setupJsInput( 'ncore_initCheckbox' );

    $attributes['name'] =  "checkbox_$postname";

    ncore_addCssClass( $attributes, 'ncore_checkbox' );

    $checked_value   = ncore_retrieveAndUnset( $attributes, 'checked_value',   '1' );
    $unchecked_value = ncore_retrieveAndUnset( $attributes, 'unchecked_value', '0' );

    $attributes[ 'data-value-checked' ]   = $checked_value;
    $attributes[ 'data-value-unchecked' ] = $unchecked_value;

    $attr_tags  = ncore_renderAttributes( $attributes );
    $label_attr = ncore_renderAttributes( $label_attributes );

    $hidden_input_attr_tags = ncore_renderAttributes( $hidden_input_attributes );

    $value = $checked ? $checked_value : $unchecked_value;

    $checked_attr = (bool) $checked
        ? ' checked="checked" '
        : '';

    $input = "<span class='ncore_checkbox'><input type='checkbox' $attr_tags $checked_attr /> "
        . "<input type='hidden' name='$postname' value='$value' $hidden_input_attr_tags /></span>";

    return $label
        ? "<label $label_attr>$input $label</label>"
        : $input;
}


function ncore_resolveOptions( $options )
{
    if (empty($options)) {
        return array();
    }

    if (is_array($options)) {

        $have_model = !empty( $options[ 'model' ] ) && !empty( $options[ 'method' ] );

        if ($have_model) {

            $api = empty( $options['api'] )
                ? ncore_api()
                : (is_object($options['api'])
                    ? $options['api']
                    : ncore_api( $options['api'] ) );

            $model = $api->load->model( $options[ 'model' ] );

            $method = $options[ 'method' ];

            $options = $model->$method();
        }

        return $options;
    }

    if (!is_string($options)) {
        trigger_error( "Expected type array or string for options" );
        return array();
    }


    static $cache;

    $menu =& $cache[ $options ];
    if (isset($menu)) {
        return $menu;
    }

    switch ($options)
    {
        case 'yes_no':
            $menu = array(
                'yes' => _ncore('yes'),
                'no'  => _ncore('no'),
            );
            break;
        case 'menu':
            ncore_api()->load->helper( 'array' );
            $menus = wp_get_nav_menus();

            if (empty($menus))
            {
                $url = ncore_siteUrl( '/wp-admin/nav-menus.php' );
                $label = _ncore( 'Design - Menus' );
                $link = ncore_htmlLink( $url, $label );
                return _ncore( 'Go to %s and add a menu.', $link );
            }

            $menu = ncore_listToArray( $menus, 'slug', 'name' );
            break;

        case 'page':
            ncore_api()->load->helper( 'array' );
            $pages = ncore_getPages( 'page' );
            $menu = ncore_listToArraySorted( $pages, 'ID', 'post_title' );
            foreach ($menu as $page_id => $page_label)
            {
                if (!$page_label) {
                    $menu[$page_id] = _ncore( 'Page %s', '#'.$page_id );
                }
            }
            break;

        case 'border_radius':
            $menu = array(
                0 => _digi( 'none - sharp corners' ),
                3 => '3 - ' . _digi( 'small' ),
                5 => 5,
                7 => 7,
                10 => 10,
                15 => 15,
                100 => '20 - ' . _digi( 'full' ),
            );
            break;

        default:
            $menu = apply_filters( 'ncore_resolve_options', array(), $options );
    }

    if (empty($menu)) {
        $menu = array();
    }

    return $menu;
}

function ncore_htmlCheckboxList($postname, $options, $checked_options, $attributes = array())
{
    ncore_setupJsInput( 'ncore_initCheckboxList' );

    $css_unchecked = 'ncore_unchecked';
    $css_checked   = 'ncore_checked';

    $seperator = ncore_retrieve($attributes, 'seperator', ' ');

    $row_size  = ncore_retrieve($attributes, 'row_size', 0 );
    $row_sep   = ncore_retrieve($attributes, 'row_sep', '<br />' );

    $have_all  = ncore_retrieve($attributes, 'have_all', false);
    $all_label = ncore_retrieve( $attributes, 'all_label', _ncore('all') );

    unset( $attributes['css_unchecked'] );
    unset( $attributes['css_checked'] );
    unset( $attributes['seperator'] );
    unset( $attributes['row_size'] );
    unset( $attributes['row_sep'] );
    unset( $attributes['have_all'] );
    unset( $attributes['all_label'] );

    if (is_array($checked_options))
    {
        $checked_options_comma_seperated = implode(',', $checked_options);
    }
    else
    {
        $checked_options_comma_seperated = $checked_options;
        $checked_options = explode(',', $checked_options_comma_seperated);
    }

    $cbname = 'checkbox_' . $postname;

    $html = '';

    $entries_in_row = 0;

    if ($have_all)
    {

        $all_option = array( 'all' => $all_label );
        $options = $all_option + $options;

        $all_checked = in_array( 'all', $checked_options );
    }
    else
    {
        $all_checked = false;
    }

    $row_count = 1;

    foreach ($options as $key => $label)
    {
        $is_optgroup = substr( $key, 0, 9 )== 'optgroup_';

        $entries_in_row++;
        $row_full = $row_size && $entries_in_row > $row_size;

        if ($row_full)
        {
            $html .= $row_sep;
            $entries_in_row = 1;
            $row_count++;

        }
        elseif ($html)
        {
            $html .= $seperator;
        }

        $checked = in_array($key, $checked_options);

        $css = $checked||$all_checked ? $css_checked : $css_unchecked;

        $attributes = array();
        $attributes['value'] = $key;
        $attributes['name'] = $cbname;

        if ($checked)
        {
            $attributes['checked'] = 'checked';
        }

        ncore_addCssClass( $attributes, 'ncore_checkbox_list' );

        $attr_html = ncore_renderAttributes( $attributes );

        if ($is_optgroup)
        {
            $html .= "<label class='ncore_checkboxlist_optgroup'>$label</label>";
        }
        else
        {
            $html .= "<label class='$css'><input type='checkbox' $attr_html />$label</label>";
        }
    }

    $html .= ncore_htmlHiddenInput( $postname, $checked_options_comma_seperated );

    ncore_setupJsInput();

    $css = "ncore_row_count_$row_count";

    return "<div class='ncore_checkbox_list ncore_post_$postname $css'>$html</div>";
}

function ncore_htmlButtonList($rows, $attributes = array())
{
    $button_text = ncore_retrieve($attributes, 'button_text', 'Kündigen' ); //lokalisieren
    $show_product_name = ncore_retrieve($attributes, 'show_product_name', false );
    $show_order_id = ncore_retrieve($attributes, 'show_order_id', false );
    $seperator = ncore_retrieve($attributes, 'seperator', '<br>' );


    unset( $attributes['class'] );
    unset( $attributes['seperator'] );
    unset( $attributes['button_text'] );
    unset( $attributes['show_product_name'] );
    unset( $attributes['show_order_id'] );
    unset( $attributes['use_generic'] );

    $html = '';
    foreach ($rows as $key => $row)
    {
        $button = ncore_htmlButtonUrl($button_text, $row['link'], array('as_popup' => true, 'class' => 'button button-primary dm-button-list-button'));
        $html .= "<div class='dm-button-list-entry'>";
        if ($show_product_name) {
            $html .= "<label class='dm-button-list-label' >".$row['name']."</label>";
        }
        if ($show_order_id) {
            $html .= "<label class='dm-button-list-label' >".$row['order_id']."</label>";
        }
        $html .= $button."</div>".$seperator;
    }
    return "<div class='dm-button-list'>$html</div>";
}

function ncore_setupJsInput( $function='all' )
{
    static $cache;

    $is_initialized = !empty( $cache[ 'all' ] ) || !empty( $cache[ $function ] );
    if ($is_initialized) {
        return;
    }

    $cache[ $function ] = true;

    $js = $function === 'all'
        ? 'ncore_setupJsForAllInputTypes();'
        : $function . '();';

    $model = ncore_api()->load->model('logic/html' );
    $model->jsOnLoad( $js );
}


function ncore_htmlRadioButton($postname, $value, $label='', $checked = false, $attributes=array() )
{
    $attributes['name'] =  $postname;
    $attributes['value'] = $value;

    if ($checked)
    {
        $attributes['checked'] = 'checked';
    }


    if ($label)
    {
        $css = ncore_retrieve( $attributes, 'class' );
        unset( $attributes['class'] );
        $css_attribute = ncore_attribute( 'class', $css );
        $radio_attr = ncore_renderAttributes( $attributes );
        return "<label $css_attribute><input type='radio' $radio_attr /> $label</label>";
    }
    else
    {
        $radio_attr = ncore_renderAttributes( $attributes );
        return "<input type='radio' $radio_attr />";
    }
}

function ncore_htmlRadioButtonList($postname, $options, $selected, $attributes=array() )
{
    $seperator = ncore_retrieve( $attributes, 'seperator', '&nbsp;' );
    unset( $attributes['seperator'] );

    $html = '';
    foreach ($options as $value => $label)
    {
        if ($html)
        {
            $html .= $seperator;
        }

        $checked = $value == $selected;

        $html .= ncore_htmlRadioButton( $postname, $value, $label, $checked, $attributes );
    }

    return "<div class='ncore_radiobutton_list'>$html</div>";
}


function ncore_htmlFlags( $postname, $value, $flag_options, $flag_tooltips, $attributes=array() )
{
    $attributes['name'] =  "flags_$postname";
    $attr_tags = ncore_renderAttributes( $attributes );

    $html_id = ncore_id( 'cb' );
    $css_cb = ncore_id( 'cb_selector' );

    $html = "<div $attr_tags>
<input id='$html_id' type='hidden' name='$postname' value='$value' />";


    foreach ($flag_options as $flag => $label)
    {
        $checked_attr = ($value & $flag)
            ? ' checked="checked" '
            : '';

        $input = "<input type='checkbox' value='$flag' $checked_attr class='$css_cb' />";

        $html .= "<div class='ncore_flag'><label>$input $label</label></div>";

        $tooltip = ncore_retrieve( $flag_tooltips, $flag );
        $html .= ncore_tooltip( $tooltip, '', array( 'class' => 'ncore_flag') );

        $html .= "<div class='ncore_flag_clear'></div>";
    }

    $html .= '</div>';

    $js_onchange = "

	var flags = 0;
	ncoreJQ.each( ncoreJQ('input.$css_cb'), function(index,obj) {
		if (obj.checked) flags |= obj.value;
	});
	ncoreJQ( '#$html_id' ).val( flags );
";

    ncore_setJsChange( "input.$css_cb", $js_onchange );
    return $html;
}

function ncore_htmlFloatInput( $postname, $value, $attributes=array() )
{
    $css = ncore_retrieve( $attributes, 'class' );

    $css .= " ncore_float_input";

    $attributes['class'] = $css;

    $js_on_load = 'ncore_setupFloats()';

    ncore_addJsOnLoad( $js_on_load );

    $defaults = array(
        'size' => 8,
        'maxlength' => 12
    );

    $attributes = array_merge( $defaults, $attributes);

    $decimals = ncore_retrieveAndUnset( $attributes, 'decimals', 2 );

    $value = is_numeric( $value )
        ? number_format_i18n( $value, $decimals )
        : $value;

    return ncore_htmlTextInput($postname, $value, $attributes );
}


function ncore_htmlPageOrUrlInput( $postname, $value='', $attributes=array() )
{
    static $initialized;
    if (empty($initialized))
    {
        $initialized = true;

        $js = "ncoreJQ('.ncore_page_or_url_container select').change( function() { var val=ncoreJQ(this).val(); if(val) ncoreJQ(this).parent().find('input').val(val); } );";

        $js .= "ncoreJQ('.ncore_page_or_url_container a').click( function() { var url=ncoreJQ(this).parent().find('input').val(); if (url) window.open(url,'_blank'); return false; } );";

        $js .= "ncoreJQ('.ncore_page_or_url_container input').keyup( function() { var val=ncoreJQ(this).val();  ncoreJQ(this).parent().find('select').val(val); } );";

        $model = ncore_api()->load->model( 'logic/html' );
        $model->jsOnLoad( $js );
    }

    $pages = ncore_resolveOptions( 'page' );

    $options = array( '' => ncore_htmlSelectNullEntryLabel( _ncore('Select page' ) ));

    $model = ncore_api()->load->model( 'logic/link' );
    foreach ($pages as $page_id => $label)
    {
        $url = $model->readPost( 'page', $page_id );
        $options[ $url ] = $label;
    }

    ncore_addCssClass( $attributes, 'ncore_page_or_url_input' );

    $input  = ncore_htmlTextInput( $postname, $value, $attributes );

    $select = ncore_htmlSelect( "selector_$postname", $options, $value, $attributes );

    $icon = ncore_icon( 'view_page', _ncore('View page') );

    $link = ncore_htmlLink( $url, $icon );

    return "<div class='ncore_page_or_url_container'>$input$link $select </div>";
}


function ncore_htmlIntInput( $postname, $value, $attributes=array() )
{
    $zero = isset( $attributes['display_zero_as'] )
        ? $attributes['display_zero_as']
        : '0';

    if ($value === 0 || $value === '0')
    {
        $value = $zero;
    }

    $css = ncore_retrieve( $attributes, 'class' );

    $css .= " ncore_int_input";

    $attributes['class'] = $css;

    $js_on_load = 'ncore_setupInputs()';

    ncore_addJsOnLoad( $js_on_load );

    $defaults = array(
        'size' => 3,
        'maxlength' => 5
    );

    $attributes = array_merge( $defaults, $attributes);

    return ncore_htmlTextInput($postname, $value, $attributes );
}

function ncore_htmlTextInput($postname, $value, $attributes=array() )
{
    if (!isset($attributes['type']))
    {
        $attributes['type'] = 'text';
    }

    $attributes['value'] = esc_attr($value);

    return ncore_htmlInput( $postname, $attributes );
}

function ncore_htmlTextInputCode( $value, $attributes=array() )
{
    static $initialized;
    if (!isset($initialized))
    {
        $initialized = true;

        $js = "ncoreJQ('input.ncore_select_all, textarea.ncore_select_all').focus( function() { this.select(); } );";
        // ."ncoreJQ('input.ncore_select_all, textarea.ncore_select_all').click( function() { this.select(); } );";

        $model = ncore_api()->load->model('logic/html' );
        $model->jsOnLoad( $js );
    }

    $rows = ncore_retrieve( $attributes, 'rows', 1 );
    $cols = ncore_retrieve( $attributes, array( 'size', 'cols' ), 0 );

    $attributes['readonly'] = 'readonly';

    $attributes['class'] = ncore_retrieve( $attributes, 'class' ) . ' ncore_code ncore_select_all';

    $use_textarea = $rows >= 2;

    if ($use_textarea)
    {
        if (!$cols) $cols = 50;
        if (!$rows) $rows = 10;

        $attributes[ 'rows' ] = $rows;
        $attributes[ 'cols' ] = $cols;



        return ncore_htmlTextarea( 'dummy', $value, $attributes );
    }
    else
    {
        $attributes['value'] = $value;
        $attributes['type'] = 'text';

        if (!$cols) {
            $cols = min( 80, max( 20, strlen( $value )) );
        }

        $attributes['size'] = $cols;

        return ncore_htmlInput( 'dummy', $attributes );
    }
}

function ncore_htmlDisplayLink( $url, $attributes=array() )
{
    $input = ncore_htmlTextInputCode( $url, $attributes );

    $icon = ncore_icon( 'open_url', _ncore( 'Open URL' ) );

    $link = ncore_htmlLink( $url, $icon, array( 'target' => '_blank' ) );

    return $input.$link;
}

function ncore_htmlHiddenInput( $postname, $value, $attributes=array() )
{
    $attributes['type'] = 'hidden';
    $attributes['value'] = htmlspecialchars($value);

    return ncore_htmlInput( $postname, $attributes );
}

function ncore_htmlInput($postname, $attributes=array() )
{
    $attributes['name'] = $postname;

    $attr_tags = ncore_renderAttributes( $attributes );

    return "<input $attr_tags />";
}

function ncore_htmlTextarea( $postname, $value, $attributes = array() )
{
    $default_atributes = array(
        'cols' => 40,
        'rows' => 5,
    );

    $attributes = array_merge( $default_atributes, $attributes );

    $attributes['name'] = $postname;

    $attr_html = ncore_renderAttributes( $attributes );

    $value_esc = htmlspecialchars( $value, ENT_QUOTES );
    return "<textarea $attr_html>$value_esc</textarea>";
}

function ncore_htmlButton( $postname, $label, $attributes = array() )
{
    $attributes['name'] = $postname;
    $attributes['type'] = (isset($attributes['type'])) ? $attributes['type'] : 'button';

    ncore_addCssClass( $attributes, 'button ncore_btn' );

    $attributes[ 'use_jquery_handler' ] = true;

    $attr_html = ncore_renderAttributes( $attributes );

    return "<button $attr_html>$label</button>";
}

function ncore_htmlButtonMinor( $postname, $label, $attr = array() )
{
    ncore_addCssClass( $attr, 'button-minor' );

    $js = ncore_retrieveAndUnset( $attr, 'onclick' );

    $value = ncore_retrieveAndUnset( $attr, 'value', '1' );

    $js .= "; ncoreJQ('<input/>',{type:'hidden',name:'$postname',value:'$value'}).appendTo('form');ncoreJQ(this).parentsUntil('form').parent().submit();return false; ";

    $attr[ 'onclick' ] = $js;

    return ncore_htmlLink( '#', $label, $attr );
}

function ncore_htmlButtonUrl( $value, $url, $attributes=array() )
{
    $as_popup = ncore_retrieveAndUnset( $attributes, 'as_popup', false );

    $onClickJs = $as_popup
        ? "window.open( '$url', '_blank' );"
        : "location.href='$url';";

    $onClickJs .= 'return false;';

    return ncore_htmlButtonJs( $value, $onClickJs, $attributes );
}

function ncore_htmlButtonJs( $value, $onClickJs, $attributes=array() )
{
    $attributes[ 'onclick' ] = "$onClickJs;return false;";

    $postname = ncore_retrieveAndUnset( $attributes, 'name', 'dummy' );

    return ncore_htmlButton( $postname, $value, $attributes );

}

function ncore_htmlImageUploaderInit()
{
    static $initialized;
    if (!empty($initialized))
    {
        return;
    }
    $initialized = true;

    if ( did_action( 'admin_enqueue_scripts' ) )
    {
        wp_enqueue_media();
    }
    else
    {
        add_action( 'admin_enqueue_scripts', 'wp_enqueue_media' );
    }
}

function ncore_htmlImageUploader( $postname, $value, $attributes = array() )
{
    // see http://www.webmaster-source.com/2013/02/06/using-the-wordpress-3-5-media-uploader-in-your-plugin-or-theme/

    $input_id  = ncore_addId( $attributes, 'img_button' );

    $button_label  = ncore_retrieve( $attributes, 'button_label',  _ncore( 'Choose image' ) );
    $dialog_title  = ncore_retrieve( $attributes, 'dialog_title',  _ncore( 'Choose image' ) );
    $dialog_button = ncore_retrieve( $attributes, 'dialog_button', _ncore( 'Choose image' ) );

    unset( $attributes[ 'button_label'  ] );
    unset( $attributes[ 'dialog_title'  ] );
    unset( $attributes[ 'dialog_button' ] );

    if (empty($value))
    {
        $value = 'http://';
    }

    $key = md5( $dialog_title.$dialog_button );

    $function = "ncore_lf_$key";

    $html = ncore_htmlTextInput( $postname, $value, $attributes );


    $onclick_js = "return $function( '$input_id' );";
    $html .= ncore_htmlButton( 'dummy', $button_label, array( 'onclick' => $onclick_js ) );

    static $initalized_loaders;

    if (isset( $initalized_loaders[ $key ] )) {
        return $html;
    }

    $initalized_loaders[ $key ] = true;

    $loader = "ncore_ld_$key";

    $js = "

function $function( input_id )
{
    $function.input_id = input_id;

    if (typeof $loader != 'undefined') {
	    $loader.open();
		return false;
	}

	$loader = wp.media.frames.file_frame = wp.media({
		title: \"$dialog_title\",
		button: {
			text: \"$dialog_button\"
		},
		multiple: false
	});

	$loader.on('select', function() {
		attachment = $loader.state().get('selection').first().toJSON();

        var input_id = $function.input_id;

		ncoreJQ( '#'+input_id).val(attachment.url);

        ncoreJQ( 'img#'+input_id + '_preview').attr('src',attachment.url).show();
	});

	$loader.open();

    return false;
}
";

    $api = ncore_api();
    $model = $api->load->model ('logic/html' );

    $model->jsFunction( $js );

    $hidden_html = '
<style>
div.media-modal {
    z-index: 1999999;
}
</style>
';
    $model->hiddenHtml( $hidden_html );



    return $html;
}

function ncore_htmleditor( $postname, $value, $settings )
{
    $simple_buttons = (bool) ncore_retrieve( $settings, 'simple_buttons', false );
    $hide_images    = (bool) ncore_retrieve( $settings, 'hide_images',    false );

    $wp_settings = array();
    $wp_settings['textarea_rows'] = ncore_retrieve( $settings, 'rows', 15 );
    $wp_settings['textarea_name'] = $postname;

    if ($simple_buttons)
    {
        $wp_settings[ 'teeny' ] = true;
    }
    if ($hide_images)
    {
        $wp_settings[ 'media_buttons' ] = false;
    }

    $editor_id = ncore_retrieve( $settings, 'editor_id' );
    if ($editor_id) {
        ncore_assert( !is_numeric( $editor_id ), "TinyMCE does not handle numeric editor ids very well - please use letters only" );
    }
    else
    {
        $editor_id = ncore_id( 'ncore_editor_', $format='alpha' );
    }

    ob_start();
    wp_editor( $value, $editor_id, $wp_settings );
    $html = ob_get_clean();
    return $html;


}

/**
 * @param string $html_id
 * @param array $meta
 *
 * @return string
 */
function ncore_renderPasswordIndicator($html_id, $meta)
{
    static $js_rendered;
    static $cache;
    $isRendered =& $cache[$js_rendered];
    if (!isset($isRendered) || !$isRendered) {
        $isRendered = true;

        $username_input_name = ncore_retrieve($meta, 'username_input', false );
        $password_input_name = ncore_retrieve($meta, 'password_input', false );
        $password2_input_name = ncore_retrieve($meta, 'password2_input', false );

        $username_value = ncore_retrieve( $meta, 'username_value', false );

        $js_username = $username_input_name
            ? "form.find('input[name=$username_input_name]').val()"
            : '"' . str_replace( '"', '', $username_value ) . '"';


        $js = "
ncoreJQ( 'input[name=$password_input_name],input[name=$password2_input_name]' ).keyup(
    function( event )
    {
        var obj  = ncoreJQ(event.target);
        var form = obj.parentsUntil( 'form' ).parent();

        var usr = $js_username;
        var pw1 = form.find('input[name=$password_input_name]').val();
        var pw2 = form.find('input[name=$password2_input_name]').val();

        var strength = dmCalculatePasswordStrength( pw1, usr, pw2 );

        ncoreJQ( '.ncore_pwstrength' ).hide();
        ncoreJQ( '.ncore_pwstrength.' + strength ).show();

    }
)
";

        /** @var ncore_HtmlLogic $html */
        $html = dm_api()->load->model( 'logic/html' );
        $html->jsOnLoad( $js );
    }

    $none = ncore_retrieve($meta, 'label_none', _ncore( 'Password strength' ));
    $bad = ncore_retrieve($meta, 'label_bad', _ncore( 'Very weak' ));
    $mismatch = ncore_retrieve($meta, 'label_mismatch', _ncore( 'No match' ));
    $weak = ncore_retrieve($meta, 'label_weak', _ncore( 'Weak' ));
    $good = ncore_retrieve($meta, 'label_good', _ncore( 'Medium' ));
    $strong = ncore_retrieve($meta, 'label_strong', _ncore( 'Strong' ));

    $html = "
<div class='ncore_pwstrength none'>$none</div>
<div class='ncore_pwstrength mismatch ncore_hidden'>$mismatch</div>
<div class='ncore_pwstrength bad ncore_hidden'>$bad</div>
<div class='ncore_pwstrength weak ncore_hidden'>$weak</div>
<div class='ncore_pwstrength good ncore_hidden'>$good</div>
<div class='ncore_pwstrength strong ncore_hidden'>$strong</div>";

    return $html;
}