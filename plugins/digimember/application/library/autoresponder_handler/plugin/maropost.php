<?php
require_once dirname(__FILE__) . '/with_tags_interface.php';
require_once dirname(__FILE__) . '/helper/maropost/Maropost_Helper.php';

/**
 * Class digimember_AutoresponderHandler_PluginMaropost
 *
 * @property-read ncore_ApiCore $api
 */
class digimember_AutoresponderHandler_PluginMaropost extends digimember_AutoresponderHandler_PluginWithTags
{
    /** @var Maropost_Helper */
    private $maropostApi;

    /**
     * digimember_AutoresponderHandler_PluginMaropost constructor.
     *
     * @param digimember_AutoresponderHandlerLib $parent
     * @param                                    $meta
     */
    public function __construct($parent, $meta)
    {
        parent::__construct($parent, $meta);
        $this->initializeMaropostApi();
    }

    private function initializeMaropostApi()
    {
        if ($this->data('api_key') && $this->data('account_id')) {
            $this->maropostApi = new Maropost_Helper($this->data('account_id'), $this->data('api_key'));
        }
    }

    /**
     * @return array
     */
    public function instructions()
    {
        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $info_url  = $model->productInfoUrl( 'maropost', 'info' );
        $order_url = $model->productInfoUrl( 'maropost', 'order' );

        return [
            ncore_linkReplace(_digi3('<a>Maropost</a> provides the best integration with %s.', $this->api->pluginDisplayName()), $info_url, true),
            ncore_linkReplace(_digi3('To get your Maropost account, <a>click here</a>!'), $order_url, true),
            _digi3('You need access to the Maropost api.'),
            ncore_linkReplace(_digi3('Enter your <a>Maropost</a> credentials below. Then save.'), $info_url, true),
            _digi3('Select which tags to add or remove below.'),
            _digi3('Optional: Select custom field names at the bottom.'),
        ];
    }

    /**
     * @return array
     */
    protected function formMetas()
    {
        $invalid_access_data_msg = _digi3('Enter your %s credentials and save.', 'Maropost');

        $metas = [];

        $metas[] = [
            'name' => 'api_key',
            'type' => 'text',
            'label' => _digi3('Maropost API Key'),
            'rules' => 'required',
        ];

        $metas[] = [
            'name' => 'account_id',
            'type' => 'text',
            'label' => _digi3('Maropost Account-Id'),
            'rules' => 'required',
        ];

        $list_options = $this->getListOptions();
        if ($list_options) {
            $metas[] = [
                'name' => 'list_id',
                'type' => 'select',
                'options' => $list_options,
                'label' => _digi3('Maropost List'),
            ];
        } else {
            $metas[] = [
                'name' => 'list_id',
                'type' => 'html',
                'label' => _digi3('Maropost List'),
                'html' => $invalid_access_data_msg,
            ];
        }

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
     * @return array|bool
     */
    private function getListOptions()
    {
        if ($this->maropostApi) {
            try {
                $lists = $this->maropostApi->fetchLists();
            } catch (Maropost_Api_Exception $e) {
                return false;
            }
            $listOptions = [];
            foreach ($lists as $list) {
                $listOptions[$list->id] = $list->name;
            }
            return $listOptions;
        } else {
            return false;
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
     * @param $email
     * @return bool
     */
    public function unsubscribe($email)
    {
        if ($this->maropostApi) {
            try {
                $contact = $this->maropostApi->fetchContactByEmail($email);
            } catch (Maropost_Api_Exception $e) {
                return false;
            }
            if ($contact !== false) {
                try {
                    $this->maropostApi->deleteContact($contact, $this->data('list_id'));
                    return true;
                } catch (Maropost_Api_Exception $e) {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param $email
     * @return array|bool
     */
    public function getPersonalData($email)
    {
        if ($this->maropostApi) {
            try {
                $contact = $this->maropostApi->fetchContactByEmail($email);
                return $contact ? (array)$contact : false;
            } catch (Maropost_Api_Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $product_id
     * @param string $order_id
     * @param bool   $force_double_optin
     * @param array  $custom_fields
     * @return bool
     */
    public function subscribe($email, $first_name, $last_name, $product_id, $order_id, $force_double_optin = true, $custom_fields = [])
    {
        if ($this->maropostApi) {
            $addTags = explode(',', $this->data('tag_id'));
            $removeTags = explode(',', $this->data('remove_tag_id'));

            $contact = new Maropost_Contact();
            $contact->email = $email;
            $contact->first_name = $first_name;
            $contact->last_name = $last_name;
            if (is_numeric($this->data('list_id'))) {
                try {
                    $this->maropostApi->createContactInList($this->data('list_id'), $contact, $custom_fields);
                } catch (Maropost_Api_Exception $e) {
                    return false;
                }
                try {
                    $this->maropostApi->addRemoveTags($email, $addTags, $removeTags);
                } catch (Maropost_Api_Exception $e) {
                    return false;
                }
                return true;
            } else {
                try {
                    $this->maropostApi->createContact($contact, $custom_fields);
                } catch (Maropost_Api_Exception $e) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @return array|bool
     */
    public function getTagOptions()
    {
        if ($this->maropostApi) {
            try {
                $tags = $this->maropostApi->fetchTags();
            } catch (Maropost_Api_Exception $e) {
                return false;
            }
            $tagOptions = [];
            foreach ($tags as $tag) {
                $tagOptions[$tag->name] = $tag->name;
            }
            return $tagOptions;
        } else {
            return false;
        }
    }

    /**
     * @param string $email
     * @param string $add_tag_ids_comma_seperated
     * @param string $remove_tag_ids_comma_seperated
     * @return bool
     */
    public function setTags($email, $add_tag_ids_comma_seperated, $remove_tag_ids_comma_seperated)
    {
        if ($this->maropostApi) {
            try {
                $this->maropostApi->addRemoveTags($email, array_filter(explode(',', $add_tag_ids_comma_seperated)), array_filter(explode(',', $remove_tag_ids_comma_seperated)));
                return true;
            } catch (Maropost_Api_Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * @param string $new_tag_name
     * @return bool
     */
    public function createTag($new_tag_name)
    {
        if ($this->maropostApi) {
            try {
                $this->maropostApi->createTag($new_tag_name);
                return true;
            } catch (Maropost_Api_Exception $e) {
            }
        }
        return false;
    }

    /**
     * @return array
     */
    protected function forbiddenCharactersInCustomFieldNames()
    {
        return [
            '{{',
            '}}',
            '.',
        ];
    }

    /**
     * @return array
     */
    protected function customFieldMetas()
    {
        $invalid_access_data_msg = _digi3('Enter your %s credentials and save.', 'Maropost');
        $must_create_fields_msg = _digi3('In Maropost, select <strong>Contacts - Fields</strong> and create custom fields.');

        $original_metas = parent::customFieldMetas();
        $defaults = [
            'field_first_name' => 'first_name',
            'field_last_name' => 'last_name',
        ];

        try {
            $data = $this->maropostApi ? $this->maropostApi->fetchCustomFields() : [];
        } catch (Maropost_Api_Exception $e) {
            $data = [];
        }
        $field_options = [];
        foreach ($data as $customField) {
            $field_options[$customField->name] = strtoupper($customField->name);
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

    /**
     * @return array
     */
    protected function customFieldDefaultNames()
    {
        return [];
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
        return _digi3('%s custom field names', 'Maropost');
    }

    /**
     * @param $placeholder_name
     * @return bool|string
     */
    protected function customFieldFormat($placeholder_name)
    {
        return '{{contact.' . strtolower($placeholder_name) . '}}';
    }

    /**
     * @return bool|mixed|string
     */
    protected function textLabel()
    {
        return 'Maropost';
    }

    /**
     * @return string
     */
    protected function customFieldInstructions()
    {
        return _digi3('In Maropost, you may extend your address book and add additional fields (in Maropost select <em>Contacts - Fields</em>). Below you find a list of fields [PLUGIN] can send to Maropost. Later, if you create an email with Maropost, you may select these fields from the dropdown list <em>placeholders</em>.');
    }

    /**
     * @return bool
     */
    protected function hasAutojoin()
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

        $instructions = '<h3>' . _digi3('Member auto join') . '</h3>';
        $instructions .= _digi3('After signing up in Maropost, new contacts may automatically get an account in DigiMember. In order to enable this, use the shortcode %s on a preferrably secret page.', $shortcode);
        $instructions .= '<br /><br />';
        $instructions .= _digi3('Use the URL of that page within the "HTTP Post" item in Maroposts journey builder. Make sure to select the "JSON" format.');
        $instructions .= '<br /><br />';
        $instructions .= _digi3('Important: The user will not be able to receive his password in any way other than by email. Please make sure that your email settings are configured and set up correctly.');

        return [
            [
                'type' => 'html',
                'label' => 'none',
                'html' => $instructions,
            ],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function retrieveAutojoinContactData()
    {
        $input = file_get_contents('php://input');
        $jsonArgs = json_decode($input, true);
        if (is_array($jsonArgs)) {
            $contact = ncore_retrieve($jsonArgs, 'contact');
            if (!$contact) {
                throw new Exception(_digi3('Invalid POST parameters for Maropost auto join. Please ensure that you followed the instructions carefully.'));
            }

            $accountId = ncore_retrieve($contact, 'id');
            $email = ncore_retrieve($contact, 'email');
            $firstName = ncore_retrieve($contact, 'first_name');
            $lastName = ncore_retrieve($contact, 'last_name');

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
            throw new Exception(_digi3('Invalid POST parameters for Maropost auto join. Please ensure that you followed the instructions carefully.'));
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

        if (!$this->maropostApi) {
            throw new Exception('Maropost api not configured');
        }

        if (!$subscriber_id) {
            throw new Exception('Internal error - should have subscriber_id here!');
        }

        $contact = new Maropost_Contact();
        $contact->id = $subscriber_id;
        $this->maropostApi->updateContactNoList($contact, $data);
    }
}