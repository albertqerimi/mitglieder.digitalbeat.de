<?php

class ncore_FormRenderer_InputSelectSequence extends ncore_FormRenderer_InputBase
{
    public function __construct( $parent, $meta )
    {
        parent::__construct( $parent, $meta );

        $this->id_name                       = $this->meta( 'id_name', 'id' );
        $this->label_name                    = $this->meta( 'label_name', 'label' );
        $this->inputs                        = $this->meta( 'inputs', array() );
        $this->options                       = $this->meta( 'options', array() );
        $this->selectedSubelements           = array();
        $this->inactiveSubelements           = array();
        $this->is_inactive_post_data_removed = $this->meta( 'is_inactive_post_data_removed', false );
    }

    public function setValue( $values )
    {
        $sub_ids = array();

        $this->selectedSubelements = array();
        $this->inactiveSubelements = array();

        $is_active_key = $this->meta( 'is_active_key' );
        $is_active_val = $this->meta( 'is_active_val' );

        $id_name = $this->id_name;
        $label_name = $this->label_name;

        if ($values)
        {
            foreach ($values as $rec)
            {
                $is_inactive = $is_active_key
                            && ncore_retrieve( $rec, $is_active_key ) != $is_active_val;

                if ($is_inactive)
                {
                    continue;
                }

                $sub_id = ncore_retrieve( $rec, $this->id_name );
                $sub_ids[] = $sub_id;

                $this->selectedSubelements[] = $rec;
            }
        }

        foreach ($this->options as $sub_id => $title)
        {
            $is_selected = in_array( $sub_id, $sub_ids );
            if (!$is_selected)
            {
                $element = new stdClass();
                $element->$id_name = $sub_id;
                $element->$label_name = $title;

                $this->inactiveSubelements[] = $element;
            }
        }

        $sub_ids_comma_seperated = implode( ',', $sub_ids );
        parent::setValue( $sub_ids_comma_seperated );
    }

    protected function onPostedValue( $field_name, &$value )
    {
        if ($field_name)
        {
            return;
        }

        $id_name = $this->id_name;

        $sub_ids_comma_seperated = $value;

        $post_recs = array();

        $sub_ids = explode( ',', $sub_ids_comma_seperated );
        foreach ($sub_ids as $sub_id)
        {
            if (!$sub_id)
            {
                continue;
            }

            $rec = array( $id_name => $sub_id );

            foreach ($this->inputs as $one)
            {
                $name = ncore_retrieve( $one, 'name' );

                $rec[ $name ] = $this->subPostValue( $sub_id, $name );
            }

            $post_recs[] = $rec;
        }

        $value = $post_recs;
    }

    protected function renderInnerWritable()
    {
        $this->maybe_add_init_js();

        $html_id = $this->htmlId();
        $options = $this->options;
        $value = esc_attr( $this->value() );

        $selected_pages = $this->selectedSubelements;
        $inactive_pages = $this->inactiveSubelements;

        $css_inactive = 'sortable inactive no_numbers no_inputs';
        $css_active = 'sortable with_numbers active';

        $id_inactive = 'inactive_pages';
        $id_active = 'active_pages';

        $hidden_input_id = $html_id . '_hidden';

        $layout_css = $this->_layoutCss( count($options), count($this->inputs) );
        $css_active .= " $layout_css";

        $html_inactive = $this->_renderList( $inactive_pages, $id_inactive, $css_inactive );
        $html_active = $this->_renderList( $selected_pages, $id_active, $css_active );

        $hidden_input = $this->_renderHiddenInput( $selected_pages, $hidden_input_id );

        $this->jsInit( $id_active, $id_inactive, $hidden_input_id );

        $headline_available = $this->meta( 'headline_available' );
        $headline_selected = $this->meta( 'headline_selected' );

        $extra_css = $this->is_inactive_post_data_removed
                   ? 'ncore_remove_inactive_post_data'
                   : '';

         return "
<table class='ncore_select_sequence $extra_css'>
    <tbody>
        <tr>
            <th>$headline_available</th>
            <th>$headline_selected</th>
        </tr>
        <tr>
            <td class='ncore_inactive_inputs'>$html_inactive</td>
            <td class='ncore_active_inputs'>$html_active</td>
        </tr>
    <tbody>
</table>

$hidden_input
";
    }

    protected function defaultRules()
    {
        return 'trim';
    }

    protected function requiredMarker()
    {
        return '';
    }

    //
    // private section
    //
    private $id_name = 'id';
    private $label_name = 'label';
    private $inputs = array();
    private $options = array();
    private $subvalues = array();
    private $is_inactive_post_data_removed = false;

    private function _renderList( $pages, $id, $css )
    {
        $html = "<div id='${id}_container' class='ncore_sortable_container'>
<ol id='$id' class='sortable $css'>\n";

        foreach ($pages as $one)
        {
            $id = ncore_retrieve( $one, $this->id_name );
            $label = $this->renderLabel( $id );

            $title = esc_attr( ncore_retrieve( $this->options, $id ) );

            $inputs = $this->renderInputs( $one );

            $html_id = $this->postIdToLiId( $id );

            $html .= "<li id='$html_id' title=\"$title\">$label$inputs</li>\n";
        }

        $html .= "</ol>\n</div>\n";

        return $html;
    }

    private function postIdToLiId( $id )
    {
        return $this->postname() . '_' . $id;
    }

    private function renderLabel( $entry_id )
    {
        $link_url = $this->meta( 'details_url' );
        $link_label = $this->meta( 'details_label' );

        $label = ncore_retrieve( $this->options, $entry_id );

        $have_link = $link_label != '' && $link_url != '';

        if ($have_link)
        {
            $find = array( '__LABEL__', '__ID__' );
            $repl = array( $label, $entry_id );

            $link_url = str_replace( $find, $repl, $link_url );
            $link_label = str_replace( $find, $repl, $link_label );

            $link = "<a href='$link_url' target='_blank'>$link_label</a>";
            return "<span class='ncore_label with_link'>$label</span><span class='link'>$link</span>";
        }
        else
        {
            return "<span class='ncore_label without_link'>$label</span>";
        }
    }

    private function jsInit( $id_active, $id_inactive, $hidden_input_id )
    {
        $postname = $this->postname();

        $id_prefix = $postname . '_';

        $select_all = "#$id_active, #$id_inactive";

        $js = "ncoreJQ(function() {
        ncoreJQ( '#$id_inactive' ).sortable({
            connectWith: 'ol'
        });

         ncoreJQ( '#$id_active'  ).sortable({
   connectWith: 'ol',
   update: function(event, ui) {
        var value = '';

        ncoreJQ( '#$id_active li' ).each( function(index) {
            if (value)
                value += ',';

            value += ncoreJQ( this ).attr('id').replace( '$id_prefix', '' );
        });

        ncoreJQ( '$select_all' ).disableSelection();

        ncoreJQ( '#$hidden_input_id' ).val( value );
   }}
);

        ncoreJQ( '$select_all input' ).bind('click.sortable mousedown.sortable',function(ev){
    ev.target.focus();
  });
});
";
        ncore_addJsOnLoad( $js );
    }

    private function _renderHiddenInput( $selected_pages, $hidden_input_id )
    {
        $postname = $this->postname();

        $value = '';
        foreach ($selected_pages as $one)
        {
            if ($value)
            {
                $value .= ',';
            }

            $id = ncore_retrieve( $one, $this->id_name );

            $value .= $id;
        }

        return ncore_htmlHiddenInput( $postname, $value, array( 'id' => $hidden_input_id ) );
    }

    private function _layoutCss( $count_posts, $count_inputs )
    {
        if ($count_posts<=9)
        {
            $css='';
        }
        elseif ($count_posts<=99)
        {
            $css='two_decimals';
        }
        elseif ($count_posts<=999)
        {
            $css='three_decimals';
        }
        else
        {
            $css='four_decimals';
        }

        switch ($count_inputs)
        {
            case 0: break;
            case 1: $css .= ' with_input'; break;
            case 2: $css .= ' with_input with_two_inputs'; break;
            default:
                $css .= ' with_input with_many_inputs';
        }

        return $css;
    }

    private function renderInputs( $post )
    {
        $id = ncore_retrieve( $post, $this->id_name );

        $seperator = '';

        $html = '';
        $need_container = false;
        foreach ($this->inputs as $one)
        {
            $hide = ncore_retrieve( $one, 'hide', false );
            if ($hide)
            {
                continue;
            }

            if ($html)
            {
                $html .= $seperator;
            }
            $type = ncore_retrieve( $one, 'type' );
            $name = ncore_retrieve( $one, 'name' );
            $label = ncore_retrieve( $one, 'label' );
            $tooltip = ncore_retrieve( $one, 'tooltip' );
            $options = ncore_retrieve( $one, 'options', array() );
            $default_value= ncore_retrieve( $one, 'default', '' );

            $value = ncore_retrieve( $post, $name, $default_value );


            $postname = $this->subPostName( $id, $name );

            $attributes = array(
                'title' => $label . _ncore(': ') . $tooltip,
            );

            switch ($type)
            {
                case 'int':
                    $attributes[ 'class' ] = 'dm-input-int-small';
                    if (isset($one['display_zero_as'])) {
                        $attributes['display_zero_as'] = $one['display_zero_as'];
                    }
                    $input = ncore_htmlIntInput( $postname, $value, $attributes ) ;
                    $need_container = true;
                    break;
                case 'text':
                    $input = ncore_htmlTextInput( $postname, $value, $attributes ) ;
                    $need_container = true;
                    break;
                case 'select':
                    $input = ncore_htmlSelect( $postname, $options, $value, $attributes ) ;
                    $need_container = true;
                    break;
                case 'hidden':
                    unset( $attributes['title' ] );
                    $input = ncore_htmlHiddenInput( $postname, $value, $attributes ) ;
                    break;
                default:
                    trigger_error( '$type not implemented' );
                    $input = '';
            }

            $html .= $input;
        }

        if ($need_container)
        {
            $html = "<span class='input'>$html</span>";
        }

        return $html;
    }

    private function subPostName( $element_id, $name )
    {
        $basename = $this->meta('name' );

        $postname = $this->postname( $basename.'_values' ) . "[$element_id][$name]";

        return $postname;
    }

    private function subPostValue( $element_id, $name )
    {
        $basename = $this->meta('name' );

        $values = $this->postedValue( $basename.'_values' );

        $rec = ncore_retrieve( $values, $element_id );

        $value = ncore_retrieve( $rec, $name );

        return $value;
    }


    private function maybe_add_init_js()
    {
        if (!$this->is_inactive_post_data_removed) {
            return;
        }

        static $is_initialized;
        if (!empty($is_initialized)) {
            return;
        }
        $is_initialized = true;

        $model = $this->api->load->model( 'logic/html' );

        $js_remove  = "ncoreJQ( '.ncore_select_sequence.ncore_remove_inactive_post_data .ncore_inactive_inputs input' ).remove();";
        $js_remove .= "ncoreJQ( '.ncore_select_sequence.ncore_remove_inactive_post_data .ncore_inactive_inputs select' ).remove();";
        $js_remove .= "ncoreJQ( '.ncore_select_sequence.ncore_remove_inactive_post_data .ncore_inactive_inputs textarea' ).remove();";

        $js  = "ncoreJQ( 'form.ncore_admin_form' ).submit( function() { $js_remove; } );";
        $js .= "ncoreJQ( 'form.ncore_user_form' ).submit( function() { $js_remove; } );";

        $model->jsOnLoad( $js );
    }



}


