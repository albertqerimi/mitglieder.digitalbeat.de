<?php

abstract class ncore_Ajax_DialogBase extends ncore_Plugin
{
	public function __construct( $parent, $type, $meta )
	{
		parent::__construct( $parent, $type, $meta );

		$this->html = $this->api->load->model( 'logic/html' );
		$this->link = $this->api->load->model( 'logic/link' );
	}


    /**
     * @param ncore_AjaxResponse $response
     */
	public function setAjaxResponse( $response )
	{
		$args = array();

		$div_id = $this->divId();

		$innerHtml = $this->innerHtml();

		$title = $this->title();

		$args['dialogClass'] = $this->css();

		$html = "<div id='$div_id' title=\"$title\">$innerHtml</div>";

		$response->html( 'ncore_ajax_dialog', $html );

		$jquery_ui_args = array( 'width', 'height' );

		foreach ($jquery_ui_args as $key)
		{
			$val = $this->meta( $key );
			if ($val)
			{
				$args[$key] = $val;
			}
		}

		$js_init = $this->initDialogJs( $args );
		$js_open = $this->openDialogJs();

		$js = "$js_init;$js_open";

		$response->js( $js );
	}

	public function showDialogJs( $omit_html=false )
	{
        if (!$omit_html)
        {
		    $this->setupHtml();
        }

		$js_init = $this->initDialogJs();
		$this->html->jsOnLoad( $js_init );

		return $this->openDialogJs();
	}

    public function open()
    {
        $this->setupHtml();

        $js_init = $this->initDialogJs();
        $js_open = $this->openDialogJs();
        $this->html->jsOnLoad( $js_init.$js_open );
    }

	abstract protected function buttons();

	abstract protected function innerHtml();

	protected function title()
	{
		return $this->meta( 'title' );
	}

	protected function divId()
	{
		if (!$this->div_id)
		{
			$this->div_id = $this->meta( 'ajax_dlg_id' );

			if (!$this->div_id)
			{
				$this->div_id = ncore_id( 'ajax_dlg' );
			}
		}

		return $this->div_id;
	}

	protected function closeButton( $label='' )
	{
		if (!$label)
		{
			$label = _ncore( 'Close' );
		}

		$label = $this->textEsc( $label );

		return array( "\"$label\"" => "function() { ncoreJQ( this ).dmDialog( 'close' ); }" );
	}

    protected function linkButton( $url, $label, $asPopup=false )
    {
        $label = $this->textEsc( $label );

        $js = $asPopup
            ? "window.open('$url');return false;"
            : "location.href='$url';return false;";

        return array( "\"$label\"" =>  "function() { $js }" );
    }

	protected function okButton( $label='', $event='ok', $confirm='' )
	{
		if (!$label)
		{
			$label = _ncore( 'Close' );
		}

		$label = $this->textEsc( $label );

        $confirm = str_replace( "|", "\\n\\n", $this->textEsc( $confirm  ) );

		$callback_js = $this->renderCallbackCode( $event );

        $js = $confirm
            ? "function() { if (confirm( \"$confirm\" )) { $callback_js } }"
            : "function() { $callback_js }";

        return array( "\"$label\"" =>  $js );
	}

	protected function textEsc( $text )
	{
		return str_replace( "'", "\\'", $text );
	}

	protected function onFormSubmitJs( $cb_form_id )
	{
	}

	private $div_id = false;
	/** @var ncore_HtmlLogic */
	private $html;
	/** @var digimember_LinkLogic */
	private $link;
	private $rendered_dialogs = array();

	private function setupHtml()
	{
		$div_id = $this->divId();

		$is_rendered = $div_id && in_array( $div_id, $this->rendered_dialogs );
		if ($is_rendered)
		{
			return;
		}
		if ($div_id)
		{
			$this->rendered_dialogs[] = $div_id;
		}


		$title = $this->title();

		$innerHtml = $this->innerHtml();

		$css = $this->css();

		$html = "<div id='$div_id' class='$css' title=\"$title\" style='display:none;'>$innerHtml</div>";

		$this->html->hiddenHtml( $html );
	}

	private function initDialogJs( $settings = array() )
	{
		$onCloseJs = 'ncoreJQ(document.body).css(\'overflow\', \'auto\');';
		$onOpenJs = 'ncoreJQ(document.body).css(\'overflow\', \'hidden\');';
		$defaults = array(
			'modal' => $this->meta('modal', true),
			'autoOpen' => false,
			'buttons' => $this->buttons(),
			'zIndex' => 999990,
            'close' => "function() { $onCloseJs }",
            'open'  => "function() { $onOpenJs }",
            'closeText' => _ncore( 'Close' ),
		);

		$defaults['dialogClass'] = 'wp-dialog ncore_jquery_ui_dialog'; // wp-dialog

		$width = $this->meta( 'width' );
		if ($width)
		{
			$defaults['width'] = $width;
		}
		$height = $this->meta( 'height' );
		if ($height)
		{
			$defaults['height'] = $height;
		}

		$div_id = $this->divId();

		$settings = array_merge( $defaults, $settings );

		$settings_js = $this->_jsArray( $settings );

        $varname = $this->_varname();
		return "window['$varname']=ncoreJQ( '#$div_id' ).dmDialog( $settings_js );";
	}

	function openDialogJs()
	{
		$varname = $this->_varname();

        return "if (typeof $varname != 'undefined') $varname.dmDialog( 'open' );";
	}

    private function _varname()
    {
        $div_id  = $this->divId();
        return 'dm_var_'.$div_id;
    }

    /**
     * @param string $event
     * @param ncore_Controller $controller
     * @return string
     */
	protected function renderCallbackJsController( $event, $controller )
	{
        if (!is_object($controller)) {
            $controller = $this->api->load->controller( $controller );
        }

        $js = "ncoreJQ( this ).dmDialog( 'close' );";

        $js .= "var data=ncoreJQ( this ).dmDialog( 'instance' ).find( 'form' ).serializeArray().reduce(function(obj, item) { obj[item.name] = item.value; return obj; }, {});";

        $js .= $controller->renderAjaxJs( $event, $params=array(), $existing_data_object_name='data' );

        return ncore_minifyJs( $js );
	}

	protected function renderCallbackJsCode( $event, $cb_js_code )
	{
		if ($this->meta('close_on_ok')) {
			return "ncoreJQ( this ).dmDialog( 'close' ); var event='$event'; $cb_js_code";
		}
		else {

			return 'var event=\''.$event.'\'; '.$cb_js_code.';';
		}
	}

	protected function renderCallbackJsHandler( $event, $cb_js_handler )
	{
		return "ncoreJQ( this ).dmDialog( 'close' ); $cb_js_handler( '$event' )";
	}

	private function renderCallbackCode( $event='ok' )
	{
		$cb_controller = $this->meta( 'cb_controller' );
		$cb_js_code = $this->meta( 'cb_js_code' );
		$cb_js_handler = $this->meta( 'cb_js_handler' );
		$cb_form_id = $this->meta( 'cb_form_id' );

		if ($cb_controller)
		{
			return $this->renderCallbackJsController( $event, $cb_controller );
		}

		if ($cb_js_code)
		{
			return $this->renderCallbackJsCode( $event, $cb_js_code );
		}

		if ($cb_js_handler)
		{
			return $this->renderCallbackJsHandler( $event, $cb_js_handler );
		}

		if ($cb_form_id)
		{
			$onSubmitJs = $this->onFormSubmitJs( $cb_form_id );
			return "ncoreJQ( this ).dmDialog( 'close' ); $onSubmitJs; ncoreJQ( '#$cb_form_id' ).submit(); ";
		}

		return '';
	}

	private function _jsArray( $settings )
	{
		$this->api->load->helper( 'array' );
		$this->api->load->helper( 'string' );

		$js = "{";

		$is_first = true;
		foreach ($settings as $key => $value)
		{
			if ($is_first)
			{
				$is_first = false;
			}
			else
			{
				$js .= ",\n";
			}

			$js .= "$key: ";

			if ($value === false)
			{
				$js .= 'false';
			}
			elseif ($value === true)
			{
				$js .= 'true';
			}
			elseif (ncore_isNumericArray( $value ) )
			{
				$js .= '"' . implode( '","', $value ) . '"';
			}
			elseif (is_array( $value ) )
			{
				$js .= $this->_jsArray( $value );
			}
			else
			{
				$must_quote = !ncore_stringStartsWith( $value, 'function(' );

				$js .= $must_quote
					 ? '"'.$value.'"'
					 : $value;
			}
		}

		$js .= "\n}";

		return $js;

	}

	private function css()
	{
        $css = 'ncore';

		$css .= ncore_isAdminArea()
			 ? ' ncore_admin'
			 : ' ncore_user';

		$css .= ' wp-dialog ncore_ajax_dialog';  // wp-dialog

		$css .= ' ' . $this->meta( 'dialogClass' );

		return $css;
	}
}