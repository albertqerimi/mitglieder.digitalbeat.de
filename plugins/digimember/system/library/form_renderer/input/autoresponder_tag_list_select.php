<?php

$load->loadPluginClass( 'checkbox_list_select' );

class ncore_FormRenderer_InputAutoresponderTagListSelect extends ncore_FormRenderer_InputCheckboxListSelect
{

    public function __construct( $parent, $meta )
    {
        if (empty($meta['invalid_label']))
        {
            $meta['invalid_label'] =_digi3( 'Invalid tag id #%s', '[VALUE]' );
        }

        parent::__construct( $parent, $meta );

        $this->_maybeCreateTag();
    }

    public function value()
    {
        $value = parent::value();

        $value = $this->_maybeAddTag( $value );

        return $value;
    }

    public function postedValue($field_name = '')
    {
        $posted_value = parent::postedValue( $field_name );

        if (!$field_name)
        {
            $posted_value = $this->_maybeAddTag( $posted_value );
        }

        return $posted_value;

    }

    protected function renderInnerWritable()
    {
        $options = $this->options();
        if ($options === false)
        {
            return $this->renderMsg( 'msg_connect_error' );
        }
        if (empty($options)) {
            return $this->renderMsg( 'msg_not_setup' )
                 . $this->renderCreateTagInput();
        }

        return parent::renderInnerWritable()
             . $this->renderCreateTagInput();
    }

    protected function options()
    {
        if (!isset($this->options))
        {
            $this->options = parent::options();

            if (!$this->options) {

                $this->options = false;

                /** @var digimember_AutoresponderHandlerLib $lib */
                $lib = $this->api->load->library( 'autoresponder_handler' );

                try
                {
                    $autoresponder_obj_or_id = $this->getAutoresponderObjOrId();
                    if ($autoresponder_obj_or_id)
                    {
                        $this->options = $lib->getTagOptions( $autoresponder_obj_or_id );
                    }
                }
                catch (Exception $e) {
                    $this->options = false;
                }
            }
        }

        return $this->options;
    }


    private $options;
    private $autoresponder;
    private $created_tag_id = false;

    private function getAutoresponderObjOrId()
    {
        if (isset($this->autoresponder)) {
            return $this->autoresponder;
        }

        $ar = $this->meta( 'autoresponder' );
        if (empty($ar)) {
            $this->autoresponder = false;
            return $this->autoresponder;
        }

        if (is_numeric($ar) && $ar>=1)
        {
            $this->autoresponder = $ar;
            return $this->autoresponder;
        }

        if (is_object($ar)) {
            $this->autoresponder = $ar;
            return $this->autoresponder;
        }

//
//  There might be a bug, that call $this->form()->value() called from the constructor returns the default value instead of the posted value.
//  So I disabled dynamicilly reading the autoresponder id from the get parameters - Christian, November 8th 2016
//
//        if (is_string($ar))
//        {
//            $postname = $ar;
//            $id = $this->form()->value( $this->element_id(), $postname );

//            if (!$id)
//            {
//                $postname = $this->form()->postname( $this->element_id(), $ar );

//                $id = (int) @$_POST[ $postname ];
//            }

//            if ($id>0)
//            {
//                $this->autoresponder = $id;

//                return $this->autoresponder;
//            }
//        }

        return $this->autoresponder;
    }

    private function getAutoresponderId()
    {
        $ar_obj_or_id = $this->getAutoresponderObjOrId();
        $id = is_numeric( $ar_obj_or_id )
            ? $ar_obj_or_id
            : ncore_retrieve( $ar_obj_or_id, 'id' );
        return $id;
    }

    private function renderMsg( $msgkey )
    {
        $default_msgs = array(
            'msg_connect_error' => _ncore( 'Error loading tags - please <a>check access data in your autoresponder settings</a>.' ),
            'msg_not_setup'     => _ncore( 'No tags created yet.' ),
        );

        $msg = $this->meta( $msgkey, $default_msgs[$msgkey] );

        $must_include_ar_edit_link = $msgkey==='msg_connect_error';

        if ($must_include_ar_edit_link)
        {
            $id = $this->getAutoresponderId();

            $this->api->load->model( 'logic/link' );
            $params = $id>0
                    ? array( 'id' => $id )
                    : array();
            $url = $this->api->link_logic->adminPage( 'digimember_newsletter', $params );

            $msg = ncore_linkReplace( $msg, $url );
        }

        $postname = $this->postname();
        $value    = $this->value();

        $html = ncore_htmlHiddenInput( $postname, $value );

        $css = $this->meta( 'error_hint_css', 'ncore_error_hint' );
        $html .= "<span class='$css'>$msg</span>";

        return $html;
    }

    private function renderCreateTagInput()
    {
        $postname = $this->postname().'_create_tag';

        $label = _ncore( 'New tag:' );

        $value = @$_POST[$postname];

        $input = ncore_htmlTextInput( $postname, $value );

        return "<br /><label>$label $input</label>";
    }

    private function _maybeCreateTag()
    {
        $postname = $this->postname().'_create_tag';

        $new_tag_name = @$_POST[ $postname ];
        $_POST[ $postname ]='';
        if (empty($new_tag_name)) {
            return;
        }

        try
        {
            $options = $this->options();
            $have_tag = $options && in_array( $new_tag_name, $options );
            if ($have_tag)
            {
                ncore_flashMessage( NCORE_NOTIFY_WARNING, _ncore( 'There is already a tag with the name %s.', "'$new_tag_name'" ) );
                return;
            }
            unset( $this->options );

            $autoresponder_obj_or_id = $this->getAutoresponderObjOrId();
            /** @var digimember_AutoresponderHandlerLib $lib */
            $lib = $this->api->load->library( 'autoresponder_handler' );

            $this->created_tag_id = $lib->createTag( $autoresponder_obj_or_id, $new_tag_name  );

            ncore_flashMessage( NCORE_NOTIFY_SUCCESS, _ncore( 'Created tag %s.', "'$new_tag_name'" ) );
        }
        catch (Exception $e) {
            ncore_flashMessage( NCORE_NOTIFY_ERROR, $e->getMessage() );
        }
    }

    private function _maybeAddTag( $value )
    {
        $must_add = !in_array( $this->created_tag_id, explode( ',', $value ) );
        if ($must_add)
        {
            if ($value) {
                $value .= ',';
            }
            $value .= $this->created_tag_id;
        }

        return $value;
    }
}