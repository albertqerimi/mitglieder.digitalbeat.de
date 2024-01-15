<?php
namespace Quentn;

/**
 * Class Sender
 * @package FlyTools\Plugins\WebinarFly\Helpers\Quentn
 */
class Sender
{
    /**
     * @var int
     */
    public $id = -1;
    /**
     * @var string
     */
    public $email = '';
    /**
     * @var string
     */
    public $first_name = '';
    /**
     * @var string
     */
    public $last_name = '';
    /**
     * @var string
     */
    public $company = '';

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