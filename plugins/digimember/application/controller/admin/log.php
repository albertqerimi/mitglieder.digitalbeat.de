<?php

$load->controllerBaseClass( 'admin/table' );

class digimember_AdminLogController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _digi('Log');
    }

    protected function modelPath()
    {
        return 'data/log';
    }

    protected function viewData()
    {
        $data = parent::viewData();

        $data[ 'below_table_html' ] = $this->renderCronInfo();

        return $data;
    }

    protected function tabs()
    {
        return array_merge(
            array(
                'all'    => _digi( 'All' ),
                'errors' => _digi( 'Errors' ),
            ),
            $this->sectionTypes(),
            array(
                'other' => _digi('Other' ),
            )
        );
    }

    protected function getModelWhere()
    {
        $section = ncore_washText( $this->currentTab() );

        switch ($section)
        {
            case 'all':    return array();
            case 'errors': return array( 'level'   => 'error'  );
            case 'other':

                $types = array_keys( $this->sectionTypes()  );
                if (!$types) {
                    return array( 'id >' => '0' );
                }

                $types_csv = '"' . implode( '","', $types ) . '"';
                $where = array();
                $where[ 'section sql' ] = "section NOT IN ($types_csv)";
                return $where;


            default:       return array( 'section' => $section );
        }
    }

    protected function columnDefinitions()
    {
        return array(
           array(
                'column' => 'created',
                'type' => 'date_time',
                'label' => _ncore('Date'),
                'sortable' => 'desc',
            ),

           array(
                'column' => 'level',
                'type' => 'array',
                'label' => _ncore('Type'),
                'sortable' => 'asc',
                'array' => $this->levelTypes(),
                'search' => 'generic',
                'compare' => 'like',
                'css' => 'dm-table--column-single-icon',
            ),

           array(
                'column' => 'section',
                'type' => 'array',
                'label' => _ncore('Section'),
                'sortable' => 'asc',
                'array' => $this->sectionTypes(),
                'search' => 'generic',
                'compare' => 'like',
            ),

            array(
                'column' => 'message',
                'type' => 'text_escaped',
                'label' => _ncore('Message'),
                'sortable' => 'asc',
                'search' => 'generic',
                'compare' => 'like',
            ),
        );
    }

    protected function settingDefinitions()
    {
        $settings = parent::settingDefinitions();

        $settings[ 'default_sorting'] = array( 'created', 'desc' );

        return $settings;
    }

    private function levelTypes()
    {
        return array(
            'info' => ncore_icon( 'info', _digi( 'Info' ) ),
            'error' => ncore_icon( 'error', _digi( 'Error' ) ),
        );
    }

    private function sectionTypes()
    {
        $types = array(
            'privacy' => _digi('Data privacy' ),
            'ipn'     => _digi('Autoresponder'),
            'api'     => _digi('Api'),
            'mail'    => _digi('Email'),
            'payment' => _digi('Payment'),
            'plugin'  => _digi('Plugin'),
            'cronjob' => _digi('Cronjob'),
            'customfields' => _digi('Custom Fields'),
            'orders' => _digi('Orders'),
            'zapier' => _digi('Zapier'),
            'webhook' => _digi('Webhook'),
        );

        $types = apply_filters( 'ncore_log_sections', $types );

        return $types;
    }

    private function renderCronInfo()
    {
        /** @var ncore_CronjobLogic $model */
        $model = $this->api->load->model( 'logic/cronjob' );
        $html = $model->renderStatusInfo();

        return $html;


    }
}


