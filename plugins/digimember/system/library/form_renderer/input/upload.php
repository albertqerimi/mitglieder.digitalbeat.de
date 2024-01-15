<?php

class ncore_FormRenderer_InputUpload extends ncore_FormRenderer_InputBase
{
    protected function hint()
    {
        $hint = parent::hint();
        if (!$hint)
        {
            $this->api->load->helper( 'format' );
            $max_size_disp = ncore_formatDataSize( $this->maxUploadFilesize() );
            $hint = _ncore( 'Max. %s', $max_size_disp );
        }

        return $hint;
    }

    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $value = $this->value();

        $attr = array();
        $attr['id'] = $html_id;
        $attr['type'] = 'file';

        return ncore_htmlInput( $postname, $attr );
    }

    protected function renderInnerReadonly()
    {
        // cannot display binary data
        return '';
    }

    private $max_size = false;
    private function maxUploadFilesize()
    {
        if ($this->max_size === false)
        {
            $this->max_size = $this->meta( 'max_size' );

            if (!$this->max_size)
            {
                $this->max_size = ncore_maxUploadFilesize();
            }
        }

        return $this->max_size;
    }
}



