<?php

class digimember_FormRenderer_InputDisplayIpnUrl extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        return $this->renderInnerReadonly();
    }

    protected function renderInnerReadonly()
    {
        $link_logic = $this->api->load->model( 'logic/link' );

        $html_id = $this->htmlId();
        $ipn_id = $this->elementId();

        if ($ipn_id<=0)
        {
            return '<div class="dm-text">' . _digi( 'Hit <em>Save changes</em> to create the notification URL.' ) . '</div>';
        }

        $url = $link_logic->ipnCall( $ipn_id );

        $size = max( 40, strlen( $url ) + 25 );

        $attributes = array(
            'id' => $html_id,
            'size' => $size,
        );

        return ncore_htmlTextInputCode( $url, $attributes );
    }

    public function isReadonly()
    {
        return true;
    }
}
