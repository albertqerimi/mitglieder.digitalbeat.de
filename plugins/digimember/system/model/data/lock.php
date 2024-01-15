<?php

class ncore_LockData extends ncore_BaseData
{
    public function cronDaily()
    {
        $table = $this->sqlTableName();

        $delete_after_days = 2;

        $expire = ncore_dbDate( time() - 86400*$delete_after_days );
        $sql = "DELETE FROM `$table`
                WHERE expire < '$expire'";

        $this->db()->query($sql);
    }

    public function lock( $name, $value, $lock_timeout_minutes=60 )
    {
        $name  = ncore_washText( $name );
        $value = ncore_washText( $value );

        $now    = ncore_dbDate();
        $expire = ncore_dbDate( time() + 60*$lock_timeout_minutes );

        $table = $this->sqlTableName();

        $sql = "UPDATE `$table`
                SET expire = '$expire',
                    locked = '$now'
                WHERE name = '$name'
                  AND value = '$value'
                  AND ((locked IS NULL) OR (expire < '$now'))";
        $this->db()->query( $sql );
        if ($this->db()->modified()) {
            return true;
        }

        $where = array();
        $where['name'] = $name;
        $where['value'] = $value;
        $have_entry = (bool) $this->getAll( $where );
        if ($have_entry) {
            return false;
        }

        $data = array();
        $data['name'] = $name;
        $data['value'] = $value;
        $this->create( $data );

        $this->db()->query( $sql );
        return $this->db()->modified();
    }

    public function unlock( $name, $value )
    {
        $name = ncore_washText( $name );
        $value = ncore_washText( $value );

        $table = $this->sqlTableName();

        $now = ncore_dbDate();

        $sql = "UPDATE `$table`
                SET locked = NULL
                WHERE name = '$name'
                  AND value = '$value'";

        $this->db()->query($sql);
    }


    //
    // protected
    //
    protected function networkModeForced()
    {
        return true;
    }

    protected function isUniqueInBlog() {

        return true;
    }

    protected function sqlBaseTableName()
    {
        return 'lock';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name'   => 'string[47]',
            'value'  => 'string[47]',
            'expire' => 'datetime',
            'locked' => 'lock_date',
       );

       $indexes = array( 'name'  );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

}