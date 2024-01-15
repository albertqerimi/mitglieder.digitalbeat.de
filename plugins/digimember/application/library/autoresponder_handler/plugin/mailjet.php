<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

class digimember_AutoresponderHandler_PluginMailjet extends digimember_AutoresponderHandler_PluginBase
{
    public function unsubscribe($email)
    {
    }

    public function getPersonalData($email)
    {
        return [];
    }

    public function subscribe($email, $first_name, $last_name, $product_id, $order_id, $force_double_optin = true, $custom_fields = [])
    {
        $list_id = $this->data('list_id');

        if (!$list_id) {
            return;
        }

        $client = $this->getClient();
        $result = $client->post(['contactslist/' . $list_id . '/managecontact', ''], [
            'body' => [
                'Action' => 'addnoforce',
                'Name' => $first_name . ' ' . $last_name,
                'Email' => $email,
            ],
        ]);

        if (!$result->success()) {
            throw new Exception($result->getReasonPhrase());
        }
    }

    public function formMetas()
    {
        $metas = [];

        $metas[] = [
            'name' => 'api_key',
            'type' => 'text',
            'label' => _digi3('MailJet API key'),
            'rules' => 'defaults',
            'hint' => _digi('E.g. %s', 'LdMZuw2vasXQdZRRGCLMBuLvdrBBtJEt'),
            'class' => 'ncore_code',
            'size' => 32,
        ];

        $metas[] = [
            'name' => 'secret_key',
            'type' => 'text',
            'label' => _digi3('MailJet Secret Key'),
            'rules' => 'defaults',
            'hint' => _digi('E.g. %s', 'NUf8VfaBH34pdpenruJLfVtBsLNxrPG8'),
            'class' => 'ncore_code',
            'size' => 32,
        ];

        $list_options = $this->getLists();
        $list_error = $list_options === 'error';;
        $have_lists = $list_options && !$list_error;
        if ($have_lists) {
            $metas[] = [
                'name' => 'list_id',
                'type' => 'select',
                'options' => $list_options,
                'label' => _digi3('MailJet list'),
                'rules' => 'required|trim',
            ];
        } else {
            $metas[] = [
                'name' => 'list_id',
                'type' => 'hidden',
            ];

            $msg = $list_error
                ? _digi3('Enter your MailJet API and secret key, save and then pick a MailJet list here.')
                : _digi3('Please log into your MailJet account and create a list.');

            $css = '';

            $show_error = $list_error && (bool)$this->apiKey();

            if ($show_error) {
                $css = 'ncore_form_cell_error_message';
                $msg = _digi3('The API key is invalid.') . ' ' . $msg;
            }

            $metas[] = [
                'label' => _digi3('MailJet list'),
                'type' => 'html',
                'html' => $msg,
                'css' => $css,
            ];

        }

        return $metas;

    }

    public function instructions()
    {
        return [
            _digi3('<strong>In MailJet</strong> click on <em>My Account</em>.'),
            _digi3('Locate the headline <em>REST API</em>. Click on <em>Master API Key</em>.'),
            _digi3('Copy the <em>API key</em> to the clipboard and paste it in <strong>DigiMember</strong> into the input field <em>MailJet API Key</em>.'),
            _digi3('Then copy the <em>secret key</em> to the clipboard and paste it in DigiMember into the input field <em>MailJet Secret Key</em>.'),
            _digi3('<strong>Here in DigiMember</strong> save your changes.'),
            _digi3('Select the MailJet list from the dropdown list and save again.'),
        ];
    }

    public function isActive()
    {
        $curl_installed = extension_loaded('curl');
        $json_installed = extension_loaded('json');

        return $curl_installed && $json_installed;
    }

    public function inactiveMsg()
    {
        return _digi3('The MailJet api needs the php extension "curl" and "json" activated for your webaccount. Without, a connection to MailJet is not possible. Please ask your webhoster to install and activate "curl" and "json" for php.');
    }

    private $client;

    private function getLists()
    {
        try {
            $client = $this->getClient();

            //$client->addRequestOption('limit', 1000);

            $options = [];

            $options[""] = _ncore('(Please select ...)');

            $filters = [
                'Limit'=>1000
            ];
            $result = $client->get(['contactslist', ''], ['filters'=>$filters]);

            if (!$result->success()) {
                throw new Exception($result->getReasonPhrase());
            }

            $lists = $result->getData();

            foreach ($lists as $one) {
                $id = ncore_retrieve($one, 'ID', false);
                $name = ncore_retrieve($one, 'Name', "List $id");

                if ($id) {
                    $options[$id] = $name;
                }
            }

            $this->api->load->helper('array');

            return ncore_sortOptions($options);
        } catch (Exception $e) {
            return 'error';
        }
    }

    private function getClient()
    {
        if (!isset($this->client)) {

            //require_once 'helper/mailjet_api/Client.php';

            $api_key = $this->apiKey();
            $secret_key = $this->secretKey();

            if (!$api_key) {
                throw new Exception(_digi3('Mailjet API key and API secret not entered. Please check MailJet API access credentials.'));
            }

            $this->client = new \Mailjet\Client($api_key, $secret_key);
        }

        return $this->client;
    }

    private function apiKey()
    {
        return trim($this->data('api_key'));
    }

    private function secretKey()
    {
        return trim($this->data('secret_key'));
    }

    public function updateUserName ($user_id) {
        $userData = ncore_getUserById($user_id);
        $userFirstName = get_user_meta($userData->ID, 'first_name', true);
        $userLastName = get_user_meta($userData->ID, 'last_name', true);
        $client = $this->getClient();
        $result = $client->get(['contact', $userData->user_email]); //$userData->user_email
        if ($result->success() && $result->getCount() > 0) {
            $subscriberDataArray = $result->getData();
            $subscriber = $subscriberDataArray[0];
            $newName = $userFirstName." ".$userLastName;
            if ($subscriber['Name'] != $newName) {
                $putResult = $client->put(['contact', $userData->user_email], ["body" => ["Name" => $newName]]);
                if ($putResult->success()) {
                    return true;
                }
                return false;
            }
        }
        if (!$result->success()) {
            return false;
        }
    }

}

