<?php

class ncore_TableRenderer_TypeYesNoBit extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $value = $this->value( $row );

        $icon = $value === 'Y'
              ? 'ok-circled'
              : 'cancel-circled';

        $tooltip = $value === 'Y'
                 ? $this->meta( 'text_active', _ncore( 'active' ) )
                 : $this->meta( 'text_inactive', _ncore( 'not active' ) );

        $tooltip = $this->_parsePlaceholders( $tooltip, $row );

        return ncore_icon( $icon, $tooltip, null, [], $value === 'Y' ? 'success' : 'error' );
    }

    private $find    = false;

    private function _parsePlaceholders( $tooltip, $row )
    {
        $placeholders = $this->meta( 'text_placeholders' );
        if (!$placeholders) {
            return $tooltip;
        }

        $have_placeholders = strpos( $tooltip, '[' ) !== false;
        if (!$have_placeholders) {
            return $tooltip;
        }

        if (!$this->find)
        {
            $this->find = array();
            foreach ($placeholders as $placeholder => $meta)
            {
                $find[] = '[' . strtoupper( $placeholder ) . ']';
            }
        }

        $repl = array();
        foreach ($placeholders as $placeholder => $meta)
        {
            if (is_string($meta))
            {
                $meta = array( 'column' => $meta, 'function' => false );
            }

            $column   = $meta[ 'column' ];
            $callable = ncore_retrieve( $meta, 'function', false );

            $value = ncore_retrieve( $row, $column );

            if ($callable)
            {
                $value = call_user_func( $callable, $value );
            }

            $repl[] = $value;
        }

        return str_replace( $find, $repl, $tooltip );
    }
}