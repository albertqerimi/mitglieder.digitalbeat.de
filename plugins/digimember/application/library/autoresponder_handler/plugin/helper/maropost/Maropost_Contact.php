<?php
require_once 'Maropost_Contact_Journey.php';
require_once 'Maropost_Contact_ListSubscription.php';

class Maropost_Contact
{
    /** @var int */
    public $id;
    /** @var int */
    public $account_id;
    /** @var string */
    public $email;
    /** @var string */
    public $first_name;
    /** @var string */
    public $last_name;
    /** @var string */
    public $phone;
    /** @var string */
    public $fax;
    /** @var string */
    public $created_at;
    /** @var string */
    public $updated_at;
    /** @var string */
    public $uid;
    /** @var Maropost_Contact_ListSubscription[] */
    public $list_subscriptions;
    /** @var Maropost_Contact_Journey[] */
    public $journeys;
}