<?php

$load->controllerBaseClass( 'admin/table' );

class ncore_AdminLogController extends ncore_AdminTableController
{
    protected function pageHeadline()
    {
         return _ncore('Log');
    }

    protected function modelPath()
    {
        return 'data/log';
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
                'type' => 'text',
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
            'info'    => ncore_icon( 'info',    _ncore( 'Info'    ) ),
            'warning' => ncore_icon( 'warning', _ncore( 'Warning' ) ),
            'error'   => ncore_icon( 'error',   _ncore( 'Error'   ) ),
        );
    }

    private function sectionTypes()
    {
        return array(
            'ipn'     => _ncore('Autoresponder'),
            'api'     => _ncore('Api'),
            'mail'    => _ncore('Mail'),
            'payment' => _ncore('Payment'),
            'plugin'  => _ncore('Plugin'),
        );
    }

    private function renderCronInfo()
    {
        $model = $this->api->load->model( 'logic/cronjob' );

        $now = ncore_serverTime();

        $settings = $this->api->load->model( 'logic/blog_config' );
        $install_time = $settings->get( 'plugin_install_time' );

        $crons = array( NCORE_CRON_WEEKLY, NCORE_CRON_DAILY, NCORE_CRON_HOURLY );

        $expected_ages = array(
            NCORE_CRON_MINUTELY => 1800,
            NCORE_CRON_HOURLY   => 3*3600,
            NCORE_CRON_DAILY    => 1.5*84000,
            NCORE_CRON_WEEKLY   => 20*84000
        );

        $labels = array(
            NCORE_CRON_MINUTELY => _ncore( 'Minutely jobs' ),
            NCORE_CRON_HOURLY   => _ncore( 'Hourly jobs'   ),
            NCORE_CRON_DAILY    => _ncore( 'Daily jobs'    ),
            NCORE_CRON_WEEKLY   => _ncore( 'Weekly jobs'   ),
        );

        $cron_errors = array();

        foreach ($crons as $one)
        {
            $last_run = $model->lastCronRun( $one );
            if (!$last_run)
            {
                $last_run = $install_time;
            }

            $age = $now - $last_run;

            $expected_age = $expected_ages[ $one ];

            $max_age = 1.2*$expected_age;

            $is_ok = $age < $max_age;

            if (!$is_ok)
            {
                $cron_errors[] = $labels[ $one ];
            }
        }

        if ($cron_errors)
        {
            $icon = ncore_icon( 'no' );
            $css = 'error';
            $msg = _ncore( 'Some recurrent tasks ("Cronjobs") are NOT running periodically:' )
                 . ' ' . implode( ', ', $cron_errors );
        }
        else
        {
            $icon = ncore_icon( 'yes' );
            $css = 'success';
            $msg = _ncore( 'The recurrent tasks ("Cronjobs") are running periodically.' );
        }

        $html = "<span class='$css'>$icon $msg</span>";

        return $html;


    }
}


