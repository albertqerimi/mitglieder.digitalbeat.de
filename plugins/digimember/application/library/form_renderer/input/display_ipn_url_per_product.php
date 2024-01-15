<?php

class digimember_FormRenderer_InputDisplayIpnUrlPerProduct extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        return $this->renderInnerReadonly();
    }

    protected function renderInnerReadonly()
    {
        $link_logic = $this->api->load->model( 'logic/link' );

        $ipn_id = (int) $this->elementId();

        if ($ipn_id<=0)
        {
            return '<div class="dm-text">' . _digi( 'Hit <em>Save changes</em> to create the notification URL.' ) . '</div>';
        }

        $max_size = 40;

        $options = $this->meta( 'options', array() );

        $urls = array();

        foreach ($options as $product_id => $product_name)
        {
            $url = $link_logic->ipnCall( $ipn_id, $product_id );
            $urls[ $product_id ] = $url;

            $max_size = max( $max_size, max( 40, strlen( $url ) + 25 ) );
        }

        $colon = _ncore( ': ' );

        $html = '';

        $attributes = array(
            'size' => $max_size,
        );

        foreach ($options as $product_id => $product_name)
        {
            $url = $urls[ $product_id ];

            $html .= "<p>$product_name$colon<br />"
                   . ncore_htmlTextInputCode( $url, $attributes );
        }

        return $html;
    }

    public function isReadonly()
    {
        return true;
    }
}
