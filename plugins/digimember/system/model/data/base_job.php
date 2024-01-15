<?php

abstract class ncore_BaseJobData extends ncore_BaseData
{
    public function status( $row )
    {
        $status = parent::status( $row );

        $is_deleted = $status == 'deleted';

        if ($is_deleted) {
            return 'deleted';
        }

        return ncore_retrieve( $row, 'status' );
    }

    public function destStatusList( $source_status, $by='user' ) {

        $transitions = ncore_retrieve( $this->statusTransistionMetas(), $by, array() );
        $dest_status_list = ncore_retrieve( $transitions, $source_status, array() );

        return $dest_status_list;
    }

    public function sourceStatusList( $dest_status, $by='user' ) {

        $transitions = ncore_retrieve( $this->statusTransistionMetas(), $by, array() );

        $source_status_list = array();

        foreach ($transitions as $from_status => $to_status_list) {
            if (in_array( $dest_status, $to_status_list)) {
                $source_status_list[] = $from_status;
            }
        }

        return $source_status_list;
    }

    public function runningJobCount( $where=array() ) {

        $timeout_min = 120;

        $table = $this->sqlTableName();

        $NCORE_JOB_STATUS_PENDING  = NCORE_JOB_STATUS_PENDING;
        $NCORE_JOB_STATUS_RUNNING  = NCORE_JOB_STATUS_RUNNING;
        $NCORE_JOB_STATUS_ABORTING = NCORE_JOB_STATUS_ABORTING;

        $count_time_unix = ncore_serverTime() - $timeout_min * 60;
        $count_time      = ncore_dbDate( $count_time_unix );

        $sql = "SELECT COUNT(1) AS count
                FROM `$table`
                WHERE status IN ('$NCORE_JOB_STATUS_PENDING','$NCORE_JOB_STATUS_RUNNING','$NCORE_JOB_STATUS_ABORTING')
                  AND modified >= '$count_time'";

        $sql_where = $this->renderWhere( $where );
        if ($sql_where) {
            $sql .= " AND $sql_where";
        }

        $rows = $this->db()->query( $sql );

        $count = $rows[0]->count;

        return $count;

    }

    public function fixCrashedJobs()
    {
        $table = $this->sqlTableName();

        $minutes = 30;

        $timeout_unix = ncore_serverTime() - 30*60;

        $timeout = ncore_dbDate( $timeout_unix );

        $sql = "SELECT id
                FROM `$table`
                WHERE locked < '$timeout'";

        $rows = $this->db()->query( $sql );
        foreach ($rows as $row) {

            $job_id = $row->id;

            $data = array( 'locked' => NULL );
            $modified = $this->update( $job_id, $data );

            if ($modified)
            {
                $this->api->logError( 'plugin', "Resetted lock time for table %s for job %s after %s minutes", $table, $job_id, $minutes );
            }
        }
    }

    public function changeStatus( $job_obj_or_id, $by, $new_status, $data=array(), $where=array() ) {

        $job_id = $this->resolveToId( $job_obj_or_id );

        $data['status' ] =  $new_status;

        $old_status_list = $this->sourceStatusList( $new_status, $by );

        $where[ 'status IN' ] = $old_status_list;

        $status_changed = $this->update( $job_id, $data, $where );

        return $status_changed;
    }


    protected function statusTransistionMetas()
    {
        return array(
            'user' => array(
                        NCORE_JOB_STATUS_CREATED  => array( NCORE_JOB_STATUS_PENDING ),
                        NCORE_JOB_STATUS_PENDING  => array( NCORE_JOB_STATUS_CREATED ),
                        NCORE_JOB_STATUS_RUNNING  => array( NCORE_JOB_STATUS_ABORTING ),
                    ),
            'system' => array(
                        NCORE_JOB_STATUS_PENDING  => array( NCORE_JOB_STATUS_RUNNING ),
                        NCORE_JOB_STATUS_RUNNING  => array( NCORE_JOB_STATUS_CREATED, NCORE_JOB_STATUS_PENDING, NCORE_JOB_STATUS_COMPLETE ),
                        NCORE_JOB_STATUS_ABORTING => array( NCORE_JOB_STATUS_CREATED, NCORE_JOB_STATUS_PENDING, NCORE_JOB_STATUS_COMPLETE ),
                    ),
        );
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'status'            => 'string[15]',
            'locked'            => 'lock_date',

            'last_result'       => 'string[15]',
            'last_run'          => 'lock_date',

            'current_perc_done' => 'decimal[1]',

       );

       $indexes = array();

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function notCopiedColumns()
    {
        $columns = parent::notCopiedColumns();

        $columns[] = 'status';
        $columns[] = 'locked';

        $columns[] = 'last_run';
        $columns[] = 'last_result';

        $meta = $this->sqlTableMeta();
        foreach ($meta['columns'] as $column => $type)
        {
            $is_current = ncore_stringStartsWith( $column, 'current_' );
            if ($is_current)
            {
                $columns[] = $column;
            }
        }

        return $columns;
    }





    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values[ 'status' ] = NCORE_JOB_STATUS_CREATED;

        return $values;
    }

    protected function canChangeIntoStatus( $job_obj_or_id, $new_status, $by='user' )
    {
        $is_id_of_new_unsaved_object = is_string( $job_obj_or_id ) && !is_numeric( $job_obj_or_id );
        if ($is_id_of_new_unsaved_object) {
            return false;
        }

        $job = $this->resolveToObj( $job_obj_or_id );
        if (!$job) {
            return false;
        }

        $old_status = $job->status;

        $old_status_list = $this->sourceStatusList( $new_status, $by );

        $can_change = in_array( $old_status, $old_status_list );

        return $can_change;
    }



}

