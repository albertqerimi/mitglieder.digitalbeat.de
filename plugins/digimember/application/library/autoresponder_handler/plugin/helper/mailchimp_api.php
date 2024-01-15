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
    private $apiServerPrefix;
    public $apiClient;


    public $errorMessage = '';
    public $errorCode = false;
    /**
     * Digimember_Mailchimp_Helper constructor.
     * @param string $apiKey
     * @throws Exception
     */
    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);
        $this->setApiServerPrefix();
        $this->getApiClient();
    }

    /**
     * getApiClient
     * instances new api client
     * @return \MailchimpMarketing\ApiClient
     */
    private function getApiClient() {
        if (!isset($this->apiClient)) {
            $this->apiClient = new MailchimpMarketing\ApiClient();
            $this->apiClient->setConfig([
                'apiKey' => $this->apiKey,
                'server' => $this->apiServerPrefix
            ]);
        }
        return $this->apiClient;
    }

    private function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    private function setApiServerPrefix() {
        $dc = 'us1';
        if (strstr($this->apiKey, '-')) {
            list($key, $dc) = explode('-', $this->apiKey, 2);
            if (!$dc) {
                $dc = 'us1';
            }
        }
        $this->apiServerPrefix = $dc;
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
     * lists
     * Gets all Lists of mailchimp
     * @return array
     */
    public function lists()
    {
        $this->resetError();
        try
        {
            $response = $this->getApiClient()->lists->getAllLists();
            return json_decode(json_encode($response), true);
        }
        catch (Exception $e)
        {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
            return [];
        }
    }

    /**
     * getSubscribeStatus
     * Gets the status of a subscriber.
     * @param $listId
     * @param $email
     * @return array|false|string[]
     */
    public function getSubscribeStatus($listId,$email) {
        try {
            if ($response = $this->getListMember($listId,$email)) {
                $output = array(
                    'status' => $response->status,
                    'memberData' => $response
                );
                return $output;
            }
            return array(
                'status' => 'notfound',
            );
        } catch (Exception $e) {
            return array(
                'status' => 'notfound',
            );
        }
        return false;
    }

    /**
     * getListMember
     * Gets a subscriber found of given listid and email.
     * @param $listId
     * @param $email
     * @return false
     */
    public function getListMember($listId,$email) {
        $email = strtolower($email);
        $email_md5 = md5( $email );
        try {
            $response = $this->apiClient->lists->getListMember($listId, $email_md5);
            return $response;
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * implement custom fields for request
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
        $upperCustomFields = [];
        foreach (array_keys($customFields) as $key) {
            $upperCustomFields[strtoupper($key)] = $customFields[$key];
        }
        $upperCustomFields = $this->filterMergeFields($listId, $upperCustomFields);
        try {
            $subscriber = false;
            $subscriberStatus = $this->getSubscribeStatus($listId, $email);
            if ($subscriberStatus['status'] == 'notfound') {
                $body = json_encode([
                    'email_address' => $email,
                    'status' => $useDoi ? 'pending' : 'subscribed',
                    'merge_fields' => array_merge([
                        'FNAME' => $firstName,
                        'LNAME' => $lastName,
                    ], $upperCustomFields),
                ]);
                $subscriber = $this->apiClient->lists->addListMember($listId, $body);
            }
            elseif ($subscriberStatus['status'] == 'unsubscribed') {
                $body = json_encode([
                    'status' => $useDoi ? 'pending' : 'subscribed',
                    'merge_fields' => array_merge([
                        'FNAME' => $firstName,
                        'LNAME' => $lastName,
                    ], $upperCustomFields),
                ]);
                $subscriberHash = $subscriberStatus['memberData']->id;
                $subscriber = $this->apiClient->lists->updateListMember($listId, $subscriberHash, $body);
            }
            else {
                $body = json_encode([
                    'status' => $subscriberStatus['status'],
                    'merge_fields' => array_merge([
                        'FNAME' => $firstName,
                        'LNAME' => $lastName,
                    ], $upperCustomFields),
                ]);
                $subscriberHash = $subscriberStatus['memberData']->id;
                $subscriber = $this->apiClient->lists->updateListMember($listId, $subscriberHash, $body);
            }
            return $subscriber;
        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function filterMergeFields($list_id, $mergeFieldsList){
        $attributes = $this->getAttributes($list_id);
        $cleanMergeFields = array();
        foreach ($mergeFieldsList as $tag => $value) {
            if(array_search($tag, array_column($attributes, 'tag'))) {
                $cleanMergeFields[$tag] = $value;
            }
        }
        return $cleanMergeFields;
    }

    public function getAttributes($list_id = 0) {
        $cleanAttributes = array();
        if (!$list_id) {
            return array();
        }
        try {
            $attributesList = $this->getApiClient()->lists->getListMergeFields($list_id);
        } catch (Exception $e) {
            return array();
        }
        if (is_array($attributesList->merge_fields) && count($attributesList->merge_fields) > 0) {
            foreach ($attributesList->merge_fields as $attribute) {
                $cleanAttributes[$attribute->merge_id]['id'] = $attribute->merge_id;
                $cleanAttributes[$attribute->merge_id]['name'] = $attribute->name;
                $cleanAttributes[$attribute->merge_id]['label'] = $attribute->name;
                $cleanAttributes[$attribute->merge_id]['tag'] = $attribute->tag;
            }
            return $cleanAttributes;
        }
        return array();
    }

    public function listIsValid($list_id) {
        try {
            $this->getApiClient()->lists->getListMergeFields($list_id);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function updateSubscriberAttributes($list_id, $subscriberHash, $attributeTag, $attributeData) {
        $body = json_encode([
            'merge_fields' => [
                $attributeTag => $attributeData,
            ],
        ]);
        $this->getApiClient()->lists->updateListMember($list_id, $subscriberHash, $body);
    }
}