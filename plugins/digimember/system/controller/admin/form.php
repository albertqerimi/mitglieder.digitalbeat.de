<?php

$load->controllerBaseClass( 'admin/tabbed');

abstract class ncore_AdminFormController extends ncore_AdminTabbedController
{
    //
    // protected section
    //
    abstract protected function editedElementIds();

    protected function submitted( $button_name )
    {
        return $this->form()->isPosted( $button_name );
    }

    protected function handleRequest()
    {
        $form = $this->form();

        $is_posted = $form->isPosted();
        if (!$is_posted)
        {
            return;
        }

        $element_ids = $this->editedElementIds();

        $errors = $this->validateForm( $form );

        if ($errors)
        {
            $this->formError( $errors );

            foreach ($element_ids as $element_id)
            {
                $data = $form->getData($element_id);
                $form->setData( $element_id, $data );
                $this->formPopulated = true;
            }
        }
        else
        {
            $modified = false;

            $prior_element_ids = implode( ',', $element_ids );

            foreach ($element_ids as $element_id)
            {
                $data = $form->getData( $element_id );

                if ($this->setData( $element_id, $data ))
                {
                    $modified = true;
                }
            }

            $post_element_ids = implode( ',', $this->editedElementIds() );

            $element_ids_changed = $post_element_ids != $prior_element_ids;
            if ($element_ids_changed)
            {
                $this->onElementIdChanged();
            }

            if ($modified )
            {
                $message = $this->formSuccessMessage();
                $must_reload = $this->isPageReloadAfterSubmit();
                if ($must_reload)
                {
                    ncore_flashMessage( NCORE_NOTIFY_SUCCESS, $message );

                    $url = ncore_currentUrl();
                    $url = ncore_removeArgs( $url, array( 'id' ), '&', false );
                    $url = ncore_addArgs( $url, array( 'id' => $post_element_ids ), '&', false );

                    ncore_redirect( $url );
                }
                else
                {
                    $this->formSuccess( $message );
                }


            }


        }
    }

    /**
     * @param ncore_FormRendererForm $form
     * @return array
     */
    protected function validateForm( $form )
    {
        $errors = $form->validate();

        return $errors;
    }

    protected function formSuccessMessage( $msg=false )
    {
        if ($msg!==false)
        {
            $this->success_message = $msg;
        }

        return $this->success_message
               ? $this->success_message
               : _ncore('Your changes have been saved.');
    }

    protected function formDisable( $msg )
    {
        if ($msg) {
            $this->addFormMsg( NCORE_NOTIFY_WARNING, $msg );
        }

        $this->is_form_enabled = false;
    }

    protected function formError( $msg )
    {
        if ($msg) {
            $this->addFormMsg( NCORE_NOTIFY_ERROR, $msg );
        }
    }

    protected function formWarning( $msg )
    {
        if ($msg) {
            $this->addFormMsg( NCORE_NOTIFY_WARNING, $msg );
        }
    }

    protected function formSuccess( $msg )
    {
        if ($msg) {
            $this->addFormMsg( NCORE_NOTIFY_SUCCESS, $msg );
        }
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        return true;
    }

    protected function formId()
    {
        return $this->baseId() . '_form';
    }

    protected function ajaxErrorMsgDivId()
    {
        return $this->baseId() . '_form_error_message';
    }

    abstract protected function getData( $element_id );

    abstract protected function setData( $element_id, $data );

    protected function viewName()
    {
        return 'admin/form';
    }

    protected function renderContent()
    {
        $this->formData();

        if ($this->is_form_enabled)
        {
            $this->renderFormMessages();

            $this->loadView();
        }
        else
        {
            $this->_renderFormMessagesDisabled();

        }
    }

    abstract protected function inputMetas();

    protected function getInputMetas()
    {
        $metas = $this->inputMetas();

        $element_id = $this->getElementId();
        $meta = array(
            'type' => 'hidden',
            'name' => 'element_id',
            'element_id' => '',
            'default' => $element_id,
        );

        return array_merge( array( $meta ), $metas );
    }

    protected function saveButtonLabel()
    {
        return _ncore('Save Changes');
    }

    protected function buttonMetas()
    {
        return array(
            array(
                'type' => 'submit',
                'name' => 'save',
                'label' => $this->saveButtonLabel(),
                'primary' => true,
            )
        );
    }

    abstract protected function sectionMetas();

    protected function formSettings() {
        return array();
    }

    protected function pageInstructions()
    {
        return array();
    }

    protected function renderFormMessages()
    {
        echo $this->_renderFormMessages();
    }

    protected function isPageReloadAfterSubmit() {
        return false;
    }

    protected function renderFormInner()
    {
        if (!$this->formPopulated)
        {
            $form_data = $this->formData();
            foreach ($form_data as $element_id => $data)
            {
                $this->form()->setData( $element_id, $data );
            }
        }

        $this->form()->render();
    }

    protected function renderFormButtons()
    {
        $this->form()->renderButtons();
    }



    protected function viewData()
    {
        $data = parent::viewData();

        $data[ 'action' ] = $this->formActionUrl();
        $data[ 'have_required' ] = $this->form()->haveRequiredRule();
        $data[ 'form_id'] = $this->formId();

        return $data;
    }

    protected function formActionUrl()
    {
        $this->api->load->helper( 'url' );
        return ncore_currentUrl();
    }

    protected function haveFormErrors()
    {
        foreach ($this->formMessages as $one)
        {
            if ($one->type == 'error')
            {
                return true;
            }
        }

        return false;
    }

    protected function renderPageFootnotes()
    {
    }

    private function renderFormMessageDisabled( $type, $msg)
    {
        $msg_types = array(
            'error'         => NCORE_NOTIFY_ERROR,
            'updated'       => NCORE_NOTIFY_SUCCESS,
        );

        $msg_type = ncore_retrieve( $msg_types, $type, $type );

        echo ncore_renderMessage( $msg_type, $msg, 'p' );
    }

    private function renderFormMessage( $type, $msg)
    {
        $type_map = array(
            'updated' => 'success',
        );
        $icon_map = [
            'error' => 'attention-circled',
            'success' => 'ok-circled',
        ];

        $type = ncore_retrieve( $type_map, $type, $type );
        $icon = ncore_retrieve( $icon_map, $type, 'attention-circled' );

        $css = 'dm-alert dm-alert-' . $type;

        $base_id  = 'ncore-error-';
        self::$html_id++;
        $id = $base_id . self::$html_id;

        return '<div id="' . $id . '" class="' . $css . '">
    <div class="dm-alert-icon">
        <span class="dm-icon icon-' . $icon . ' dm-color-' . $type . '"></span>        
    </div>
    <div class="dm-alert-content">
        <label>' . $msg . '</label>
    </div>
</div>';
    }

    protected function onElementIdChanged()
    {
        $this->form( $force_reload = true );
    }

    /** @var bool | ncore_FormRendererForm */
    private $form = false;
    private $formPopulated = false;
    private $is_form_enabled = true;
    private $formMessages = array();
    private static $html_id = 0;

    private function _renderFormMessagesDisabled()
    {
        $ajax_div_id = $this->ajaxErrorMsgDivId();

        $html = "<div id='$ajax_div_id'>";

        foreach ($this->formMessages as $one)
        {
            $type = $one->type;
            $msg  = $one->msg;

            $html .= $this->renderFormMessageDisabled( $type, $msg );
        }

        $html .= '</div>';

        return ncore_minifyHtml( $html );
    }

    private function _renderFormMessages()
    {
        $ajax_div_id = $this->ajaxErrorMsgDivId();

        $html = "<div id='$ajax_div_id' class='dm-form-messages'>";

        foreach ($this->formMessages as $one)
        {
            $type = $one->type;
            $msg  = $one->msg;

            $html .= $this->renderFormMessage( $type, $msg );
        }

        $html .= '</div>';

        return ncore_minifyHtml( $html );
    }

    /**
     * @param bool $force_reload
     * @return ncore_FormRendererForm | bool
     */
    protected function form( $force_reload = false )
    {
        if ($this->form !== false && !$force_reload)
        {
            return $this->form;
        }
        /** @var ncore_FormRendererLib $lib */
        $lib = $this->api->load->library('form_renderer');

        $input_metas = $this->getInputMetas();

        $button_metas = $this->buttonMetas();

        $sections = $this->sectionMetas();

        $settings = $this->formSettings();

        $this->form = $lib->createForm(  $sections, $input_metas, $button_metas, $settings );

        return $this->form;
    }

    private function addFormMsg( $type, $message_or_messages )
    {
        if (empty($this->formMessages))
        {
            $this->formMessages = array();
        }

        $messages = is_array( $message_or_messages )
                  ? $message_or_messages
                  : array( $message_or_messages );

        foreach ($messages as $msg)
        {
            foreach ($this->formMessages as $one)
            {
                $have_message = $one->msg == $msg;
                if ($have_message) {
                    continue 2;
                }
            }

            $obj                  = new StdClass();
            $obj->type            = $type;
            $obj->msg             = $msg;

            $this->formMessages[] = $obj;
        }
    }

    protected function getElementId()
    {
        $have_id = !empty($this->element_id)
                && is_numeric($this->element_id);
        if (!$have_id)
        {
            $id = ncore_retrieve( $_GET, 'id', 0 );
            if (is_numeric($id) && $id > 0)
            {
                $this->element_id = $id;
                $have_id = true;
            }
        }
        if (!$have_id)
        {
            $id = ncore_retrieve( $_POST, 'ncore_element_id', 0 );
            if (is_numeric($id) && $id > 0)
            {
                $this->element_id = $id;
            }
        }

        return $this->element_id;
    }

    private $form_data;
    private $success_message = '';
    private function formData( $force_reload = false )
    {
        if ($force_reload || !isset($this->form_data))
        {
            $this->form_data = array();

            $element_ids = $this->editedElementIds();
            foreach ($element_ids as $element_id)
            {
                $this->form_data[ $element_id ] = $this->getData( $element_id );
            }
        }

        return $this->form_data;
    }



}

