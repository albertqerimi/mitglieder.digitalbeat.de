<?php

class ncore_AjaxResponse extends ncore_Class
{
    public function error( $msg )
    {
        $this->error = $msg;
    }

    public function success( $msg )
    {
        $this->success = $msg;
    }

    public function setResponseObject( $object )
    {
        $this->prepared_reponse_object = $object;
    }

    public function html( $target_div_id, $html )
    {
        $this->target_div_id = $target_div_id;
        $this->html .= $html;
    }

    public function js( $js )
    {
        if ($this->js) {
            $this->js .= ';';
        }
        $this->js .= $js;
    }

    public function reload( $enable = true)
    {
        $this->must_reload = $enable;
    }

    public function redirect( $url )
    {
        $this->redirect = $url;
    }

    public function output()
    {
        if ($this->prepared_reponse_object)
        {
            die( json_encode( $this->prepared_reponse_object ) );
        }

        $response = new stdClass();

        $response->error = $this->error;
        $response->success = $this->success;
        $response->html = $this->html;
        $response->target_div_id = $this->target_div_id;
        $response->js = $this->js;
        $response->redirect = $this->redirect;
        $response->must_reload = $this->must_reload;

        $html = $this->api->load->model( 'logic/html' );
        $jsOnLoad = $html->getAjaxResponseJs();
        if ($jsOnLoad)
        {
            $response->js .= ";$jsOnLoad";
        }

        $response->html .= $html->getAjaxResponseHtml();

        die( json_encode( $response ) );
    }


    private $error = '';
    private $success = '';
    private $html = '';
    private $js = '';
    private $redirect = '';
    private $target_div_id = '';
    private $must_reload = false;
    private $prepared_reponse_object = false;

}