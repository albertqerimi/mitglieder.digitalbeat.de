<?php

/**
 * Class StripeResponse
 */
class StripeResponse
{
    /** @var string */
    public $event_type;
    /** @var string */
    public $product_code;
    /** @var string */
    public $order_id;
    /** @var string */
    public $email;
    /** @var string */
    public $first_name;
    /** @var string */
    public $last_name;
    /** @var string */
    public $street;
    /** @var string */
    public $zip_code;
    /** @var string */
    public $state;
    /** @var string */
    public $city;
    /** @var string */
    public $country;
}