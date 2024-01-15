<?php
require_once dirname(__FILE__) . '/StripeCustomerSourceWrapper.php';

/**
 * Class StripeCustomer
 */
class StripeCustomer
{
    /** @var string */
    public $id;
    /** @var string */
    public $object;
    /** @var float */
    public $account_balance;
    /** @var string */
    public $created;
    /** @var string */
    public $currency;
    /** @var string */
    public $default_source;
    /** @var string */
    public $delinquent;
    /** @var string */
    public $description;
    /** @var string */
    public $discount;
    /** @var string */
    public $email;
    /** @var string */
    public $invoice_prefix;
    /** @var string */
    public $livemode;
    /** @var StripeCustomerSourceWrapper */
    public $sources;
    /** @var string */
    public $tax_info;
    /** @var string */
    public $tax_info_verification;
}