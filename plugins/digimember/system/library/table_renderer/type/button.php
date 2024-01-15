<?php

class ncore_TableRenderer_TypeButton extends ncore_TableRenderer_TypeBase
{
    protected function init()
    {
        parent::init();

        ncore_api()->load->helper( 'html_input' );
    }


    protected function renderInner( $row )
    {
        $replace = $this->meta( 'replace', array() );

        $label      = $this->meta( 'label', _ncore( 'Edit' ) );
        $image_url  = $this->meta( 'imageurl' );
        $tooltip    = $this->meta( 'tooltip' );
        $confirm    = $this->meta( 'confirm' );
        $confirm2   = $this->meta( 'confirm2' );
        $postname   = $this->meta( 'postname' );

        $js = $this->meta( 'js' );
        $css = $this->meta( 'css', 'ncore_table_inline' );

        $url       = $this->meta( 'url' );
        $as_popup  = $this->meta( 'as_popup', false );



        $find = array();
        $repl = array();
        foreach ($replace as $placeholder => $col)
        {
            $value = ncore_retrieve( $row, $col );

            $find[] = $placeholder;
            $repl[] = $value;

            $find[] = urlencode( $placeholder );
            $repl[] = $value;

        }

        $url      = str_replace( $find, $repl, $url   );
        $js       = str_replace( $find, $repl, $js    );
        $label    = str_replace( $find, $repl, $label );
        $tooltip  = str_replace( $find, $repl, $tooltip );
        $confirm  = str_replace( $find, $repl, $confirm );
        $confirm2 = str_replace( $find, $repl, $confirm2 );
        $postname = str_replace( $find, $repl, $postname );

        if ($image_url)
        {
            $title_attr = $tooltip
                        ? ''
                        : "title=\"$label\"";
            $label = "<img src='$image_url' alt=\"$label\" $title_attr />";

            $css .= ' ncore_image_button';
        }

        $attributes = array();
        $attributes['class']    = $css;
        $attributes['confirm']  = $confirm;
        $attributes['confirm2'] = $confirm2;
        $attributes['tooltip']  = $tooltip;
        $attributes['as_popup'] = $as_popup;
        $attributes['name']     = $postname;

        if ($postname && !$js) {
            $js = 'return true;';
        }

        if ($js)
        {
            return ncore_htmlButtonJs( $label, $js, $attributes );
        }
        else {
            return ncore_htmlButtonUrl( $label, $url, $attributes );
        }
    }


}