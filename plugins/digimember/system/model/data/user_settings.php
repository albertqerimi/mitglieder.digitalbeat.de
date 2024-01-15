<?php

class ncore_UserSettingsData extends ncore_BaseData
{
    public function get( $name, $default='' )
    {
        $user_id = ncore_userId();

        return $this->getForUser( $user_id, $name, $default );
    }

    public function set( $name, $value )
    {
        $user_id = ncore_userId();

        $this->setForUser( $user_id, $name, $value );
    }

    public function getForUser( $user_id, $name, $default='' )
    {
        $user_id = $this->resolveUserid( $user_id );
        if (!$user_id) {
            return $default;
        }


        $where = array(
            'user_id' => $user_id,
            'name'    => $name,
        );

        $row = $this->getWhere( $where, 'id DESC' );

        if ($row)
        {
            return $row->value;
        }

        return $default;
    }

    public function getAllForUser($user_id) {
        $user_id = $this->resolveUserid( $user_id );
        if (!$user_id) {
            return array();
        }
        return $this->getAll(array(
            'user_id' => $user_id,
            'name like' => 'customfield_',
        ));
    }

    public function setForUser( $user_id, $name, $value )
    {
        $user_id = $this->resolveUserid( $user_id );
        if (!$user_id) {
            return;
        }

        $where = array(
            'user_id' => $user_id,
            'name'    => $name,
        );

        $row = $this->getWhere( $where );
        if ($row)
        {
            if ($value)
            {
                $data = array( 'value' => $value );
                $this->update( $row->id, $data );
            }
            else
            {
                $this->delete( $row->id );
            }
        }
        elseif ($value)
        {
            $data = array(
                'user_id' => $user_id,
                'name'    => $name,
                'value'   => $value
            );
            $this->create( $data );
        }
    }

    public function setForName($name, $value) {
        $where = array(
            'name'    => $name,
        );
        $rows = $this->getAll( $where );
        foreach ($rows as $row) {
            if ($value)
            {
                $data = array( 'value' => $value );
                $this->update( $row->id, $data );
            }
            else
            {
                $this->delete( $row->id );
            }
        }
    }

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }


    //
    // protected
    //
    protected function sqlBaseTableName()
    {
        return 'user_settings';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'user_id' => 'string[127]',
            'name'    => 'string[63]',
            'value'   => 'string[127]',
       );

       $indexes = array( 'user_id' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function isUniqueInBlog() {

        return true;
    }

    private function resolveUserid( $email_or_wp_user_id )
    {
        if (is_numeric($email_or_wp_user_id)) {
            return $email_or_wp_user_id;
        }

        if (is_email( $email_or_wp_user_id ))
        {
            $user = get_user_by( 'email', $email_or_wp_user_id );
            return ncore_retrieve( $user, array( 'ID', 'id' ), $email_or_wp_user_id );
        }

        return ncore_userId( $email_or_wp_user_id );
    }

    public function updateCustomfieldsData ($user_id, $data) {
        $modified = false;
        $userSettings = $this->getAllForUser($user_id);
        $customFieldsModel = $this->api->load->model('data/custom_fields');
        $customFields = $customFieldsModel->getAllActive();


        foreach ($customFields as $customField) {
            if (array_key_exists($customField->name, $data)) {
                $customfieldName = 'customfield_'.$customField->id;
                if ($user_cf_setting = ncore_findInArrayOfObjects($userSettings, 'name', $customfieldName)) {
                    if ($user_cf_setting->value != $data[$customField->name]) {
                        $this->setForUser($user_id, 'customfield_'.$customField->id, $data[$customField->name]);
                        $modified = true;
                    }
                }
                else {
                    if ($data[$customField->name] != '') {
                        $this->setForUser($user_id, 'customfield_'.$customField->id, $data[$customField->name]);
                        $modified = true;
                    }
                }
            }
        }
        if ($modified) {
            $userdata = ncore_getUserById($user_id);
            $this->api->log('customfields', _ncore('Custom fields for user %s updated.', $userdata->user_email));
        }
        return $modified;
    }

    public function pushArcfLinks($user_id) {
        $modified = false;
        $userCustomFieldSettings = $this->getAllForUser($user_id);
        /** @var ncore_CustomFieldsData */
        $customFieldsModel = $this->api->load->model('data/custom_fields');
        /** @var ncore_ArcfLinksData */
        $arcfModel = $this->api->load->model('data/arcf_links');
        $arcfModel->createTableIfNeeded();

        $arLibrary = $this->api->load->library( 'autoresponder_handler' );
        $dataSets = array();
        foreach ($userCustomFieldSettings as $settingsEntry) {
            list ($trash, $id) = explode('_', $settingsEntry->name);
            if ($customFieldsModel->getIfActive($id)) {
                $arcfLinks = $arcfModel->getAllForCf($id);
                foreach ($arcfLinks as $arcfLink) {
                    $dataSets[$arcfLink->autoresponder][$settingsEntry->user_id][$arcfLink->mapping] = $settingsEntry->value;
                }
            }
        }
        foreach ($dataSets as $autoresponderId => $arcfData) {
            $autoresponderPlugin = $arLibrary->plugin($autoresponderId);
            if ($autoresponderPlugin->isEnabled()) {
                foreach ($arcfData as $user => $data) {
                    $autoresponderPlugin->updateSubscriber($user, $data);
                    $modified = true;
                }
            }
        }
        if ($modified) {
            $userdata = ncore_getUserById($user_id);
            $this->api->log('ipn', _ncore('Data of the custom fields for user %s where syncronized to the active autoresponders.', $userdata->user_email) );
        }
    }

    public function pushUserName($user_id) {
        $modified = false;
        $arLibrary = $this->api->load->library( 'autoresponder_handler' );
        $arModel = $this->api->load->model('data/autoresponder');
        $activeAutoresponders = $arModel->getAll();
          $dataSets = array();
          foreach ($activeAutoresponders as $autoResponder) {
              $dataSets[$autoResponder->id] = $user_id;
          }
        foreach ($dataSets as $autoresponderId => $userId) {
            $autoresponderPlugin = $arLibrary->plugin($autoresponderId);
            try {
                $arResult = $autoresponderPlugin->updateUserName($userId);
                $modified = $arResult;
            }
            catch(Exception $e)  {}
        }
        if ($modified) {
            $userdata = ncore_getUserById($user_id);
            $this->api->log('ipn', _ncore('Pushed Name to active autoresponders for user: %s.', $userdata->user_email) );
        }
    }

}