<?php

$load->controllerBaseClass( 'ajax/form' );

abstract class ncore_AjaxEditController extends ncore_AjaxFormController
{


    //
    // protected section
    //
    protected abstract function modelPath();

    protected function model()
    {
        if (!isset($this->model))
        {
            $model_path = $this->modelPath();
            $this->model = $this->api->load->model( $model_path );
        }

        return $this->model;
    }

    protected function getData( $id )
    {
        $model = $this->model();

        $site = $model->get( $id );

        return $site && $model->mayRead( $site )
               ? $site
               : $model->emptyObject();
    }

    protected function setData( $id, $data )
    {
        try
        {
            $model = $this->model();

            if ($id)
            {
                $model->update( $id, $data );
            }
            else
            {
                $model->create( $data );
            }

            return true;
        }
        catch ( Exception $e )
        {
            $this->formError( $e->getMessage() );
            return false;
        }
    }


}

