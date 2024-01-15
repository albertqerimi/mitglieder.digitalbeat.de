<?php

abstract class ncore_ShortCodeController extends ncore_Controller
{
    const prefix = 'ds';

    public function availableWidgets()
    {
        $widgets = array();

        return $widgets;
    }

    public function prepareWidgetInputMetas( $shortcode, &$metas )
    {
    }

    public static function widgetLabel( $shortcode )
    {
        $map = array(
            'login' => _ncore( 'Login' ),
        );

        if (!empty( $map[ $shortcode] ))
        {
            return $map[ $shortcode];
        }

        return ncore_camelCase( $shortcode );
    }

    public function init( $settings=array() )
	{
        if ($this->class_initialized) {
            return;
        }

        $this->class_initialized = true;

        if (empty(self::$shortcodes_initialized))
        {
            self::$shortcodes_initialized=true;
            add_filter("widget_text", "do_shortcode");
        }

		parent::init( $settings );

        $this->_setupShortcodes();

        add_action( 'widgets_init', array( $this, 'setupWidgets' ) );
    }

	public function filterContent( $contents )
	{
		$prefix = $this->shortcode( '' );

		$have_shortcode = strpos( $contents, '['.$prefix ) !== false;
		if (!$have_shortcode)
		{
			return $contents;
		}

		$regex = "/(<p> *)*(\[${prefix}.*?\])( *<\/p>| *<br \/>)*/";

		$contents = preg_replace( $regex, '$2', $contents );

		return $contents;
	}

	public function renderShortcode( $shortcode, $attributes=array() )
	{
		$callbacks = $this->shortcodeCallbacks();

		foreach ($callbacks as $code => $callback)
		{
			$matches = $code == $shortcode;
			if ($matches)
			{
				return $this->$callback( $attributes );
			}
		}

		return '';
	}

	public function shortcode( $code )
	{
		$shortcode = self::prefix.'_'.$code;

		return $shortcode;
	}

	public function renderTag( $tag )
	{
        $prefix = self::prefix . '_';

        $must_add_prefix = !ncore_stringStartsWith( $tag, $prefix );
        if ($must_add_prefix) {
            $tag =  $prefix.$tag;
        }

		return "<span class='digimember_code'>[$tag]</span>";
	}

    public function getShortcodeMetas( $shortcode = 'all' )
	{
		$metas =& $this->expandedMetas;

		if ($metas === false)
		{
			$metas     = array();
            $sections  = array();
            foreach ($this->getShortcodeControllers() as $one)
            {
                $metas    = array_merge( $metas, $one->shortcodeMetas() );
                $sections = array_merge( $sections,  $one->shortcodeSections() );
            }

            $sort_array = array();

			foreach ($metas as $index => $meta)
			{
				$tag = $this->shortCode( $meta['code'] );

				$metas[$index]['tag'] = $tag;
				$metas[$index]['rendered'] = '['.$tag.']';

				$this->postProcessShortcodeArgs( $metas[$index]['args'] );

                $section_name = ncore_retrieve( $meta, 'section' );
                $section_data = ncore_retrieve( $sections, $section_name );

                $metas[$index]['section'] = ncore_retrieve( $section_data, 'label' );

                $section_sort  = ncore_retrieve( $section_data, 'sort', 0 );

                $element_sort = ncore_retrieve( $meta, 'sort', 0 );

                $sort = 1000 * $section_sort + $element_sort;

                $sort_array[] = $sort;
			}

            array_multisort( $sort_array, SORT_NUMERIC, $metas );
		}

		if ($shortcode === 'all')
		{
			return $metas;
		}

		$this->api->load->helper( 'array' );
		return ncore_findByKey( $metas, 'code', $shortcode, array() );
	}

    abstract protected function shortcodeSections();

	abstract protected function shortcodeMetas();

    protected function shortcodeCallbacks()
    {
        $callbacks = array();
        foreach ($this->shortcodeMetas() as $meta)
        {
            $is_valid = !empty( $meta ) && !empty( $meta['code'] ) && !empty($meta['callback'] );
            if (!$is_valid) {
                continue;
            }
            $callbacks[ $meta['code'] ] = $meta['callback'];
        }
        return $callbacks;
    }

    protected function removeShortcodeComment( $contents )
    {
        while (true)
        {
            $pos1 = strpos( $contents, '[*' );
            if ($pos1 === false)
            {
                return $contents;
            }

            $pos2 = strpos( $contents, '*]', $pos1 );
            if ($pos2 === false)
            {
                return $contents;
            }

            $head = substr( $contents, 0, $pos1 );
            $tail = substr( $contents, $pos2+2 );

            $have_linebreak = substr( $head, -1 ) == "\n";
            if (!$have_linebreak)
            {
                // empty
            }
            elseif (ncore_stringStartsWith( $tail, "</p>\n<p>" ) )
            {
                $tail = substr( $tail, 8 );
            }
            elseif (ncore_stringStartsWith( $tail, "<br />\n" ) )
            {
                $tail = substr( $tail, 7 );
            }

            $contents = $head.$tail;
        }
    }

	private function postProcessShortcodeArgs( &$arg_metas )
	{
		if (!is_array($arg_metas))
		{
			$arg_metas = array();
			return;
		}

		foreach ($arg_metas as $index => $dummy)
		{
			$arg =& $arg_metas[$index];

			$is_required = strpos( ncore_retrieve( $arg, 'rules' ), 'required' ) !== false;
			$is_select = $arg['type'] == 'select';

			$may_be_replaced_by_checkbox = ncore_retrieve( $arg, 'may_be_replaced_by_checkbox' );

			if ($may_be_replaced_by_checkbox
				 && count( $arg['options'] ) != 1 )
			{
				$may_be_replaced_by_checkbox = false;
			}

			if ($may_be_replaced_by_checkbox)
			{
				$arg['type'] = 'checkbox';
				foreach ($arg['options'] as $key => $label)
				{
					$arg['label'] = $label;
					$arg['name'] = $key;
				}
			}
			elseif ($is_select && !$is_required)
			{
				$arg['allow_null'] = true;
			}

			if  ($arg['type'] == 'page' && !$is_required)
			{
				$arg['allow_null'] = true;
			}
		}
	}

	protected function viewName()
	{
		trigger_error( 'View not implemented for this controller.');
	}


	protected function filters()
	{
		return array(
			'filterContent' => 'the_content',
		);

	}
	protected function actions()
	{
		return array();
	}

	protected function shortcodeError( $message_or_messages )
	{
		$messages = is_array( $message_or_messages )
				  ? $message_or_messages
				  : array( $message_or_messages );

		$html = "";

		foreach ($messages as $one)
		{
			$html .= "<div class='error ncore_user ncore_error'>$one</div>\n";
		}

		return $html;
	}

    protected function shortcodeErrorMissingArg( $arg )
    {
        $msg = _ncore( 'Parameter %s is required.', $arg );
        return $this->shortcodeError( $msg );
    }

	protected function santizeAttributes( $attributes )
	{
		$result = array();

		$n = 0;
		if (is_array($attributes))
		{
			foreach ($attributes as $key => $value)
			{
				$is_flag = is_numeric( $key ) && $key == $n;
				if ($is_flag)
				{
					$result[ $value ] = true;
					$n++;
				}
				else
				{
					$result[ $key] = $value;
				}
			}
		}
		return $result;
	}

    protected function isBoolAttributeSet( $attr, $key )
    {
        $is_set  = (isset( $attr[ $key ] ) && ncore_isTrue( $attr[ $key ]))
                       || (is_array($attr) && in_array( $key, $attr ))
                       || $attr === $key;
        return $is_set;
    }

    private $expandedMetas = false;
    private $class_initialized = false;
    private static $shortcodes_initialized;

    private function getShortcodeControllers()
    {
        global $ncore_shortcode_controller_instances;

        if (empty($ncore_shortcode_controller_instances)) {
            $ncore_shortcode_controller_instances = array();
        }

        return $ncore_shortcode_controller_instances;
    }

    private function registerShortcodeController( $controller_obj )
    {
        global $ncore_shortcode_controller_instances;

        if (empty($ncore_shortcode_controller_instances)) {
            $ncore_shortcode_controller_instances = array();
        }

        $ncore_shortcode_controller_instances[] = $controller_obj;
    }

    private function _setupShortcodes()
    {
        $callbacks = $this->shortcodeCallbacks();

//        $have_legacy_shortcodes = method_exists( $this->api->blog_config_logic, 'haveLegacyShortCodes' )
//                                  && $this->api->blog_config_logic->haveLegacyShortCodes()
//                                  && strtolower( $this->api->pluginName() ) == 'digimember';

        foreach ($callbacks as $code => $method)
        {
            $callback = is_array( $method )
                        ? $method
                        : array( $this, $method );

            $shortcode = $this->shortcode( $code );
            add_shortcode( $shortcode, $callback );

//            if ($have_legacy_shortcodes)
//            {
//                $legacy_shortcode = 'digimember_'.$code;
//                add_shortcode( $legacy_shortcode, $callback );
//            }
        }

        $filters = $this->filters();
        foreach ($filters as $method => $filter)
        {
            $callback = array( $this, $method );

            add_filter( $filter, $callback );
        }

        $actions = $this->actions();
        foreach ($actions as $method)
        {
            $callback = array( $this, $method );

            add_action( "get_header", $callback );
        }

        $this->registerShortcodeController( $this );
    }

    public function setupWidgets()
    {
        /** @var ncore_WidgetClass $class */
        $class = $this->api->load->miscClass( 'widget' );

        $class::setShortCodeController( $this );

        $widgets = $this->availableWidgets();

        foreach ($widgets as $shortcode)
        {
            $class = $this->api->load->miscClass( "widget_$shortcode" );

            register_widget( $class );
        }
    }

}