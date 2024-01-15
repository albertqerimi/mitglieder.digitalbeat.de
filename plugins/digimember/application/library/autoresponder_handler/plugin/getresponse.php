<?php

use GetResponse\Api;

require_once dirname(__FILE__) . '/helper/getresponse_api/Api.php';

class digimember_AutoresponderHandler_PluginGetresponse extends digimember_AutoresponderHandler_PluginBase
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
        $contact = $this->getClient() ? $this->getClient()->getContactByEmail($email) : null;
        if (count($contact)) {
            try {
                $this->getClient()->deleteContact($contact[0]['contactId']);
                return true;
            }
            catch (Exception $e) {
                return false;
            }
        }
        return true;
    }

    public function getPersonalData( $email )
    {
        $contact = $this->getClient() ? $this->getClient()->getContactByEmail($email) : null;
        if (count($contact)) {
            return $contact[0];
        }
        return [];
    }

    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        $api_key = $this->apiKey();
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
            $customFields = $this->getClient()->getCustomFields();
            foreach ($custom_fields as $key => $value)
            {
                if (!$value && $value !== '0') {
                    $value = '-';
                }
                $field = array_values(array_filter($customFields, function($field) use($key) {
                    return $field['name'] == $key;
                }));

                if (count($field)) {
                    $customs[] = array(
                        'customFieldId' => ncore_retrieve($field[0], 'customFieldId'),
                        'value' => [$value]
                    );
                }
            }
        }
        catch (Exception $e) {}

        try
        {
            $response = $client->optIn(
                $email,
                $name,
                $customs ? $customs : []
            );
            if ($response !== true) {
                throw new Exception($response);
            }
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
            _digi3('<strong>In GetResponse</strong> in the upper right corner click on My Account - Account Details.'),
            _digi3('Click on <em>API & OAuth</em>.'),
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

    /**
     * @return array
     */
    protected function customFieldMetas()
    {
        $invalid_access_data_msg = _digi3( 'The API key is invalid.');
        $must_create_fields_msg = _digi3('In GetResponse, select <strong>Contacts - Contact Fields</strong> and create custom fields.');

        $original_metas = parent::customFieldMetas();
        $defaults = [];

        try {
            $data = $this->getClient() ? $this->getClient()->getCustomFields() : [];
        } catch (Exception $e) {
            $data = [];
        }
        $data = is_array($data) ? $data : [];
        $field_options = [];
        foreach ($data as $customField) {
            $field_options[ncore_retrieve($customField, 'name')] = ncore_retrieve($customField, 'name');
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

    /** @var Api */
    private $client;

    /**
     * @return array|string
     */
    private function getCampaigns()
    {
        $client = $this->getClient();

        $api_key = $this->apiKey();

        if (!$api_key)
        {
            return 'no_api_key';
        }

        try {
            $options = array();

            $options[ "" ] = _ncore( '(Please select ...)' );

            $result = $client->getCampaigns();
            if (!is_array($result)) {
                throw new Exception($result);
            }

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

    private function getClient()
    {
        if (!isset($this->client)) {
            $this->client = new Api($this->apiKey(), $this->data( 'campaign_id' ));
        }

        return $this->client;
    }

    private function apiKey()
    {
        return trim( $this->data( 'api_key' ) );
    }
}
