<?php

class ncore_FormRenderer_InputFile extends ncore_FormRenderer_InputBase
{
    public function __construct( $parent, $meta )
    {
        parent::__construct( $parent, $meta );

        $this->_init();
    }

    protected function renderInnerWritable()
    {
        $mailtextModel = $this->api->load->model('data/mail_text');
        $mailtextModel->updateTableIfNeeded();
        $html_id = $this->htmlId();

        $postname = $this->postname();
        $file_id = $this->value();

        $file_html = '';
        $filename = '';
        if ($file_id)
        {
            $previewImage = wp_get_attachment_image_src($file_id, 'thumbnail', true);
            $filename = basename(get_attached_file($file_id));
            if (is_array($previewImage) && isset($previewImage[0])) {
                $file_html = "<img src=\"$previewImage[0]\" alt='' />";
            }
            $fileSize = filesize( get_attached_file( $file_id ) );
        }


        $button_label = $this->meta( 'select_label', _digi('Add attachment') );
        $remove_label = $this->meta( 'remove_label', _ncore( 'Remove' ) );

        $remove_button = "<a href=\"#\">$remove_label</a>";

        $file_attr = $file_html
                    ? ''
                    : "style='display: none;'";

        $showButtonCss = $file_html
                        ? "style='display: none;'"
                        : "";

        $removeButtonCss = $file_html
            ? ""
            : "style='display: none;'";

        $label = $this->label();
        if ($label === 'none' || $label==='skip')
        {
            $label = _ncore( 'Select image' );
        }
        $title = $this->meta( 'dialog_title', $label );

        $output = '';

        $output .= '<div class="dm-select-file-container">';
            $output .= '<div class="dm-row">';
                $output .= '<div class="dm-col-md-12 dm-col-xs-12"><span ' . $file_attr . ' class="dm-select-file-preview"><span class="dm-select-file-preview-img"> ' . $file_html . '</span></span></div>';
            $output .= '</div>';
            $output .= '<div class="dm-row">';
                $output .= '<div class="dm-col-md-12 dm-col-xs-12"><span class="dm-select-file-name"> '. $filename .'</span></div>';
            $output .= '</div>';
            $output .= '<div class="dm-row">';
                $output .= '<div class="dm-col-md-8 dm-col-xs-8"><button '.$showButtonCss.' class="dm-btn dm-btn-primary dm-btn-fullwidth dm-btn-outlined dm-select-file-button" type="button" data-dialog-hl="' . $title . '">' . $button_label . '</button><button '.$removeButtonCss.' class="dm-btn dm-btn-secondary dm-btn-fullwidth dm-select-file-preview-remove" type="button">' . $remove_button . '</button></div>';
            $output .= '</div>';
            $output .= '<input class="dm-select-file-input" type="hidden" value="' . $file_id . '" id="' . $html_id . '" name="' . $postname . '" />';
            $output .= '<input class="dm-select-file-modal-title" type="hidden" value="'.$button_label.'">';
            $output .= '<input class="dm-select-file-modal-button" type="hidden" value="'.$button_label.'">';
        $output .= '</div>';
        return $output;
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

    private function getFileType($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }
}
