<?php

$load->modelClass( 'data/base' );

abstract class ncore_BaseQueue extends ncore_BaseData
{
    const max_try_count=5;

    public function execJob( $id )
    {
        ignore_user_abort(true);

        $jobs_completed = 0;

        while (true)
        {
            $row = $this->getAndLock( $id );

            if ($row)
            {
                try
                {
                    $success = $this->process( $row );

                    if ($success) {
                        $jobs_completed++;
                    }
                }
                catch( Exception $e )
                {
                    $success = false;
                }
                $this->unLock( $row, $success);
            }
            else
            {
                break;
            }

            if ($id)
            {
                break;
            }
        }

        return $jobs_completed;
    }

    public function cronMinutely()
    {
        $this->execJob( $id=false );
    }

    public function cronHourly()
    {
        $this->execJob( $id=false );

        $tableName = $this->sqlTableName();

        $now = ncore_dbDate();

        $sql = "UPDATE `$tableName`
                SET locked_at = NULL
                WHERE locked_at < '$now' - INTERVAL 1 HOUR";

        $this->db()->query($sql);
    }


    public function cronDaily()
    {
        $this->purgeQueue();
    }

    //
    // protected section
    //
    abstract protected function process($data);

    protected function keepTimeDays()
    {
        return 30;
    }

    protected function purgeQueue()
    {
        $days = $this->keepTimeDays();
        if (!$days) {
            return;
        }

        $tableName = $this->sqlTableName();

        $expire_at = ncore_dbDate( time() - 86400 * $days );

        $sql = "DELETE FROM `$tableName`
                WHERE processed_at < '$expire_at'";

        $this->db()->query($sql);
    }


    protected function sqlTableMeta()
    {
        $columns = array(
            'processed_at' => 'lock_date',
            'try_count'    => 'int',
            'next_try_at'  => 'lock_date',
            'locked_at'    => 'lock_date'
        );

        $indexes = array(
            'processed_at'
        );

        $meta = array(
            'columns' => $columns,
            'indexes' => $indexes
        );

        return $meta;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values[ 'try_count' ]   = '0';
        $values[ 'next_try_at' ] = '2010-01-01 00:00:00';

        return $values;
    }

    abstract protected function onGiveUp( $row, $tries_so_far );

    abstract protected function onFailure( $row, $tries_so_far, $tries_left );

    //
    // private section
    //

    private function getAndLock( $id=false )
    {
        $order_by = 'id ASC';
        $where    = array(
            'processed_at'  => null,
            'locked_at'     => null,
            'next_try_at <' => ncore_dbDate(),
        );

        if ($this->hasTrash()) {
            $where[ 'deleted' ] = null;
        }

        if ($id)
        {
            $where[ 'id' ] = $id;
        }

        $row = $this->getWhere($where, $order_by);
        if (!$row)
        {
            return false;
        }

        $data = array(
            'locked_at' => ncore_dbDate(),
        );

        $modified = $this->update($row->id, $data, $where);

        if ($modified)
        {
            return $row;
        }

        return false;
    }

    private function unLock($row, $mark_as_finished)
    {
        if (is_array($mark_as_finished)) {
            $data = $mark_as_finished;
            $mark_as_finished = (bool) $mark_as_finished;
        }
        else
        {
            $data = array();
        }

        $data[ 'locked_at' ] = null;

        if ($mark_as_finished==='ignore')
        {
            // empty
        }
        elseif ($mark_as_finished)
        {
            $data[ 'processed_at' ] = ncore_dbDate();
            $data[ 'next_try_at' ] = null;
        }
        else
        {
            $tries_so_far = $row->try_count + 1;

            $tries_left = self::max_try_count - $tries_so_far;

            if ($tries_left <= 0)
            {
                $data[ 'processed_at' ] = ncore_dbDate();
                $data[ 'next_try_at' ] = null;
                $data[ 'try_count' ] = $tries_so_far;

                $this->onGiveUp( $row, $tries_so_far );
            }
            else
            {
                $wait_hours = $this->waitHours( $tries_so_far );

                $this->onFailure( $row, $tries_so_far, $tries_left );

                $data[ 'next_try_at' ] = ncore_dbDate( time() + 3600 * $wait_hours );
                $data[ 'try_count' ]   = $tries_so_far;
            }
        }

        $this->update($row->id, $data);
    }

    private function waitHours( $tries_so_far )
    {
        if ($tries_so_far <= 2)
        {
            $wait_hours = 2;
        }
        elseif ($tries_so_far <= 4)
        {
            $wait_hours = 12;
        }
        else
        {
            $wait_hours = 12;
        }

        return $wait_hours;
    }
}