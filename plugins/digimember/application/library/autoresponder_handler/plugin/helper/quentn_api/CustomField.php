<?php

namespace Quentn;

/**
 * Class Term
 * @package Quentn
 */
class CustomField
{
    /**
     * @var int
     */
    public $field_id = -1;
    /**
     * @var string
     */
    public $field_name = '';
    /**
     * @var string
     */
    public $label = '';
    /**
     * @var string
     */
    public $description = '';
    /**
     * @var string
     */
    public $type = '';
    /**
     * @var bool
     */
    public $required = false;

    /**
     * Sender constructor.
     * @param array $values
     */
    public function __construct($values = [])
    {
        foreach ($values as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }
}