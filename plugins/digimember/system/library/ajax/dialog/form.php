<?php

class ncore_Ajax_DialogForm extends ncore_Ajax_DialogBase
{
    public function setData( $data )
    {
        $this->form()->setData( 0, $data );
    }

    public function getData()
    {
        return $this->form()->getData( 0 );
    }

    public function validate()
    {
        $messages = $this->form()->validate();
        return $messages;
    }

    public function setErrorMessages( $messages )
    {
        foreach ($messages as $one)
        {
            if (!in_array( $one, $this->error_messages))
            {
                $this->error_messages[] = $one;
            }
        }
    }

    protected function renderCallbackJsCode( $event, $cb_js_code )
    {
        $form_id = $this->form_id();
        if ($this->meta('close_on_ok')) {
            return "ncoreJQ( this ).dmDialog( 'close' ); var event='$event'; var form_id='$form_id'; $cb_js_code";
        }
        else {
            return 'var event = \''.$event.'\'; var form_id = \''.$form_id.'\'; '.$cb_js_code.';';
        }
    }

    protected function buttons()
    {
        $label_ok     = $this->meta( 'label_ok',     _ncore( 'Ok' ) );
        $label_cancel = $this->meta( 'label_cancel', _ncore( 'Cancel' ) );

        $confirm_ok   = $this->meta( 'confirm_ok' );

        $ok_button = $this->okButton( $label_ok, 'ok', $confirm_ok   );
        $close_button = $this->closeButton( $label_cancel );

        return array_merge( $ok_button, $close_button );
    }

    protected function innerHtml()
    {
        $message = $this->meta( 'message' );

        $icon = $this->meta( 'icon', 'info' );

        $html = '';

        foreach ($this->error_messages as $one)
        {
            $html .= $this->renderFormMessage( 'error', $one );
        }

        $html .= $message
                ? "<div class='instruction $icon'>$message</div>"
                : '';


        $form = $this->form();

        $form_id = $this->form_id();

        $html .= "<form id='$form_id' action='?' method='POST' class='ncore_ajax_form dm-formbox'>\n"
                . "<table class='ncore_table ncore_form'><tbody>\n"
                . $form->html()
               . "</tbody></table></form>
<script>
    if (typeof ncoreJQ.fn.dmInit !== 'undefined') {
        ncoreJQ('#$form_id').dmInit();
    }
</script>
\n";

        return $html;
    }

    protected function onFormSubmitJs( $cb_form_id )
    {
        $my_form_id = $this->form_id();

        $js = "var args=ncoreJQ( '#$my_form_id' ).serializeArray();
               ncoreJQ.each( args, function(index,input) {
                    ncoreJQ('<input>').attr('type','hidden').attr('name',input.name).val(input.value).appendTo('#$cb_form_id');
               } );";


        return $js;
    }

    private $form_id='';
    private $form=false;
    private $error_messages=array();

    private function form_id()
    {
        if (!$this->form_id)
        {
            $this->form_id = ncore_id('ajax_form');
        }

        return $this->form_id;
    }

    function form()
    {
        if ($this->form)
        {
            return $this->form;
        }

        $sections     = $this->meta(  'form_sections', array() );
        $input_metas  = $this->meta(  'form_inputs', array() );
        $button_metas = $this->meta(  'buttons', array() );

        /** @var ncore_FormRendererLib $lib */
        $lib = $this->api->load->library('form_renderer' );

        $settings = array();
        $settings[ 'post_readonly_data' ] = true;

        // foreach ($input_metas as $index => $meta)
        // {
        //     $input_metas[$index]['method'] = 'get';
        // }

        $this->form = $lib->createForm( $sections, $input_metas, $button_metas, $settings );

        return $this->form;
    }

    private static $html_id=0;
    private function renderFormMessage( $type, $msg )
    {
        $type_map = array(
            'updated' => 'success',
        );

        $base_id  = 'ncore-error-';

        $type = ncore_retrieve( $type_map, $type, $type );

        self::$html_id++;
        $id = $base_id . self::$html_id;

        $css = "ncore_msg ncore_msg_$type";

        return "<div id='$id' class='$css'>
        $msg
    </div>
    ";
    }
}