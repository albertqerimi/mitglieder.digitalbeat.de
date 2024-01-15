<?php
namespace Quentn;

/**
 * Class Config
 * @package FlyTools\Plugins\WebinarFly\Helpers\Quentn
 */
class Config
{
    /**
     * @var string
     */
    private $apiUrl;
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var string
     */
    private $senderId = -1;

    /**
     * @return Config
     */
    public static function compose()
    {
        return new self();
    }

    /**
     * @param string|null $value
     * @return $this|string
     */
    public function apiUrl($value = null)
    {
        if ($value !== null) {
            $this->apiUrl = $value;
            return $this;
        }
        return $this->apiUrl;
    }

    /**
     * @param string|null $value
     * @return $this|string
     */
    public function apiKey($value = null)
    {
        if ($value !== null) {
            $this->apiKey = $value;
            return $this;
        }
        return $this->apiKey;
    }

    /**
     * @param int|null $value
     * @return $this|int
     */
    public function senderId($value = null)
    {
        if ($value !== null) {
            $this->senderId = $value;
            return $this;
        }
        return $this->senderId;
    }

}