<?php
require_once dirname(__FILE__) . '/JsonMapper.php';
require_once dirname(__FILE__) . '/Maropost_List.php';
require_once dirname(__FILE__) . '/Maropost_Api_Exception.php';
require_once dirname(__FILE__) . '/Maropost_Tag.php';
require_once dirname(__FILE__) . '/Maropost_Contact.php';
require_once dirname(__FILE__) . '/Maropost_Custom_Field.php';

/**
 * Class Maropost_Helper
 */
class Maropost_Helper
{
    /** @var int */
    private $accountId;
    /** @var string */
    private $apiKey;
    /** @var JsonMapper */
    private $jsonMapper;
    /** @var string */
    private $lastError = '';

    /**
     * Digimember_Maropost_Helper constructor.
     *
     * @param $accountId
     * @param $apiKey
     */
    public function __construct($accountId, $apiKey)
    {
        $this->accountId = $accountId;
        $this->apiKey = $apiKey;
        $this->jsonMapper = new JsonMapper();
        $this->jsonMapper->bStrictNullTypes = false;
    }

    /**
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @return Maropost_List[]
     * @throws Maropost_Api_Exception
     */
    public function fetchLists()
    {
        static $cache;
        if (!empty($cache)) {
            return $cache;
        }
        $lists = $this->apiCall('lists', 'GET');
        if ($lists === false) {
            throw new Maropost_Api_Exception($this->lastError);
        }
        try {
            /** @var Maropost_List[] $lists */
            $lists = $this->jsonMapper->mapArray($lists, [], 'Maropost_List');
            $cache = $lists;
        } catch (JsonMapper_Exception $e) {
            $cache = [];
        }
        return $cache;
    }

    /**
     * @return Maropost_Tag[]
     * @throws Maropost_Api_Exception
     */
    public function fetchTags()
    {
        static $cache;
        if (!empty($cache)) {
            return $cache;
        }
        $tags = $this->apiCall('tags', 'GET');
        if ($tags === false) {
            throw new Maropost_Api_Exception($this->lastError);
        }
        try {
            /** @var Maropost_Tag[] $tags */
            $tags = $this->jsonMapper->mapArray($tags, [], 'Maropost_Tag');
            $cache = $tags;
        } catch (JsonMapper_Exception $e) {
            $cache = [];
        }
        return $cache;
    }

    /**
     * @param string $tagName
     * @throws Maropost_Api_Exception
     */
    public function createTag($tagName)
    {
        $result = $this->apiCall('tags', 'POST', [
            'tag' => [
                'name' => $tagName,
            ],
        ]);
        if (!$result) {
            throw new Maropost_Api_Exception($this->lastError);
        }
    }

    /**
     * @param string   $email
     * @param string[] $addTags
     * @param string[] $removeTags
     * @throws Maropost_Api_Exception
     */
    public function addRemoveTags($email, $addTags, $removeTags)
    {
        $result = $this->apiCall('add_remove_tags', 'PUT', [
            'tags' => [
                'email' => $email,
                'add_tags' => $addTags,
                'remove_tags' => $removeTags,
            ],
        ]);
        if (!$result) {
            throw new Maropost_Api_Exception($this->lastError);
        }
    }

    /**
     * @param int $tagId
     * @throws Maropost_Api_Exception
     */
    public function deleteTag($tagId)
    {
        $result = $this->apiCall('tags/' . $tagId, 'DELETE');
        if (!$result) {
            throw new Maropost_Api_Exception($this->lastError);
        }
    }

    /**
     * @param $email
     * @return bool|Maropost_Contact
     * @throws Maropost_Api_Exception
     */
    public function fetchContactByEmail($email)
    {
        static $cache = [];
        if (!empty($cache[$email])) {
            return $cache[$email];
        }
        $result = $this->apiCall('contacts/email?contact[email]=' . urlencode($email), 'GET');
        if ($result === false) {
            throw new Maropost_Api_Exception($this->lastError);
        }
        try {
            /** @var Maropost_Contact $contact */
            $contact = $this->jsonMapper->map($result, new Maropost_Contact());
            $return = $contact;
        } catch (JsonMapper_Exception $e) {
            $return = false;
        }
        $cache[$email] = $return;
        return $return;
    }

    /**
     * @param Maropost_Contact $contact
     * @param array            $customFields
     * @throws Maropost_Api_Exception
     */
    public function createContact($contact, $customFields = [])
    {
        $result = $this->apiCall('contacts', 'POST', array_merge([
            'remove_from_dnm' => true,
            'custom_field' => $customFields,
        ], array_filter((array)$contact)));
        if (!$result) {
            throw new Maropost_Api_Exception($this->lastError);
        }
    }

    /**
     * @param Maropost_Contact $contact
     * @param int|null         $list_id
     * @throws Maropost_Api_Exception
     */
    public function deleteContact($contact, $list_id = null)
    {
        $url = $list_id ? 'lists/' . $list_id . '/contacts/' . $contact->id : 'contacts/delete_all?contact[email]=' . $contact->email;
        $result = $this->apiCall($url, 'DELETE');
        if (!$result) {
            throw new Maropost_Api_Exception($this->lastError);
        }
    }

    /**
     * @param int              $listId
     * @param Maropost_Contact $contact
     * @param array            $customFields
     * @throws Maropost_Api_Exception
     */
    public function createContactInList($listId, $contact, $customFields = [])
    {
        $result = $this->apiCall('lists/' . $listId . '/contacts', 'POST', array_merge([
            'remove_from_dnm' => true,
            'custom_field' => $customFields,
        ], array_filter((array)$contact)));
        if (!$result) {
            throw new Maropost_Api_Exception($this->lastError);
        }
    }

    /**
     * @return Maropost_Custom_Field[]
     * @throws Maropost_Api_Exception
     */
    public function fetchCustomFields()
    {
        static $cache;
        if (!empty($cache)) {
            return $cache;
        }
        $customFields = $this->apiCall('custom_fields', 'GET');
        if ($customFields === false) {
            throw new Maropost_Api_Exception($this->lastError);
        }
        try {
            /** @var Maropost_Custom_Field[] $customFields */
            $customFields = $this->jsonMapper->mapArray($customFields, [], 'Maropost_Custom_Field');
            $cache = $customFields;
        } catch (JsonMapper_Exception $e) {
            $cache = [];
        }
        return $cache;
    }

    /**
     * @param int              $listId
     * @param Maropost_Contact $contact
     * @param array            $customFields
     * @throws Maropost_Api_Exception
     */
    public function updateContact($listId, $contact, $customFields = [])
    {
        $result = $this->apiCall('lists/' . $listId . '/contacts/' . $contact->id, 'PUT', array_merge(array_filter((array)$contact), [
            'custom_field' => $customFields,
        ]));
        if (!$result) {
            throw new Maropost_Api_Exception($this->lastError);
        }
    }

    /**
     * @param Maropost_Contact $contact
     * @param array            $customFields
     * @throws Maropost_Api_Exception
     */
    public function updateContactNoList($contact, $customFields = [])
    {
        $result = $this->apiCall('contacts/' . $contact->id, 'PUT', [
            'contact' => array_merge(
                array_filter((array)$contact),
                [
                    'custom_field' => $customFields,
                ]
            ),
        ]);
        if (!$result) {
            throw new Maropost_Api_Exception($this->lastError);
        }
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $body
     * @return false|object
     */
    private function apiCall($endpoint, $method = 'POST', $body = [])
    {
        $this->lastError = '';

        $url = $this->apiURL() . $endpoint;
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'auth_token=' . $this->apiKey;

        $options = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'sslverify' => false,
            'timeout' => 30,
        ];
        if ($method != 'GET') {
            $options = array_merge($options, [
                'body' => json_encode($body),
            ]);
        }
        $response = wp_remote_request($url, $options);

        if ($response instanceof WP_Error) {
            return false;
        }
        $body = json_decode(wp_remote_retrieve_body($response));
        $code = wp_remote_retrieve_response_code($response);
        $message = wp_remote_retrieve_response_message($response);
        if (!is_numeric($code) || ($code < 200 || $code > 202)) {
            $this->lastError = $message;
            return false;
        }
        if (is_object($body) && property_exists($body, 'error')) {
            $this->lastError = $body->error;
            return false;
        }
        return $body;
    }

    /**
     * @return string
     */
    private function apiURL()
    {
        return 'https://sandbox.maropost.com/accounts/' . $this->accountId . '/';
        return 'https://api.maropost.com/accounts/' . $this->accountId . '/';
    }
}