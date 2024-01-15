<?php
namespace Quentn;

/**
 * Class Recipient
 * @package FlyTools\Plugins\WebinarFly\Helpers\Quentn
 */
class Recipient
{
    /**
     * @var Contact
     */
    private $contact;
    /**
     * @var array
     */
    private $subData;
    /**
     * @var string
     */
    private $preSuf = '@';

    /**
     * Recipient constructor.
     * @param Contact $contact
     * @param array   $subData
     * @param string  $preSuf
     */
    public function __construct(Contact $contact, $subData = [], $preSuf = '@')
    {
        $this->contact = $contact;
        $this->subData = $subData;
        $this->preSuf = $preSuf;
    }

    /**
     * @return array
     */
    public function apiRecord()
    {
        $subData = [];
        foreach ($this->subData as $key => $val) {
            $subData[$this->preSuf . $key . $this->preSuf] = $val;
        }
        return [
            'id' => $this->contact->id,
            'substitution_data' => $subData,
        ];
    }
}