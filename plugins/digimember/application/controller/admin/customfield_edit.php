<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminCustomfieldEditController extends ncore_AdminFormController
{
    protected function pageHeadline()
    {
        return _ncore('Custom Fields');
    }

    protected function pageInstructions()
    {
        $message = '';
        $message .= _ncore('Here you can create or edit a single field.');

        return array(
            $message
        );
    }

    protected function inputMetas()
    {
        $api = $this->api;
        $customFieldModel = $api->load->model( 'data/custom_fields' );
        $arcfModel = $api->load->model( 'data/arcf_links' );
        $id = $this->getElementId();

        $field_options = $customFieldModel->resolveSelectOptions('fieldtypes', false);
        $select_options = $customFieldModel->resolveSelectOptions('selectoptions', false);

        $metas = array();

        $metas[] = array(
            'name' => 'id',
            'section' => 'general',
            'type' => 'int',
            'label' => _ncore('Id' ),
            'element_id' => $id,
            'rules' => 'readonly',
        );

        $metas[] = array(
            'name' => 'type',
            'section' => 'general',
            'type' => 'select',
            'label' => _ncore('Field type'),
            'rules' => 'defaults',
            'element_id' => $id,
            'options' => $field_options,
        );

        $metas[] = array(
            'name' => 'content_type',
            'section' => 'general',
            'type' => 'select',
            'label' => _ncore('Used select'),
            'rules' => 'defaults|required',
            'element_id' => $id,
            'options' => $select_options,
            'depends_on' => array( 'type' => 'select' ),
            'allow_null' => false,
        );

        $metas[] = array(
            'name' => 'content',
            'section' => 'general',
            'type' => 'text',
            'label' => _ncore('Content of the selct list'),
            'rules' => 'defaults|required',
            'element_id' => $id,
            'depends_on' => array(
                'type' => 'select',
                'content_type' => 'custom'
            ),
            'allow_null' => false,
            'hint' => _ncore('Here you can input the options of the select field with a comma seperated list. Example: option 1,option 2,option 3.<br>Pro tip: its possible to set a specific value for each option by using a # sign between the value and the label of an option. Example: value1#option 1,value2#option 2,value3#option 3. The values are not visible for the users.')
        );

        $metas[] = array(
            'name' => 'label',
            'section' => 'general',
            'type' => 'text',
            'label' => _ncore('Label'),
            'rules' => 'defaults|required',
            'element_id' => $id,
            'hint' => _ncore('This is the label of the field. Its visible for the users in the input forms.')
        );

        $metas[] = array(
            'name' => 'name',
            'section' => 'general',
            'type' => 'text',
            'label' => _ncore('Name'),
            'element_id' => $id,
            'depends_on' => array( 'type' => 'never' ),
        );

        $metas[] = array(
            'name' => 'hinttext',
            'section' => 'general',
            'type' => 'text',
            'label' => _ncore('Hint text'),
            'element_id' => $id,
            'hint' => _ncore('This hint text will be presented to the users as tooltip in the input forms.')
        );

        //didnt found a way to deliver value with a invisible field atm. functional invisibility for now. task for refactor
        $metas[] = array(
            'name' => 'section',
            'section' => 'general',
            'type' => 'select',
            'label' => 'Zuordnung',
            'element_id' => $id,
            'options' => array(
                //'general' => _ncore('General'),
                'account' => _ncore('Account data'),
                'poll' => _ncore('Form data'),
            ),
            'hint' => _ncore('Defines the category of the field. Only Account data can be seen and edited by users. Form data fields may be used in forms. The collected data can only be seen from admin area.'),
        );

        $metas[] = array(
            'name' => 'position',
            'section' => 'general',
            'rules' => 'defaults|numeric',
            'type' => 'text',
            'label' => 'Position',
            'element_id' => $id,
            'hint' => _ncore('This is the position of the field in the forms. The field with the lowest number will be displayed first.')
        );

        $metas[] = array(
            'name' => 'is_active',
            'section' => 'status',
            'type' => 'yes_no_bit',
            'label' => _ncore('Active'),
            'element_id' => $id,
            'default' => 'Y',
        );

        $metas[] = array(
            'name' => 'visible',
            'section' => 'status',
            'type' => 'yes_no_bit',
            'label' => _ncore('Visible'),
            'element_id' => $id,
            'tooltip' => _ncore('The visibility of a field defines if a field is visible in a shortcode or not.<br>Fields that are active but not visible will not be visible by the user in a shortcode but available in the %s.','<a href='.ncore_getUserManagementPage().'>'._ncore('Wordpress account management').'</a>'),
            'default' => 'Y',
        );

        $metas[] = array(
            'section' => 'links',
            'type' => 'function',
            'label' => 'none',
            'function' => array($arcfModel,'getArListByCf'),
            'params' => $id,
        );
        return $metas;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');
        $link = $linkLogic->adminPage( 'customfields' );

        $metas[] = array(
                'type' => 'link',
                'label' => _ncore('Back'),
                'url' => $link,
                );

        return $metas;
    }

    protected function sectionMetas() {
        return array(
            'general' =>  array(
                'headline' => _ncore('Configuration'),
                'instructions' => '',
            ),
            'visibility' =>  array(
                'headline' => _ncore('Visibility'),
                'instructions' => '',
            ),
            'status' =>  array(
                'headline' => _ncore('Field status'),
                'instructions' => '',
            ),
            'links' =>  array(
                'headline' => _ncore('Autoresponder links'),
                'instructions' => '',
            )
        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        $model = $this->api->load->model( 'data/custom_fields' );

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            $obj = $model->get( $id );
        }
        else
        {
            $obj = $model->emptyObject();
        }

        if (!$obj)
        {
            $this->formDisable( _ncore( 'The element has been deleted.' ) );
            return false;
        }

        return $obj;
    }

    protected function setData( $id, $data )
    {
        $model = $this->api->load->model( 'data/custom_fields' );

        $have_id = is_numeric( $id ) && $id > 0;

        $subdata = array();

        foreach ($data as $col => $value)
        {
            $is_data = ncore_stringStartsWith( $col, 'sub_data_' );
            if ($is_data)
            {
                $key = substr( $col, 9 );
                $subdata[$key] = $value;
            }
        }

        $data[ 'data_serialized' ] = serialize( $subdata );

        if ($have_id)
        {
            return $model->update( $id, $data );
        }
        else
        {
            $model->setNameField($data);
            $id = $model->create( $data );
            $data['id'] = $id;
            $model->setNameField($data, false);
            $model->setPosition($data);
            $model->update($id, $data);

            $this->setElementId( $id );

            return (bool) $id;
        }
    }

    protected function formActionUrl()
    {
        $this->api->load->helper( 'url' );

        $action_url = parent::formActionUrl();

        $id =  $this->getElementId();

        if ($id)
        {

            $args = array( 'id' => $id );

            return ncore_addArgs( $action_url, $args );
        }
        else
        {
            return $action_url;
        }
    }

    protected function handleRequest()
    {
        parent::handleRequest();
    }
}