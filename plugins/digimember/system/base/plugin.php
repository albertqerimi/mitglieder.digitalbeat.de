<?php

abstract class ncore_Plugin
{
    public function __construct( $parent, $type, $meta )
    {
        $this->parent = $parent;
        $this->api = $parent->api();
        $this->type = $type;
        $this->meta = (array) $meta;

        foreach ($this->metaDefaults() as $key => $value)
        {
            $must_set = !isset( $this->meta[ $key ] );
            if ($must_set) {
                $this->meta[ $key ] = $value;
            }
        }
    }

    /** @var ncore_ApiCore */
    protected $api;

    protected function meta( $name_or_names, $default = '' )
    {
        $names = is_array($name_or_names)
               ? $name_or_names
               : array( $name_or_names);

        foreach ($names as $name)
        {
            if (isset( $this->meta[$name] ))
            {
                return $this->meta[$name];
            }
        }

        if ($default === NCORE_ARG_REQUIRED)
        {
            $names_txt = implode( '/', $names);
            trigger_error( "Meta '$names_txt' is required!" );
        }

        return $default;
    }

    protected function metaDefaults()
    {
        return array();
    }

    protected function metas() {
        return $this->meta;
    }

    protected function parent()
    {
        return $this->parent;
    }

    protected function type()
    {
        return $this->type;
    }

    private $meta;
    private $parent;
    private $type;
}
