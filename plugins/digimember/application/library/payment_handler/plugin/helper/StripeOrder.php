<?php
require_once dirname(__FILE__) . '/StripeOrderItem.php';

/**
 * Class StripeOrder
 */
class StripeOrder
{
    /** @var string */
    public $id;
    /** @var string */
    public $object;
    /** @var float */
    public $amount;
    /** @var float */
    public $amount_returned;
    /** @var string */
    public $application;
    /** @var string */
    public $application_fee;
    /** @var string */
    public $charge;
    /** @var int */
    public $created;
    /** @var string */
    public $currency;
    /** @var string */
    public $customer;
    /** @var string */
    public $email;
    /** @var StripeOrderItem[] */
    public $items;
    /** @var bool */
    public $livemode;
    /** @var string */
    public $status;
    /** @var int */
    public $updated;
}