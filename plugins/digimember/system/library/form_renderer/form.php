<?php

class ncore_FormRendererForm
{
	public function __construct( $api, $sections, $settings=array() )
	{
		$this->api = $api;
		$this->sections = $sections;
		$this->settings = $settings;
	}

    public function formCss()
    {
        return 'ncore_form_' . $this->layout() . ' ' . $this->setting( 'form_css', '' );
    }

	public function addInput( ncore_FormRenderer_InputBase $input )
	{
		$this->inputs[] = $input;

		if ( $this->_hasRequiredRule( $input ) )
		{
			$this->required_input_count++;
		}
        else
        {
            $this->optional_input_count++;
        }
	}

    public function addButton( ncore_Formrenderer_ButtonBase $button )
	{
		$this->buttons[] = $button;
	}

	public function haveRequiredRule()
	{

		return !$this->setting('hide_required_hint')
               && $this->required_input_count > 0;
               //&& $this->optional_input_count > 0;
	}

	public function html()
	{
		ob_start();
		$this->render();
		$html = ob_get_clean();
		return $html;
	}

	public function layout()
	{
		return $this->setting('layout', 'table');
	}

	public function getInput( $column, $element_id)
	{
		foreach ($this->inputs as $input)
		{
			if ($input->columnName() == $column
				&& $input->elementId() == $element_id)
			{
				return $input;
			}
		}

		return false;
	}

	public function getInputArray($column) {
		$return = array();
		foreach ($this->inputs as $input) {
			if (ncore_stringStartsWith($input->columnName(),$column.'[')) {
				$return[] = $input;
			}
		}

		return $return;
	}

	public function render()
	{
		$current_section = false;

        $have_required_marker = $this->haveRequiredRule();

        $this->renderFormBegin();

        $section = false;

        $inputs_in_row = array();

		foreach ($this->inputs as $input)
		{
			if ($input->isHidden())
			{
				continue;
			}

            if ($input->isHiddenInput())
            {
                echo $input->render();
                continue;
            }


            $section = $input->section();

			$have_new_section = $section != $current_section;
			if ($have_new_section)
			{
                if ($inputs_in_row) {
                    $this->renderer()->renderInput( $inputs_in_row, $have_required_marker );
                    $inputs_in_row = array();
                }

				if ($current_section)
				{
					$this->renderSectionEnd();
				}

				$this->renderSectionBegin( $section );
				$current_section = $section;
			}

            $is_in_row_with_next = $input->isInRowWithNext();
            if ($is_in_row_with_next)
            {
                $inputs_in_row[] = $input;
                continue;
            }

            if ($inputs_in_row)
            {
                $inputs_in_row[] = $input;
                $this->renderer()->renderInput( $inputs_in_row, $have_required_marker );
                $inputs_in_row= array();
                continue;
            }

			$this->renderer()->renderInput( array($input), $have_required_marker );
		}

        if ($inputs_in_row)
        {
            $this->renderer()->renderInput( $inputs_in_row, $have_required_marker );
        }

		if ($section)
		{
			$this->renderSectionEnd();
		}

		$this->renderFormEnd();
	}

	public function renderButtons()
	{
		$seperator = ' ';

		$is_first = true;

		foreach ($this->buttons as $one)
		{
			if ($is_first)
			{
				$is_first = false;
			}
			else
			{
				echo $seperator;
			}

			list( $html, /*$tooltip, $css*/ ) = $one->render();

            echo $html;
		}
	}

	public function getData( $element_id )
	{
		$post_readonly_data = $this->setting( 'post_readonly_data', false );
		$data = array();
		foreach ($this->inputs as $one)
		{
			$key = $one->columnName();
			if (!$key)
			{
				continue;
			}

			if ($one->element_id() != $element_id)
			{
				continue;
			}

			if ($one->isReadonly() && !$post_readonly_data)
			{
				 continue;
			}

			$value = $one->postedValue();

			$data[ $key ] = $value;
		}

		return $data;
	}

	public function setData( $element_id, $data )
	{
        $post_readonly_data = $this->setting( 'post_readonly_data', null );
		foreach ($this->inputs as $one)
		{
			if ($one->element_id() != $element_id || ($post_readonly_data !== null && $one->isReadonly() && !$post_readonly_data))
			{
				continue;
			}

			$key = $one->columnName();

			$use_plain_postnames = $this->setting( 'plain_postnames' );
			if ($use_plain_postnames && !isset( $data[ $key ] ) && preg_match( '/\[.*\]\[(.*)\]$/', $key, $matches ))
			{
				$key = $matches[1];
			}

			$value = ncore_retrieve( $data, $key );

			$one->setValue( $value );
		}

	}

	public function validate()
	{
		$messages = array();

		foreach ($this->inputs as $one)
		{
			$message = $one->validate();

			if ($message)
			{
				$messages[] = $message;
			}
		}

		return $messages;
	}

	public function xssPreventionRequired()
	{
		return ncore_isLoggedIn();
	}

	public function isPosted( $button_name='' )
	{
        $this->api->load->helper( 'xss_prevention' );

		if (empty( $_POST )
			|| !is_array($_POST)
			|| !count($_POST)
            || empty( $_POST[ ncore_XssVariableName() ])
            )
		{
			return false;
		}

        if ($this->xssPreventionRequired())
		{
			if (!ncore_XssPasswordVerified())
			{
				return false;
			}
		}

        if ($button_name) {
            if (empty( $_POST[$button_name] )) {
                return false;
            }
        }

		return true;
	}

	public function api()
	{
		return $this->api;
	}

	public function value( $element_id, $column )
	{
		foreach ($this->inputs as $one)
		{
			$key = $one->columnName();
			if ($key != $column)
			{
				continue;
			}

			if ($one->element_id() != $element_id)
			{
				continue;
			}

			$value = $one->value();

			return $value;
		}

		return false;
	}

	public function postname( $element_id, $column )
	{
		foreach ($this->inputs as $one)
		{
			$key = $one->columnName();
			if ($key != $column)
			{
				continue;
			}

			if ($one->element_id() != $element_id)
			{
				continue;
			}

			$name = $one->postname();

			return $name;
		}

		return false;
	}

	public function setting( $key, $default='')
	{
		return ncore_retrieve( $this->settings, $key, $default );
	}

    public function pushHiddenInput( $html )
    {
        $this->hidden_input_html .= $html;
    }

    public function popHiddenInput()
    {
        $html = $this->hidden_input_html;
        $this->hidden_input_html = '';
        return $html;
    }

	private $api;
	private $sections = array();
	private $settings = array();

	private $inputs = array();
	private $buttons = array();
	private $required_input_count = 0;
    private $optional_input_count = 0;
	private $xss_prevention_rendered = false;
    private $hidden_input_html = '';

    /**
     * @param ncore_Formrenderer_InputBase $input
     * @return bool
     */
	private function _hasRequiredRule( $input )
	{
		$rules = explode( '|', $input->rules() );

		$has_required_rule = in_array( 'required', $rules );

		return $has_required_rule;
	}

	private function renderFormBegin()
	{
		$this->api->load->helper( 'xss_prevention' );
        $this->xss_prevention_rendered = false;
	}

	private function renderFormEnd()
	{
		echo $this->popHiddenInput();
	}

	private function renderSectionBegin( $section )
	{
		$section_data = ncore_retrieve( $this->sections, $section );
		$headline     = ncore_retrieve( $section_data,   'headline' );
		$instructions = ncore_retrieve( $section_data,   'instructions', array() );
		$collapsed    = ncore_retrieve($section_data,    'collapsed',    null    );

		if (!$instructions)
		{
			$instructions = array();
		}
		elseif (!is_array($instructions))
		{
			$instructions = explode( '|', $instructions );
		}

        $this->renderer()->renderHead( $section, $headline, $instructions, $collapsed );

		$this->renderer()->renderSectionBegin();

		if (!$this->xss_prevention_rendered)
		{
			$this->xss_prevention_rendered = true;

			$this->renderer()->renderHiddenHtml( ncore_XssPasswordHiddenInput() );
		}

	}

	private function renderSectionEnd()
	{
		$this->renderer()->renderSectionEnd();

        $this->renderer()->renderFoot();
	}

	private $renderers = array();

	public function renderer()
	{
		$layout = $this->layout();

		$instance =& $this->renderers[ $layout ];
		if (!isset($instance))
		{
			require_once "layout/base.php";
            /** @noinspection PhpIncludeInspection */
            require_once "layout/$layout.php";
			$class = "ncoreFormLayout_$layout";
			/** @var ncoreFormLayout_base $instance */
			$instance = new $class( $this->settings, $this->api, $layout, 'layout' );
		}

		return $instance;
	}

}