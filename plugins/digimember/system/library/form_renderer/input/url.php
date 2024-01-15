<?php

class ncore_FormRenderer_InputUrl extends ncore_FormRenderer_InputBase
{
	protected function renderInnerWritable()
	{
		$html_id = $this->htmlId();
		$postname = $this->postname();
		$value = esc_attr( $this->value() );

//        if (empty($value)) {
//            $value = 'http://';
//        }

		$size = $this->meta( 'size', 50 );

		$attributes = array(
			'id' => $html_id,
			'size' => $size,
		);

		return ncore_htmlTextInput( $postname, $value, $attributes );
	}

	protected function defaultRules()
	{
		return 'url';
	}

    public function value()
    {
        $value = parent::value();

        $is_void = $value == 'http://' || $value == 'https://';

        return $is_void
               ? ''
               : $value;
    }

}


