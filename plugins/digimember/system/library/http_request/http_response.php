<?php

class ncore_HttpResponse
{
    public function __construct( $contents, $http_code )
    {
        $this->contents = $contents;
        $this->http_code = $http_code;
    }

    public function setError( $errorNo, $errorMessage )
    {
        $this->isError = true;

        $this->error_no = $errorNo;
        $this->error_msg = $errorMessage;
    }

    public function httpCode()
    {
        return $this->http_code;
    }

    public function contents()
    {
        return $this->contents;
    }

    public function isError()
    {
        return $this->isError;
    }

    public function errorMsg()
    {
        return $this->error_msg;
    }

    public function errorNo()
    {
        return $this->error_no;
    }

    private $isError=false;
    private $contents='';
    private $http_code='';
    private $error_no=0;
    private $error_msg = '';
}