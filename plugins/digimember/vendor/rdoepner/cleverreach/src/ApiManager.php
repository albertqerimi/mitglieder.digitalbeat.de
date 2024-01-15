<?php

namespace rdoepner\CleverReach;

use rdoepner\CleverReach\Http\AdapterInterface as HttpAdapter;

class ApiManager implements ApiManagerInterface
{
    /**
     * @var HttpAdapter
     */
    protected $adapter;

    /**
     * ApiManager constructor.
     *
     * @param HttpAdapter $adapter
     */
    public function __construct(HttpAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * getGroups
     * gets all groups from cleverreach account
     */
    public function getGroups()
    {
        return $this->adapter->action('get', "/v3/groups.json");
    }

    /**
     * getForms
     * gets all forms from the clever reach account by given groupId
     * @param $groupId
     * @return mixed
     */
    public function getForms($groupId)
    {
        return $this->adapter->action('get', "/v3/groups.json/".$groupId."/forms");
    }


    /**
     * createSubscriber
     * creates or updates a subscriber via upsert api functionality
     * @param int $groupId
     * @param array $user
     * @return mixed
     */
    public function createSubscriber(
        int $groupId,
        array $user = []
    ) {
        $existentSubscriber = $this->getSubscriber($user['email'], $groupId);
        $now = time();
        $userData = array(
            'email' => $user['email'],
            'registered' => $now,
            'global_attributes' => $user['global_attributes'],
        );
        if ($existentSubscriber) {
            $userData['activated'] = $existentSubscriber['activated'];
        }
        else {
            $userData['activated'] = $user['activated'];
        }
        if (array_key_exists("orders", $user)) {
            $userData["orders"] = $user['orders'];
        }

        $postData[] = $userData;
        return $this->adapter->action(
            'post',
            "/v3/groups.json/{$groupId}/receivers/upsert",
            $postData
        );
    }

    /**
     * getSubscriber
     * gets a subscriber by its email. if group id is set it uses that too.
     * @param string $email
     * @param int|null $groupId
     * @return mixed
     */
    public function getSubscriber(string $email, int $groupId = null)
    {
        if ($groupId) {
            return $this->adapter->action('get', "/v3/groups.json/{$groupId}/receivers/{$email}");
        }

        return $this->adapter->action('get', "/v3/receivers.json/{$email}");
    }

    /**
     * setSubscriberStatus
     * enables or disables subscriber by its mail and groupid and given status
     * @param string $email
     * @param int $groupId
     * @param bool $active
     * @return mixed
     */
    public function setSubscriberStatus(string $email, int $groupId, $active = true)
    {
        if ($active) {
            return $this->adapter->action('put', "/v3/groups.json/{$groupId}/receivers/{$email}/activate");
        }

        return $this->adapter->action('put', "/v3/groups.json/{$groupId}/receivers/{$email}/deactivate");
    }

    /**
     * triggerDoubleOptInEmail
     * triggers the optin mail for a subscriber
     * @param string $email
     * @param int $formId
     * @param array $options
     * @return mixed
     */
    public function triggerDoubleOptInEmail(string $email, int $formId, array $options = [])
    {
        return $this->adapter->action(
            'post',
            "/v3/forms.json/{$formId}/send/activate",
            [
                'email' => $email,
                'doidata' => array_merge(
                    [
                        'user_ip' => isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '' ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',
                        'referer' => isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '' ? $_SERVER['HTTP_REFERER'] : 'http://localhost',
                        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] != '' ? $_SERVER['HTTP_USER_AGENT'] : 'FakeAgent/2.0 (Ubuntu/Linux)',
                    ],
                    $options
                ),
            ]
        );
    }

    /**
     * triggerDoubleOptOutEmail
     * triggers optout mail for a subscriber
     * @param string $email
     * @param int $formId
     * @param array $options
     * @return mixed
     */
    public function triggerDoubleOptOutEmail(string $email, int $formId, array $options = [])
    {
        return $this->adapter->action(
            'post',
            "/v3/forms.json/{$formId}/send/deactivate",
            [
                'email' => $email,
                'doidata' => array_merge(
                    [
                        'user_ip' => isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '' ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',
                        'referer' => isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '' ? $_SERVER['HTTP_REFERER'] : 'http://localhost',
                        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] != '' ? $_SERVER['HTTP_USER_AGENT'] : 'FakeAgent/2.0 (Ubuntu/Linux)',
                    ],
                    $options
                ),
            ]
        );
    }

    /**
     * deleteSubscriber
     * deletes a subscriber by its mail and groupid
     * @param string $email
     * @param int $groupId
     * @return mixed
     */
    public function deleteSubscriber(string $email, int $groupId)
    {
        return $this->adapter->action('delete', "/v3/groups.json/{$groupId}/receivers/{$email}");
    }

    /**
     * getAttributes
     * get attributes by group id
     * @param int $groupId
     * @return mixed
     */
    public function getAttributes(int $groupId = null)
    {
        $groupId = $groupId === null ? 0 : $groupId;
        return $this->adapter->action('get', "/v3/attributes.json?group_id={$groupId}");
    }

    /**
     * updateSubscriberAttributes
     * update attributes
     * @param int $poolId
     * @param int $attributeId
     * @param string $value
     * @return mixed
     */
    public function updateSubscriberAttributes(int $poolId, int $attributeId, string $value)
    {
        return $this->adapter->action(
            'put',
            "/v3/receivers.json/{$poolId}/attributes/{$attributeId}",
            [
                'value' => $value,
            ]
        );    
    }

    /**
     * replaceSubscriberTags
     * @param string $email
     * @param int $groupId
     * @param array $tags
     * @return mixed
     */
    public function replaceSubscriberTags(string $email, int $groupId, array $tags)
    {
        return $this->adapter->action(
            'put',
            "/v3/groups.json/{$groupId}/receivers/{$email}",
            [
                'tags' => $tags,
            ]
        );
    }

    /**
     * getAdapter
     * Returns the HTTP adapter.
     * @return HttpAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}
