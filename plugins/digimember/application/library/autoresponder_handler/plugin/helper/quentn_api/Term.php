<?php
namespace Quentn;

/**
 * Class Term
 * @package FlyTools\Plugins\WebinarFly\Helpers\Quentn
 */
class Term
{
    /**
     * @var int
     */
    public $id = -1;
    /**
     * @var string
     */
    public $name = '';
    /**
     * @var string
     */
    public $description = '';

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