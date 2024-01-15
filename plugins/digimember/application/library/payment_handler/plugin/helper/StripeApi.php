<?php
require_once dirname(__FILE__) . '/../../../autoresponder_handler/plugin/helper/maropost/JsonMapper.php';
require_once dirname(__FILE__) . '/StripeCustomer.php';
require_once dirname(__FILE__) . '/StripeOrder.php';

/**
 * Class StripeApi
 */
class StripeApi
{
    /** @var string */
    private $apiKey;
    /** @var JsonMapper */
    private $jsonMapper;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->jsonMapper = new JsonMapper();
        $this->jsonMapper->bStrictNullTypes = false;
    }

    /**
     * @param string $orderId
     * @return null|StripeOrder
     */
    public function fetchOrder($orderId)
    {
        $result = $this->apiCall('orders/' . $orderId);
        try {
            /** @var StripeOrder $order */
            $order = $this->jsonMapper->map($result, new StripeOrder());
            return $order;
        } catch (JsonMapper_Exception $e) {
            return null;
        }
    }

    /**
     * @param string $customerId
     * @return null|StripeCustomer
     */
    public function fetchCustomer($customerId)
    {
        $result = $this->apiCall('customers/' . $customerId);
        try {
            /** @var StripeCustomer $customer */
            $customer = $this->jsonMapper->map($result, new StripeCustomer());
            return $customer;
        } catch (JsonMapper_Exception $e) {
            return null;
        }
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $body
     * @return false|object
     */
    private function apiCall($endpoint, $method = 'GET', $body = [])
    {
        $url = $this->apiURL() . $endpoint;

        $options = [
            'method' => $method,
            'body' => $method == 'POST' ? json_encode($body) : null,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->apiKey),
            ],
            'sslverify' => false,
            'timeout' => 30,
        ];
        $response = wp_remote_request($url, $options);

        if ($response instanceof WP_Error) {
            return false;
        }

        return json_decode($response['body']);
    }

    /**
     * @return string
     */
    private function apiURL()
    {
        return 'https://api.stripe.com/v1/';
    }
}