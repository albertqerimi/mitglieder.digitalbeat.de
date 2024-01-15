<?php

class ncore_FormRenderer_InputMap extends ncore_FormRenderer_InputBase
{
	protected function renderInnerWritable()
	{
		$postname = $this->postname();
		$value = $this->value();
		$value_esc = esc_attr( $value );

		$options = $this->meta( 'array', array() );
		$select_options = $this->meta('select_options', null);
		$label_template = $this->meta( 'label_template', '%s'._ncore(': ') );

		$css_class = ncore_id( 'map_input_element' );
		$hidden_input_id = ncore_id( 'map_input_hidden' );
		$html = '<div>';

		$this->api->load->helper( 'array' );

		$values = $this->valueArray();

        $selector = "input.$css_class";

        $onchange_js = "

			var value='';
			ncoreJQ('$selector').each( function( index, obj ) {

				obj.value = obj.value.replace( '\"', '' );

				var id = obj.name.replace( 'map_input_', '' );

				if (value)
				{
					value += ',';
				}

				value += id + ':\"' + obj.value + '\"';
			} );
			ncoreJQ('#$hidden_input_id').val( value );
		";

		foreach ($options as $key => $label)
		{
			$element_postname = "map_input_$key";
			$element_value = esc_attr( ncore_retrieve( $values, $key ) );

			if ($select_options) {
			    $input = ncore_htmlCheckboxListShort($element_postname, $select_options, explode(',', $element_value), [
			        'class' => $css_class,
                ]);
            }
			else {
			    $input = "<input type='text' name='$element_postname' value='$element_value' class='$css_class dm-input dm-fullwidth' />";
            }

			$label = $label_template
				   ? sprintf( $label_template, $label )
				   : $label;

			$html .= '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-md-4 dm-col-xs-12">' . $label . '</div>
    <div class="dm-col-md-8 dm-col-xs-12">' . $input . '</div>
</div>
';
		}

		$html .= "</div>";

		$html .= "<input id='$hidden_input_id' type='hidden' name='$postname' value='$value_esc' />";

		/** @var ncore_HtmlLogic $model */
		$model = $this->api->load->model( 'logic/html' );
		$model->jsChange($selector, $onchange_js);

		return $html;
	}

	protected function defaultRules()
	{
		return 'trim';
	}

	protected function defaultValue()
	{
		$default = $this->meta('default' );

		if (is_array($default))
		{
			$this->api->load->helper( 'array' );
			$default = ncore_simpleMapImplode( $default );
		}

		return $default;
	}

	protected function valueArray()
	{
		$value = $this->value();

		$values = ncore_simpleMapExplode( $value );

		$is_empty = true;
		foreach ($values as $one)
		{
			if ($one)
			{
				 $is_empty = false;
				 break;
			}
		}

		if ($is_empty)
		{
			return $this->meta('default', array() );
		}

		return $values;
	}
}


