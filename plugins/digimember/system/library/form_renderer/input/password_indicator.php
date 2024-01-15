<?php

class ncore_FormRenderer_InputPasswordIndicator extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = ncore_id();

        $this->renderJs( $html_id );

        $none = _ncore( 'Password strength' );
        $bad = _ncore( 'Very weak' );
        $mismatch = _ncore( 'No match' );
        $weak = _ncore( 'Weak' );
        $good = _ncore( 'Medium' );
        $strong = _ncore( 'Strong' );

        $html = "
<div class='ncore_pwstrength none'>$none</div>
<div class='ncore_pwstrength mismatch ncore_hidden'>$mismatch</div>
<div class='ncore_pwstrength bad ncore_hidden'>$bad</div>
<div class='ncore_pwstrength weak ncore_hidden'>$weak</div>
<div class='ncore_pwstrength good ncore_hidden'>$good</div>
<div class='ncore_pwstrength strong ncore_hidden'>$strong</div>";

        return $html;
    }

    private static $js_rendered=false;
    private function renderJs( $html_id )
    {
        if (self::$js_rendered) {
            return;
        }

        self::$js_rendered = true;

        $username_input_name = $this->meta( 'username_input', false );
        $password_input_name = $this->meta( 'password_input', false );
        $password2_input_name = $this->meta( 'password2_input', false );

        $username_value = $this->meta( 'username_value', false );

        $password_input = $this->parent()->getInput( $password_input_name, $this->elementId() );
        if (!$password_input)
        {
            trigger_error( 'Invalid value for meta parent_password_input_name - input not found.' );
            return false;
        }
        $password_postname = $password_input->postname();

        $password2_input = $this->parent()->getInput( $password2_input_name, $this->elementId() );
        if (!$password2_input)
        {
            trigger_error( 'Invalid value for meta parent_password_input_name - input not found.' );
            return false;
        }
        $password2_postname = $password2_input->postname();


        $username_postname = '';

        if ($username_input_name)
        {
            $username_input = $this->parent()->getInput( $username_input_name, $this->elementId() );
            if (!$username_input)
            {
                trigger_error( 'Invalid value for meta parent_username_input_name - input not found.' );
                return false;
            }
            $username_postname = $username_input->postname();
        }

        $js_username = $username_postname
                     ? "form.find('input[name=$username_postname]').val()"
                     : '"' . str_replace( '"', '', $username_value ) . '"';


        $js = "
ncoreJQ( 'input[name=$password_postname],input[name=$password2_postname]' ).keyup(
    function( event )
    {
        var obj  = ncoreJQ(event.target);
        var form = obj.parentsUntil( 'form' ).parent();

        var usr = $js_username;
        var pw1 = form.find('input[name=$password_postname]').val();
        var pw2 = form.find('input[name=$password2_postname]').val();

        var strength = dmCalculatePasswordStrength( pw1, usr, pw2 );

        ncoreJQ( '.ncore_pwstrength' ).hide();
        ncoreJQ( '.ncore_pwstrength.' + strength ).show();

    }
)
";

        $html = $this->api->load->model( 'logic/html' );
        $html->jsOnLoad( $js );

        return true;
    }


}




