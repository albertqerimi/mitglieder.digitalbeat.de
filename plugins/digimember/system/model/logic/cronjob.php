<?php

class ncore_CronjobLogic extends ncore_BaseLogic
{
	public function lastCronRun( $type )
	{
		$key      = "last_cron_$type";
		$settings = $this->api->load->model( 'logic/blog_config' );
		return $settings->get( $key, false );
	}

    /*
	public function runAll()
	{
		$this->api->load->config( 'cronjob' );

		$weekly_models   = $this->api->config->get( NCORE_CRON_WEEKLY );
		$daily_models    = $this->api->config->get( NCORE_CRON_DAILY );
		$hourly_models   = $this->api->config->get( NCORE_CRON_HOURLY );
        $minutely_models = $this->api->config->get( NCORE_CRON_MINUTELY );

		$this->run( $weekly_models,    'cronWeekly' );
		$this->run( $daily_models,     'cronDaily' );
		$this->run( $hour_models,      'cronHourly' );
        $this->run( $minutely_models,  'cronMinutely' );
	}*/

	public function cronDaily()
	{
		// set_site_transient('update_plugins', null)
	}

	public function runJobs()
	{
		if ( !$this->canRunJobs() )
		{
			return;
		}

		ignore_user_abort( true );

        $plugin_name = $this->api->pluginLogName();

		$config = $this->api->load->config( 'cronjob' );

		$timer = $this->api->load->model( 'data/timer' );

		$run_weekly = $timer->weekly( 'cronjob' );
		if ( $run_weekly )
		{
			$this->updateCronTimestamp( NCORE_CRON_WEEKLY );
			$weekly_models = $config->get( NCORE_CRON_WEEKLY );
			$this->api->log( 'cronjob', _ncore( 'Running weekly %s jobs', $plugin_name ) );
			$this->run( $weekly_models, 'cronWeekly' );
			return;
		}

		$run_daily = $timer->daily( 'cronjob' );
		if ( $run_daily )
		{
            $lib = $this->api->loadLicenseLib();
			$lib->getLicenseErrors();

			$this->updateCronTimestamp( NCORE_CRON_DAILY );
			$daily_models = $config->get( NCORE_CRON_DAILY );
			$this->api->log( 'cronjob', _ncore( 'Running daily %s jobs', $plugin_name ) );
			$this->run( $daily_models, 'cronDaily' );
			return;
		}

		$run_hourly = $timer->hourly( 'cronjob' );
		if ( $run_hourly )
		{
			$this->updateCronTimestamp( NCORE_CRON_HOURLY );
			$hour_models = $config->get( NCORE_CRON_HOURLY );
			$this->run( $hour_models, 'cronHourly' );
			$this->api->log( 'cronjob', _ncore( 'Running hourly %s jobs', $plugin_name ) );
		}

        $run_munitely = $timer->minutely( 'cronjob' );
        if ($run_munitely)
        {
            $minutely_models = trim( $config->get( NCORE_CRON_MINUTELY ) );
            if ($minutely_models)
            {
                $this->updateCronTimestamp( NCORE_CRON_MINUTELY );
                $this->run( $minutely_models, 'cronMinutely' );
                // $this->api->log( 'cronjob', _ncore( 'Running minutely %s jobs', $plugin_name ) );
            }
        }

	}


    public function renderStatusInfo()
    {
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
            $last_run = $this->lastCronRun( $one );
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


	private function run( $models_text, $method )
	{
		ignore_user_abort( true );

		$api = $this->api;

		$lines = explode( "\n", $models_text );
		foreach ( $lines as $line )
		{
			$model_path = trim( $line );

			if ( !$model_path )
			{
				continue;
			}

            if (NCORE_DEBUG) {
                $this->api->log( 'cronjob', _ncore( 'Started %s job %s', $this->api->pluginLogName(), $model_path.'::'.$method.'()' ) );
            }

			$model = $api->load->model( $model_path );

			$model->$method();

            if (NCORE_DEBUG) {
                $this->api->log( 'cronjob', _ncore( 'Finished %s job %s', $this->api->pluginLogName(), $model_path.'::'.$method.'()' ) );
            }

		}
	}

	private function updateCronTimestamp( $type )
	{
		$key      = "last_cron_$type";
		$settings = $this->api->load->model( 'logic/blog_config' );
		$settings->set( $key, ncore_serverTime() );
	}

	private function canRunJobs()
	{
        $this->api->load->helper( 'cron' );

		$is_cron_script_run = ncore_isCronjob();
        if ( $is_cron_script_run )
        {
            return true;
        }

		$timer  = $this->api->load->model( 'data/timer' );
		$must_run_during_current_page_view = $timer->runNow( 'cronjob_pageview', 7200 );

		return $must_run_during_current_page_view;
	}

}
