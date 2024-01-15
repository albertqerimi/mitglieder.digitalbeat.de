<?php

namespace Quentn;

use Exception;
use WP_Error;

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Contact.php';
require_once __DIR__ . '/Email.php';
require_once __DIR__ . '/Recipient.php';
require_once __DIR__ . '/Sender.php';
require_once __DIR__ . '/Term.php';
require_once __DIR__ . '/CustomField.php';

/**
 * Class Api
 *
 * @package FlyTools\Plugins\WebinarFly\Helpers\Quentn
 */
class Api
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private static $additionalHeaders = [
        'x-sender-source' => 'webinarfly',
        'x-sender-source-key' => 'U2VQnGErJywqLiKMB0LocDfw5tk5qmobdDJtJ',
    ];

    /**
     * Api constructor.
     *
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Retrieves a single contact either by email or id
     *
     * @param string|int $emailOrId
     * @param array      $fields
     * @return Contact
     * @throws Exception
     */
    public function contactGet($emailOrId, $fields = [])
    {
        try {
            $result = $this->makeRequest('GET', 'contact/' . $emailOrId, [
                'fields' => join(',', array_merge(
                    ['mail', 'first_name', 'family_name', 'mail_status', 'tags'],
                    $fields
                )),
            ]);
            if ($result['response']['code'] != 200) {
                throw new Exception($result['body']);
            }

            $body = json_decode($result['body'], true);
            if (is_array($body) && count($body)) {
                return new Contact($body[0]);
            }

            throw new Exception($result['body']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param int $contactId
     * @return bool
     */
    public function contactDelete($contactId)
    {
        try {
            $result = $this->makeRequest('DELETE', 'contact/' . $contactId);
            if ($result['response']['code'] != 200) {
                return false;
            }
            $body = json_decode($result['body'], true);

            if (isset($body['success']) && $body['success']) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return CustomField[]
     * @throws Exception
     */
    public function customFieldsGetAll()
    {
        try {
            $result = $this->makeRequest('GET', 'custom-fields');
            if ($result['response']['code'] != 200) {
                throw new Exception($result['body']);
            }

            $body = json_decode($result['body'], true);
            if (is_array($body) && count($body)) {
                return array_map(function($customField) {
                    return new CustomField($customField);
                }, $body);
            }

            throw new Exception($result['body']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves an array of terms
     *
     * @return Term[]
     * @throws Exception
     */
    public function termsGetAll()
    {
        try {
            $result = $this->makeRequest('GET', 'terms');
            if ($result['response']['code'] != 200) {
                throw new Exception($result['body']);
            }

            $body = json_decode($result['body'], true);
            if (is_array($body) && count($body)) {
                return array_map(function($termInfo) {
                    return new Term($termInfo);
                }, $body);
            }

            throw new Exception($result['body']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Contact $contact
     * @param array   $additionalFields
     * @return Contact
     * @throws Exception
     */
    public function contactAdd($contact, $additionalFields = [])
    {
        try {
            $result = $this->makeRequest('POST', 'contact', [
                'contact' => array_merge([
                    'first_name' => $contact->first_name,
                    'family_name' => $contact->family_name,
                    'mail' => $contact->mail,
                    'mail_status' => $contact->mail_status,
                    'terms' => $contact->terms,
                ], $additionalFields),
                'duplicate_check_method' => 'email',
                'duplicate_merge_method' => 'update_add',
            ]);

            $body = json_decode($result['body'], true);

            if (isset($body['id']) && is_numeric($body['id'])) {
                $contact->id = $body['id'];
                return $contact;
            }

            throw new Exception($result['body']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Contact $contact
     * @return bool
     */
    public function contactUpdate($contact)
    {
        try {
            $result = $this->makeRequest('PUT', 'contact/' . $contact->id, [
                'first_name' => $contact->first_name,
                'family_name' => $contact->family_name,
                'mail' => $contact->mail,
                'mail_status' => $contact->mail_status,
            ]);

            $body = json_decode($result['body'], true);

            if (isset($body['success']) && $body['success']) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function customFieldsUpdate($contact, $customFields) {
        try {
            $result = $this->makeRequest('PUT', 'contact/' . $contact->id, $customFields);

            $body = json_decode($result['body'], true);

            if (isset($body['success']) && $body['success']) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param Contact $contact
     * @param int[]   $tags
     * @return bool
     */
    public function tagsAdd($contact, $tags = [])
    {
        try {
            $result = $this->makeRequest('PUT', 'contact/' . $contact->id . '/terms', $tags);

            $body = json_decode($result['body'], true);

            if (isset($body['success']) && $body['success']) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param Contact $contact
     * @param int[]   $tags
     * @return bool
     */
    public function tagsRemove($contact, $tags = [])
    {
        try {
            $result = $this->makeRequest('DELETE', 'contact/' . $contact->id . '/terms', $tags);

            $body = json_decode($result['body'], true);

            if (isset($body['success']) && $body['success']) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $termName
     * @return bool|int
     */
    public function termAdd($termName)
    {
        try {
            $result = $this->makeRequest('POST', 'terms', [
                'name' => $termName,
                'description' => '',
            ]);

            $body = json_decode($result['body'], true);

            if (isset($body['id']) && is_numeric($body['id'])) {
                return $body['id'];
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Checks if the API can be queried with the provided config
     *
     * @return bool|string
     */
    public function apiCheck()
    {
        try {
            $result = $this->makeRequest('GET', 'terms');

            if ($result['response']['code'] != 200) {
                if ($result['response']['code'] == 403) {
                    return _digi3('API Key not accepted');
                }
                return _digi3('Unkown Error') . ': ' . $result['body'];
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Retrieves a list of available senders
     *
     * @return Sender[]
     * @throws Exception
     */
    public function sendersGet()
    {
        try {
            $result = $this->makeRequest('GET', 'mail/senders');
            if ($result['response']['code'] != 200) {
                throw new Exception($result['body']);
            }

            $body = json_decode($result['body'], true);
            if (is_array($body) && count($body)) {
                return array_map(function($senderData) {
                    return new Sender($senderData);
                }, $body);
            }

            throw new Exception($result['body']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Email $email
     * @return Email
     * @throws Exception
     */
    public function emailAdd(Email $email)
    {
        try {
            $result = $this->makeRequest('POST', 'mail/add', [
                'subject' => $email->subject,
                'body_html' => $email->body_html,
                'body_text' => $email->body_text,
                'context' => $email->context,
                'sender_id' => $email->sender_id,
            ]);

            $body = json_decode($result['body'], true);

            if (isset($body['id']) && is_numeric($body['id'])) {
                $email->id = $body['id'];
                return $email;
            }

            throw new Exception($result['body']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param int $emailId
     * @return Email
     * @throws Exception
     */
    public function emailGet($emailId)
    {
        try {
            $result = $this->makeRequest('GET', 'mail/' . $emailId);
            if ($result['response']['code'] != 200) {
                throw new Exception($result['body']);
            }

            $body = json_decode($result['body'], true);
            if (is_array($body)) {
                return new Email($body);
            }

            throw new Exception($result['body']);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Email $email
     * @return bool
     * @throws Exception
     */
    public function emailUpdate($email)
    {
        try {
            $result = $this->makeRequest('PUT', 'mail/' . $email->id, [
                'subject' => $email->subject,
                'body_html' => $email->body_html,
                'body_text' => $email->body_text,
                'context' => $email->context,
                'sender_id' => $email->sender_id,
            ]);

            $body = json_decode($result['body'], true);

            if (isset($body['success']) && $body['success']) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Email       $email
     * @param Recipient[] $recipients
     * @param int         $senderId
     * @return bool
     * @throws Exception
     */
    public function emailSend($email, $recipients, $senderId = null)
    {
        try {
            $result = $this->makeRequest('POST', 'mail/' . $email->id . '/send', [
                'email_id' => $email->id,
                'sender_id' => $senderId,
                'recipients' => array_map(function(Recipient $recipient) {
                    return $recipient->apiRecord();
                }, $recipients),
            ]);

            $body = json_decode($result['body'], true);

            if (isset($body['success'])) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $data
     * @return array|WP_Error
     * @throws Exception
     */
    private function makeRequest($method, $path, $data = [])
    {
        $url = $this->config->apiUrl();
        $url = strpos($url, '/cb/') !== false ? (substr($url, 0, strpos($url, '/cb'))) : $url;
        $url = rtrim($url, '/') . '/' . $path;
        $body = count($data) ? json_encode($data) : null;

        if ($method == 'GET') {
            $body = array_map(function($value) {
                return (is_array($value)) ? json_encode($value) : $value;
            }, $data);
        }

        $response = wp_remote_request(
            $url,
            [
                'method' => $method,
                'body' => $body,
                'headers' => $this->getRequestHeaders(),
                'sslverify' => false,
            ]
        );

        try {
            $this->WpRemoteErrorCheck($response, false);
        }
        catch (Exception $e) {
            throw $e;
        }
        return $response;
    }

    /**
     * @return array
     */
    private function getRequestHeaders()
    {
        return array_merge(self::$additionalHeaders, [
            'Authorization' => 'Bearer ' . $this->config->apiKey(),
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @param mixed $response
     * @param bool  $codeCheck
     * @throws Exception
     */
    private function WpRemoteErrorCheck($response, $codeCheck = true)
    {
        if ($response instanceof WP_Error) {
            $err = '';
            foreach ($response->errors as $val) {
                foreach ($val as $key => $error) {
                    if ($key == 'http_request_failed') {
                        $err .= _digi3('Connection failed:');
                        $subError = '';
                        if (is_array($error)) {
                            foreach ($error as $val) {
                                $subError .= $val;
                            }
                        } else {
                            $subError .= $error;
                        }

                        if ($subError) {
                            $err .= '<br><span style="font-family: Courier,serif;">' . $subError . '</span>';
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
     * @param Config $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }
}