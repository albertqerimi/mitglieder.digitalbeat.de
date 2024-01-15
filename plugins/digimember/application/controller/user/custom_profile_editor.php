<?php

$load->controllerBaseClass( 'user/customfields' );

class digimember_UserCustomProfileEditorController extends ncore_UserCustomFieldsController
{
    private $ownProfile = false;

    public function init( $settings=array() )
    {
        parent::init( $settings );
    }



	protected function inputMetas()
	{
        $user_id  = $this->setting('wpUserID', ncore_userId());
        $this->ownProfile = $this->setting('ownProfile', false);
        if ($user_id === 'current') {
            $this->ownProfile = true;
            $user_id = ncore_userId();
        }

        $customFieldsObj = $this->api->load->model('data/custom_fields');
        $customFields = $this->getCustomFields();

        $metas = array();

        if (count($customFields) > 0) {
            foreach ($customFields as $customField) {
                $metas[] = $customFieldsObj->getMeta($user_id, $customField);
            }
        }

        return $metas;
	}

	protected function sectionMetas()
	{
		return array(
			'account' =>  array(
                'headline' => $this->getHeadlineText(),
                'instructions' => $this->getInstructionsText(),
              ),
		);
	}

	protected function getHeadlineText() {
        return $this->ownProfile ? _ncore('DigiMember data') : _ncore('DigiMember - Data from custom fields');
    }

    protected function getInstructionsText() {
        $linkModel = $this->api->load->model('logic/link');
        $customfields_link = $linkModel->adminMenuLink('customfields');
        return $this->ownProfile ? '' : _ncore('This is the data of the active fields of %s. If the fields are empty, there was nothing saved for this user until now.', $customfields_link);
    }

	protected function buttonMetas()
	{
		$metas = array();
		return $metas;
	}

	protected function editedElementIds()
	{
        $user_id  = $this->setting('wpUserID', ncore_userId());

		return array( $user_id );
	}

    protected function formSettings()
    {
        return array(
             'layout' => 'table_user',
             'hide_required_hint' => true,
        );
    }


	protected function handleRequest()
	{
		parent::handleRequest();

        if ($this->haveFormErrors())
        {
            return;
        }
	}


	protected function getData( $user_id )
	{
		$customFieldObject = new stdClass();
        $userSettingsModel = $this->api->load->model( 'data/user_settings' );
        $customFields = $this->getCustomFields();
        foreach ($customFields as $customField) {
            $value = $userSettingsModel->getForUser($user_id,'customfield_'.$customField->id);
            $fieldname = $customField->name;
            $customFieldObject->$fieldname = $value;
        }
		return $customFieldObject;
	}

	protected function setData( $user_id, $data )
	{
        $userNameUpdated = apply_filters('digimember_ipn_push_user_name', $user_id);
        $customFieldsUpdated = apply_filters('digimember_cf_update_data', $user_id, $data);
        if ($customFieldsUpdated) {
            apply_filters('digimember_ipn_push_arcf_links', $user_id);
        }
        $modified = $customFieldsUpdated;
        $modified = $modified ? $modified : $userNameUpdated;
        return $modified;
	}

	protected function getCustomFields() {
        $customFieldsModel = $this->api->load->model( 'data/custom_fields' );
        if ($this->ownProfile) {
            return $customFieldsModel->getCustomFieldsBySection('account', true);
        }
        return $customFieldsModel->getAllActive();
    }

}
