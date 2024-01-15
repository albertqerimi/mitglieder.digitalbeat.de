<?php

class ncore_HtmlLogic extends ncore_BaseLogic
{
	public function __construct($api, $file, $dir)
	{
		parent::__construct($api, $file, $dir);
		$this->init();

		self::$instances[] = $this;
	}

	public function cbOnHeadersDone()
	{
		self::$header_rendered = true;
	}

	public function init()
	{
        static $is_initialized;

        if (empty($is_initialized)) {
            $is_initialized = false;

            $this->api->load->helper( 'url' );
        }

		add_action('wp_head',   array( $this, 'cbLoadScripts' ),  998931);
		add_action('admin_head', array( $this, 'cbLoadScripts' ), 998931);


		if (!$is_initialized)
		{
			add_action( 'wp_head',    array( $this, 'cbOnHeadersDone' ),  998932);
			add_action( 'admin_head', array( $this, 'cbOnHeadersDone' ),  998932);
        }

        $this->_includeDefaultFiles();

        add_action( 'wp_enqueue_scripts',    array( $this, 'wp_enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

        $is_initialized = true;
	}

    /**
     * @param string $package
     *
     * @return string|false
     */
	public function getPackageUrl($package)
    {
        $path = $this->api->rootDir() . '/webinc/packages';
        if (!file_exists($path) || !file_exists($path . '/manifest.json')) {
            return false;
        }
        $manifest = json_decode(file_get_contents($path . '/manifest.json'), true);
        if (!is_array($manifest)) {
            return false;
        }

        $package = ncore_washText($package);

        $includePath = ncore_retrieve($manifest, $package);
        if ($includePath && file_exists($path . '/' . $includePath)) {
            return plugins_url('digimember/webinc/packages/' . $includePath);
        }
        return false;
    }

    /**
     * @param string $package
	 * @param array $deps
     */
	public function loadPackage($package, $deps = [])
    {
        $path = $this->api->rootDir() . '/webinc/packages';
        if (!file_exists($path) || !file_exists($path . '/manifest.json')) {
            return;
        }
        $manifest = json_decode(file_get_contents($path . '/manifest.json'), true);
        if (!is_array($manifest)) {
            return;
        }

        $package = ncore_washText($package);
        $is_loaded = in_array($package, $this->loaded_packages);
        if ($is_loaded) {
            return;
        }
        $this->loaded_packages[] = $package;

        $includePath = ncore_retrieve($manifest, $package);
        if ($includePath && file_exists($path . '/' . $includePath)) {
            $filePath = '../packages/' . $includePath;
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            if ($extension == 'js') {
                foreach (array_keys($manifest) as $key) {
                    if (strpos($key, 'vendor') !== false && strpos($key, str_replace('.js', '', $package)) !== false) {
                        $this->includeJs('../packages/' . ncore_retrieve($manifest, $key), [], $deps);
                    }
                }
                $this->includeJs($filePath, [], $deps);
            } else if ($extension == 'css') {
                $this->includeCss($filePath);
            }
        }
    }

	public function includeCss( $file, $css_root=false )
	{
		if (self::$header_rendered)
		{
            $url = $this->resolveCssUrl( $file, $css_root );
			echo "<style type='text/css'> @import url('$url'); </style>";
		}
		else
		{
			$this->css_styles[] = array( $css_root, $file ) ;
		}
	}

    public function wp_enqueue_scripts()
    {
        $plugin_name = $this->api->pluginName();
        $ver         = $this->fileVersion();

        foreach ($this->css_styles as $index => $root_file)
        {
            list( $root, $file ) = $root_file;
            $url = $this->resolveCssUrl( $file, $root );

            wp_enqueue_style( "${plugin_name}_css_$index", $url, array(), $ver );
        }

        foreach ($this->css_handles as $one)
        {
        	wp_enqueue_style( $one );
        }

        foreach ($this->js_handles as $one)
        {
            if ($one === 'jquery') {
                wp_enqueue_script( $one, '', array(), false, true );
            }
            else {
                wp_enqueue_script( $one );
            }
        }

        foreach ($this->js_scripts as $index => $file_args)
        {
            list( $file, $args, $deps ) = $file_args;

            $url = $this->_jsUrl( $file, $args );
            wp_enqueue_script( "${plugin_name}_js_$index", $url, $deps=array(), $ver, $in_footer=true );
        }
        $this->js_scripts = array();

    }


    public function includeJsHandle( $handle )
    {
        $this->js_handles[] = $handle;
    }

    public function includeCssHandle( $handle )
    {
    	$this->css_handles[] = $handle;
    }

    private $add_js_files = array();
	public function includeJs( $file, $args=array(), $deps=array() )
	{
        $is_added = in_array( $file, $this->add_js_files );
        if ($is_added) {
            return;
        }

        $this->add_js_files[] = $file;

        $cannot_queue = did_action( 'wp_enqueue_scripts')
                     || did_action( 'admin_enqueue_scripts')
                     || did_action( 'login_enqueue_scripts');

        if ($cannot_queue)
        {
            $plugin_name = $this->api->pluginName();
            $index       = count($this->add_js_files );

            $url = $this->_jsUrl( $file, $args );
            $ver = $this->fileVersion();
            wp_enqueue_script( "${plugin_name}_js_no_queue_$index", $url, $deps, $ver, $in_footer=true );
        }
        elseif (self::$header_rendered)
		{
			$this->renderLoadJsScripts( $file, $args );
		}
		else
		{
			$this->js_scripts[] = array( $file, $args, $deps );
		}

        $this->mayAddFooterActions();
	}

    public function cssAdd( $css )
    {
        if (!$css) {
            return;
        }

        $html = "<style>
$css
</style>";

        $this->hiddenHtml( $html );
    }

	public function jsOnLoad($js_code)
	{
		$js_code = ncore_minifyJs( $js_code );

		if (!$js_code)
		{
			return;
		}

		if (!in_array($js_code, $this->js_on_loads))
		{
			$this->js_on_loads[] = $js_code;
		}

        $this->mayAddFooterActions();
	}

	public function getAjaxResponseHtml()
	{
		$html = '';
		foreach (self::$instances as $one)
		{
			$html .= $one->_getAjaxResponseHtml();
		}
		return $html;
	}

	public function getAjaxResponseJs()
	{
		$js = '';
		foreach (self::$instances as $one)
		{
			$js .= $one->_getAjaxResponseJs();
		}
		return $js;
	}

	public function jsChange( $jquery_selector, $js_onchange )
	{
		$js_onchange = trim( $js_onchange, "; \r\n" );
		$js_onchange .= '; return false';

		$js_onload = "ncoreJQ(\"$jquery_selector\").off('change').on( 'change', function() { $js_onchange } );";

		$this->jsOnLoad( $js_onload );
	}

	public function jsFunction( $function_jscode_or_array )
	{
		$this->mayAddFooterActions();

		if (is_array($function_jscode_or_array))
		{
			$this->js_functions = array_merge( $this->js_functions, $function_jscode_or_array );
		}
		else
		{
			$this->js_functions[] = $function_jscode_or_array;
		}
	}

	public function hiddenHtml($hidden_html)
	{
		if (!$hidden_html)
		{
			return;
		}

		$this->mayAddFooterActions();

		if (!in_array( $hidden_html, $this->hidden_html))
		{
			$this->hidden_html[] = $hidden_html;
		}
	}

	public function cbLoadScripts()
    {
        static $must_include_default_js_code;

        if (!isset($must_include_default_js_code)) $must_include_default_js_code = true;

        if ($must_include_default_js_code) {
            $must_include_default_js_code = false;

            echo "
<script type='text/javascript'>
    if (typeof jQuery == 'undefined') {
        window.onload = function () {
            if (typeof jQuery == 'undefined') {
                console.log( 'DIGIMEMBER needs JQUERY, but could not detect it.' );
            }
            else {
                ncoreJQ = jQuery;
            }
        };
    }
    else {
        ncoreJQ = jQuery;
    }
</script>
";
        }
    }

	static private $instances = array();
	static private $default_html_rendered = false;

	private $loaded_packages = array();

	public function cbRenderFooterCode()
	{
		if (!self::$default_html_rendered)
		{
			echo $this->defaultHiddenHtml(), "\n";
		}

        foreach ($this->js_scripts as $index => $rec)
        {
            list( $file, $args, $dependencies ) = $rec;

            $url = $this->_jsUrl( $file, $args );
            echo "<script src=\"$url\"></script>\n";
        }

        echo "<div style='display: none;'>";

		foreach ($this->hidden_html as $one)
		{
			echo "\n$one\n";
		}
        echo "</div>";

		$jsscript = "

";
		foreach ($this->js_functions as $js)
		{
			$jsscript .= $js;
		}


		if ($this->js_on_loads)
		{
			$jsscript .= "

	ncoreJQ(document).ready(function() {";

		foreach ($this->js_on_loads as $js)
		{
			$jsscript .= "
		$js
";
		}

		$jsscript .= "
	} )
";
		}



		$jsscript = ncore_minifyJs( $jsscript );

		if ($jsscript)
		{
			echo "<div class=\"ncore_hidden\"><script type=\"text/javascript\">$jsscript</script></div>";
		}

		self::$default_html_rendered = true;

        $this->js_scripts   = array();
		$this->js_functions = array();
		$this->js_on_loads  = array();
		$this->hidden_html  = array();
	}

	protected function defaultHiddenHtml()
	{
		return "
<div id='ncore_ajax_wait'><div id='ncore_ajax_wait_icon'><div id='ncore_ajax_wait_icon_inner'></div></div><div id='ncore_ajax_wait_curtain'></div></div>
<div id='ncore_ajax_dialog' class='ncore_hidden'></div>
";
	}

	//
	// private
	//
	private $footer_actions_added = false;
	private $css_styles = array();
	private $js_scripts = array();
    private $js_handles = array();
    private $css_handles = array();
	private $js_on_loads = array();
	private $js_functions = array();
	private $hidden_html = array();

	static private $header_rendered = false;

	private function includeFiles($files, $method)
	{

	}

	private function mayAddFooterActions()
	{
        $footer_rendered = did_action( 'wp_footer')
                        || did_action( 'get_footer')
                        || did_action( 'admin_print_footer_scripts');

        if ($footer_rendered)
        {
            $this->cbRenderFooterCode();
            return;
        }

		if (!$this->footer_actions_added)
		{
			$this->footer_actions_added = true;

			$callback = array( $this, 'cbRenderFooterCode' );

        	add_action('wp_footer',                  $callback );
			add_action('get_footer',                 $callback );
			add_action('admin_footer',               $callback );
            add_action('admin_print_footer_scripts', $callback, 999 );
    	}
	}

	private function _translation( $file )
	{
		$config = $this->api->load->config('html_include');
		$all = $config->get( 'translation' );

		$translation = ncore_retrieve( $all, $file, array() );

		return $translation;
	}


	private function _explodeJsFunction( $js )
	{
		$js = trim( $js );

		$pos_args = strpos( $js, '(' );
		$pos_body = strpos( $js, '{', $pos_args );


		$start_of_name=strlen( 'function ' );
		$name = substr( $js, $start_of_name, $pos_args-$start_of_name );
		$args = substr( $js, $pos_args, $pos_body-$pos_args );
		$body = substr( $js, $pos_body );

		$name = trim( $name );
		$args = trim( $args );
		$body = trim( $body );

		return array( $name, $args, $body );
	}

	private function _jsUrl( $file, $extra_args=array() )
	{
        if (ncore_isAbsoluteUrl( $file )) {
            return $file;
        }

		$translation = $this->_translation( $file );
		if ($translation || $extra_args)
		{
			$args = $translation + $extra_args;
			$this->localizeJs( $file, $args );
		}

		$version = $this->api->pluginVersion();

		$root_url = $this->api->pluginUrl();

		$have_ext = ncore_stringEndsWith( $file, '.js' );
		if (!$have_ext)
		{
			$file .= ".js";
		}

		return $root_url."webinc/js/$file?ver=$version";
    }

    private function renderLoadJsScripts( $file, $extra_args=array() )
    {
        $source = $this->_jsUrl( $file, $extra_args );

		echo "<script type=\"text/javascript\" src=\"$source\"></script>\n";
	}

	private function resolveCssUrl( $file, $css_root=false )
	{
		$version = $this->api->pluginVersion();

		$root_url = $this->api->pluginUrl();

		$have_ext = ncore_stringEndsWith( $file, '.css' );
		if (!$have_ext)
		{
			$file .= ".css";
		}

		if (!$css_root)
		{
			$css_root = "webinc/css";
		}

		$src = "$root_url$css_root/$file?ver=$version";

        return $src;
	}

	private function localizeJs( $file, $args )
	{
		$function = "__ncore_$file";
		echo "<script type=\"text/javascript\">
function $function( key )
{
	switch (key)
	{
";

		foreach ($args as $key => $value)
		{
			$value = str_replace( '"', '\\"', $value );
			echo "         case \"$key\": return \"$value\";\n";
		}

		echo "
	}
}
</script>\n";
	}

    private function _getAjaxResponseHtml()
	{
		$html = implode( "", $this->hidden_html );
		$this->hidden_html = array();

		return "<div style='display:none;'>$html</div>";
	}

	private function _getAjaxResponseJs()
	{
		$js_ajax = "";

		foreach ($this->js_functions as $js)
		{
			list( $name, $args, $body ) = $this->_explodeJsFunction( $js );
			$js_ajax .= "window['$name'] = function$args\n{\n$body\n}\n\n";
		}

		$onload = trim( implode( ";", $this->js_on_loads ), ';' );
		if ($onload)
		{
			$js_ajax .= "$onload;";
		}


		$this->js_functions = array();
		$this->js_on_loads = array();

		return $js_ajax;

	}

    private function _includeDefaultFiles()
    {
        $config = $this->api->load->config('html_include');
        $config_item = ncore_isAdminArea() ? 'admin_css' : 'user_css';

        $files = $config->get($config_item, array());
        foreach ($files as $one)
        {
            $this->includeCss( $one );
        }

        $config_item = ncore_isAdminArea() ? 'admin_js' : 'user_js';

        $files = $config->get($config_item, array());
        foreach ($files as $one)
        {
            $this->includeJs( $one );
        }

        $config_item = ncore_isAdminArea() ? 'admin_js_handles' : 'user_js_handles';

        $files = $config->get($config_item, array());
        foreach ($files as $one)
        {
            $this->includeJsHandle( $one );
        }

        $config_item = ncore_isAdminArea() ? 'admin_css_handles' : 'user_css_handles';

        $files = $config->get($config_item, array());
        foreach ($files as $one)
        {
            $this->includeCssHandle( $one );
        }

        $config_item = ncore_isAdminArea() ? 'admin_packages' : 'user_packages';

        $files = $config->get($config_item, array());
        foreach ($files as $one)
        {
            $this->loadPackage( $one );
        }
    }


    private function fileVersion()
    {
        return $this->api->pluginVersion();
    }


}
