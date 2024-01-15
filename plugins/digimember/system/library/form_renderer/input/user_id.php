<?php

class ncore_FormRenderer_InputUserId extends ncore_FormRenderer_InputBase
{
    protected function metaDefaults()
    {
        return parent::metaDefaults();
    }

    protected function defaultRules()
    {
        return 'trim|user_id';
    }

    public function setValue( $value )
    {
        if (!$value)
        {
            $value      = '';
            $this->user = false;
        }
        else
        {
            $this->user = $this->_resolveUser( $value );

            if ($this->user)
            {
                $value = ncore_retrieve( $this->user, array( 'ID', 'id' ) );
            }
        }

        parent::setValue( $value );
    }

    protected function onPostedValue( $field_name, &$value )
    {
        parent::onPostedValue( $field_name, $value );

        $this->user = $this->_resolveUser( $value );

        if ($this->user)
        {
            $value = $this->user->ID;
        }
    }


    protected function renderInnerReadonly()
    {
        return $this->_displayValue( $this->user );
    }

    protected function renderInnerWritable()
    {
        $html_id = $this->htmlId();

        $postname = $this->postname();

        $attributes = array( 'id' => $html_id );

        $value = $this->_displayValue( $this->user );

        if (!$value) {
            $value = '';
        }

        $this->meta2attributes( $attributes, array( 'size', 'class', 'maxlength', 'placeholder', 'readonly') );

        if ($this->hasRule( 'lower_case' )){
            $js = "this.value=this.value.toLowerCase()";
            $jsAttr=array(
                'onchange' => $js,
                'onkeyup' => $js,
            );
            $attributes = ncore_mergeAttributes( $attributes, $jsAttr );
        }

        return ncore_htmlTextInput( $postname, $value, $attributes );
    }

    //
    // private
    //
    private $user = false;

    private function _resolveUser( $value )
    {
        if (!$value) {
            return false;
        }

        static $cache;

        $user =& $cache[ $value ];
        if (isset($user)) {
            return $user;
        }

        $keys_to_try = array( 'id', 'name', 'login', 'email', 'slug' );
        foreach ($keys_to_try as $key)
        {
            $user = ncore_getUserBy( $key, $value );
            if ($user) {
                $user_id = ncore_retrieve( $user, array( 'ID','id' ) );
                $cache[ $user_id ] = $user;
                return $user;
            }
        }

        return $user = false;
    }

    private function _displayValue( $user )
    {
        return ncore_retrieve( $user, array( 'user_login', 'user_name', 'user_email', 'user_id' ), '' );
    }
}



