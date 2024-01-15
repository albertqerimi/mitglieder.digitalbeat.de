<?php

    function ncore_htmlDateInput( $postname, $value, $settings=array(), $attributes=array() )
    {
        static $initialized_cache = array();

        $is_past   = (bool) ncore_retrieve( $settings, 'past_dates_only' );
        $is_future = (bool) ncore_retrieve( $settings, 'future_date_only' );

        $css = 'ncore_dateedit ncore_dateedit' . ($is_past?'':'_past').($is_future?'':'_future');

        $initialized =& $initialized_cache[ $css ];

        if (!isset($initialized))
        {
            ncore_api()->load->helper( 'date' );

            $initialized = ncore_id('datePicker');
            $date_format = str_replace(['m', 'd', 'y'], ['mm', 'dd', 'yyyy'], strtolower(_ncore( 'm/d/Y' )));

            $language = str_replace('_', '-', get_locale());
            $datepicker_options = "
        language: '$language',
        format: '$date_format',
        weekStart: 1,
        autoHide: true,
        zIndex: 2147483647,
";

            if ($is_past)
            {
                $datepicker_options .= "endDate: new Date(),startDate: new Date(0),";
            }
            elseif ($is_future)
            {
                $datepicker_options .= "startDate: new Date(),";
            }
            else {
                $datepicker_options .= "startDate: new Date(0),";
            }

            $js_onload = "
    ncoreJQ( '.$initialized' ).dmDatePicker({
        $datepicker_options
});
";
            /** @var ncore_HtmlLogic $html */
            $html = ncore_api()->load->model( 'logic/html' );
            $html->jsOnLoad( $js_onload );
        }

        $attributes['size'] = 10;
        $attributes['class'] = ncore_retrieve( $attributes, 'class' ) . ' ' . $css . ' ' . $initialized;
        $attributes['autocomplete'] = 'off';

        $value = ncore_formatDate( strtotime($value) );

        return ncore_htmlTextInput( $postname, $value, $attributes );
}