<?php

namespace GetResponse;

use Exception;
use WP_Error;

/**
 * Class Api
 * @package GetResponse
 */
class Api
{
    /**
     * @var
     */
    private $apiKey;
    /**
     * @var
     */
    private $campaignId;

    /**
     * Api constructor.
     * @param string $apiKey
     * @param string $campaignId
     */
    public function __construct($apiKey, $campaignId)
    {
        $this->apiKey = $apiKey;
        $this->campaignId = $campaignId;
    }

    /**
     * @param string $email
     * @param string $name
     * @param array $customFields
     * @return bool | string
     * @throws Exception
     */
    public function optIn($email, $name, $customFields = [])
    {
        $response = wp_remote_post(
            $this->apiURL() . '/contacts', [
                'body' => json_encode([
                    'name' => $name,
                    'email' => $email,
                    'dayOfCycle' => 0,
                    'campaign' => [
                        'campaignId' => $this->campaignId,
                    ],
                    'customFieldValues' => $customFields,
                ]),
                'headers' => [
                    'X-Auth-Token' => 'api-key ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'sslverify' => false,
                'timeout' => 30,
            ]
        );

        $this->WpRemoteErrorCheck($response, false);

        if ($response['response']['code'] == 202) {
            return true;
        } else {
            $body = json_decode($response['body'], true);
            $context = json_encode(ncore_retrieve($body, 'context', '{}'));
            return $body['message'] . ' (' . $body['code'] . ')' . $context;
        }
    }

    /**
     * @param $email
     * @return array|string
     * @throws Exception
     */
    public function getContactByEmail($email)
    {
        $response = wp_remote_get(
            $this->apiURL() . '/campaigns/' . $this->campaignId . '/contacts?query[email]=' . urlencode($email), [
                'headers' => [
                    'X-Auth-Token' => 'api-key ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'sslverify' => false,
                'timeout' => 30,
            ]
        );

        return $this->dataReturn($response);
    }

    /**
     * @param $contactId
     * @return array|mixed|object|string
     * @throws Exception
     */
    public function deleteContact($contactId)
    {
        $response = wp_remote_request($this->apiURL() . '/contacts/' . urlencode($contactId), [
            'method' => 'DELETE',
            'headers' => [
                'X-Auth-Token' => 'api-key ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'sslverify' => false,
            'timeout' => 30,
        ]);

        return $this->dataReturn($response);
    }

    /**
     * @return array|string
     * @throws Exception
     */
    public function getCampaigns()
    {
        $response = wp_remote_get(
            $this->apiURL() . '/campaigns', [
                'headers' => [
                    'X-Auth-Token' => 'api-key ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'sslverify' => false,
                'timeout' => 30,
            ]
        );

        return $this->dataReturn($response);
    }

    /**
     * @return array|string
     * @throws Exception
     */
    public function getCustomFields()
    {
        $response = wp_remote_get(
            $this->apiURL() . '/custom-fields', [
                'headers' => [
                    'X-Auth-Token' => 'api-key ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'sslverify' => false,
                'timeout' => 30,
            ]
        );

        return $this->dataReturn($response);
    }

    /**
     * @param $response
     * @return array|mixed|object|string
     * @throws Exception
     */
    private function dataReturn($response)
    {
        $this->WpRemoteErrorCheck($response, false);

        if ($response['response']['code'] == 200) {
            return json_decode(ncore_retrieve($response, 'body', '[]'), true);
        } else {
            $body = json_decode($response['body'], true);
            return $body['message'] . ' (' . $body['code'] . ')';
        }
    }

    /**
     * @param string $name
     * @return array
     */
    public function splitName($name)
    {
        $exp = explode(' ', $name);
        $c = count($exp);
        if ($c <= 1) {
            $first_name = $name;
            $last_name = '';
        } else {
            $first_name = '';
            for ($i = 0; $i < $c - 1; $i++) {
                $first_name .= $exp[$i] . ' ';
            }
            $first_name = trim($first_name);
            $last_name = $exp[$c - 1];
        }

        return [
            $first_name,
            $last_name,
        ];
    }

    /**
     * @param      $response
     * @param bool $codeCheck
     * @throws Exception
     */
    protected function WpRemoteErrorCheck($response, $codeCheck = true)
    {
        if ($response instanceof WP_Error) {
            $err = '';
            foreach ($response->errors as $val) {
                foreach ($val as $key => $error) {
                    if ($key == 'http_request_failed') {
                        $err .= 'Connection failed:';
                        $subError = '';
                        if (is_array($error)) {
                            foreach ($error as $val) {
                                $subError .= $val;
                            }
                        } else {
                            $subError .= $error;
                        }

                        if ($subError) {
                            $err .= '<br><span style="font-family: Courier,monospace;">' . $subError . '</span>';
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
            throw new Exception($err);
        }

        if ($codeCheck && $response['response']['code'] != 200) {
            throw new Exception($response['response']['message']);
        }
    }

    /**
     * @return string
     */
    private function apiURL()
    {
        return 'https://api.getresponse.com/v3';
    }
}