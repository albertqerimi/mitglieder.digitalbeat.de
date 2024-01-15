<?php
require_once dirname(__FILE__) . '/StripeApi.php';
require_once dirname(__FILE__) . '/StripeResponse.php';

define('STRIPE_MAPPING_ORDER', 'order_product');
define('STRIPE_MAPPING_METADATA', 'metadata');
define('STRIPE_MAPPING_MANUAL', 'manual');

/**
 * Class StripeResponseParser
 */
class StripeResponseParser
{
    /** @var string|null */
    private $apiKey;
    /** @var string */
    private $mappingType;
    /** @var string */
    private $metadataKey;
    /** @var int */
    private $manualProductId;

    /** @var StripeApi|null */
    private $api;

    /** @var StripeOrder|null */
    private $order;
    /** @var StripeCustomer|null */
    private $customer;

    /**
     * StripeResponseProcessor constructor.
     * @param string $apiKey
     * @param string $mappingType
     * @param string $metadataKey
     * @param int    $manualProductId
     */
    public function __construct($apiKey, $mappingType, $metadataKey, $manualProductId)
    {
        $this->apiKey = $apiKey;
        $this->mappingType = $mappingType;
        $this->metadataKey = $metadataKey;
        $this->manualProductId = $manualProductId;
        if ($this->apiKey) {
            $this->api = new StripeApi($this->apiKey);
        }
    }

    /**
     * @param array $rawResponse [Needs to be flattened array in dot notation]
     * @return string|StripeResponse
     */
    public function parseRawResponse($rawResponse)
    {
        $this->hydrateApiData($rawResponse);

        $response = new StripeResponse();
        $response->event_type = $this->getEventType($rawResponse);

        $product_code = $this->getProductCode($rawResponse);
        if (is_null($product_code)) {
            return _digi3('%s could not be determined for this API response', 'Product code');
        }
        $response->product_code = $product_code;

        $email = $this->getCustomerEmail($rawResponse);
         if (is_null($email)) {
            return _digi3('%s could not be determined for this API response', 'Email address');
        }
        $response->email = $email;

        $addressData = $this->getCustomerAddress($rawResponse);
        $name = ncore_retrieve($addressData, 'first_name');
        if ($name) {
            $nameExplode = explode(' ', $name);
            $addressData['first_name'] = array_shift($nameExplode);
            $addressData['last_name'] = join(' ', $nameExplode);
        }
        foreach ($addressData as $key => $value) {
            $response->$key = $value;
        }

        $orderId = ncore_retrieve($rawResponse, 'data.object.order', false);
        $response->order_id = $orderId;

        return $response;
    }

    /**
     * @param array $rawResponse
     * @return string|null
     */
    private function getEventType($rawResponse)
    {
        switch (ncore_retrieve($rawResponse, 'type')) {
            case 'charge.succeeded':
            case 'charge.dispute.funds_reinstated':
                return EVENT_SALE;
            case 'charge.refunded':
            case 'charge.dispute.funds_withdrawn':
                return EVENT_REFUND;
            case 'charge.failed':
            case 'charge.dispute.created':
                return EVENT_MISSED_PAYMENT;
            default:
                return null;
        }
    }

    /**
     * @param array $rawResponse
     * @return array
     */
    private function getCustomerAddress($rawResponse)
    {
        $addressMap = [
            'city' => 'city',
            'country' => 'country',
            'first_name' => 'name',
            'state' => 'state',
            'street' => 'line1',
            'zip_code' => 'postal_code',
        ];

        $addressData = [];
        foreach ($addressMap as $key => $responseKey) {
            $maybeValue = ncore_retrieve($rawResponse, 'data.object.shipping.' . $responseKey);
            if (!is_null($maybeValue) && $maybeValue) {
                $addressData[$key] = $maybeValue;
                continue;
            }
            $maybeValue = ncore_retrieve($rawResponse, 'data.object.source.' . $responseKey);
            if (!is_null($maybeValue) && $maybeValue) {
                $addressData[$key] = $maybeValue;
                continue;
            }
        }
        if (count($addressData) == count($addressMap)) {
            return $addressData;
        }

        if ($this->customer && count($this->customer->sources->data)) {
            $source = $this->customer->sources->data[0];
            foreach ($addressMap as $key => $responseKey) {
                $maybeValue = ncore_retrieve($source, 'address_' . ($responseKey == 'postal_code' ? 'zip' : $responseKey));
                if (!is_null($maybeValue) && $maybeValue) {
                    $addressData[$key] = $maybeValue;
                }
            }
        }

        return $addressData;
    }

    /**
     * @param array $rawResponse
     * @return string|null
     */
    private function getCustomerEmail($rawResponse)
    {
        $maybeEmail = ncore_retrieve($rawResponse, 'data.object.email');
        if ($maybeEmail) {
            return $maybeEmail;
        }
        $maybeEmail = ncore_retrieve($rawResponse, 'data.object.name');
        if ($maybeEmail) {
            return $maybeEmail;
        }
        $maybeEmail = ncore_retrieve($rawResponse, 'data.object.receipt_email');
        if ($maybeEmail) {
            return $maybeEmail;
        }
        if ($this->order && $this->order->email) {
            return $this->order->email;
        }
        if ($this->customer && $this->customer->email) {
            return $this->customer->email;
        }
        return null;
    }

    /**
     * @param array $rawResponse
     * @return array|int|null
     */
    private function getProductCode($rawResponse)
    {
        switch ($this->mappingType) {
            case STRIPE_MAPPING_MANUAL:
                return $this->manualProductId;
            case STRIPE_MAPPING_METADATA:
                return ncore_retrieve($rawResponse, 'data.object.metadata.' . $this->metadataKey);
            case STRIPE_MAPPING_ORDER:
                if (is_null($this->order)) {
                    return null;
                }
                $skus = [];
                foreach ($this->order->items as $item) {
                    if ($item->type == 'sku') {
                        $skus[] = $item->parent;
                    }
                }
                return count($skus) ? $skus : null;
        }
        return null;
    }

    private function hydrateApiData($rawResponse)
    {
        if (is_null($this->api)) {
            return;
        }
        $orderId = ncore_retrieve($rawResponse, 'data.object.order');
        if ($orderId && is_null($this->order)) {
            $this->order = $this->api->fetchOrder($orderId);
            if ($this->order && $this->order->customer) {
                $this->customer = $this->api->fetchCustomer($this->order->customer);
                $this->api = null;
                return;
            }
        }
        $customerId = ncore_retrieve($rawResponse, 'data.object.customer');
        if ($customerId && is_null($this->customer)) {
            $this->customer = $this->api->fetchCustomer($customerId);
        }
        $this->api = null;
        return;
    }
}