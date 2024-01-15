<?php

class ncore_FormRenderer_InputImageUrl extends ncore_FormRenderer_InputBase
{
    public function __construct( $parent, $meta )
    {
        parent::__construct( $parent, $meta );

        ncore_htmlImageUploaderInit();
    }

    protected function onPostedValue( $field_name, &$value )
    {
        parent::onPostedValue( $field_name, $value );

        $is_void = $value == 'http://' || $value == 'https://';
        if (!$this->meta('empty_button',false) && $is_void)
        {
            $value = '';
        }

    }

    protected function renderInnerWritable()
    {
        $postname = $this->postname();
        $value = $this->value();

        $id = ncore_id();

        $attributes=array();

        $attributes['id'] = $id;

        $preview = $this->_renderPreview( $id.'_preview' );

        $label = $this->label();
        if ($label === 'none' || $label==='skip')
        {
            $label = _ncore( 'Select image' );
        }
        $title = $this->meta( 'dialog_title', $label );

        $emptyButton = '';
        if ($this->meta('empty_button',false)) {
            $emptyButton = '<div class="dm-col-md-4 dm-col-xs-12">
                <span class="dm-select-image-preview">
                    <button class="dm-btn dm-btn-secondary dm-btn-fullwidth dm-select-image-preview-remove">' . $this->meta('empty_button') . '</button>        
                </span>
            </div>';
        }
        $button_label  = ncore_retrieve( $attributes, 'button_label',  _ncore( 'Choose image' ) );

        $colLength = $emptyButton ? 8 : 12;
        return '
<div class="dm-select-image-container dm-select-image-container-url">
    <div class="dm-row">
        <div class="dm-col-md-' . $colLength . ' dm-col-xs-12">
            <div class="dm-input-group dm-fullwidth">
                <input class="dm-select-image-input dm-input" type="text" value="' . $value . '" id="' . $id . '" name="' . $postname . '" />
                <button class="dm-btn dm-btn-primary dm-input-button dm-select-image-button" data-dialog-hl="' . $title . '">' . $button_label . '</button>
            </div>
        </div>
        ' . $emptyButton . '
    </div>
    <div class="dm-row">
        <div class="dm-col-md-12 dm-col-xs-12">
            <span class="dm-select-image-preview">
                <span class="dm-select-image-preview-img">
                    ' . $preview . '
                </span>
            </span>
        </div>
    </div>
</div>
';

//        $input   = ncore_htmlImageUploader( $postname, ncore_val($value), $attributes );
//        $preview = $this->_renderPreview( $id.'_preview' );
//
//        $html = $input;
//
//        if ($this->meta('empty_button',false)) {
//            $html .= ncore_htmlButton($postname.'_empty',$this->meta('empty_button'),array('onclick'=>'ncoreJQ(\'input[name="'.$postname.'"]\').val(\'http://\').parent().find(\'.ncore_image_url_preview img\').hide();'));
//        }
//
//        if ($preview)
//        {
//            $html .= "<div class='ncore_image_url_preview'>$preview</div>";
//        }
//
//        return $html;

    }


    private function _renderPreview($id='')
    {
        $url = $this->value();

        $id_attr = '';
        if ($id) {
            $id_attr = "id='$id'";
        }

        $have_image = (bool) trim( str_ireplace( array( 'http://', 'https://' ), '', $url ));

        $style='';
        if (!$have_image) {
            $style = "display:none;";
        }

        return "<img $id_attr style=\"max-height: 100px; max-width: 300px; $style\" src=\"$url\" />";
    }


}

