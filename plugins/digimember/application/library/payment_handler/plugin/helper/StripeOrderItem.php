<?php

/**
 * Class StripeOrderItem
 */
class StripeOrderItem
{
    /** @var string */
    public $object;
    /** @var int */
    public $amount;
    /** @var string */
    public $currency;
    /** @var string */
    public $description;
    /** @var string */
    public $parent;
    /** @var int */
    public $quantity;
    /** @var string */
    public $type;
}