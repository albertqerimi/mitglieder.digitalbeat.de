<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use GetResponse\Api;
use Getresponse\Sdk\GetresponseClientFactory;
use Getresponse\Sdk\Operation\Campaigns\GetCampaigns\GetCampaigns;
use Getresponse\Sdk\Operation\CustomFields\GetCustomFields\GetCustomFields;
use Getresponse\Sdk\Operation\Contacts\CreateContact\CreateContact;
use Getresponse\Sdk\Operation\Model\CampaignReference;
use Getresponse\Sdk\Operation\Model\NewContact;
use Getresponse\Sdk\Operation\Model\NewContactCustomFieldValue;
use Getresponse\Sdk\Operation\Contacts\GetContacts\GetContacts;
use Getresponse\Sdk\Operation\Contacts\GetContacts\GetContactsSearchQuery;
use Getresponse\Sdk\Operation\Model;
use Getresponse\Sdk\Operation\Contacts\UpdateContact\UpdateContact;
use Getresponse\Sdk\Operation\Contacts\DeleteContact\DeleteContact;
use Getresponse\Sdk\Operation\Contacts\CustomFields\UpsertCustomFields\UpsertCustomFields;

require_once dirname(__FILE__) . '/helper/getresponse_api/Api.php';


class digimember_AutoresponderHandler_PluginGetresponseRest extends digimember_AutoresponderHandler_PluginBase
{
    /**
     * @return bool
     */
    public function hasUnsubscribe()
    {
        return true;
    }

    public function unsubscribe( $email )
    {
        $this->getClient();
        $subscribers = $this->getSubscriberByEmail($email);

        if ($subscribers) {
            foreach ($subscribers as $subscriber) {
                try {
                    $deleteContactOperation = new DeleteContact($subscriber['contactId']);
                    $response = $this->client->call($deleteContactOperation);
                    if ($response->isSuccess()) {
                        return true;
                    }
                }
                catch (Exception $e) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getPersonalData( $email )
    {
        $this->getClient();
        $subscriber = $this->getSubscriberByEmail($email);
        if ($subscriber) {
            return $subscriber[0];
        }
        return [];
    }

    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        $campaign_id = $this->data( 'campaign_id' );
        if (!$campaign_id)
        {
            throw new Exception( _digi3( 'No GetResponse Campaign selected.' ) );
        }
        $client  = $this->getClient();
        $name = "$first_name $last_name";
        $name = trim($name) ? $name : '-';
        $customs = array();
        try {
            $customFields = $this->getCustomFields();
            foreach ($custom_fields as $key => $value)
            {
                if (!$value && $value !== '0') {
                    $value = '-';
                }
                $field = array_values(array_filter($customFields, function($field) use($key) {
                    return $field['customFieldId'] == $key;
                }));

                if (count($field)) {
                    $customs[] = new NewContactCustomFieldValue(
                        ncore_retrieve($field[0], 'customFieldId'),
                        [$value]
                    );
                }
            }
        }
        catch (Exception $e) {}

        $subscribers = $this->getSubscriberByEmail($email, $campaign_id);
        if ($subscribers) {
            $this->updateSubscriberName($name, $subscribers[0]);
        }
        else {
            $createContact = new NewContact(
                new CampaignReference($campaign_id),
                $email
            );
            $createContact->setName($name);
            $createContact->setDayOfCycle(0);
            $customs ? $createContact->setCustomFieldValues($customs) : null;
            $createContactOperation = new CreateContact($createContact);
            try
            {
                $client->call($createContactOperation);
            }
            catch (Exception $e)
            {
                $message = $e->getMessage();

                $errors_to_ignore = array(
                    "Contact already queued for target campaign",
                    "Contact already added to target campaign",
                );

                $do_ignore_error = false;
                foreach ($errors_to_ignore as $one)
                {
                    $is_ignored = strpos( $message, $one ) !== false;
                    if ($is_ignored)
                    {
                        $do_ignore_error = true;
                        break;
                    }
                }
                if (!$do_ignore_error) {
                    throw $e;
                }
            }
        }
    }


    public function formMetas()
    {
        $metas = array();
        $metas[] = array(
                'name' => 'api_key',
                'type' => 'text',
                'label' => _digi3('GetResponse API key' ),
                'rules' => 'required|trim',
                'hint'  => _digi3('E.g. %s', '5G7NmvZTyIprRKNngUQvqpIr7s9t8taR' ),
                'class' => 'ncore_code',
                'size'  => 32,
            );
        $list_options = $this->getCampaigns();
        $list_error = !is_array( $list_options );
        $have_lists = $list_options && !$list_error;
        if ($have_lists)
        {
            $metas[] = array(
                'name' => 'campaign_id',
                'type' => 'select',
                'options' => $list_options,
                'label' => _digi3('GetResponse campaign' ),
                'rules' => 'required|trim',
            );
        }
        else
        {
            $metas[] = array(
                'name' => 'campaign_id',
                'type' => 'hidden',
            );
            if ($list_options == 'no_api_key')
            {
                $msg = _digi3( 'Enter your GetResponse API key, save and then pick a GetResponse campaign here.' );
            }
            elseif ($list_error)
            {
                $msg = $list_options;
            }
            else
            {
                $msg = _digi3( 'Please log into your GetResponse account and create a campaign.' );
            }
            $css = '';
            $show_error = $list_error && (bool) $this->apiKey();
            if ($show_error)
            {
                $css = 'ncore_form_cell_error_message';
                $msg = _digi3( 'The API key is invalid.' ) . ' ' . $msg;
            }
            $metas[] = array(
                'label' => _digi3('GetResponse campaign' ),
                'type' => 'html',
                'html' => $msg,
                'css'  => $css,
            );
        }
        return $metas ;
    }

protected function forbiddenCharactersInCustomFieldNames()
    {
        $forbidden_chars = array( '[', ']' );

        return $forbidden_chars;
    }

    protected function customFieldInstructions()
    {
        return _digi3( 'In GetResponse, you may add custom fields (in GetResponse select <em>Contacts - Custom Fields</em>). Important: choose field type <strong>text</strong>. Below you find a list of fields [PLUGIN] can send to GetResponse. Add the fields in GetResponse and make sure the names in [PLUGIN] and in GetResponse match. Later, if you create an email in GetResponse, you may add these fields by putting their names in double square brakets, e.g. [[Firstname]].' );
    }

    protected function customFieldFormat( $placeholder_name )
    {
        $placeholder_name = trim( $placeholder_name, '{}[]% ');
        return '[[' . $placeholder_name . ']]';
    }

    public function instructions()
    {
        return array(
            _digi3('<strong>In GetResponse</strong> in the upper left corner click on Menu - Integrations & API.'),
            _digi3('Click on <em>API</em>.'),
            _digi3('Copy the API key to the clipboard.' ),
            _digi3('<strong>Here in DigiMember</strong> paste the API key into the <em>GetResponse API key</em> text field.' ),
            _digi3('Save your changes.' ),
            _digi3('Select the GetResponse campaign from the dropdown list and save again.' ),
            );
    }

    protected function hasCustomFields()
    {
        return true;
    }

    protected function hasDynamicCustomFields() {
        return true;
    }

    public function customFieldMetas()
    {
        $this->api->load->helper( 'html_input' );
        $find = ['[ARNAME]', '[PLUGIN]'];
        $repl = [$this->textLabel(), $this->api->pluginDisplayName()];
        $headline = str_replace($find, $repl, $this->customFieldMetaHeadline());
        $text = str_replace($find, $repl, $this->customFieldInstructions());
        $headline = '<div class="dm-formbox-headline">' . $headline . '</div>';
        $attributes = $this->getCustomFields();
        $options = array();
        $options[] = _ncore('No linking');
        foreach ($attributes as $attribute) {
            $options[$attribute['customFieldId']] = $attribute['name'] != '' ? $attribute['name'].' - '.$attribute['customFieldId'] : $attribute['customFieldId'];
        }
        $metas = [
            [
                'type' => 'html',
                'label' => 'none',
                'html' => $headline,
            ],
            [
                'type' => 'html',
                'label' => 'none',
                'html' => $text,
            ],
            [
                'name' => 'field_first_name',
                'type' => 'select',
                'label' => _digi3('First name'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_first_name'),
                'options' => $options
            ],
            [
                'name' => 'field_last_name',
                'type' => 'select',
                'label' => _digi3('Last name'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_last_name'),
                'options' => $options
            ],
            [
                'name' => 'field_date',
                'type' => 'select',
                'label' => _digi3('Order date'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_date'),
                'options' => $options
            ],
            [
                'name' => 'field_order_id',
                'type' => 'select',
                'label' => _digi3('Order id'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_order_id'),
                'options' => $options
            ],
            [
                'name' => 'field_login',
                'type' => 'select',
                'label' => _digi3('Username'),
                'rules' => 'defaults',
                'tooltip' => _digi3('The user\'s login name for your site. Default is his email address.'),
                'hint' => $this->renderCustomFieldHint('field_login'),
                'options' => $options
            ],
            [
                'name' => 'field_password',
                'type' => 'select',
                'label' => _digi3('Password'),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldHint('field_password'),
                'options' => $options
            ],
            [
                'name' => 'field_loginurl',
                'type' => 'select',
                'label' => _digi3('Login URL'),
                'rules' => 'defaults',
                'tooltip' => _digi3('The URL to the web page the user visits to log into your site. This is the page containing the login form.'),
                'hint' => $this->renderCustomFieldHint('field_loginurl'),
                'options' => $options
            ],
            [
                'name' => 'field_loginkey',
                'type' => 'select',
                'label' => _digi3('Login key'),
                'tooltip' => _digi3('You may use the login key for auto login links in your email.|Add a GET parameter %s to your blogs URL int the email and set it to the value of the login key custom field.', DIGIMEMBER_LOGINKEY_GET_PARAM),
                'rules' => 'defaults',
                'hint' => $this->renderCustomFieldLoginkeyHint('field_loginkey', 'ncore_form_hint'),
                'options' => $options
            ],
        ];
        if (!$this->couldConnect) {
            $invalid_access_data_msg = _digi3('Enter your %s credentials and save.', 'GetResponse');
            foreach ($metas as &$meta) {
                if ($meta['type'] != 'html') {
                    $meta['type'] = 'hidden';
                    $meta['hint'] = $invalid_access_data_msg;
                }
            }
        }
        return $metas;
    }

    /** @var Api */
    private $client;
    private $couldConnect = false;

    /**
     * @return array|string
     */
    private function getCampaigns()
    {
        $client = $this->getClient();
        $api_key = $this->apiKey();
        if (!$api_key)
        {
            $this->couldConnect = false;
            return 'no_api_key';
        }
        try {
            $options = array();
            $options[ "" ] = _ncore( '(Please select ...)' );
            $campaignsOperation = new GetCampaigns();
            $response = $client->call($campaignsOperation);
            if (!$response->isSuccess()) {
                throw new Exception($response->getResponse()->getStatusCode());
            }
            else {
                $this->couldConnect = true;
            }
            $result = $response->getData();
            foreach ($result as $id=>$one)
            {
                $options[ ncore_retrieve($one, 'campaignId') ] = ncore_retrieve( $one, 'name', "Campaign $id" );
            }
            $this->api->load->helper( 'array' );
            return ncore_sortOptions( $options );
        }
        catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function getCustomFields() {
        $arCustomFields = [];
        $client = $this->getClient();
        $customFieldOperation = new GetCustomFields();
        $response = $client->call($customFieldOperation);
        if ($response->isSuccess()) {
            $arCustomFields = $response->getData();
        }
        return $arCustomFields;
    }

    public function getAttributes($groupId = 0) {
        $cleanAttributes = array();
        if($this->couldConnect) {
            $attributesList = $this->getCustomFields();
            if (is_array($attributesList)) {
                foreach ($attributesList as $key => $attribute) {
                    $cleanAttributes[$key]['id'] = $attribute['customFieldId'];
                    $cleanAttributes[$key]['group'] = $groupId;
                    $cleanAttributes[$key]['name'] = $attribute['name'];
                    $cleanAttributes[$key]['label'] = $attribute['name'];
                    $cleanAttributes[$key]['tag'] = $attribute['customFieldId'];
                }
                return $cleanAttributes;
            }
        }
        return array();
    }

    private function getClient()
    {
        if (!isset($this->client)) {
            $this->client = GetresponseClientFactory::createWithApiKey($this->apiKey());
            $this->testConnection();
        }
        return $this->client;
    }
    private function apiKey()
    {
        return trim( $this->data( 'api_key' ) );
    }

    /**
     * updateSubscriber
     * sets attributes given as data array for a given user_id
     * only works if there is a related subscriber and attribute fields that match at the provider
     * @param $user_id
     * @param $data
     */
    public function updateSubscriber ($user_id, $data) {
        $userData = ncore_getUserById($user_id);
        $updated = false;
        $subscribers = $this->getSubscriberByEmail($userData->user_email);
        if ($subscribers) {
            foreach ($subscribers as $subscriber) {
                if ($this->updateCustomfields($data, $subscriber)) {
                    $updated = true;
                }
            }
            return $updated;
        }
        return false;
    }

    public function updateSubscriberName($name, $subscriber) {
        $updateContact = new Model\UpdateContact();
        $updateContact->setName($name);
        $updateContactOperation = new UpdateContact($updateContact, $subscriber['contactId']);
        $this->client->call($updateContactOperation);
    }

    public function updateUserName ($user_id) {
        $userData = ncore_getUserById($user_id);
        $userFirstName = get_user_meta($userData->ID, 'first_name', true);
        $userLastName = get_user_meta($userData->ID, 'last_name', true);
        $campaign_id = $this->data( 'campaign_id' );
        $subscriber = $this->getSubscriberByEmail($userData->user_email, $campaign_id);
        if ($subscriber) {
            $newName = $userFirstName." ".$userLastName;
            try {
                $this->updateSubscriberName($newName, $subscriber[0]);
            }
            catch(Exception $e){
                return false;
            }
            return true;
        }
        return false;
    }

    public function updateCustomfields($data, $subscriber) {
        $customFieldData = [];
        $customFields = $this->getCustomFields();
        foreach ($customFields as $customfield) {
            if (array_key_exists($customfield['customFieldId'],$data)) {
                $customFieldData[] = new NewContactCustomFieldValue(
                    $customfield['customFieldId'],
                    [$data[$customfield['customFieldId']]]
                );
            }
        }
        if (count($customFieldData) > 0) {
            $upsertModel = new Model\UpsertContactCustomFields($customFieldData);
            $upsertCustomfieldsOperation = new UpsertCustomFields($upsertModel, $subscriber['contactId']);
            $response = $this->client->call($upsertCustomfieldsOperation);
            if ($response->isSuccess()) {
                return true;
            }
        }
        return false;
    }

    public function getSubscriberByEmail($email, $campaignId = false){
        $this->getClient();
        $searchQuery = (new GetContactsSearchQuery())
            ->whereEmail($email);
        if ($campaignId) {
            $searchQuery = $searchQuery->whereCampaignId($campaignId);
        }
        $getContactsOperation = new GetContacts();
        $getContactsOperation->setQuery($searchQuery);
        $response = $this->client->call($getContactsOperation);
        if ($response->isSuccess()) {
            $result = $response->getData();
            if (is_array($result) && array_key_exists(0, $result)) {
                return $result;
            }
        }
        return false;
    }

    public function testConnection() {
        $campaignsOperation = new GetCampaigns();
        $response = $this->client->call($campaignsOperation);
        if ($response->isSuccess()) {
            $this->couldConnect = true;
        }
        else {
            $this->couldConnect = false;
        }
    }
}
