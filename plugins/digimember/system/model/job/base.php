<?php

abstract class ncore_BaseJob extends ncore_Model
{
    const lock_timeout_seconds = 3600;

    public function cronMinutely() {

        if (!$this->canRun())
        {
            return;
        }

        $this->model()->fixCrashedJobs();

        $this->runJobs();

        $this->abortJobs();
    }

    public function jobStatusse() {

        return array(
            NCORE_JOB_STATUS_CREATED   => _ncore( 'New' ),
            NCORE_JOB_STATUS_PENDING   => _ncore( 'Waiting ...' ),
            NCORE_JOB_STATUS_RUNNING   => _ncore( 'Running ...' ),
            NCORE_JOB_STATUS_ABORTING  => _ncore( 'Aborting ...' ),
            NCORE_JOB_STATUS_COMPLETE  => _ncore( 'Finished' ),
        );
    }

    public function jobResults() {
        return array(
            NCORE_RESULT_SUCCESS => _ncore( 'Success' ),
            NCORE_RESULT_ABORTED => _ncore( 'Aborted' ),
            NCORE_RESULT_ERROR   => _ncore( 'Error!' ),
            NCORE_RESULT_UNKNOWN => _ncore( 'Running' ),
        );
    }

    public function jobResultsCss() {
        return array(
            NCORE_RESULT_SUCCESS => 'ncore_msg ncore_msg_success ncore_plain',
            NCORE_RESULT_ABORTED => 'ncore_msg ncore_msg_error ncore_plain',
            NCORE_RESULT_ERROR   => 'ncore_msg ncore_msg_error ncore_plain',
            NCORE_RESULT_UNKNOWN => 'ncore_msg ncore_msg_error ncore_plain',
        );
    }

    public function jobResultsWithCss() {

        $results = array();

        $css = $this->jobResultsCss();

        foreach ($this->jobResults() as $result => $label) {
            $class = $css[ $result ];
            $results[ $result ] = "<span class='$class'>$label</span>";
        }

        return $results;
    }

    //
    // protected
    //
    abstract protected function model();

    abstract protected function computePercentDone( $job );

    protected function canRun()
    {
        return true;
    }

    protected function startJob( $data=array(), $where=array(), $order_by = 'modified ASC' ) {

        $model = $this->model();

        $where[ 'status' ] = NCORE_JOB_STATUS_PENDING;
        $where[ 'locked' ] = null;

        $data['locked']             = ncore_dbDate();
        $data['current_perc_done']  = 0.1;
        $data['current_start_time'] = ncore_dbDate();

        $all = $model->getAll( $where, $limit=false, $order_by );

        foreach ($all as $job) {

            $locked = $model->changeStatus( $job->id, $by='system', NCORE_JOB_STATUS_RUNNING, $data, $where );

            if ($locked) {
                foreach ($data as $key => $value)
                {
                    $job->$key = $value;
                }
                return $job;
            }
        }

        return false;
    }

    protected function resumeJob()
    {

        $model = $this->model();

        $where = array( 'status' => NCORE_JOB_STATUS_RUNNING, 'locked' => null );
        $limit    = '';
        $order_by = 'modified ASC';

        $data = array();
        $data['locked'] = ncore_dbDate();

        $all = $model->getAll( $where, $limit, $order_by );

        foreach ($all as $job) {
            $locked = $model->update( $job->id, $data );
            if ($locked) {
                return $job;
            }
        }

        return false;
    }

    protected function finishJob( $job, $result, $next_status=NCORE_JOB_STATUS_COMPLETE,  $data=array() )
    {
        $model = $this->model();

        $start_time_unix = strtotime( $job->current_start_time );

        $data['last_run']    = ncore_dbDate();
        $data['last_result'] = $result;
        $data['locked']      = null;

        $data['current_start_time']  = null;
        $data['current_perc_done']   = 100;

        $model->changeStatus( $job->id, $by='system', $next_status, $data );

        $run_time_msg = '';
        if ($start_time_unix)
        {
            $this->api->load->helper( 'date' );
            $run_time_unix = ncore_serverTime() - $start_time_unix;
            $run_time_disp = ncore_formatTimeSpan( $run_time_unix, 'timespan' );

            $run_time_msg = ' (' . _ncore( 'run time: %s', $run_time_disp ) . ')';
        }


        switch ($result)
        {
            case NCORE_RESULT_SUCCESS:
                $this->success( $job, _ncore( 'Successfully completed' ) .  $run_time_msg );
                break;

            default;
                $this->error( $job, _ncore( 'Completed with errors' ) .  $run_time_msg );
        }
    }

    protected function touchJob( $job, $data=array() )
    {
        $this->computePercentDone( $job );

        $data['current_perc_done']   = $job->current_perc_done;

        $model = $this->model();

        $model->update( $job->id, $data );
    }

    abstract protected function processJob( $job );

    protected function error( $job, $msg ) {

        $section = $this->encode_log_id( $job );

        $this->log()->log( 'error', $section, $msg );
    }

    protected function warning( $job, $msg ) {

        $section = $this->encode_log_id( $job );

        $this->log()->log( 'warning', $section, $msg );
    }

    protected function success( $job, $msg ) {

        $section = $this->encode_log_id( $job );

        $this->log()->log( 'info', $section, $msg );
    }

    //
    // private
    //
    private $log;
    private function log() {
        if (!isset( $this->log)) {
            $this->log = $this->api->load->model( 'data/backup_log' );
        }
        return $this->log;
    }

    private function maxConcurrentJobCount() {

        $config = $this->api->load->model( 'logic/network_config' );
        $concurrent_job_count = $config->get( 'concurrent_job_count', 3 );

        return $concurrent_job_count;
    }

    private function runningJobCount()
    {
        $model1 = $this->api->load->model( 'data/job' );
        $model2 = $this->api->load->model( 'data/snapshot' );

        return $model1->runningJobCount() + $model2->runningJobCount();
    }

    private function runJobs()
    {
        $job = false;

        while (ncore_cronMayContinue())
        {
            if (!$job) {
                $job = $this->resumeJob();
            }
            if (!$job) {

                $max_concurrent_job_count = $this->maxConcurrentJobCount();

                $job_count = $this->runningJobCount();
                if ($job_count > $max_concurrent_job_count) {
                    break;
                }

                $job = $this->startJob();
            }
            if (!$job) {
                break;
            }

            list( $finished,
                  $result ) = $this->processJob( $job );

            if ($finished) {
                $this->finishJob( $job, $result );
                $job = false;
            }
        }

        if ($job) {
            $this->unlockJob( $job );
        }
    }

    private function abortJobs() {

        $model = $this->model();

        $where = array(
            'status' => NCORE_JOB_STATUS_ABORTING,
            'locked' => null,
        );
        $all = $model->getAll( $where );
        foreach ($all as $one) {
            $this->warning( $one, _ncore( 'Aborted on user request.' ) );
            $this->finishJob( $one, NCORE_RESULT_ABORTED );
        }


        $lock_timed_out_at = ncore_serverTime() - self::lock_timeout_seconds;
        $where = array(
            'locked <' => ncore_dbDate( $lock_timed_out_at ),
        );
        $all = $model->getAll( $where );
        foreach ($all as $one) {
            $this->warning( $one, _ncore( 'Job crashed! Job now resetted.' ) );
            $this->finishJob( $one, NCORE_RESULT_ABORTED );
        }
    }


    private function encode_log_id( $job )
    {
        return $job->table . '_' . $job->id;
    }

    private function unlockJob( $job )
    {
        $data = array();
        $data['locked'] = null;

        $this->touchJob( $job, $data );
    }

}