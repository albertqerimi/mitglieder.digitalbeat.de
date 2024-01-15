<?php

class digimember_GdprLogic extends ncore_BaseLogic
{
    
    function filename( $format='txt' )
    {
        $content_types = array(
            'xml' => 'text/xml; charset="utf-8"',
            'txt' => 'text/plain; charset="utf-8"',
        );
        
        $default_format = 'txt';
        
        if (empty( $content_types[ $format ]))
        {
            $format = $default_format;
        }
                
        $filename = _digi( 'personal_data' ) . '.' . $format;
        
        $content_type = ncore_retrieve( $content_types, $format, 'application/binary' );
        
        return array( $filename, $content_type );
    }
    
    function download_personal_data_report( $user_obj_or_id='current', $format='xml' )
    {
        $this->api->load->model( 'data/user' );
        
        $user_id = ncore_userId( $user_obj_or_id );
        
        $ncore_data = $this->api->user_data->getUserData();
        
        $user_data           = get_userdata($user_id);
        $user_meta           = get_user_meta($user_id);
        $auto_responder_data = $this->_getAutoresponderData( $user_id );
        
        $data = [
            'user_data'     => $user_data,
            'user_meta'     => $user_meta,
            'autoresponder' => $auto_responder_data,
        ];
        
        $data = apply_filters( 'ncore_gdpr_data_export', $data );
        
        $data[ 'ncore_data' ] = $ncore_data;
        
        switch ($format)
        {
            case 'xml':
                return "<personal_data user_id='$user_id'>" . $this->_renderXml( $data ) . '</personal_data>';
                
            case 'txt':
            default:
                return $this->_renderText( $data );
        }        
    }
    
    private function _renderXml( $data )
    {
        if (is_string($data) || is_numeric($data))
        {
            return htmlspecialchars( $data );
        }
        
        $this->api->load->helper( 'array' );
        $is_numeric_array = ncore_isNumericArray( $data );
        if ($is_numeric_array) {
            $xml = '';
            $xml .= $this->_xml_tag_open( 'items' );
            foreach ($data as $one)
            {
                $xml .= $this->_xml_tag_open( 'item' )
                      . $this->_renderXml( $one )
                      . $this->_xml_tag_close( 'item' );
            } 
            $xml .= $this->_xml_tag_close( 'items' );
            return $xml; 
        }
        
        $is_record = is_array( $data ) || is_object( $data );
        if($is_record) {
            $xml = '';
            foreach ( (array) $data as $key => $value)
            {
                $xml .= $this->_xml_tag_open( $key ) . $this->_renderXml( $value ). $this->_xml_tag_close( $key );
            }
            return $xml;
        }
        
        return htmlspecialchars( (string) $data );
    }
    
    private function _xml_tag_open( $tag )
    {
        return '<' . $this->_xml_tag_clean( $tag ) . '>';
    }
    private function _xml_tag_close( $tag )
    {
        return '</' . $this->_xml_tag_clean( $tag ) . '>';
    }
    
    private function _xml_tag_clean( $tag )
    {
        $forbidden_chars = array( ' ', "\0" );
        
        $tag = htmlspecialchars( str_replace( $forbidden_chars, '_', trim( $tag )) );
        
        if (!$tag) {
            return 'item';
        }
        
        if (is_numeric($tag[0])) {
            return 'X'.$tag;
        }
        
        return $tag;
    }
    
    private function _getAutoresponderData( $user_id )
    {
        /** @var digimember_AutoresponderHandlerLib $lib */
        $lib = $this->api->load->library( 'autoresponder_handler' );
        $user = ncore_getUserById( $user_id );
        if (!$user) {
            return array();
        }

        return $lib->getPersonalData( $user->user_email );
    }
    
    private function _renderTextHeadline( $text, $level )
    {
        $indent = $this->_renderTextIndent( $level );
        
        $chars = array( '=', '-', '.' );
        
        $len = function_exists( 'mb_strlen' )
             ? mb_strlen( $text )
             : strlen( $text );
        
        $char = ncore_retrieve( $chars, $level, false );
        if ($char)
        {
            $text .= "\n" . $indent . str_pad( '', $len, $char );
        }
        
        return "\n$indent$text\n";
    }
    
    private function _renderTextLabel( $label, $level )
    {
        $indent = $this->_renderTextIndent( $level );
        
        return $indent.$label . ':';
    }
    
    private function _renderTextIndent( $level )
    {
        return str_pad( '', $level*2, ' ' );
    }
    
    private function _renderText( $data, $level = 0 )
    {
        $is_one_element_array = is_array( $data ) && count( $data )  == 1 && isset( $data[0] );
        if ($is_one_element_array) {
            $data = $data[0];
        }

        $this->api->load->helper( 'array' );        
        $is_numeric_array = ncore_isNumericArray( $data );
        
        if ($is_numeric_array)
        {
            $text = '';
            foreach ($data as $index => $one)
            {
                $text .= $this->_renderTextHeadline( _digi( '%s. entry', $index+1 ), $level );
                
                $text .= $this->_renderText( $one, $level+1 );
            }
            
            return $text;
        }
        
        if (!is_array($data) && !is_object($data))
        {
            return $this->_renderTextIndent( $level ) . $data;
        }
        
        $text = '';
        foreach ( (array) $data as $k => $v)
        {
            $has_sub_section = is_array($v) || is_object($v);
            
            if ($has_sub_section) {
                $text .= $this->_renderTextHeadline( $k, $level )
                       . $this->_renderText( $v, $level+1 )
                       . "\n";
            }
            else
            {
                $text .= $this->_renderTextLabel( $k, $level+1 ) . $v . "\n";
            }
        }
         
        return $text;
    }
                
                
}