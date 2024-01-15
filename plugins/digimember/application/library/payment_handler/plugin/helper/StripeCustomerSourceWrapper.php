<?php
require_once dirname(__FILE__) . '/StripeCustomerSource.php';

/**
 * Class StripeCustomerSourceWrapper
 */
class StripeCustomerSourceWrapper
{
    /** @var string */
    public $object;
    /** @var StripeCustomerSource[] */
    public $data;
    /** @var bool */
    public $has_more;
    /** @var int */
    public $total_count;
    /** @var string */
    public $url;
}