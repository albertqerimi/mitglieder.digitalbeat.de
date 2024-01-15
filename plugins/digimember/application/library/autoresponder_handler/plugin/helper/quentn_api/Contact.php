<?php
namespace Quentn;

/**
 * Class Contact
 * @package FlyTools\Plugins\WebinarFly\Helpers\Quentn
 */
class Contact
{
    /**
     * @var int
     */
    public $id = -1;
    /**
     * @var string
     */
    public $first_name = '';
    /**
     * @var string
     */
    public $family_name = '';
    /**
     * @var string
     */
    public $mail = '';
    /**
     * @var int
     */
    public $mail_status = 4;
    /**
     * @var int[]
     */
    public $terms = [];

    /**
     * Contact constructor.
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