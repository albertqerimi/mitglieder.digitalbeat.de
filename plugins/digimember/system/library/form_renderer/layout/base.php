<?php

abstract class ncoreFormLayout_base extends ncore_Class
{
    public function __construct( $settings, ncore_ApiCore $api, $file='', $dir='' )
    {
        parent::__construct( $api,$file, $dir );
        $this->settings = $settings;
        $this->api->load->helper( 'string' );

        $this->init();
    }

   public function renderInput( $inputs_in_row, $have_required_marker )
    {
        $have_colon = $this->haveLabelColons();

        $label = $this->renderLabel( $inputs_in_row, $have_required_marker, $have_colon );
        $have_label = $label !== 'none';
        $omit_label = $label == 'omit';

        $row_css   = '';
        $label_css = '';
        $input_css = ncore_isAdminArea() ? 'dm-column-input' : '';

        $input_html = '';

        $hints = array();

        $has_error = false;

        $have_multiple_inputs_in_row = count($inputs_in_row) >= 2;
        if ($have_multiple_inputs_in_row)
        {
            $row_css .= ' ';
            $row_css .= ncore_isAdminArea() ? 'dm-row-multiple-inputs' : 'ncore_multiple_inputs';
        }

        $is_single_row_input = true;
        $is_full_width = false;

        foreach ($inputs_in_row as $input)
        {
            /** @var ncore_FormRenderer_InputBase $input */
            if (!$input->isSingleRow())
            {
                $is_single_row_input = false;
            }

            if (empty($input_id))
            {
                $input_id= $input->htmlId();
            }

            list( $one_html, $one_hint, $one_row_css ) = $input->render();

            $input_html .= ' ' . $one_html;
            $hints += $one_hint;
            $row_css .= ' ' . $one_row_css;

            $label_css .= ' ' . $input->labelCss();
            $input_css .= ' ' . $input->inputCss();

            if ($input->hasError())
            {
                $has_error = true;
            }
            if ($input->fullWidth()) {
                $is_full_width = true;
            }
        }

        if ($has_error)
        {
            $row_css .=  ' formerror';
        }

        $data = array(
            'have_label' => $have_label,
            'label'      => $label,
            'input_html' => $input_html,
            'input_id'   => $input_id,
            'row_layout' => $input->rowLayout(),
            'row_css'    => $row_css,
            'label_css'  => $label_css,
            'input_css'  => $input_css,
            'hints'      => $hints,
            'single_row' => $is_single_row_input,
            'have_value' => $input->haveValue(),
            'full_width' => $is_full_width,
            'omit_label' => $omit_label,
        );

        $this->renderRow( $data );
    }

    abstract public function renderHead( $section, $headline, $instructions );
    public function renderFoot() {}
    abstract public function renderSectionBegin();
    abstract public function renderSectionEnd();
    abstract public function renderRow( $data );
    abstract public function renderHiddenHtml( $html );

    protected function setting( $key, $default='' ) {
        return ncore_retrieve( $this->settings, $key, $default );
    }

    protected function haveLabelColons()
    {
        return true;
    }

    protected function init()
    {
    }

    /**
     * @param ncore_FormRenderer_InputBase[] $inputs_in_row
     * @param bool                           $have_required_marker
     * @param bool                           $have_colon
     * @param string                         $label_sep
     * @return string
     */
    protected function renderLabel( $inputs_in_row, $have_required_marker, $have_colon=false, $label_sep='<br />' )
    {
        $labels   = array();
        $suffixes = array();
        $required = array();

        $does_input_span_label_cell = true;
        $has_non_empty_label        = false;

        foreach ($inputs_in_row as $index => $input)
        {
            $label = $input->label();
            if ($label == 'none' || $label == 'omit')
            {
                continue;
            }
            $does_input_span_label_cell = false;

            $required_marker = $have_required_marker
                               ? $input->renderRequiredMarker()
                               : '';
            $tooltip_icon = $input->renderTooltip();

            $label = $input->label();
            $labels[] = $label;

            if ($label)
            {
                $has_non_empty_label = true;
            }

            $suffixes[] = $tooltip_icon;
            $required[] = $required_marker;
        }

        if ($does_input_span_label_cell) {
            return 'omit';
//            return ncore_adminConditional('omit', 'none');
        }

        if (!$labels || !$has_non_empty_label)
        {
            return '';
        }

        if ($have_colon)
        {
            $colon = _ncore( ': ' );
            $last_suffix =& $suffixes[ count($suffixes) -1 ];
            $last_suffix .= $colon;
        }

        $result = '';
        foreach ($labels as $i => $label)
        {
            if ($result) {
                $result .= $label_sep;
            }

            $suffix = $suffixes[$i].$required[$i];

            $result .= ncore_appendUnbrokenSuffixToLabel( $label, $suffix );
        }

        return $result;
    }


    private $settings = array();

}