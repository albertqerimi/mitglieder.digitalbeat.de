<?php

class ncore_FormVisibilityLib extends ncore_Library
{
    public function create( $form, $column, $element_id, $depends_on )
    {
        return new ncore_FormVisibilityHandler( $form, $column, $element_id, $depends_on );
    }
}

class ncore_FormVisibilityHandler
{
    public function __construct( $form, $column, $element_id, $depends_on )
    {
        $this->form = $form;
        $this->column = $column;
        $this->element_id = $element_id;

        $this->depends_on = $depends_on
                          ? $depends_on
                          : array();
    }

    public function input_css()
    {
        $css = '';

        foreach ($this->depends_on as $column => $value_or_values)
        {
            $base = $this->_input_css_base( $column );

            $css .= " $base";

            $values = is_array( $value_or_values )
                    ? $value_or_values
                    : array( $value_or_values );

            foreach ($values as $value)
            {
                $css .= ' ' . $base . '_' . $value;
            }
        }

        return $css;
    }

    public function select_css( $column='' )
    {
        if (!$column) {
            $column = $this->column;
        }

        static $cache;

        $form_id = 'ncore_form';

        $cachekey = $form_id . '/' . $column . '/' . $this->element_id;

        $css =& $cache[ $cachekey ];
        if (empty($css))
        {
            $column_clean = str_replace( array( '[',']' ), '_', $column );
            $css = ncore_id( "vis_sel_$column_clean" );
        }

        return $css;
    }

    public function renderOnInitJs( &$js_code )
    {
        if (!$this->depends_on)
        {
            return;
        }

        foreach ($this->depends_on as $column => $value_or_values)
        {
            $css_of_select = $this->select_css( $column );

            static $handled;
            if (isset( $handled[$css_of_select] ))
            {
                continue;
            }
            $handled[$css_of_select] = true;

            $my_css = $this->_input_css_base( $column );

            $function = ncore_id( 'ncore_form_visibility' );

            $js_function = "function $function( val )
            {
                if (typeof val == 'undefined' || !val)
                    return;



                var val_css = '${my_css}_' + val;

                if (typeof window.ncore_formvisibility == 'undefined')
                    window.ncore_formvisibility = new Object();

                ncore_formvisibility[ '$my_css' ] = val;

                for (var css in ncore_formvisibility)
                {
                    ncoreJQ('.'+css).show();
                }

                for (var css in ncore_formvisibility)
                {

                    val = ncore_formvisibility[ css ];

                    var val_css = css + '_' + val;

                    ncoreJQ('.'+css+':not(.'+val_css+')').hide();
                }
            }
";

            $model = $this->form->api()->load->model( 'logic/html' );
            $model->jsFunction( $js_function );

            $js_code .= "
ncoreJQ('.$css_of_select').not('input[type=radio]').change(
    function (obj) {
        var val = ncoreJQ(this).val();
        $function( val );
    }
);

ncoreJQ('.$css_of_select').not('input[type=radio]').keyup(
    function (obj) {
        var val = ncoreJQ(this).val();
        $function( val );
    }
);

$function( ncoreJQ('.$css_of_select').not('input[type=radio]').val() );


ncoreJQ('.$css_of_select input[type=radio]').change(
    function (obj) {
        var val = ncoreJQ('.$css_of_select input[type=radio]:checked').val();
        if (val)
            $function( val );
    }
);

var val = ncoreJQ('.$css_of_select input[type=radio]:checked').val();
$function( val );



";


        }

    }

    public function isVisible( $form_data )
    {
        foreach ($this->depends_on as $key => $value_or_values)
        {
            $values = is_array( $value_or_values )
                    ? $value_or_values
                    : array( $value_or_values );

            $value = ncore_retrieve( $form_data, $key );

            $visible = in_array( $value, $values );

            if (!$visible)
            {
                return false;
            }

        }
        return true;
    }

    private $form = null;
    private $column = '';
    private $element_id = 0;
    private $depends_on = array();

    private function _input_css_base( $column )
    {
        static $cache;

        $form_id = 'ncore_form'; // $this->form->form_id();

        $cachekey = $form_id . '/' . $column . '/' . $this->element_id;

        $css =& $cache[ $cachekey ];
        if (empty($css))
        {
            $css = ncore_id('form_visiblity');
        }

        return $css;
    }

}