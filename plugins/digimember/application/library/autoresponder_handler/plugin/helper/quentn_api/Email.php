<?php
namespace Quentn;

/**
 * Class Email
 * @package FlyTools\Plugins\WebinarFly\Helpers\Quentn
 */
class Email
{
    /**
     * @var int
     */
    public $id = -1;
    /**
     * @var string
     */
    public $subject = '';
    /**
     * @var string
     */
    public $body_html = '';
    /**
     * @var string
     */
    public $body_text = '';
    /**
     * @var string
     */
    public $context = '';
    /**
     * @var null|int
     */
    public $sender_id = null;

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