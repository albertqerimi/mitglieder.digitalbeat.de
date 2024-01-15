<?php

class ncore_LogData extends ncore_BaseData
{
    const log_keep_days = 90;

    public function log( $level, $section, $message_templ, $arg1='', $arg2='', $arg3='', $arg4='', $arg5='', $arg6='' )
    {
        $user_id = ncore_userId();

        if (NCORE_DEBUG) {
            $clean = str_replace( '%%', '', $message_templ );
            $count = substr_count( $clean, '%' );
            if ($count>=4) {
                trigger_error( "Too many placeholders in log message:  $message_templ" );

                $message_templ = sprintf( $message_templ, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6, '', '', '', '', '' );
            }
        }

        $message = sprintf( $message_templ, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6 );

        $data = array(
            'level'   => $level,
            'user_id' => $user_id,
            'section' => $section,
            'message' => $message,
        );

        $this->create( $data );
    }

    public function cronDaily()
    {
        $days = self::log_keep_days;

        $db = $this->db();

        $table = $this->sqlTableName();

        $now = ncore_dbDate();

        $query = $db->query("DELETE FROM `$table`
                             WHERE created < '$now' - INTERVAL $days DAY");
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'log';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'user_id' => 'id',
            'section' => 'string[15]',
            'message' => 'string[255]',
            'level'   => 'string[7]',
       );

       $indexes = array();

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function isUniqueInBlog() {

        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values['level'] = 'info';

        return $values;
    }



}