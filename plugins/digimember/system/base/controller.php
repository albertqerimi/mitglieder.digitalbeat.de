<?php

abstract class ncore_Controller extends ncore_Class
{
	public function init( $settings=array() )
	{
		if (!is_array($settings))
		{
			$settings = $settings
                      ? array( $settings )
                      : array();
		}

		foreach ($this->defaultSettings() as $key => $value)
		{
			if (!isset( $settings[$key]))
			{
				$settings[$key] = $value;
			}
		}

		$this->settings = $settings;
	}

	protected function defaultSettings()
	{
		return array();
	}

	public function dispatch()
	{
		if ($this->readAccessGranted())
		{
			if ($this->writeAccessGranted())
			{
				$this->handleRequest();
			}

			$this->view();
		}
	}

    public function renderAjaxJs( $event, $params=array(), $existing_data_object_name='' )
    {
        $this->api->load->helper( 'xss_prevention' );

        global $ncore_js_ajax_url_code_rendered;
        if (empty($ncore_js_ajax_url_code_rendered)) {
             $ajax_url = admin_url( 'admin-ajax.php' );
             $js = "ncore_ajax_url='$ajax_url';";
             $model = $this->api->load->model('logic/html');
             $model->jsOnLoad($js);
        }

        $event = ncore_washText( $event );

        $must_wait = empty( $params[ 'no_wait' ] );

        $params['action']             = 'ncore_ajax_action';
        $params['ncore_plugin']       = $this->api->pluginName();
        $params['ncore_controller']   = $this->baseName();
        $params['ncore_event']        = $event;
        $params['ncore_xss_password'] = ncore_XssPassword();

        foreach ($this->settings as $key => $value)
        {
            $do_skip = is_array($value) || is_object($value);
            if ($do_skip) {
                continue;
            }

            $params["ncore_ctr_settings_$key"] = $value;
        }

        if ($existing_data_object_name)
        {
            $js = '';
            $data = $existing_data_object_name;
            foreach ($params as $key => $value)
            {
                $js .= "$data.$key='$value';\n";
            }
        }
        else
        {
            $js = "var data = {
";

            $is_first = true;
            foreach ($params as $key => $value)
            {
                if ($is_first) $is_first=false; else $js .= ",
";
                $js .= "'$key': '$value'";
            }

        $js .= "
};
";
        }

        if ($must_wait)
        {
            $js .=  'dmDialogAjax_Start();
';
        }

        $js .= "
ncoreJQ.post(ncore_ajax_url, data, dmDialogAjax_Callback )
.fail(function(result){
    var callbackResult = {
        error: 'Action failed.',
        success: '',
        html: '',
        target_div_id: '',
        js: '',
        redirect: false,
        must_reload: false,
    };
    if (result.status == 403 && result.responseText != '') {
           callbackResult.error = result.responseText;
    }
    dmDialogAjax_Callback(JSON.stringify(callbackResult));
});";

        return ncore_minifyJs($js);
    }

	public function dispatchAjax( $event, $args=array() )
	{
		if ($this->readAccessGranted())
		{
            $this->ajax_args = $args;

			ob_start();
			$response = new ncore_AjaxResponse( $this->api );
			$this->handleAjaxEvent( $event, $response );
			$output = ob_get_clean();

			if ($output)
			{
				$response->error( "Internal error - had html output: $output" );
			}

			return $response;
		}
		else
		{
			$response = new ncore_AjaxResponse( $this->api );
			$response->error( _ncore( 'Permission denied.' ) );
			return $response;
		}
	}

	public function render()
	{

		ob_start();
		$this->dispatch();
		return ob_get_clean();
	}

	public function mustVerifyXssPassword( $event )
	{
		$secure_ajax_events = $this->secureAjaxEvents();

		$must_validate = !in_array( $event, $secure_ajax_events );

		return $must_validate;
	}

	//
	// protected
	//
	protected function readAccessGranted()
	{
		return true;
	}

	protected function writeAccessGranted()
	{
		return true;
	}

	protected function handleRequest()
	{
	}

	protected function handleAjaxEvent( $event, $response )
	{
		$handlers = $this->ajaxEventHandlers();
		$handler = ncore_retrieve( $handlers, $event );
		if ($handler)
		{
			$this->$handler( $response );
		}
	}

	protected function ajaxEventHandlers()
	{
		return array();
	}

	protected function secureAjaxEvents()
	{
		return array( 'subscribe' );
	}


	protected function view()
	{
		$this->loadView();
	}

	protected function loadView()
	{
		$view = $this->viewName();

		$data = $this->viewData();

		extract( $data );

		$app_dir = $this->api->appDir();
		$path = "$app_dir/view/$view.php";

		if (file_exists( $path ))
		{
			require $path;
			return;
		}

		$sys_dir = $this->api->sysDir();
		$path = "$sys_dir/view/$view.php";

		require $path;
	}

	protected function viewName()
	{
		return $this->baseName();
	}

	protected function viewData()
	{
		return array();
	}

	protected function setting( $key, $default='' )
	{
		if ($key === 'all')
		{
			return $this->settings;
		}

		return ncore_retrieve( $this->settings, $key, $default );
	}

    protected function ajaxArg( $key, $default='' )
    {
        return urldecode( ncore_retrieve( $this->ajax_args, $key, $default ) );
    }

	protected function setSettings( $settings )
	{
		$this->settings = array_merge( $this->settings, $settings );
	}

	//
	// private
	//
	private $settings = array();
    private $ajax_args = array();

}