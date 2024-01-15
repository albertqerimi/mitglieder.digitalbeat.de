<?php

$load->controllerBaseClass( 'user/base' );

abstract class ncore_UserCustomFieldsController extends ncore_UserBaseController
{
    private static $formCount = 0;
    private $formNumber = 0;

    public function __construct( ncore_ApiCore $api, $file='', $dir='' )
    {
        parent::__construct( $api, $file, $dir );
        $this->formNumber = self::$formCount++;
    }

    public function init( $settings=array() )
    {
        parent::init( $settings );
        $this->form();
    }

    //
    // protected section
    //
    abstract protected function editedElementIds();

    protected function formNumber()
    {
        return  $this->formNumber;
    }

    protected function isPosted()
    {
        $postname = $this->form->postname( 0, 'ncore_is_posted_' . $this->formNumber );

        $a = $this->form()->isPosted();
        $b = !empty( $_POST[ $postname ] );

        return $a && $b;
    }

    protected function handleRequest()
    {
        $form = $this->form();

        $is_posted = $this->isPosted();
        if (!$is_posted)
        {
            return;
        }

        $element_ids = $this->editedElementIds();

        $errors = $form->validate();

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
                $this->formSuccess( $this->formSuccessMessage() );
            }
        }
    }

    protected function formSuccessMessage()
    {
        return _ncore('Your changes have been saved.');
    }

    protected function formDisable( $msg )
    {
        $this->addFormMsg( 'error', $msg );
        $this->formEnabled = false;
    }

    protected function formError( $msg )
    {
        $this->addFormMsg( 'error', $msg );
    }

    protected function formSuccess( $msg )
    {
        $this->addFormMsg( 'updated', $msg );
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
    protected function ajaxErrorMsgDivClass()
    {
        return $this->baseId() . '_form_error_message';
    }



    abstract protected function getData( $element_id );

    abstract protected function setData( $element_id, $data );

    protected function viewName()
    {
        return 'user/customfieldprofile';
    }

    abstract protected function inputMetas();

    protected function getInputMetas()
    {
        $metas = $this->inputMetas();

        $metas[] = array(
            'type' => 'hidden',
            'name' => 'ncore_is_posted_' . $this->formNumber,
            'value' => 1,
            'element_id' => 0,
        );

        return $metas;
    }

    abstract protected function sectionMetas();

    protected function pageInstructions()
    {
        return array();
    }

    protected function renderInstructions()
    {
        foreach ($this->pageInstructions() as $instructions)
        {
            echo "<p>$instructions</p>";
        }
    }

    protected function renderFormMessages()
    {
        echo $this->formMessages();
        $this->formMessages = array();
    }

    protected function isFormVisible()
    {
        return true;
    }

    protected function renderFormInner()
    {
        if (!$this->isFormVisible()) {
            return;
        }

        if (!$this->formPopulated)
        {
            foreach ($this->formData() as $element_id => $data)
            {
                $data = $this->getData( $element_id );

                $this->form()->setData( $element_id, $data );
            }
        }
        $this->form()->render();
    }

    protected function renderFormMessage( $type, $msg )
    {
        $type_map = array(
            'updated' => 'success',
        );

        $type = ncore_retrieve( $type_map, $type, $type );

        $base_id  = 'ncore-error-';

        self::$html_id++;
        $id = $base_id . self::$html_id;

        $css = "ncore_msg ncore_msg_$type";

        return "<div id='$id' class='$css'>$msg</div>
    ";
    }

    protected function viewData()
    {
        $data = parent::viewData();

        $data[ 'action' ] = $this->formActionUrl();
        $data[ 'have_required' ] = $this->form()->haveRequiredRule();
        $data[ 'form_id'] = $this->formId();
        $data[ 'container_css' ] = $this->containerCss();
        $data[ 'form_css' ]      = $this->setting( 'form_css' ) . ' ' . $this->form()->formCss();

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

    protected function containerCss()
    {
        return $this->setting( 'container_css' );
    }

    protected function renderPageFootnotes()
    {
    }

    /** @var ncore_FormRendererForm | bool */
    private $form = false;
    private $formPopulated = false;
    private $formEnabled = true;
    private $formMessages = array();
    private static $html_id = 0;

    private function onElementIdChanged()
    {
        $this->form( $force_reload = true );
    }

    private function formMessages()
    {
        $ajax_div_id    = $this->ajaxErrorMsgDivId();
        $ajax_div_class = $this->ajaxErrorMsgDivClass();

        $html = "<div id='$ajax_div_id' class='$ajax_div_class'>";

        foreach (ncore_getFlashMessages() as $one)
        {
            $type = $one['type'] == NCORE_NOTIFY_SUCCESS
                    ? 'updated'
                    : 'error';

            $msg  = $one['text'];

            $html .= $this->renderFormMessage( $type, $msg );
        }

        foreach ($this->formMessages as $one)
        {
            $type = $one->type;
            $msg  = $one->msg;

            $html .= $this->renderFormMessage( $type, $msg );
        }

        $html .= '</div>';

        return ncore_minifyHtml( $html );
    }


    private function form( $force_reload = false )
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

    private function addFormMsg($type, $message_or_messages)
    {
        $valid_types = array(
            'error',
            'updated'
        );

        $type_valid = in_array($type, $valid_types);
        if (!$type_valid)
        {
            trigger_error('Invalid $type');
        }

        $messages = is_array( $message_or_messages )
                  ? $message_or_messages
                  : array( $message_or_messages );

        foreach ($messages as $one)
        {
            $obj                  = new StdClass();
            $obj->type            = $type;
            $obj->msg             = $one;

            $this->formMessages[] = $obj;
        }
    }


    private $form_data;

    private function formData()
    {
        if (!isset($this->form_data))
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

