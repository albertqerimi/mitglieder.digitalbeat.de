<?php

class ncore_FormRenderer_InputColor extends ncore_FormRenderer_InputBase
{
    public function __construct( $parent, $meta )
    {
        parent::__construct( $parent, $meta );
    }

    public function postedValue($field_name = '')
    {
        $value = parent::postedValue($field_name);
        if (!$value) {
            return '';
        }

        return '#' . ncore_washText( substr($value,1), '', '#-_()' );
    }

    protected function renderInnerWritable()
    {
        $input_id       = ncore_id( 'color_input' );
        $select_id      = ncore_id( 'color_select' );
        $marker_id      = ncore_id( 'color_marker' );
        $postname       = $this->postname();
        $selected_value = $this->value();

        $select_css = $this->form_visibility->select_css();

        $options = array_merge(
            array( '' => '&nbsp;' ),
            $this->colorOptions()
        );

        $selected_value_marker = $selected_value
                               ? $selected_value
                               : 'transparent';

        $marker = "<span id='$marker_id' style='width: 42px; height: 42px; border-radius: 3px; background-color: $selected_value_marker; padding: 0; display: inline-block; border: 1px dashed rgba(0, 0, 0, 0.1);'>&nbsp;</span>";

        $select_attributes = array(
            'id '         => $select_id,
            'class'       => "$select_css ncore_color_input_select",
        );

        $text_attributes = array(
            'id'          => $input_id,
            'name'        => $postname,
            'class'       => "$select_css ncore_color_input_text",
            'size'        => 7,
        );

        return '
<div class="dm-row dm-middle-xs dm-color-input">
    <div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-5">' . ncore_htmlSelect($options, $options, $selected_value, $select_attributes) . '</div>
    <div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-5">' . ncore_htmlTextInput($postname, $selected_value, $text_attributes) . '</div>
    <div class="dm-col-md-1 dm-col-xs-2">' . $marker . '</div>
</div>
';
    }

    protected function renderInnerReadonly()
    {
        $color_rgb   = $this->value();

        if (!$color_rgb) {
            return '';
        }

        $html = "<span style='width: 25px; height: 25px; background-color: $color_rgb; padding: 0; display: inline-block;'>&nbsp;</span> $color_rgb";

        return $html;

    }

    protected function requiredMarker()
    {
        return '';
    }

    private function colorOptions()
    {
        return array(
            '#FFFFFF' => _ncore( 'white' ),
            '#F44336' => _ncore( 'red' ),
            '#FF9800' => _ncore( 'orange' ),
            '#FFEB3B' => _ncore( 'yellow' ),
            '#2196F3' => _ncore( 'blue' ),
            '#4CAF50' => _ncore( 'green' ),
            '#9C27B0' => _ncore( 'purple' ),
            '#009688' => _ncore( 'teal' ),
            '#E91E63' => _ncore( 'pink' ),
            '#00BCD4' => _ncore( 'cyan' ),
            '#707070' => _ncore( 'gray' ),
            '#000000' => _ncore( 'black' ),
        );
    }
}