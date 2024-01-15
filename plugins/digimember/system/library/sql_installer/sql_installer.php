<?php

class ncore_SqlInstallerLib extends ncore_Library
{
    const max_table_name_length = 64;

    private $sql_type_aliases = array(
        'textarea' => 'text',
        'select'   => 'string',
    );

    public function setup( $db, $table, $meta )
    {
        $max    = self::max_table_name_length;
        $length = strlen( $table );
        $update_Charset = false;

        if ($length > self::max_table_name_length)
        {
            $log_message = "The table name '$table' is too long. The name has $length characters, but the maximum is $max characters. Please select a shorter table name prefix and rename your tables accordingly. We suggest to not use more than 20 characters for the prefix.";
            $this->api()->logError( _ncore('database'), $log_message );
            return;
        }
        $this->createModelTable( $db, $table );

        $tableDefinition = $db->query("SHOW CREATE TABLE `$table`");


        $have_table = (bool) $tableDefinition;
        if ($have_table)
        {
            $row = (array) $tableDefinition[0];
            $sql_create = $row['Create Table'];
            $update_Charset = $this->updateCharset('utf8mb4', $sql_create); //check if charset needs update to utf8mb4 for DM-145
        }
        else
        {
            $this->createModelTable( $db, $table );
            $sql_create = '';
        }

        $this->applyChanges( $db, $table, $sql_create, $meta, $update_Charset );
    }

    public function teardown( $db, $table_name )
    {
        $sql = "DROP TABLE IF EXISTS `$table_name`";
        $db->query( $sql );
    }

    public function deleteSubTableEntry( $db, $main_table, $sub_table, $id_column )
    {
        $sql = "DELETE `$sub_table`
                FROM `$sub_table`
                LEFT JOIN `$main_table`
                    ON `$main_table`.id = `$sub_table`.$id_column
                WHERE `$main_table`.id IS NULL
                  AND `$sub_table`.$id_column IS NOT NULL";

       $db->query( $sql );

    }

    //
    // protected
    //
    protected function pluginDir()
    {
        return 'type';
    }

    //
    // private
    //
    private $loaded_plugins = array();

    private function createModelTable( $db, $table )
    {
        $id = $this->loadPlugin( 'id' );
        $datetime = $this->loadPlugin( 'datetime' );

        $settings = array( 'is_primary_key' => true );
        $sql_id = $id->render( $settings );
        $sql_created = $datetime->render();

        $collate = $datetime->collate();

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
    id $sql_id,
    created $sql_created
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=$collate";

        $db->query( $sql );
    }

    /**
     * Checks if given charset is set on table
     * returns true if it is not.
     * @param $searchFor
     * @param $sqlCreateStatement
     * @return bool
     */
    private function updateCharset($searchFor, $sqlCreateStatement) {
        return strpos($sqlCreateStatement, 'CHARSET='.$searchFor) === false;
    }

    private function applyChanges( $db, $table, $sqlCreate, $tableMeta, $updateCharset = false )
    {
        $columnMetas = $tableMeta['columns'];
        $indexMetas  = $tableMeta['indexes'];
        $uniqueMetas = ncore_retrieve( $tableMeta, 'uniques', array() );

        $newColumns = array();
        $modifiedColumns = array();
        $newIndexes = array();
        $newUniques = array();


        foreach ($columnMetas as $column => $definition )
        {
            list( $type, $meta ) = $this->parseMeta( $definition );
            $plugin = $this->loadPlugin( $type );
            $sql_column = $plugin->render( $meta );

            $sql_present = $this->retrieveSqlDefinition( $column, $sqlCreate );

            $missing = !$sql_present;
            $modified = $sql_present != $sql_column;

            if ($missing)
            {
                $newColumns[ $column ] = $sql_column;
            }
            elseif ($modified)
            {
                $modifiedColumns[ $column ] = $sql_column;
            }
        }

        $valid_index_columns = array_merge(
                                    array( 'id', 'created' ),
                                    array_keys( $columnMetas )
                               );

        foreach ($indexMetas as $column)
        {
            $column_valid = in_array( $column, $valid_index_columns );
            if (!$column_valid)
            {
                trigger_error( "Invalid index column '$column' in table '$table'" );
            }
            $have_index = $this->haveSqlIndex( $column, $sqlCreate );
            if (!$have_index)
            {
                $newIndexes[] = $column;
            }
        }

        foreach ($uniqueMetas as $column)
        {
            $have_index = $this->haveSqlUnique( $column, $sqlCreate );
            if (!$have_index)
            {
                $newUniques[] = $column;
            }
        }

        $sql_alter = array();
        foreach ($newColumns as $column => $sql)
        {
            $sql_alter[] = "ADD `$column` $sql";
        }

        foreach ($modifiedColumns as $column => $sql)
        {
            $sql_alter[] = "CHANGE `$column` `$column` $sql";
        }

        foreach ($newIndexes as $column)
        {
            $sql_alter[] = " ADD KEY(`$column`)";
        }

        foreach ($newUniques as $column)
        {
            if (is_string($column))
            {
                $sql_col = `$column`;
            }
            else
            {
                $sql_col = '';
                foreach ($column as $one)
                {
                    if ($sql_col) {
                        $sql_col .= ',';
                    }
                    $sql_col .= "`$one`";
                }
            }
            $sql_alter[] = " ADD UNIQUE( $sql_col )";
        }

        if ($sql_alter)
        {
            $sql = "ALTER TABLE `$table` " . implode(', ', $sql_alter );

            $db->query( $sql );
        }

        //updates charset to utf8mb4 DM-145
        if ($updateCharset) {
            $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4";
            $db->query( $sql );
        }
    }

    private function loadPlugin( $type  )
    {
        $type = ncore_retrieve( $this->sql_type_aliases, $type, $type );

        $plugin =& $this->loaded_plugins[ $type ];

        if (!isset($plugin))
        {
            $class_name = $this->loadPluginClass( $type );

            if (!$class_name) {
                throw new Exception( "Could not load plugin type '$type'" );
            }

            $plugin = new $class_name( $this );
        }

        return $plugin;
    }

    private function parseMeta( $definition )
    {
        if (is_array($definition))
        {
            $raw_type = ncore_retrieveAndUnset( $definition, 'type' );
            $meta     = $definition;
        }
        else
        {
            $raw_type = $definition;
            $meta     = array();
        }

        $have_args = preg_match('/^(.*)\[(.*)\]$/', $raw_type, $matches);
        if ($have_args)
        {
            $type                 = $matches[ 1 ];
            $args_comma_separated = $matches[ 2 ];
            $args                 = explode(',', $args_comma_separated);
        }
        else
        {
            $type = $raw_type;
            $args = array();
        }

        $meta[ 'args' ] = $args;

        return array( $type, $meta );
    }

    private function retrieveSqlDefinition( $searchColumn, $sqlCreate )
    {
        $lines = explode( "\n", $sqlCreate );

        foreach ($lines as $line)
        {
            $line = trim( str_replace( '`', '', $line ), " ," );

            $pos = strpos( $line, ' ' );
            if ($pos===false)
            {
                continue;
            }

            $column = substr( $line, 0, $pos );

            $matches = $column == $searchColumn;
            if ($matches)
            {
                $sql = substr( $line, $pos+1 );
                return $sql;
            }
        }

        return '';
    }

    private function haveSqlIndex( $searchColumn, $sqlCreate )
    {
        $index_key_words = array( 'KEY', 'INDEX' );
        $lines = explode( "\n", $sqlCreate );

        foreach ($lines as $line)
        {
            $line = trim( str_replace( '`', '', $line ), " ," );

            $pos = strpos( $line, ' ' );
            if ($pos===false)
            {
                continue;
            }

            $keyword = substr( $line, 0, $pos );

            $is_index = in_array( $keyword, $index_key_words );
            if (!$is_index)
            {
                continue;
            }

            $have_column = preg_match('/^.*\((.*)\)$/', $line, $matches);
            if (!$have_column)
            {
                continue;
            }

            $column = trim( $matches[ 1 ], ' `' );

            $matches = $column == $searchColumn;
            if ($matches)
            {
                return true;
            }
        }

        return false;
    }

    private function haveSqlUnique( $searchColumn, $sqlCreate )
    {
        if (empty($searchColumn)) {
            return true;
        }

        if (!is_array($searchColumn))
        {
            $searchColumn = array($searchColumn);
        }

        $have_no_uniques = stripos( $sqlCreate, 'UNIQUE KEY' ) === false;
        if ($have_no_uniques)
        {
            return false;
        }

        // UNIQUE KEY `access_key` (`access_key`,`name`)
        $have_uniques = preg_match_all( '/[,\n] *UNIQUE KEY.*\((.*)\)/', $sqlCreate, $matches );
        if (!$have_uniques)
        {
            return false;
        }

        $columns_comma_separated = $matches[1];
        foreach ($columns_comma_separated as $one)
        {
            $one = str_replace( array( '`',' '), '', $one );

            $present_columns = explode( ',', $one );

            if (count($present_columns) != count($searchColumn)) {
                continue;
            }

            $is_match = true;
            foreach ($searchColumn as $i => $search_col)
            {
                if ($search_col != $present_columns[$i]) {
                    $is_match = false;
                    break;
                }
            }
            if ($is_match) {
                return true;
            }
        }

        return false;
    }




}