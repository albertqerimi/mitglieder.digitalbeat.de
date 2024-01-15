<?php

require_once __DIR__ . '/../../../../../vendor/autoload.php';

/**
 * Class Digimember_Mailchimp_Helper
 *
 * Provides connection helpers for the mailchimp API
 */
class Digimember_Mailchimp_Helper
{
    /**
     * @var string API Key for Mailchimp
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiUrl;

    private $apiServerPrefix;

    /**
     * @var int
     */
    public $errorCode;

    /**
     * @var string
     */
    public $errorMessage;
    public $apiClient;

    /**
     * Digimember_Mailchimp_Helper constructor.
     * @param string $apiKey
     * @throws Exception
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->setApiUrl();
        $this->getApiClient();
    }

    private function getApiClient() {
        if (!isset($this->apiClient)) {
            $this->apiClient = new MailchimpMarketing\ApiClient();
            $this->apiClient->setConfig([
                'apiKey' => $this->apiKey,
                'server' => $this->apiServerPrefix
            ]);
//            $this->apiClient->lists->getAllLists();
        }
        return $this->apiClient;
    }

    /**
     * Derives the API Url from the provided key by extracting the datacenter part     *
     * @throws Exception
     */
    private function setApiUrl()
    {
        $dc = 'us1';
        if (strstr($this->apiKey, '-')) {
            list($key, $dc) = explode('-', $this->apiKey, 2);
            if (!$dc) {
                $dc = 'us1';
            }
        }
        $this->apiServerPrefix = $dc;
        $this->apiUrl = 'https://' . $dc . '.api.mailchimp.com/3.0';
    }

    /**
     * Returns the auth headers for the request
     * @return array
     */
    private function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode('anystring' . ':' . $this->apiKey),
        ];
    }

    /**
     * Resets the error before each request
     */
    private function resetError()
    {
        $this->errorMessage = '';
        $this->errorCode = null;
    }

    /**
     * Depricated: not used after switching to api client
     * @return array
     */
    public function lists()
    {
        $this->resetError();

        try {
            $response = wp_remote_get(
                $this->apiUrl . '/lists?count=1000000', [
                    'headers' => $this->getHeaders(),
                    'sslverify' => false,
                    'timeout' => 30,
                ]
            );
            $this->checkResponseError($response);

            return json_decode($response['body'], true);
        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
            return [];
        }
    }

    public function fetchLists() {
        try {
            $response = $this->apiClient->lists->getAllLists();
            if ($response instanceof stdClass) {
                $response = json_decode(json_encode($response), true);
                return $response;
            }
            else {
                $this->errorCode = '999';
                $this->errorMessage = 'Invalid Responseformat';
                return [];
            }
        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
            return [];
        }
    }

    /**
     * @param string $listId
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param bool   $useDoi
     * @param array  $customFields
     * @return array
     */
    public function listSubscribe($listId, $email, $firstName, $lastName, $useDoi, $customFields)
    {
        $this->resetError();

        // Mailchimps merge field keys are forcibly upper case
        $upperCustomFields = [];
        foreach (array_keys($customFields) as $key) {
            $upperCustomFields[strtoupper($key)] = $customFields[$key];
        }

        try {


            $body = json_encode([
                'email_address' => $email,
                'status' => $useDoi ? 'pending' : 'subscribed',
                'merge_fields' => array_merge([
                    'FNAME' => $firstName,
                    'LNAME' => $lastName,
                ], $upperCustomFields),
            ]);
            $response = $this->apiClient->lists->setListMember($listId, $body);

            $test = '';

//            $response = wp_remote_post(
//                $this->apiUrl . '/lists/' . $listId . '/members/', [
//                    'body' => json_encode([
//                        'email_address' => $email,
//                        'status' => $useDoi ? 'pending' : 'subscribed',
//                        'merge_fields' => array_merge([
//                            'FNAME' => $firstName,
//                            'LNAME' => $lastName,
//                        ], $upperCustomFields),
//                    ]),
//                    'headers' => $this->getHeaders(),
//                    'sslverify' => false,
//                    'timeout' => 30,
//                ]
//            );

            //$this->checkResponseError($response);

        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * @param mixed $response
     * @param bool  $checkResponseCode
     * @throws Exception
     */
    public function checkResponseError($response)
    {
        if ($response instanceof \WP_Error) {
            $err = '';
            foreach ($response->errors as $val) {
                foreach ($val as $key => $error) {
                    if ($key == 'http_request_failed') {
                        $subError = '';
                        if (is_array($error)) {
                            foreach ($error as $val) {
                                $subError .= $val;
                            }
                        } else {
                            $subError .= $error;
                        }

                        if ($subError) {
                            $err .= ' - ' . $subError;
                        }
                    } else {
                        if (is_array($error)) {
                            foreach ($error as $val) {
                                $err .= $val;
                            }
                        } else {
                            $err .= $error;
                        }
                    }
                }
            }
            throw new \Exception($err);
        }
    }
}