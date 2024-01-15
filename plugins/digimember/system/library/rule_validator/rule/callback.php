<?php

class ncore_RuleValidator_RuleCallback extends ncore_RuleValidator_RuleBase
{
    public function validate( $string, $arg1='', $arg2='', $arg3='' )
    {
        // dbiz_api.data/site->ruleNameIsValid
        
        try
        {
            
            $part = '([A-Za-z0-9_\/]*)';
            if (preg_match( "/^$part\.$part\-\>$part\$/", $arg1, $matches ))
            {
                $api_function = $matches[1];
                $model_path   = $matches[2];
                $method       = $matches[3];
                
                $api = $api_function();
                $model = $api->load->model( $model_path );
                return $model->$method( $string, $arg2, $arg3 );  
            }
            elseif (preg_match( "/^$part\-\->$part\$/", $arg1, $matches )) 
            {
                $model_path   = $matches[2];
                $method       = $matches[3];
                
                $api = ncore_api();
                $model = $api->load->model( $model_path );
                return $model->$method( $string, $arg2, $arg3 );  
            }
            else
            {
                $function = $arg1;
                return $function( $string, $arg2, $arg3 );
            }
        }
        catch (Exception $e)
        {
            $this->error_msg = $e->getMessage();
            return false;    
        }
    }
    
    public function errorMessageTemplate()
    {
        return $this->error_msg;
    }    
    
    private $error_msg='ERROR - VALIDATOR FUNCTION SHOULD THROW AN EXCEPTION IN CASE OF VALIDATIN ERROR!';
}