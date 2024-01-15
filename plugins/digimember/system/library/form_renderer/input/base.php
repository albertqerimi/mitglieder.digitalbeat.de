<?php

/**
 * Class ncore_Formrenderer_InputBase
 * @method ncore_FormRendererForm parent()
 */
abstract class ncore_Formrenderer_InputBase extends ncore_Formrenderer_ElementBase
{
    protected $_has_error = false;

	public function __construct( $parent, $meta )
	{
		parent::__construct( $parent, $meta );

		$depends_on = $this->meta( 'depends_on', array() );

		$column = $this->columnName();

		$element_id = $this->element_id();

		/** @var ncore_FormVisibilityLib $formVisibility */
		$formVisibility = $this->api->load->library('form_visibility');
		$this->form_visibility = $formVisibility->create( $parent, $column, $element_id, $depends_on );

		$this->_setupJsAndCss();

	}

	public function value()
	{
		if (!$this->value && (!$this->value_set || $this->is_readonly))
		{
			$default = $this->defaultValue();
			if ($default)
			{
				return $default;
			}
		}
		return $this->value;
	}

	public function postedValue($field_name = '')
	{
		if (isset( $this->validated_value ) )
		{
			return $this->validated_value;
		}

		$use_get_method = $this->meta( 'method', 'post' ) == 'get';

        $maybe_quoted_value = $use_get_method
							?  $this->retrieveSubmittedValue( $_GET,  $field_name  )
							:  $this->retrieveSubmittedValue( $_POST, $field_name  );

        $unquoted_value = ncore_stripSlashes( $maybe_quoted_value );

		$this->onPostedValue( $field_name, $unquoted_value );

		return $unquoted_value;
	}

	public function setValue( $value )
	{
		$this->value_set = true;
		$this->value = $value;
	}

    public function haveValue()
    {
        if (!$this->value()) {
            return false;
        }

        if (!is_string($this->value())) {
            return true;
        }

        return (bool) trim( $this->value() );
    }

	public function postname( $field_name='' )
	{
		if (!$field_name)
		{
			$field_name = $this->columnName();
		}

		$element_id = $this->element_id();

		$use_plain_names = $this->form()->setting( 'plain_postnames', false );

		return $use_plain_names
			   ? $field_name
			   : "ncore_$field_name$element_id";
	}

	public function rules()
	{
		$rules = $this->meta( 'rules', 'defaults' );
		return str_replace( 'defaults', $this->defaultRules(), $rules );
	}

	public function hasRule( $rule )
	{
		$rules = explode( '|', $this->rules() );
		$has_rule = in_array( $rule, $rules );
		return $has_rule;
	}

	public function isReadonly()
	{
		if (!isset($this->is_readonly))
		{
			$rules = explode( '|', $this->meta( 'rules' ) );
			$this->is_readonly = in_array( 'readonly', $rules );
		}

		return $this->is_readonly;
	}

	public function render()
	{
		if ($this->isHiddenInput())
		{
            $html = $this->renderInnerWritable();
            $this->form()->pushHiddenInput( $html );
			return null;
		}

		$css = trim( $this->cssClass() );

        /** @noinspection PhpUnusedLocalVariableInspection */
        list( $is_required, $hints ) = $this->_validateRules();

		$hint = $this->hint();
		if ($hint)
		{
			$hints[] = $hint;
		}

        $hidden_input_html = $this->form()->popHiddenInput();

		$html_input = $this->renderInputOuter();

        $html_input .= $hidden_input_html;

        return array( $html_input, $hints, $css );
	}

    public function isSingleRow()
    {
        return false;
    }

	public function validate()
	{
		$element_id = $this->elementId();
		$form_data = $this->parent()->getData( $element_id );

		$is_visible = $this->form_visibility->isVisible( $form_data);
		if (!$is_visible)
		{
			return '';
		}

		unset( $this->validated_value );

		/** @var ncore_RuleValidatorLib $validator */
		$validator = $this->api->load->library( 'rule_validator' );

		$rules = $this->rules();
		$label = $this->label();
		$value = $this->postedValue();


		$error_msg = $validator->validate( $label, $value, $rules );

		if ($error_msg)
		{
			$this->_has_error = true;
		}

		$this->validated_value = $value;

		return $error_msg;
	}

	public function elementId()
	{
		return $this->meta( 'element_id', 0 );
	}

    public function isInRowWithNext()
    {
        return (bool) $this->meta( 'in_row_with_next', false );
    }

	public function hasError($has_error = null,$error_message = '')
	{
		if (is_bool($has_error))
		{
			$this->_has_error = $has_error;
			$this->_error_message = $error_message;
		}
		return $this->_has_error;
	}

	public function section()
	{
		return $this->meta( 'section' );
	}

	public function isHiddenInput()
	{
		return false;
	}

    public function rowLayout()
    {
        return 'line';
    }

    public function renderTooltip()
    {
        $tooltip = $this->meta('tooltip');


        return $tooltip
               ? ncore_tooltip( $tooltip, $inner_html='', $attr=array( 'tag' => 'span') )
               : '';
    }

    public function renderRequiredMarker()
    {
        list( $is_required/*, $hints*/ ) = $this->_validateRules();

        return $is_required
                     ? $this->requiredMarker()
                     : '';
    }

	//
	// protected function
	//
    /** @var null|ncore_FormVisibilityHandler */
	protected $form_visibility;
    
    protected function cssClass()
    {
        return $this->form_visibility->input_css()
               . ' '
               . $this->meta('css')
               . ' '
               . 'ncore_input_' . $this->type();
    }
               
    protected function retrieveSubmittedValue( $_POST_or_GET, $field_name )
    {
        $postname = $this->postname( $field_name );

        // Accounts for posted arrays
        $brack_pos = strpos($postname,'[');
        if ($brack_pos !== false) {
        	$base_name = substr($postname,0,$brack_pos);
        	preg_match_all("/\[[^\]]*\]/", $postname, $matches);
        	$base_result = ncore_retrieve($_POST_or_GET,$base_name);

        	foreach ($matches[0] as $key) {
        		$base_result = ncore_retrieve($base_result,str_replace(array('[',']'),'',$key));
        	}

        	return $base_result;
        }

        return ncore_retrieve( $_POST_or_GET, $postname );
    }

	protected function onPostedValue( $field_name, &$value )
	{
	}

	protected abstract function renderInnerWritable();
	protected function renderInnerReadonly()
	{
		$value = $this->value();
		return $value;
	}

	private function renderInputOuter()
	{
		if ($this->isReadonly())
		{
			$postname = $this->postname();
			$value = $this->value();
			$input = $this->renderInnerReadonly()
				   . ncore_htmlHiddenInput( $postname, $value );
		}
		else
		{
			$input = $this->renderInnerWritable();
		}

        $prefix = $this->meta( 'prefix', '' );
        $suffix = $this->meta( 'suffix', '' );

		$unit = $this->meta( 'unit' );
		if ($unit)
		{
		    $input = '
<div class="dm-input-group">
    ' . $input . '
    <label class="dm-input-icon" for="' . $this->postname().$this->elementId() . '">
        ' . $unit . '
    </label>
</div>
';
		}
		$button = $this->meta( 'button' );
		if ($button)
		{
		    $input = '
<div class="dm-input-group">
    ' . $input . '
    ' . $button . '
</div>
';
		}

        $html = "$prefix$input$suffix";

		return $html;
	}

	protected function requiredMarker()
	{
		return '<label class="dm-input-label dm-input-label-required">' . _ncore( 'Required' ) . '</label>';
	}

	protected function defaultRules()
	{
		return '';
	}

	protected function onInitJs( &$js_code  )
	{
		$this->form_visibility->renderOnInitJs( $js_code );
	}

	protected function defaultValue()
	{
		return $this->meta('default' );
	}

	protected function meta2attributes( &$attributes, $keys )
	{
		foreach ($keys as $key)
		{
			$attr = $this->meta( $key, false );
			if ($attr !== false)
			{
				$attributes[ $key ] = $attr;
			}
		}
	}

    protected function hint()
    {
        return $this->meta('hint');
    }

    public function fullWidth() {
	    return $this->meta('full_width', false);
    }

	//
	// private section
	//
	private $_error_message = false;
	private $is_readonly;
	private $value = '';
	private $value_set = false;
	private $validated_value;
    private $rule_validate_result;

	private function _setupJsAndCss()
	{
		$js_code = '';
		$this->onInitJs( $js_code );

		/** @var ncore_HtmlLogic $model */
        $model = $this->api->load->model( 'logic/html' );
		$model->jsOnLoad( $js_code );
	}

    private function _validateRules( $force_reload=false )
    {
        if ($force_reload||!isset( $this->rule_validate_result))
        {
            /** @var ncore_RuleValidatorLib $validator */
            $validator = $this->api->load->library( 'rule_validator' );

            $rules = $this->rules();

            $this->rule_validate_result = $validator->hints( $rules );
        }

        return $this->rule_validate_result;
    }

}




