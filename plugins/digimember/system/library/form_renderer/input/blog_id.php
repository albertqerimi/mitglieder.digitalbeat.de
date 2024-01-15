<?php

class ncore_FormRenderer_InputBlogId extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $value = $this->value();

        $attributes = array( 'id' => $html_id );

        $zero = $this->meta( 'display_zero_as', '0' );
        $size = $this->meta( 'size', false );
        $maxlength = $this->meta( 'maxlength', 0 );

        if ($value === '0' || intval($value) === 0)
        {
            $value = $zero;
        }

        if ($size)
        {
              $attributes['size'] = $size;

              if ($maxlength < $size)
              {
                  $maxlength = $size;
              }
        }
        if ($maxlength)
        {
            $attributes['maxlength'] = $maxlength;
        }


        $display_id = $html_id . '_blog_name';

        $args = array(
            'target_div_id' => $display_id,
            'not_found_msg' => '<em>(' . _ncore( 'Invalid blog id' ) . ')</em>',
        );

        $controller = $this->api->load->controller( 'ajax/info' );
        $js_fetch_blog_name = $controller->renderAjaxJs( 'get_blog_name', $args, $existing_data_object_name='data' );

        $on_change_fct = "onchange_$html_id";

        $js_fct = "
function $on_change_fct(timeout) {

    if (typeof this.timeout_handler == 'undefined') {
        this.timeout_handler = false;
    }

    if (typeof prev_$on_change_fct == 'undefined') {
        prev_$on_change_fct = 0;
    }

    if (this.timeout_handler) {
        window.clearTimeout( this.timeout_handler );
        this.timeout_handler = false;
    }

    var blog_id = document.getElementById( '$html_id' ).value;

    blog_id = blog_id.replace(/[^0-9]/g,'');

    var nothing_to_do = blog_id > 0 && blog_id == prev_$on_change_fct;
    if (nothing_to_do) {
        return;
    }

    document.getElementById( '$display_id' ).innerHTML = '';
    prev_$on_change_fct = 0;

    if (blog_id > 0)
    {
        this.timeout_handler = window.setTimeout( prev_$on_change_fct = ' + blog_id + '; dmDialogAjax_Start(\"$display_id\"); var data = { 'blog_id' : ' + blog_id + \" }; $js_fetch_blog_name;\", timeout );
    }
}
";

        $js_onload = "
ncoreJQ( '#$html_id' ).change( function() { $on_change_fct(0) } );
ncoreJQ( '#$html_id' ).keyup( function() { $on_change_fct(500) } );
ncoreJQ( '#$html_id' ).trigger( 'change' );

";
        $model = $this->api->load->model( 'logic/html' );
        $model->jsFunction( $js_fct );
        $model->jsOnLoad( $js_onload );


        $blog_id = $this->value();
        $blog_name = ncore_getBlogDomain( $blog_id );

        $html_blog_name = " <span id='$display_id'>$blog_name</span>";



        return ncore_htmlIntInput( $postname, $value, $attributes ) . $html_blog_name;
    }

    protected function renderInnerReadonly() {

        $blog_id = parent::renderInnerReadonly();

        $blog_name = ncore_getBlogDomain( $blog_id );

        $html = $blog_id;

        if ($blog_name)
        {
            $blog_name .= " ($blog_name)";
        }

        return $html;
    }

    protected function defaultRules()
    {
        return 'trim|numeric';
    }
}


