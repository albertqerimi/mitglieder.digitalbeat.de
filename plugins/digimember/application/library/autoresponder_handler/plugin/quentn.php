<?php

use Quentn\Api;
use Quentn\Config;
use Quentn\Contact;
use Quentn\CustomField;

require_once dirname(__FILE__) . '/with_tags_interface.php';
require_once dirname(__FILE__) . '/helper/quentn_api/Api.php';


/**
 * Class digimember_AutoresponderHandler_PluginQuentn
 */
class digimember_AutoresponderHandler_PluginQuentn extends digimember_AutoresponderHandler_PluginWithTags
{
    /**
     * @var Api
     */
    private $quentnApi;
    private $couldConnect = false;

    /**
     * digimember_AutoresponderHandler_PluginQuentn constructor.
     *
     * @param digimember_AutoresponderHandlerLib $parent
     * @param                                    $meta
     */
    public function __construct(digimember_AutoresponderHandlerLib $parent, $meta)
    {
        parent::__construct($parent, $meta);
        $this->initializeQuentnApi();
    }

    private function initializeQuentnApi()
    {
        if ($this->data('api_key') && $this->data('api_url')) {
            $this->quentnApi = new Api(
                Config::compose()
                    ->apiKey($this->data('api_key'))
                    ->apiUrl($this->data('api_url'))
            );
        }
    }

    /**
     * @return bool
     */
    public function hasUnsubscribe()
    {
        return true;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function unsubscribe($email)
    {
        if (!$this->quentnApi) {
            return false;
        }
        try {
            $contact = $this->quentnApi->contactGet($email);
        } catch (Exception $e) {
            return false;
        }
        if (!$contact) {
            return false;
        }
        return $this->quentnApi->contactDelete($contact->id);
    }

    /**
     * @param string $email
     * @return array|bool
     */
    public function getPersonalData($email)
    {
        if (!$this->quentnApi) {
            return false;
        }
        try {
            $contact = $this->quentnApi->contactGet($email);

        } catch (Exception $e) {
            return false;
        }
        if (!$contact) {
            return false;
        }
        return (array)$contact;
    }

    /**
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param int    $product_id
     * @param string $order_id
     * @param bool   $force_double_optin
     * @param array  $custom_fields
     * @return bool
     * @throws Exception
     */
    public function subscribe($email, $first_name, $last_name, $product_id, $order_id, $force_double_optin = true, $custom_fields = [])
    {
        if (!$this->quentnApi) {
            return false;
        }

        $addTags = explode(',', $this->data('tag_id'));
        $removeTags = explode(',', $this->data('remove_tag_id'));

        $contact = new Contact();
        $contact->mail = $email;
        $contact->first_name = $first_name;
        $contact->family_name = $last_name;
        $contact->terms = $addTags;

        $result = $this->quentnApi->contactAdd($contact, $custom_fields);
        if ($result instanceof Contact) {
            $this->quentnApi->tagsRemove($result, $removeTags);
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    protected function customFieldInstructions()
    {
        return _digi3('In Quentn, you may extend your address book and add additional fields (in Quentn select <em>Contacts - Fields</em>). Below you find a list of fields [PLUGIN] can send to Quentn. Later, if you create an email with Quentn, you may select these fields from the dropdown list <em>placeholders</em>.');
    }

    /**
     * @return array
     */
    protected function formMetas()
    {
        $invalid_access_data_msg = $this->invalidAccessDataMessage();

        $metas = [];

        $metas[] = [
            'name' => 'api_key',
            'type' => 'text',
            'label' => _digi3('%s API Key', 'Quentn'),
            'rules' => 'required',
        ];

        $metas[] = [
            'name' => 'api_url',
            'type' => 'text',
            'label' => _digi3('Quentn API Url'),
            'rules' => 'required',
        ];

        $metas[] = [
            'name' => 'tag_id',
            'type' => 'autoresponder_tag_list_select',
            'label' => _digi3('Add tags'),
            'msg_connect_error' => $invalid_access_data_msg,
            'rules' => '',
            'seperator' => '<br />',
            'autoresponder' => $this->id(),
        ];
        $metas[] = [
            'name' => 'remove_tag_id',
            'type' => 'autoresponder_tag_list_select',
            'label' => _digi3('Remove tags'),
            'msg_connect_error' => $invalid_access_data_msg,
            'rules' => '',
            'seperator' => '<br />',
            'autoresponder' => $this->id(),
        ];

        return $metas;
    }

    /**
     * @return array
     */
    protected function customFieldDefaultNames()
    {
        return [
            'first_name' => 'first_name',
            'last_name' => 'family_name',
        ];
    }

    /**
     * @return bool
     */
    protected function hasCustomFields()
    {
        return true;
    }

    protected function hasDynamicCustomFields() {
        return true;
    }

    /**
     * @return string
     */
    protected function customFieldMetaHeadline()
    {
        return _digi3('%s custom field names', $this->textLabel());
    }

    /**
     * @return string
     */
    private function invalidAccessDataMessage()
    {
        static $message;
        if ($message) {
            return $message;
        }
        $result = $this->quentnApi ? $this->quentnApi->apiCheck() : false;
        if ($result !== true) {
            $message = _digi3('Login failed. Please check your %s credentials.', $this->textLabel());
        } else {
            $message = _digi3('Enter your %s API credentials and save.', $this->textLabel());
        }

        return $message;
    }

    /**
     * @return array
     */
    protected function customFieldMetas()
    {
        $this->api->load->helper( 'html_input' );
        $invalid_access_data_msg = $this->invalidAccessDataMessage();
        $must_create_fields_msg = _digi3('In Quentn, select <strong>Contacts - Contact Fields</strong> and create custom fields.');

        $original_metas = parent::customFieldMetas();
        $defaults = [
            'field_first_name' => 'first_name',
            'field_last_name' => 'family_name',
        ];

        try {
            $data = $this->quentnApi ? $this->quentnApi->customFieldsGetAll() : [];
        } catch (Exception $e) {
            $data = [];
        }
        $data = $data ? $data : [];
        /** @var CustomField[] $data */
        $field_options = [];
        foreach ($data as $customField) {
            $field_options[$customField->field_name] = strtoupper($customField->field_name);
        }

        if (count($field_options) > 0) {
            $field_options = array_merge(array( 0 => _ncore('No linking')), $field_options);
        }

        $my_metas = [];

        foreach ($original_metas as $meta) {
            $name = ncore_retrieve($meta, 'name');
            $default = ncore_retrieve($defaults, $name);

            if (empty($name) || $meta['type'] == 'html') {
                // empty
            } else if ($default) {
                $meta['type'] = 'hidden';
                $meta['value'] = $default;
                $placeholder = $this->customFieldFormat($default);
                $meta['hint'] = _digi3('Placeholder in %s mails: %s', $this->textLabel(), "<tt>$placeholder</tt>");
                $meta['must_save_css'] = 'klicktipp_hint';
            } else if ($field_options) {
                $meta['type'] = 'select';
                $meta['options'] = $field_options;
                $meta['invalid_label'] = _digi3('Invalid field name: %s', '[VALUE]');

                if (!empty($meta['name']) && empty($meta['default'])) {
                    $meta['default'] = ncore_retrieve($defaults, $meta['name']);
                    if ($meta['default']) {
                        $meta['rules'] = 'readonly';
                    }
                }
            } else {
                $meta['type'] = 'hidden';
                $meta['hint'] = $field_options === false
                    ? $invalid_access_data_msg
                    : $must_create_fields_msg;
            }

            $my_metas[] = $meta;
        }

        return $my_metas;
    }

    public function dynamicCustomFieldMetas() {
        $linkModel = $this->api->load->model('logic/link');
        $customfields_link = $linkModel->adminMenuLink('customfields');
        $metas = parent::dynamicCustomFieldMetas();
        array_splice( $metas, 1, 0, array(array(
            'type' => 'html',
            'label' => 'none',
            'html' => '<div class="dm-form-instructions">'._ncore('In addition to the fixed fields above, the %s can also be synchronized with %s. This means that the custom fields can also be used in e-mails in %s via the drop-down list placeholders.', $customfields_link, 'Quentn', 'Quentn').'</div>',
        )));
        if (count($metas) < 3) {

            if (!$this->couldConnect) {
                $hint = _ncore('To use the %s with the fields in %s, please first enter your access data and save it.', $customfields_link, 'Quentn');
            }
            else {
                $hint = _digi3( 'In Quentn, select <strong>Contacts - Contact Fields</strong> and create custom fields.' );
            }
            $meta['type'] = 'html';
            $meta['label'] = 'none';
            $meta['html'] = $hint;
            $metas[] = $meta;
        }
        return $metas;
    }

    /**
     * @param $placeholder_name
     * @return bool|string
     */
    protected function customFieldFormat($placeholder_name)
    {
        return '[contact:' . strtolower($placeholder_name) . ']';
    }

    /**
     * @return bool|mixed|string
     */
    protected function textLabel()
    {
        return 'Quentn';
    }

    /**
     * @return bool
     */
    public function hasActionSupport()
    {
        return true;
    }

    /**
     * @return array|bool
     * @throws Exception
     */
    public function getTagOptions()
    {
        if (!$this->quentnApi) {
            return false;
        }
        $terms = $this->quentnApi->termsGetAll();
        if (!$terms) {
            return false;
        }
        $tagOptions = [];
        foreach ($terms as $term) {
            $tagOptions[$term->id] = $term->name;
        }
        return $tagOptions;
    }

    /**
     * @param string $email
     * @param string $add_tag_ids_comma_seperated
     * @param string $remove_tag_ids_comma_seperated
     * @return bool
     * @throws Exception
     */
    public function setTags($email, $add_tag_ids_comma_seperated, $remove_tag_ids_comma_seperated)
    {
        if (!$this->quentnApi) {
            return false;
        }
        $contact = $this->quentnApi->contactGet($email);
        if (!$contact) {
            return false;
        }
        $remove = $this->quentnApi->tagsRemove($contact, array_filter(explode(',', $remove_tag_ids_comma_seperated)));
        $add = $this->quentnApi->tagsAdd($contact, array_filter(explode(',', $add_tag_ids_comma_seperated)));
        return $remove && $add;
    }

    /**
     * @param string $new_tag_name
     * @return bool
     */
    public function createTag($new_tag_name)
    {
        if (!$this->quentnApi) {
            return false;
        }
        return $this->quentnApi->termAdd($new_tag_name);
    }

    public function hasAutojoin()
    {
        return true;
    }

    /**
     * @return array
     */
    protected function autoJoinMetas()
    {
        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller('shortcode');
        $shortcode = '[' . $controller->shortcode('autojoin') . ']';

        $headline = _digi3('Member auto join');
        $text = _digi3('After signing up in Quentn, new contacts may automatically get an account in DigiMember. In order to enable this, use the shortcode %s on a preferrably secret page.', $shortcode);
        $text .= '<br /><br />';
        $text .= _digi3('Important: The user will not be able to receive his password in any way other than by email. Please make sure that your email settings are configured and set up correctly.');

        $headline = '<div class="dm-formbox-headline">' . $headline . '</div>';
        $text = '<div class="dm-form-instructions">' . $text . '</div>';

        return [
            [
                'type' => 'html',
                'label' => 'none',
                'html' => $headline,
            ],
            $metas[] = [
                'type' => 'html',
                'label' => 'none',
                'html' => $text,
            ],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function retrieveAutojoinContactData()
    {
        if (!$this->quentnApi) {
            throw new Exception('Quentn API not initialized');
        }
        $data = ncore_retrieveGET('qntn');
        $data = base64_decode($data);
        $jsonArgs = json_decode($data, true);
        if (is_array($jsonArgs)) {
            $email = ncore_retrieve($jsonArgs, 'email');
            $contactId = ncore_retrieve($jsonArgs, 'cid');
            if (!$email || !$contactId) {
                throw new Exception(_digi3('Invalid POST parameters for Quentn auto join. Please ensure that you followed the instructions carefully.'));
            }

            $contact = $this->quentnApi->contactGet($email);
            if (!$contact instanceof Contact) {
                throw new Exception(_digi3('Email %s does not exist in Quentn', $email));
            }

            $accountId = ncore_retrieve($contact, 'id');
            $email = ncore_retrieve($contact, 'mail');
            $firstName = ncore_retrieve($contact, 'first_name');
            $lastName = ncore_retrieve($contact, 'family_name');

            $field_password = $this->data('field_password');
            $field_loginkey = $this->data('field_loginkey');

            $password = $field_password
                ? ncore_retrieve($contact, $field_password, false)
                : false;

            $loginkey = $field_loginkey
                ? ncore_retrieve($contact, $field_loginkey, false)
                : false;

            return [$accountId, $email, $firstName, $lastName, $password, $loginkey];
        } else {
            throw new Exception(_digi3('Invalid POST parameters for Quentn auto join. Please ensure that you followed the instructions carefully.'));
        }
    }

    /**
     * @param int    $subscriber_id
     * @param string $username
     * @param string $password
     * @param string $login_url
     * @param string $loginkey
     * @throws Exception
     */
    public function setAutojoinLoginData($subscriber_id, $username, $password, $login_url, $loginkey)
    {

        $field_login = $this->data('field_login');
        $field_password = $this->data('field_password');
        $field_loginurl = $this->data('field_loginurl');
        $field_loginkey = $this->data('field_loginkey');

        $data = [];

        if ($field_login && $username) {
            $data[$field_login] = $username;
        }

        if ($field_password && $password) {
            $data[$field_password] = $password;
        }

        if ($field_loginurl && $login_url) {
            $data[$field_loginurl] = $login_url;
        }

        if ($field_loginkey && $loginkey) {
            $data[$field_loginkey] = $loginkey;
        }

        if (!$data) {
            return;
        }
        $data['id'] = $subscriber_id;

        if (!$this->quentnApi) {
            throw new Exception('Quentn api not configured');
        }

        if (!$subscriber_id) {
            throw new Exception('Internal error - should have subscriber_id here!');
        }

        $contact = new Contact($data);
        $this->quentnApi->contactUpdate($contact);
    }

    public function getAttributes() {
        $cleanAttributes = array();
        $attributesList = array();
        if ($this->quentnApi) {
            try {
                $attributesList = $this->quentnApi->customFieldsGetAll();
            }
            catch (Exception $e) {
                $this->api->logError( 'autorsponder', 'Quentn: '.$e->getMessage() );
            }

            $this->couldConnect = true;
        }
        if (is_array($attributesList)) {
            foreach ($attributesList as $key => $attribute) {
                $cleanAttributes[$key]['id'] = $attribute->field_id;
                $cleanAttributes[$key]['name'] = $attribute->field_name;
                $cleanAttributes[$key]['label'] = $attribute->label;
                $cleanAttributes[$key]['tag'] = $attribute->field_name;
            }
            return $cleanAttributes;
        }
        return array();
    }

    public function updateSubscriber ($user_id, $data) {
        $userData = ncore_getUserById($user_id);
        $subscriber = $this->getPersonalData($userData->user_email);
        if (is_array($subscriber) && ncore_retrieve($subscriber, 'id', false)) {
            $contact = new Contact($subscriber);
            $updated = $this->quentnApi ? $this->quentnApi->customFieldsUpdate($contact, $data) : false;
            return $updated;
        }
        return false;
    }

    public function updateUserName ($user_id) {
        $userData = ncore_getUserById($user_id);
        $subscriber = $this->getPersonalData($userData->user_email);
        if (is_array($subscriber) && ncore_retrieve($subscriber, 'id', false)) {
            $userFirstName = get_user_meta($userData->ID, 'first_name', true);
            $userLastName = get_user_meta($userData->ID, 'last_name', true);
            $subscriber['first_name'] = $userFirstName;
            $subscriber['family_name'] = $userLastName;
            $contact = new Contact($subscriber);
            $updated = $this->quentnApi ? $this->quentnApi->contactUpdate($contact) : false;
            return $updated;
        }
        return false;
    }
}