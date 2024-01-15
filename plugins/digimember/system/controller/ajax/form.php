<?php

$load->controllerBaseClass( 'user/base' );

abstract class ncore_AjaxFormController extends ncore_UserBaseController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
        $this->dialog();
    }

    public function renderFormOpenJs( $id )
    {
        $id = ncore_washText( $id );

        $args = array( 'id' => $id );
        $event = $this->formOpenEvent();

        $js = $this->renderAjaxJs( $event, $args );

        return $js;
    }

    //
    // protected section
    //
    protected function formOpenEvent()
    {
        return 'edit';
    }

    protected function formSaveEvent()
    {
        return 'ok';
    }

    protected function formError( $message )
    {
        $this->error_messages[] = $message;
    }

    protected function editedElementId()
    {
        if ($this->id === false)
        {
            $this->id = ncore_retrievePOST( 'id', 0 );
            if (!$this->id || $this->id==='new')
            {
                $this->id = ncore_retrievePOST( 'ncore_id', 0 );
            }
            if (!$this->id || $this->id==='new')
            {
                $this->id = ncore_retrievePOST( 'ncore_element_id', 0 );
            }
            if (!$this->id || $this->id==='new')
            {
                $this->id = ncore_retrieveGET( 'id', 0 );
            }
            if (!$this->id || $this->id==='new')
            {
                $this->id = ncore_retrieveGET( 'ncore_id', 0 );
            }
            if (!$this->id || $this->id==='new')
            {
                $this->id = ncore_retrieveGET( 'ncore_element_id', 0 );
            }
        }
        return $this->id;
    }

    protected function ajaxEventHandlers()
    {
        $handlers = parent::ajaxEventHandlers();

        $event = $this->formOpenEvent();
        $handlers[$event] = 'handleAjaxFormOpenEvent';

        $event = $this->formSaveEvent();
        $handlers[$event] = 'handleAjaxFormSaveEvent';

        return $handlers;
    }

   protected function dialogWidth()
   {
       return 500;
   }

    protected function renderInstructions()
    {
        $instructions = $this->pageInstructions();
        if (!$instructions)
        {
            return '';
        }

        $html = implode( " ", $instructions );
        return $html;
    }



    protected function handleAjaxFormSaveEvent( $response )
    {
        $dialog = $this->dialog();

        $messages = array_merge(
                        $this->error_messages,
                        $dialog->validate()
                    );

        $data = $dialog->getData();

        $this->error_messages = array();

        if ($messages)
        {
            $dialog->setData( $data );
            $dialog->setErrorMessages( $messages );
            $dialog->setAjaxResponse( $response );
            return;
        }

        $id = $this->editedElementId();

        $modified = $this->setData( $id, $data );

        if ($this->error_messages)
        {
            $dialog->setData( $data );
            $dialog->setErrorMessages( $this->error_messages );
            $dialog->setAjaxResponse( $response );

            $this->error_messages = array();
            return;
        }

        if ($modified)
        {
            $response->reload();
        }
    }

    protected function handleAjaxFormOpenEvent( $response )
    {
        $dialog = $this->dialog();

        $data = $this->getData( $this->editedElementId() );
        $dialog->setData( $data );

        $dialog->setAjaxResponse( $response );
    }

    abstract protected function getData( $element_id );

    abstract protected function setData( $element_id, $data );

    protected function viewName()
    {
        trigger_error( 'View not implemented for this controller' );
    }

    abstract protected function inputMetas();

    final protected function getInputMetas()
    {
        $metas = $this->inputMetas();

        $element_id = $this->editedElementId();
        $meta = array(
            'type'       => 'hidden',
            'name'       => 'element_id',
            'element_id' => '',
            'value'      => $element_id,
            'default'    => $element_id, // deprecated for hidden input, use value
        );

        array_unshift( $metas, $meta );

        return $metas;
    }

    abstract protected function sectionMetas();

    protected function dialogMetas()
    {
        return array();
    }

    protected function buttonMetas()
    {
        return array();
    }


    protected function viewData()
    {
        trigger_error( 'View not implemented for this controller' );
    }

    private $dialog = false;
    private $error_messages = array();
    private $id=false;


    private function dialog( $force_reload = false )
    {
        if ($this->dialog !== false && !$force_reload)
        {
            return $this->dialog;
        }

        $meta = $this->dialogMeta();
        $lib = $this->api->load->library( 'ajax' );
        $this->dialog = $lib->dialog( $meta );

        return $this->dialog;
    }

    private function dialogMeta()
    {
        $width         = $this->dialogWidth();
        $cb_controller = $this->baseName();

        $input_metas = $this->getInputMetas();

        $defaults = array(
            'type' => 'form',
            'cb_controller' => $cb_controller,
            'message' => $this->renderInstructions(),
            'title' => $this->pageHeadline(),
            'width' => $width.'px',
            'form_sections' => $this->sectionMetas(),
            'form_inputs'   => $input_metas,
            'buttons'       => $this->buttonMetas(),
        );

        return array_merge( $defaults, $this->dialogMetas() );
    }



}

