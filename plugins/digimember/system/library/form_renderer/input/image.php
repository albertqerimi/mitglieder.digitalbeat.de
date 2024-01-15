<?php

class ncore_FormRenderer_InputImage extends ncore_FormRenderer_InputBase
{
    public function __construct( $parent, $meta )
    {
        parent::__construct( $parent, $meta );

        $this->_init();
    }

    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $image_id = $this->value();

        $image_html = '';
        if ($image_id)
        {
            $url = wp_get_attachment_url( $image_id );
            if ($url)
            {
                $image_html = "<img src=\"$url\" alt='' />";
            }
        }

        $button_label = $this->meta( 'select_label', _ncore( 'Select image' ) );
        $remove_label = $this->meta( 'remove_label', _ncore( 'Remove' ) );

        $remove_button = "<a href=\"#\">$remove_label</a>";

        $image_attr = $image_html
                    ? ''
                    : "style='display: none;'";

        $label = $this->label();
        if ($label === 'none' || $label==='skip')
        {
            $label = _ncore( 'Select image' );
        }
        $title = $this->meta( 'dialog_title', $label );

        return '
<div class="dm-select-image-container">
    <div class="dm-row">
        <div class="dm-col-md-8 dm-col-xs-8">
            <button class="dm-btn dm-btn-primary dm-btn-fullwidth dm-btn-outlined dm-select-image-button" type="button" data-dialog-hl="' . $title . '">' . $button_label . '</button>
        </div>
        <div class="dm-col-md-4 dm-col-xs-4">
            <span ' . $image_attr . ' class="dm-select-image-preview">
                <button class="dm-btn dm-btn-secondary dm-btn-fullwidth dm-select-image-preview-remove" type="button">' . $remove_button . '</button>        
            </span>
        </div>
    </div>
    <div class="dm-row">
        <div class="dm-col-md-12 dm-col-xs-12">
            <span ' . $image_attr . ' class="dm-select-image-preview">
                <span class="dm-select-image-preview-img">
                    ' . $image_html . '
                </span>
            </span>
        </div>
    </div>
    <input class="dm-select-image-input" type="hidden" value="' . $image_id . '" id="' . $html_id . '" name="' . $postname . '" />
</div>
';
    }

    protected function defaultRules()
    {
        return 'trim|numeric';
    }

    private function _init()
    {
        static $is_initialized;
        if (!empty($is_initialized))
        {
            return;
        }
        $is_initialized = true;

        wp_enqueue_media();
    }
}
