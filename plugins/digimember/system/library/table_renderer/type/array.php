<?php

class ncore_TableRenderer_TypeArray extends ncore_TableRenderer_TypeBase
{

    protected function renderInner( $row )
    {
        $array = $this->options();

        $value = $this->value( $row );

        if (isset( $array[ $value ] )) {
            return $array[ $value ];
        }
        return $value;
    }

    public function renderWhere( $search_for )
    {
        $array = $this->options();

        $column = $this->column;
        $compare = $this->meta( 'compare', 'equal' );

        $values = array( $search_for );

        foreach ($array as $key => $label)
        {
            $label = trim(strtolower( $label ));

            $matches = $compare == 'equal'
                     ? $label == $search_for
                     : strpos( $label, $search_for ) !== false;

            if ($matches)
            {
                $values[] = $key;
            }
        }

        $where["$column IN"] = $values;

        return $where;
    }

    private function options()
    {
        $options = $this->meta( array('array','options'), array() );

        $options = ncore_resolveOptions( $options );

        if (is_array($options)) {

            $null_value = '';

            if (isset( $options[0])) {
                $null_value = $options[0];
            }
            if (isset( $options['0'])) {
                $null_value = $options['0'];
            }


            $options['0'] = $null_value;
            $options[0]   = $null_value;
            $options['']  = '';
            return $options;
        }

        return array();

    }
}