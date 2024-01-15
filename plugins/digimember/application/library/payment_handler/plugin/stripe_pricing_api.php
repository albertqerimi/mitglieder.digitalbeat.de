<?php

use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;
use Stripe\InvoiceLineItem;
use Stripe\Price;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\WebhookEndpoint;
use Stripe\WebhookSignature;

require_once(__DIR__ . '/helper/stripe_php/init.php');

/**
 * Class digimember_PaymentHandler_PluginStripePricingApi
 */
class digimember_PaymentHandler_PluginStripePricingApi extends digimember_PaymentHandler_PluginBase
{
    const API_VERSION = '2020-08-27';

    /**
     * digimember_PaymentHandler_PluginStripePricingApi constructor.
     *
     * @param digimember_PaymentHandlerLib $parent
     * @param array                        $meta
     */
    public function __construct(digimember_PaymentHandlerLib $parent, $meta)
    {
        // Create webhook and inject webhook_secret field
        if (isset($_POST['stripe_create_webhook_' . ncore_retrieve($meta, 'id', '')]) && key_exists('data', $meta)) {
            try {
                $secret = $this->createStripeWebhook(ncore_retrieve($meta['data'], 'stripe_pricing_api_secret_key'), ncore_retrieve($meta, 'id', ''));
                if ($secret) {
                    $meta['data']['stripe_pricing_api_webhook_secret'] = $secret;
                    /** @var digimember_PaymentData $model */
                    $model = dm_api()->load->model('data/payment');
                    $model->update($meta['id'], [
                        'data_serialized' => serialize($meta['data']),
                    ]);
                    $_POST['ncore_sub_data_stripe_pricing_api_webhook_secret' . $meta['id']] = $secret;
                }
            } catch (ApiErrorException $errorException) {
                var_dump($errorException->getMessage());
            }
        }
        parent::__construct($parent, $meta);
        if (isset($_POST['stripe_delete_webhook_' . ncore_retrieve($meta, 'id', '')]) && key_exists('data', $meta)) {
            try {
                $this->deleteStripeWebhook();
                $_POST['ncore_sub_data_stripe_pricing_api_webhook_secret' . $meta['id']] = '';
                $meta['data']['stripe_pricing_api_webhook_secret'] = '';
                /** @var digimember_PaymentData $model */
                $model = dm_api()->load->model('data/payment');
                $model->update($meta['id'], [
                    'data_serialized' => serialize($meta['data']),
                ]);
            } catch (ApiErrorException $errorException) {
                var_dump($errorException->getMessage());
            }
        }
    }

    /**
     * @return string
     */
    public function type()
    {
        return 'stripe_pricing_api';
    }

    /**
     * @return array
     */
    protected function methods()
    {
        return [METHOD_INPUT_JSON];
    }

    /**
     * @return array
     */
    public function formMetas()
    {
        $invalid_access_data_msg = _digi3('Enter your %s credentials and save.', 'Stripe');
        $metas = [];

        $metas[] = [
            'name' => 'secret_key',
            'type' => 'text',
            'value' => $this->data('secret_key'),
            'label' => _digi3('Stripe Secret Key'),
        ];

        try {
            $existingWebhook = $this->getStripeWebhook();
            if ($this->data('secret_key')) {
                if (!$existingWebhook) {
                    $metas = array_merge([
                        [
                            'name' => 'create_webhook',
                            'type' => 'html',
                            'html' => sprintf('
<div class="dm-col-md-8 dm-col-sm-9 dm-col-xs-12">
    <button class="dm-btn dm-btn-primary" style="margin-left: auto;" type="submit" name="%s">%s</button>
</div>
                                ', 'stripe_create_webhook_' . $this->meta('id', ''), _digi3('Establish connection to Stripe')),
                        ],
                    ], $metas);
                } else {
                    $metas = array_merge([
                        [
                            'name' => 'create_webhook',
                            'type' => 'html',
                            'html' => sprintf('
<div class="dm-col-md-8 dm-col-sm-9 dm-col-xs-12">
    <button class="dm-btn dm-btn-secondary" style="margin-left: auto;" type="submit" name="%s">%s</button>
</div>
                                ', 'stripe_delete_webhook_' . $this->meta('id', ''), _digi3('Disconnect from Stripe')),
                        ],
                    ], $metas);
                }
            }


            $stripe_products = $this->getStripeProducts();
            if (count($stripe_products)) {
                $metas[] = [
                    'name' => 'webhook_secret',
                    'type' => 'text',
                    'value' => $this->data('webhook_secret'),
                    'label' => _digi3('Webhook Secret'),
                ];
                $metas[] = [
                    'name' => 'product_code_map',
                    'type' => 'map',
                    'label' => _digi3('Stripe products'),
                    'array' => $this->productOptions(),
                    'select_options' => $stripe_products,
                    'html' => $invalid_access_data_msg,
                ];
            } else {
                $metas[] = [
                    'name' => 'product_code_map',
                    'type' => 'html',
                    'label' => _digi3('Stripe products'),
                    'html' => $invalid_access_data_msg,
                ];
            }
        } catch (ApiErrorException $errorException) {
            $metas[] = [
                'name' => 'error',
                'type' => 'html',
                'html' => sprintf('<div class="error ncore_error"><p><b style="font-size: 1.1rem;">%s:</b><br /><br/><code style="padding: 10px;">%s</code></p></div>', _digi3('Error connecting to the Stripe API'), $errorException->getMessage()),
            ];
        }

        return $metas;
    }

    /**
     * @return array
     * @throws ApiErrorException
     */
    protected function getStripeProducts()
    {
        $key = $this->data('secret_key');
        if ($key) {
            $stripe = $this->stripeClient($key);
            $products = $stripe->prices->all(['active' => true, 'limit' => 100]);
            $assoc = [];
            foreach ($products as $price) {
                /** @var Price $price */
                $product = $stripe->products->retrieve($price->product);
                $unit_price = $price->unit_amount > 0 ? (float) $price->unit_amount / 100 : 0;
                $amount = (new NumberFormatter(get_locale(), NumberFormatter::PATTERN_SEPARATOR_SYMBOL))->formatCurrency($unit_price, $price->currency);
                if ($price->recurring) {
                    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
                    $recurring = ' / ' . $price->recurring->interval_count . ' ' . ucfirst($price->recurring->interval);
                    $amount .= $recurring;
                }
                $name = $price->nickname ? sprintf('%s (%s)', $price->nickname, $amount) : $amount;
                if ($product) {
                    $name = sprintf("%s: %s", $product->name, $name);
                }
                $assoc[$price->id] = $name;
            }
            asort($assoc);
            return $assoc;
        } else {
            return [];
        }
    }

    /**
     * @return WebhookEndpoint|null
     * @throws ApiErrorException
     */
    protected function getStripeWebhook()
    {
        $key = $this->data('secret_key');
        if ($key) {
            $url = $this->getIpnUrl();
            $stripe = $this->stripeClient($key);
            $endpoints = $stripe->webhookEndpoints->all();
            foreach ($endpoints as $endpoint) {
                /** @var WebhookEndpoint $endpoint */
                if ($endpoint->url == $url) {
                    return $endpoint;
                }
            }
        }
        return null;
    }

    /**
     * @return bool
     * @throws ApiErrorException
     */
    protected function deleteStripeWebhook()
    {
        $key = $this->data('secret_key');
        if (!$key) {
            return false;
        }
        $webhook = $this->getStripeWebhook();
        if (!$webhook) {
            return false;
        }
        $stripe = $this->stripeClient($key);
        $stripe->webhookEndpoints->delete($webhook->id);
        return true;
    }

    /**
     * @param string $secret_key
     * @param string $id
     *
     * @return string|null
     * @throws ApiErrorException
     */
    protected function createStripeWebhook($secret_key, $id)
    {
        if ($secret_key) {
            /** @var digimember_LinkLogic $link_model */
            $link_model = dm_api()->load->model('logic/link');
            $url = $link_model->ipnCall($id, false, '&');
            $stripe = $this->stripeClient($secret_key);
            $endpoint = $stripe->webhookEndpoints->create([
            'enabled_events' => [
                    'invoice.paid',
                    'invoice.marked_uncollectible',
                    'invoice.payment_failed',
                    'customer.subscription.deleted',
                    'customer.subscription.updated',
                    'checkout.session.completed',
                    'charge.refunded',
                ],
                'url' => $url,
                'description' => _digi3('Automatically created by DigiMember'),
                'api_version' => static::API_VERSION,
            ]);
            return $endpoint->secret;
        }
        return null;
    }

    /**
     * @return string
     */
    private function getIpnUrl()
    {
        /** @var digimember_LinkLogic $link_model */
        $link_model = $this->api->load->model('logic/link');
        return $link_model->ipnCall($this->meta('id'), false, '&');
    }

    /**
     * @param $key
     *
     * @return StripeClient
     */
    private function stripeClient($key)
    {
        static $cache = [];

        $result =& $cache[$key];
        if ($result) {
            return $result;
        }
        $result = new StripeClient([
            'api_key' => $key,
            'stripe_version' => static::API_VERSION,
        ]);

        return $result;
    }

    /**
     * @return array
     */
    protected function eventMap()
    {
        return [
            'invoice.paid' => EVENT_SALE,
            'invoice.marked_uncollectible' => EVENT_MISSED_PAYMENT,
            'invoice.payment_failed' => EVENT_REFUND,
            'customer.subscription.deleted' => EVENT_REFUND,
            'checkout.session.completed' => EVENT_SALE,
            'charge.refunded' => EVENT_REFUND,
        ];
    }

    /**
     * @throws Exception
     */
    public function validateRequestParams()
    {
        parent::validateRequestParams();
        $webhook_secret = $this->data('webhook_secret');
        if (!$webhook_secret) {
            return;
        }
        $key = $this->data('secret_key');
        if (!$key) {
            $this->exception(_digi3('Secret key is not set for stripe'));
        }
        if (!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            $this->exception(_digi3('Received Stripe IPN request without signature'));
        }
        if (!WebhookSignature::verifyHeader(
            file_get_contents('php://input'),
            $_SERVER['HTTP_STRIPE_SIGNATURE'],
            $webhook_secret,
            300
        )) {
            $this->exception(_digi3('Could not verify signature for Stripe IPN request'));
        }
    }

    /** @var Invoice */
    private $stripeInvoice = null;

    /**
     * @return false|Invoice
     * @throws Exception
     */
    private function getInvoice()
    {
        if ($this->stripeInvoice !== null) {
            return $this->stripeInvoice;
        }
        if (!ncore_retrieve($this->eventMap(), ncore_retrieveJSON('type'))) {
            $this->stripeInvoice = false;
            return false;
        }
        if ($this->stripeInvoice == null) {
            $data = ncore_retrieveJSON('data', []);
            $object = ncore_retrieve($data, 'object', []);
            $id = ncore_retrieve($object, 'id');
            if (!$id) {
                $this->stripeInvoice = false;
                return false;
            }
            $key = $this->data('secret_key');
            if (!$key) {
                return false;
            }
            $stripe = $this->stripeClient($key);
            try {
                $invoice = $stripe->invoices->retrieve($id);
            } catch (ApiErrorException $errorException) {
                $this->exception(_digi3('Error connecting to the Stripe API') . ': ' . $errorException->getMessage());
                return false;
            }
            $this->stripeInvoice = $invoice;
        }
        return $this->stripeInvoice;
    }

    /**
     * @return null|false|Subscription
     * @throws Exception
     */
    private function getSubscription()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $type = ncore_retrieveJSON('type');
        if ($type == 'customer.subscription.updated' || $type == 'customer.subscription.deleted') {
            $data = ncore_retrieveJSON('data');
            $object = ncore_retrieve($data, 'object');
            $id = ncore_retrieve($object, 'id');
            if (!$id) {
                $cache = false;
                return false;
            }
            $key = $this->data('secret_key');
            if (!$key) {
                $cache = false;
                return false;
            }
            $stripe = $this->stripeClient($key);
            try {
                $subscription = $stripe->subscriptions->retrieve($id);
                $this->stripeInvoice = $stripe->invoices->retrieve($subscription->latest_invoice);
            } catch (ApiErrorException $errorException) {
                $this->exception(_digi3('Error connecting to the Stripe API') . ': ' . $errorException->getMessage());
                $cache = false;
                return false;
            }
            $cache = $subscription;
        }
        return $cache;
    }

    /**
     * @return null|false|Subscription
     * @throws Exception
     */
    private function getSession()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $type = ncore_retrieveJSON('type');
        if ($type == 'checkout.session.completed') {
            $data = ncore_retrieveJSON('data');
            $object = ncore_retrieve($data, 'object');
            $id = ncore_retrieve($object, 'id');
            if (!$id) {
                $cache = false;
                return false;
            }
            $key = $this->data('secret_key');
            if (!$key) {
                $cache = false;
                return false;
            }
            $stripe = $this->stripeClient($key);
            try {
                $session = $stripe->checkout->sessions->retrieve($id,[
                    'expand' => ['payment_intent','payment_link','subscription','line_items', 'customer'],
                ]);
            } catch (ApiErrorException $errorException) {
                $this->exception(_digi3('Error connecting to the Stripe API') . ': ' . $errorException->getMessage());
                $cache = false;
                return false;
            }
            $cache = $session;
        }
        return $cache;
    }

    /**
     * @return null|false|Subscription
     * @throws Exception
     */
    private function getCharge()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $type = ncore_retrieveJSON('type');
        if ($type == 'charge.refunded') {
            $data = ncore_retrieveJSON('data');
            $object = ncore_retrieve($data, 'object');
            $id = ncore_retrieve($object, 'id');
            if (!$id) {
                $cache = false;
                return false;
            }
            $key = $this->data('secret_key');
            if (!$key) {
                $cache = false;
                return false;
            }
            $stripe = $this->stripeClient($key);
            try {
                $charge = $stripe->charges->retrieve($id, [
                    'expand' => ['payment_intent'],
                ]);
            } catch (ApiErrorException $errorException) {
                $this->exception(_digi3('Error connecting to the Stripe API') . ': ' . $errorException->getMessage());
                $cache = false;
                return false;
            }
            $cache = $charge;
        }
        return $cache;
    }

    /**
     * @param       $variable_name
     * @param false $required
     * @param false $array_allowed
     * @param false $do_split_mapped_and_unmapped_value
     *
     * @return array|array[]|false|mixed|string
     * @throws Exception
     */
    public function getParam($variable_name, $required = false, $array_allowed = false, $do_split_mapped_and_unmapped_value = false)
    {
        $requestType = $this->mapStripeRequestType();

        if ($requestType) {
            switch ($requestType) {
                case 'invoice':
                case 'customer':
                    return $this->getParamForInvoice($variable_name, $required, $array_allowed, $do_split_mapped_and_unmapped_value);
                case 'checkout':
                    return $this->getParamForCheckout($variable_name, $required, $array_allowed, $do_split_mapped_and_unmapped_value);
                case 'charge':
                    return $this->getParamForCharge($variable_name, $required, $array_allowed, $do_split_mapped_and_unmapped_value);
                default:
                    return $this->getParamForInvoice($variable_name, $required, $array_allowed, $do_split_mapped_and_unmapped_value);
            }
        }
    }

    public function getParamForCheckout($variable_name, $required = false, $array_allowed = false, $do_split_mapped_and_unmapped_value = false)
    {
        $session = $this->getSession();

        if ($variable_name == 'event_type') {
            $type = ncore_retrieveJSON('type');
            $event_type = ncore_retrieve($this->eventMap(), $type);
            if ($event_type) {
                return $event_type;
            }
            return null;
        }

        if (!$session) {
            $this->exception('Checkout Session not found.');
        }

        $billingInformation = $this->getSessionBillingInformation($session);
        switch ($variable_name) {
            case 'order_id':
                if ($session->payment_intent) {
                    return $session->payment_intent->id;
                }
            case 'email':
                return $billingInformation['email'];
            case 'first_name':
                return $billingInformation['name'] ? explode(' ', $billingInformation['name'])[0] : '';
            case 'last_name':
                return $billingInformation['name'] ? explode(' ', $billingInformation['name'])[1] : '';
            case 'street':
                return ncore_retrieve($billingInformation, 'line1');
            case 'zip_code':
                return ncore_retrieve($billingInformation, 'postal_code');
            case 'state':
                return ncore_retrieve($billingInformation, 'state');
            case 'city':
                return ncore_retrieve($billingInformation, 'city');
            case 'country':
                return ncore_retrieve($billingInformation, 'country');
            case 'product_code':
                $product_codes = [];
                foreach ($session->line_items as $line) {
                    $code = ncore_retrieve($this->productCodeMap(), $line->price->id);
                    if ($code) {
                        $product_codes[] = $code;
                    }
                }
                return [$product_codes, []];
            case 'quantity':
                $amounts = [];
                foreach ($session->line_items as $line) {
                    /** @var InvoiceLineItem $line */
                    $amounts[] = $line->quantity;
                }
                return $amounts;
            default:
                return ncore_retrieve(ncore_retrieve(ncore_retrieveJSON('data'), 'object'), $variable_name);
        }
    }

    public function getParamForCharge($variable_name, $required = false, $array_allowed = false, $do_split_mapped_and_unmapped_value = false)
    {
        $charge = $this->getCharge();

        if ($variable_name == 'event_type') {
            $type = ncore_retrieveJSON('type');
            $event_type = ncore_retrieve($this->eventMap(), $type);
            if ($event_type) {
                return $event_type;
            }
            return null;
        }

        if (!$charge) {
            $this->exception('Charge not found.');
        }

        //$billingInformation = $this->getSessionBillingInformation($session);
        switch ($variable_name) {
            case 'order_id':
                if ($charge) {
                    return $charge->payment_intent->id;
                }
            default:
                return ncore_retrieve(ncore_retrieve(ncore_retrieveJSON('data'), 'object'), $variable_name);
        }
    }

    public function getSessionBillingInformation($session) {
        $billingDetails = false;
        $charges = $session->payment_intent->charges;
        foreach ($charges->data as $charge) {
            $billingDetails = $charge->billing_details;
        }
        if ($billingDetails) {
            return [
                "city" => $billingDetails->address->city,
                "country" => $billingDetails->address->country,
                "line1" => $billingDetails->address->line1,
                "line2" => $billingDetails->address->line2,
                "postal_code" => $billingDetails->address->postal_code,
                "state" => $billingDetails->address->state,
                "email" => $billingDetails->email,
                "name" => $billingDetails->name,
            ];
        }
        return false;
    }

    public function getParamForInvoice($variable_name, $required = false, $array_allowed = false, $do_split_mapped_and_unmapped_value = false)
    {
        $subscription = $this->getSubscription();
        $invoice = $this->getInvoice();

        if ($variable_name == 'event_type') {
            $type = ncore_retrieveJSON('type');
            $event_type = ncore_retrieve($this->eventMap(), $type);
            if ($event_type) {
                return $event_type;
            } else if ($subscription instanceof Subscription) {
                if ($subscription->status == Subscription::STATUS_INCOMPLETE_EXPIRED) {
                    return EVENT_REFUND;
                } else if ($subscription->status == Subscription::STATUS_CANCELED) {
                    return EVENT_SALE;
                } else if ($subscription->status == Subscription::STATUS_ACTIVE) {
                    return EVENT_SALE;
                } else if ($subscription->status == Subscription::STATUS_PAST_DUE) {
                    return EVENT_MISSED_PAYMENT;
                }
            }
            return null;
        }

        if (!$invoice) {
            $this->exception(_digi3('Stripe invoice object not found'));
        }

        switch ($variable_name) {
            case 'order_id':
                if ($subscription) {
                    return $subscription->id;
                }
                if ($invoice->subscription) {
                    $key = $this->data('secret_key');
                    $stripe = $this->stripeClient($key);
                    $subscription = null;
                    try {
                        $subscription = $stripe->subscriptions->retrieve($invoice->subscription);
                    } catch (ApiErrorException $errorException) {
                    }
                    if ($subscription) {
                        return $subscription->id;
                    }
                }
                return $invoice->id;
            case 'email':
                return $invoice->customer_email;
            case 'first_name':
                return $invoice->customer_name ? explode(' ', $invoice->customer_name)[0] : '';
            case 'street':
                return ncore_retrieve($invoice->customer_address, 'line1');
            case 'zip_code':
                return ncore_retrieve($invoice->customer_address, 'postal_code');
            case 'state':
                return ncore_retrieve($invoice->customer_address, 'state');
            case 'city':
                return ncore_retrieve($invoice->customer_address, 'city');
            case 'country':
                return ncore_retrieve($invoice->customer_address, 'country');
            case 'product_code':
                $product_codes = [];
                foreach ($invoice->lines as $line) {
                    /** @var InvoiceLineItem $line */
                    $code = ncore_retrieve($this->productCodeMap(), $line->price->id);
                    if ($code) {
                        $product_codes[] = $code;
                    }
                }
                return [$product_codes, []];
            case 'quantity':
                $amounts = [];
                foreach ($invoice->lines as $line) {
                    /** @var InvoiceLineItem $line */
                    $amounts[] = $line->amount;
                }
                return $amounts;
            default:
                return ncore_retrieve(ncore_retrieve(ncore_retrieveJSON('data'), 'object'), $variable_name);
        }
    }

    public function mapStripeRequestType() {
        $type = ncore_retrieveJSON('type');
        if ($type && $type != '') {
            $typeSplit = explode(".", $type);
            switch ($typeSplit[0]) {
                case 'invoice':
                    return 'invoice';
                case 'customer':
                    return 'customer';
                case 'checkout':
                    return 'checkout';
                case 'charge':
                    return 'charge';
                default:
                    return false;
            }
        }
        return false;
    }


    public function instructions()
    {
        $instructions = parent::instructions();

        $instructions[] = _digi3('Log into your Stripe account. If you don\'t have one yet, create one.');
        $instructions[] = _digi3('<strong>In Stripe: </strong> Click on <i>Developers > API Keys</i>. Reveal and copy the Secret key from the <i> Standard keys</i> section.');
        $instructions[] = _digi3("<strong>In DigiMember:</strong> Paste the secret key here into the Stripe secret key field and save." );
        $instructions[] = _digi3("Click: Establish Connection to Stripe.");
        $instructions[] = _digi3("If the Webhook secret has not been set automatically, switch to Stripe again and click <i> Developers > Webhooks</i>. There should be an Endpoint with the same URL als the notification URL in DigiMember.");
        $instructions[] = _digi3("Select that endpoint and reveal and copy the webhook secret from the <i> Signing secret </i> section. Paste it into DigiMember and save.");
        $instructions[] = _digi3("Select the Stripe products you want to connect with your DigiMember product and save again. <br> Your connection is now set, new Stripe orders for the selected products will now create orders in DigiMember.");


        return $instructions;
    }
}
