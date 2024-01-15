<?php

$load->controllerBaseClass( 'admin/tabbed');

abstract class ncore_AdminTableController extends ncore_AdminTabbedController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );

        $this->api->load->helper( 'xss_prevention' );
    }

    //
    // protected
    //
    protected function viewData()
    {
        $data = parent::viewData();

        $table = $this->getTableObj();

        $data[ 'table' ] = $table;
        $data[ 'messages' ] = $this->messages;
        $data[ 'below_table_html' ] = $this->renderPageFootnotes();
        $data[ 'is_table_hidden' ] = $this->isTableHidden();

        return $data;
    }

    protected function renderPageFootnotes()
    {
        return '';
    }

    protected function currentView()
    {
        if (isset($this->current_view)) {
            return $this->current_view;
        }

        $view = ncore_retrieveREQUEST( 'view' );

        $is_valid   = false;
        $first_view = false;

        foreach ($this->viewDefinitions() as $one)
        {
            $one_view = $one['view'];

            if ($one_view == $view) {
                $is_valid = true;
            }

            if ($first_view === false)
            {
                $first_view = $one_view;
            }
        }

        $this->current_view = $is_valid
                            ? $view
                            : $first_view;

        return $this->current_view;
    }

    protected function currentUrlArgs()
    {
        $args = parent::currentUrlArgs();

        $view = ncore_retrieveGET( 'view' );
        if ($view)
        {
            $args[ 'view'] = $view;
        }

        return $args;
    }

    protected function isTableHidden()
    {
        return false;
    }

    protected function viewName()
    {
        return 'admin/table';
    }

    /**
     * @return ncore_BaseData
     */
    protected function model()
    {
        $model_path = $this->modelPath();

        $api = $this->api;

        return $api->load->model( $model_path );
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        return ncore_XssPasswordVerified();
    }

    protected function handleRequest()
    {
        $action = ncore_retrieve( $_REQUEST, 'action' );
        if (!$action)
        {
            return;
        }

        $element_comma_seperated = ncore_retrieve( $_REQUEST, 'ids' );

        $element_comma_seperated = ncore_washText( $element_comma_seperated, ',' );

        $elements = $element_comma_seperated
                  ? explode( ',', $element_comma_seperated )
                  : array();

        $handler = "handle" . ucfirst( $action );

        $action_valid = method_exists( $this, $handler );

        if ($action_valid)
        {
            call_user_func( array( $this, $handler), $elements );
        }
    }

    protected function actionUrlExtraArgs()
    {
        return array();
    }

    protected function actionUrl( $action, $element_ids, $extra_params=array() )
    {
        $model = $this->api->load->model( 'logic/link' );

        if (is_array($element_ids))
        {
            $element_ids = implode(',', $element_ids );
        }

        $page       = ncore_retrieve( $_REQUEST, 'page' );

        $name = ncore_XssVariableName();
        $pw = ncore_XssPassword();

        $action = ncore_washText( $action );
        $element_ids = ncore_washText( $element_ids, ',' );



        $action_params = array( 'ids' => $element_ids, 'action' => $action, $name => $pw );

        $currentUrlArgs = $this->currentUrlArgs();

        $params = array_merge( $this->actionUrlExtraArgs(), $extra_params, $action_params, $currentUrlArgs );

        return $this->isNetworkController()
               ? $model->networkPage( $page, $params )
               : $model->adminPage( $page, $params );
    }


    protected function pageHeadlineActionRec( $type, $url, $label='', $data=array() )
    {
        $label_prefixes = array(
            'create' => '<span class="dm-icon icon-plus-circled"></span>',
        );

        $default_labels = array(
            'create'   => _ncore( 'Add new' ),
            // 'settings' => _ncore( 'Settings' ),
        );

        $prefix = ncore_retrieve( $label_prefixes, $type );

        if (!$label)
        {
            $label  = ncore_retrieve( $default_labels, $type );
        }

        if (!$label)
        {
            trigger_error( '$label required' );
        }

        $label = $prefix . $label;

        $data[ 'url' ]   = $url;
        $data[ 'label' ] = $label;

        return $data;
    }

    abstract protected function modelPath();

    abstract protected function columnDefinitions();

    protected function bulkActionDefinitions()
    {
        return array();
    }

    protected function viewDefinitions()
    {
        return array();
    }

    protected function settingDefinitions()
    {
        $settings = array(
            'no_items_msg' => _ncore( 'No items found.' ),
            'no_hits_msg'  => _ncore( 'No items found.' ),
        );

        return $settings;
    }

    protected function undoAction( $action )
    {
        return false;
    }

    protected function actionSuccessMessage( $action, $count )
    {
        switch ($action)
        {
            case 'delete':
                if ($count==1)
                    return _ncore( 'Deleted one item irrevocably.' );
                else
                    return _ncore( 'Deleted %s items irrevocably.', $count );

            case 'trash':
                if ($count==1)
                    return _ncore( 'Moved one item to trash.' );
                else
                    return _ncore( 'Moved %s items to trash.', $count );

            case 'restore':
                if ($count==1)
                    return _ncore( 'Restored one item from trash.' );
                else
                    return _ncore( 'Restored %s items from trash.', $count );

            case 'activate':
                if ($count==1)
                    return _ncore( 'Activated one item.' );
                else
                    return _ncore( 'Activated %s items.', $count );

            case 'deactivate':
                if ($count==1)
                    return _ncore( 'Deactivated one item.' );
                else
                    return _ncore( 'Deactivated %s items.', $count );

            case 'copy':
                if ($count==1)
                    return _ncore( 'Copied one item.' );
                else
                    return _ncore( 'Copied %s items.', $count );

            case 'publish':
                if ($count==1)
                    return _ncore( 'Published one item.' );
                else
                    return _ncore( 'Published %s items.', $count );

            case 'unpublish':
                if ($count==1)
                    return _ncore( 'Unpublished one item.' );
                else
                    return _ncore( 'Unpublished %s items.', $count );

            default:
                $action = ncore_camelCase($action);
                return _ncore( 'Action %s performend successfully on %s item(s).', $action, $count );
        }
    }

    protected function actionFailureMessage( $action, $count )
    {
        return _ncore( 'The action could not be completed.' );
    }

    protected function actionSuccess( $action, $element_ids )
    {
        $undo_action = $this->undoAction( $action );
        $message = $this->actionSuccessMessage( $action, count($element_ids) );
        $action = '';

        if ($undo_action)
        {
            $undo_url = $this->actionUrl( $undo_action, $element_ids );
            $undo_label = _ncore( 'Undo' );

            $this->api->load->helper('html_input');
            $undoButton = ncore_htmlButtonUrl( $undo_label, $undo_url, ['class' => 'dm-btn-success'] );

            $action = $undoButton;
        }

        $this->messages[] = array(
            'type' => 'success',
            'text' => $message,
            'action' => $action,
        );
    }

    protected function actionFailure( $action, $element_ids, $custom_message='' )
    {
        $message = $custom_message
                 ? $custom_message
                 : $this->actionFailureMessage( $action, count($element_ids) );

        $this->messages[] = array(
            'type' => 'error',
            'text' => $message,
        );
    }

    protected function getModelWhere()
    {
        return array();
    }

    //
    // private
    //
    private $tableObj;
    private $messages = array();
    private $current_view;



    private function execActionEdit( $row_id )
    {
        $obj = $this->model()->get( $row_id );

        $table = $this->getTableObj();

        $html = 'HERE IS PLACE FOR THE QUICK EDITOR :-)';

        return $response = array(
                'action' => 'update_row',
                'html' => $html,

        );
    }

    private  function execActionDelete( $row_id )
    {
        $this->model()->moveToTrash( $row_id );

        return $response = array(
                'action' => 'delete_row',
        );
    }

    private function execActionUndelete( $row_id )
    {
        $this->model()->retoreFromTrash( $row_id );

        return $response = array(
                'action' => 'delete_row',
        );
    }

    private function getTableObj()
    {
        if (isset($this->tableObj))
        {
            return $this->tableObj;
        }

        $lib = $this->api->load->library( 'table_renderer' );

        $model = $this->modelPath();
        $columns = $this->columnDefinitions();
        $settings = $this->settingDefinitions();

        $settings[ 'where' ] = $this->getModelWhere();
        $settings[ 'views' ] = $this->viewDefinitions();
        $settings[ 'bulk_actions' ] = $this->bulkActionDefinitions();

        $this->tableObj = $lib->createModelTable( $model, $columns, $settings );

        return $this->tableObj;
    }


}
