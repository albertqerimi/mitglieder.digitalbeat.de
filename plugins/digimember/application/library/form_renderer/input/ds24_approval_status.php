<?php

class digimember_FormRenderer_InputDs24ApprovalStatus extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $statusse_which_allow_to_request_approval = array( 'new', 'rejected' );
        $statusse_which_allow_to_cancel_request   = array( 'pending' );


        $postname = $this->postname();
        $value = $this->value();


        list( $status, $hint, $alertType ) = $this->_evalStatus($value);

        $can_request = in_array( $value, $statusse_which_allow_to_request_approval );
        $can_cancel  = in_array( $value, $statusse_which_allow_to_cancel_request );

        $have_checkox = $can_request || $can_cancel;
        if ($have_checkox)
        {
            $ds24name = $this->api->Digistore24DisplayName(false);

            $label = $can_request
                   ? _digi('Request approval by %s', $ds24name)
                   : _digi('Cancel request' );

            $new_value = $can_request
                       ? 'pending'
                       : 'new';

            $old_value = $value;

            $attributes = array();
            $attributes[ 'checked_value' ]   = $new_value;
            $attributes[ 'unchecked_value' ] = $old_value;

            if ($can_request)
            {
                $ds24name = $this->api->Digistore24DisplayName(false);
                $attributes['confirm'] = _digi( 'To speed up the approval process, make sure, that you have:|(1) added and proofread the product name and the product description|(2)added a note to the THANK YOU page on how the buyer receives his password.|(3) added a second note to the THANK YOU page stating that the payment is processed by %s|(4) created an imprint page and a link to it on every page|Have you completed ALL of the steps before? If not, please cancel.', $ds24name );
            }


            $input = ncore_htmlCheckbox( $postname, false, $label, $attributes, [], [ 'style' => 'white-space: nowrap; margin-left: 10px;' ] );

            $input_pos_before = $can_request
                              ? '<br />' . $input
                              : '';

            $input_pos_after = $can_request
                              ? ''
                              : '<br />' . $input;
        }
        else
        {
            $input_pos_before = ncore_htmlHiddenInput( $postname, $value );
            $input_pos_after  = '';
        }

        return ncore_htmlAlert($alertType, $status, $alertType, $hint, $input_pos_after.$input_pos_before);
    }

    protected function renderInnerReadonly()
    {
        $value = $this->value();

        list( $status, $hint, $css ) = $this->_evalStatus($value);

        $css .= ' ' . $this->meta('css');

        return "<div class=\"ncore_ds24approvalstatus $css\">$status<br /><span class='ncore_form_hint'>$hint</span></div>";
    }

    protected function defaultRules()
    {
        return 'trim|readonly';
    }


    protected function retrieveSubmittedValue( $_POST_or_GET, $field_name )
    {
        $postname = $this->postname( $field_name );

        return ncore_retrieve( $_POST_or_GET, $postname );
    }



    private $options;
    private $doc_link;

    private function doc_link()
    {
        if (!isset($this->doc_link))
        {
            $this->options();
        }
        return $this->doc_link;
    }

    private function options()
    {
        if (!isset($this->options))
        {
            $this->api->load->model( 'logic/digistore_connector' );
            $ds24 = $this->api->digistore_connector_logic;

            $have_ds24 = $ds24->isConnected( $force_reload=false );

            $this->options = $have_ds24
                           ? $ds24->getGlobalSetting( 'types', 'approval_status' )
                           : false;

            $this->doc_link  = $ds24->url( 'doc_product_approval' );
        }

        return $this->options;
    }

    private function _evalStatus( $status )
    {
        $plugin   = $this->api->pluginDisplayName();
        $ds24name = $this->api->Digistore24DisplayName(false);

        $status_overrides = array(
            'pending'  =>_digi( 'Waiting for %s...', $ds24name ),
            'new'      =>_digi( 'Not yet requested' ),
        );

        $status_hints = array(
            'approved' =>_digi( '%s has approved your product. Start selling now.', $ds24name ),
            'pending'  =>_digi( 'Please wait until %s has approved your product.', $ds24name ),
            'new'      =>_digi( 'First setup your product here. Then mark the checkbox above.', $ds24name ),
            'rejected' =>_digi( '%1$s has rejected your product. Check your mailbox for a email from the %1$s support.', $ds24name ),
        );

        $status_css = array(
            'approved' => 'success',
            'pending'  => 'warning',
            'new'      => 'info',
            'rejected' => 'error',
        );


        $not_connected_hint  = _digi( 'Not connected to %s', $plugin );

        $options = $this->options();

        $have_options = $options !== false;

        if ($have_options)
        {
            $status_msg = ncore_retrieve( $status_overrides, $status, $status );
            if (!$status_msg){
                $status_msg = ncore_retrieve( $options, $status, $status );
            }

            $hint       = ncore_retrieve( $status_hints, $status, '' );
            $alertType        = ncore_retrieve( $status_css,   $status, 'error' );
        }
        else
        {
            $status_msg = $status;
            $hint       = $not_connected_hint;
            $alertType        = 'error';
        }

        $url   = $this->doc_link();
        $label = _digi( 'View %s approval policy', $ds24name );
        $hint .= $url
             ? '<br />' . ncore_htmlLink( $url, $label, array( 'as_popup' => true ) )
             : '';

        return array( $status_msg, $hint, $alertType );
    }

    /**
     * @return bool
     */
    public function fullWidth()
    {
        return true;
    }

}



