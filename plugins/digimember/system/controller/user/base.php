<?php

abstract class ncore_UserBaseController extends ncore_Controller
{
    public function init( $settings=array() )
    {
        $allowed_settings = $this->allowSettings();

        if ($allowed_settings !== 'all')
        {
            $filtered_settings = array();
            foreach ($allowed_settings as $key)
            {
                if (isset( $settings[$key] ) )
                {
                    $filtered_settings[$key] = $settings[$key];
                }
            }

            $settings = $filtered_settings;
        }

        parent::init( $settings );

        $this->initCssFiles();
    }


    protected function allowSettings()
    {
        return 'all';

        // return array( 'css', 'size' );
    }

    private function initCssFiles()
    {
        $css = $this->setting( 'css' );
        if (!$css) {
            return;
        }

        if (!is_array($css)) $css = array( $css );

        /** @var ncore_HtmlLogic $html */
        $html = $this->api->load->model( 'logic/html' );

        foreach ($css as $one)
        {
            $html->includeCss( $one );
        }
    }

    protected function view()
    {
        $this->loadView();
    }

    protected function formSettings() {
        return [];
    }

}
