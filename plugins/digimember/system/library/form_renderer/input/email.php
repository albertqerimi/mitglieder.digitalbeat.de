<?php

class ncore_FormRenderer_InputEmail extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $value = $this->value();

        $size = $this->meta( 'size', 50 );

        $attributes = array( 'id' => $html_id, 'size' => $size );

        $input = ncore_htmlTextInput( $postname, $value, $attributes );

        $show_wp_user = $this->meta( 'show_wp_user', false );

        $info_html = '';
        if ($show_wp_user) {

        }


        return $input . $info_html;
    }

    protected function defaultRules()
    {
        return 'trim|email';
    }

    public function value()
    {
        $value = parent::value();

        $this->mayAddDefaultDomain( $value );

        return $value;
    }

    protected function onPostedValue( $field_name, &$value )
    {
        $this->mayAddDefaultDomain( $value );
    }

    private function mayAddDefaultDomain( &$value )
    {
        $default_domain = $this->meta( 'default_domain' );
        if (!$default_domain)
        {
            return;
        }

        $have_domain = strpos( $value, '@' ) !== false;
        if ($have_domain)
        {
            return $have_domain;
        }

        $value .= '@' . $default_domain;
    }

}


