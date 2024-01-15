<?php

abstract class ncore_BaseData extends ncore_Model
{
    public function __construct( ncore_ApiCore $api, $file='', $dir='' )
    {
        parent::__construct( $api, $file, $dir );

        if ($this->networkModeForced())
        {
            $this->in_network_mode = true;
        }
    }

    public function sqlLockedDateInfo( $table_prefix='auto' )
    {
        $table_name = $table_prefix === 'auto'
                    ? $this->sqlTableName()
                    : $table_prefix.$this->unprefixedTableName();

        $lock_date_columns = array();

        $meta = $this->sqlTableMeta();

        foreach ($meta['columns'] as $column => $type)
        {
            $is_lock_date = $type === 'lock_date';
            if ($is_lock_date) {
                $lock_date_columns[] = $column;
            }
        }

        return array( $table_name, $lock_date_columns );
    }

    public function isId( $txt )
    {
        if (is_numeric($txt) && $txt > 0) {
            return true;
        }

        return false;
    }

    public function setup()
    {
        $table_name = $this->sqlTableName();
        $meta = $this->_getSqlTableMeta();

        /** @var ncore_SqlInstallerLib $installer */
        $installer = $this->api->load->library( 'sql_installer' );

        $db = $this->db();

        $installer->setup( $db, $table_name, $meta );

        $this->onSetup();
    }

    public function teardown()
    {
        $table_name = $this->sqlTableName();

        /** @var ncore_SqlInstallerLib $installer */
        $installer = $this->api->load->library( 'sql_installer' );

        $db = $this->db();

        $installer->teardown( $db, $table_name );
    }

    public function sqlTableExists()
    {
        $table_name = $this->sqlTableName();

        $query = $this->db()->query("SHOW TABLES LIKE '$table_name'");

        $table_exists = (bool) $query;

        return $table_exists;
    }

    public function resolveToId( $object_or_id )
    {
        return is_numeric( $object_or_id )
               ? $object_or_id
               : ncore_retrieve( $object_or_id, 'id', false );
    }

    public function resolveToObj( $object_or_id )
    {
        return is_numeric( $object_or_id ) || is_string( $object_or_id )
               ? $this->get( $object_or_id )
               : $object_or_id;
    }

    public function resolveToObjCached( $object_or_id )
    {
        return is_numeric( $object_or_id ) || is_string( $object_or_id )
               ? $this->getCached( $object_or_id )
               : $object_or_id;
    }

    public function create( $data )
    {
        $this->onBeforeUpdate( $new_id=0, $data );

        $this->packSerializedData( $data );

        $data = $this->sanitizeData( __FUNCTION__, $data );

        $default_values = $this->defaultValues();
        foreach ($default_values as $column => $value)
        {
            if (!isset( $data[$column] ) )
            {
                $data[$column] = $value;
            }
        }

        $tableName = $this->sqlTableName();

        $sql_col = "";
        $sql_val = "";


        foreach ($data as $column => $value)
        {
            if ($sql_col)
            {
                $sql_col .= ',';
                $sql_val .= ',';
            }

            $column_esc = $this->colEscape( $column );
            $value_esc = $this->valEscape( $value );

            $sql_col .= $column_esc;
            $sql_val .= $value_esc;
        }

        $sql = "INSERT INTO `$tableName` ($sql_col) VALUES ($sql_val)";

        $this->db()->query( $sql );

        $new_id = $this->db()->insertId();

        $this->deleteVoidSubTableEntries();

        $this->onUpdate( $new_id, $data );

        if ($this->callOnUpdateDiff()) {
            $new_object = $this->get( $new_id );
            $this->onUpdateDiff( $new_object, null );
        }

        return $new_id;
    }

    public function defaultValue( $key, $fallback='' )
    {
        $values = $this->defaultValues();

        return ncore_retrieve( $values, $key, $fallback );
    }

    public function emptyObject()
    {
        $values = $this->defaultValues();

        $obj = (object) $values;

        $this->buildObject( $obj );

        return $obj;
    }

    protected function onBeforeUpdate( $obj_or_id, &$data )
    {
    }

    protected function onBeforeCopy( &$data )
    {
    }

    protected function callOnUpdateDiff() {
        return false;
    }

    protected function onUpdateDiff( $new_object, $old_object ) {
    }

    protected function onSetup()
    {
    }


    public function update( $obj_or_id, $data, $where = array() )
    {
        $this->onBeforeUpdate( $obj_or_id, $data );

        $id = $this->resolveToId( $obj_or_id );

        $this->packSerializedData( $data, $id );

        $tableName = $this->sqlTableName();

        $data = $this->sanitizeData( __FUNCTION__, $data );

        if (empty($data)) {
            return false;
        }

        $old_object = null;
        if ($this->callOnUpdateDiff()) {
            $old_object = $this->get( $id );
        }

        $sql_set = "";

        foreach ($data as $column => $value)
        {
            if ($sql_set)
            {
                $sql_set .= ',';
            }

            $column_esc = $this->colEscape( $column );
            $value_esc = $this->valEscape( $value );

            $sql_set .= "$column_esc = $value_esc";
        }

        if (is_object($obj_or_id))
        {
            foreach ($data as $column => $value)
            {
                $obj_or_id->$column = $value;
            }
        }

        $where['id'] = $id;
        $sql_where = $this->renderWhere( $where );

        $sql = "UPDATE `$tableName` SET $sql_set";

        if ($sql_where)
        {
            $sql .= " WHERE $sql_where";
        }

        $this->db()->query( $sql );

        $modified = $this->db()->modified();

        $must_update_modification_date = $modified && $this->hasModified();

        if ($must_update_modification_date)
        {
            $now = ncore_dbDate();
            $id_esc = $this->valEscape( $id );
            $sql = "UPDATE `$tableName` SET modified='$now' WHERE id = $id_esc";
            $this->db()->query( $sql );
        }

        if ($modified)
        {
            $this->onUpdate( $id, $data );
        }


        $this->deleteVoidSubTableEntries();

        if ($modified && $this->callOnUpdateDiff()) {
            $new_object = $this->get( $id );
            $this->onUpdateDiff( $new_object, $old_object );
        }

        return (bool) $modified;
    }

    public function delete( $obj_or_id )
    {
        $id = $this->resolveToId( $obj_or_id );

        $tableName = $this->sqlTableName();

        $id = $this->db()->escape( $id );

        $sql = "DELETE FROM `$tableName` WHERE id = '$id'";

        $this->db()->query( $sql );

        $modified = $this->db()->modified();

        $this->deleteVoidSubTableEntries();

        return (bool) $modified;
    }

    public function deleteWhere( $where )
    {
        $count = 0;
        $all = $this->getAll( $where );

        foreach ($all  as $one)
        {
            $modified = $this->delete( $one->id );
            if ($modified) {
                $count++;
            }
        }

        return $count;
    }


    public function copy( $id, $set_columns = array() )
    {
        $obj = $this->get( $id );
        $data = (array) $obj;

        $columns_to_unset = $this->notCopiedColumns();
        foreach ($columns_to_unset as $key) {
            unset( $data[ $key ] );
        }
        unset( $data['id'] );

        $name_column = $this->nameColumn();
        $name = ncore_retrieve( $data, $name_column );

        if ($name)
        {
            $data[$name_column] = $this->copyName( $name );
        }

        foreach ($set_columns as $key => $value)
        {
            $data[ $key ] = $value;
        }

        $this->onBeforeCopy( $data );

        $new_id = $this->create( $data );

        $sub_models = $this->subModelsToCopy();
        foreach ($sub_models as $model_name => $id_name)
        {
            /** @var ncore_BaseData $model */
            $model = $this->api->load->model( "data/$model_name" );

            $where = array( $id_name => $id );
            $set_columns = array( $id_name => $new_id );;

            $all = $model->getAll( $where );
            foreach ($all as $one)
            {
                $model->copy( $one->id, $set_columns );
            }
        }

        return $new_id;
    }

    public function get( $id )
    {
        $where = array( 'id' => $id );

        return $this->getWhere( $where );
    }

    public function getCached( $id )
    {
        $cache_key = $this->in_network_mode
                   ? "n-$id"
                   : "x-$id";

        $obj =& $this->cache[ $cache_key ];

        if (!isset( $obj ) || !empty($_GET['reload']))
        {
            $obj = $this->get( $id );
        }

        return $obj;
    }

    public function getWhere( $where, $order_by='' )
    {
        $list = $this->getAll( $where, $limit='0,1', $order_by );

        return $list
             ? $list[0]
             : false;
    }

    public function getCount( $where=array() )
    {
        $tableName = $this->sqlTableName();

        $sql = "SELECT COUNT(1) AS count FROM `$tableName`";

        $sql_where = $this->renderWhere( $where );

        if ($sql_where)
        {
            $sql .= " WHERE $sql_where";
        }

        $list = $this->db()->query( $sql );

        $count = $list[0]->count;

        return $count;

    }

    public function getAll( $where=array(), $limit=false, $order_by='' )
    {
        $tableName = $this->sqlTableName();

        $columns = $this->sqlColumns();

        $sql = "SELECT $columns FROM `$tableName`";

        $sql_where = $this->renderWhere( $where );

        if ($sql_where)
        {
            $sql .= " WHERE $sql_where";
        }

        if ($order_by)
        {
            $order_by .= ', ';
        }
        $order_by .= $this->defaultOrder();

        if ($order_by)
        {
            $sql .= " ORDER BY $order_by";
        }

        if ($limit)
        {
            list( $first, $count ) = explode( ',', $limit);
            $first = intval( $first );
            $count = intval( $count );
            $sql .= " LIMIT $first,$count";
        }

        return $this->getBySql( $sql );
    }

    public function asArray( $value_column, $key_column='id', $where = array() )
    {
        $limit = '';
        $order_by = "$value_column ASC";

        $all = $this->getAll( $where, $limit, $order_by );

        $array = array();
        foreach ($all as $one)
        {
            $key = $one->$key_column;
            $val = $one->$value_column;

            $array[ $key ] = $val;
        }

        return $array;
    }

    public function getByColumn( $column, $value )
    {
        $order_by = 'id ASC';

        $where = array( $column => $value );

        return $this->getWhere( $where, $order_by );
    }

    public function moveToTrash( $id )
    {
        if ($this->hasTrash())
        {
            $data = array( 'deleted' => ncore_dbDate() );
            $modified = $this->update( $id, $data );
        }
        else
        {
            $modified = $this->delete( $id );
        }

        return $modified;
    }

    public function retoreFromTrash( $id )
    {
        if ($this->hasTrash())
        {
            $data = array( 'deleted' => null );
            $where = array( 'deleted !=' => null );
            $modified = $this->update( $id, $data, $where );
        }
        else
        {
            $modified = false;
        }

        return $modified;
    }

    public function status( $row )
    {
        $is_deleted = (bool) ncore_retrieve( $row, 'deleted' );

        return $is_deleted
             ? 'deleted'
             : 'created';
    }

    public function statusLabels()
    {
        $labels = array(
            'created' => _ncore('created'),
        );

        if ($this->hasTrash())
        {
            $labels['deleted'] = _ncore('deleted');
        }

        return $labels;
    }

    public function search( $column, $search_for, $compare='like' )
    {
        $column = ncore_WashText( $column );

        $where = $compare == 'equal'
               ? array( $column => $search_for)
               : array( "$column like" => '%'.$search_for.'%' );

        $matches = $this->getAll( $where, '', "$column ASC" );

        return $matches;
    }

    public function pushNetworkMode() {

        if ($this->networkModeForced()) {
            $this->in_network_mode = true;
            return;
        }

        $this->network_modes[] = $this->in_network_mode;
        $this->in_network_mode = true;
    }

    public function popNetworkMode() {

        if ($this->networkModeForced()) {
            $this->in_network_mode = true;
            return;
        }

        if ($this->network_modes)
        {
            $previous = array_pop( $this->network_modes );
            $this->in_network_mode = $previous;
        }
    }

    public function getDeleted( $min_age_in_days )
    {
        if (!$this->hasTrash())
        {
            return array();
        }

        $min_age_in_days = ncore_washInt( $min_age_in_days );

        $table_name = $this->sqlTableName();

        $columns = $this->sqlColumns();

        $now = ncore_dbDate();

        $sql = "SELECT $columns FROM `$table_name` WHERE deleted < '$now' - INTERVAL $min_age_in_days DAY";

        return $this->getBySql( $sql );
    }


    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_PLUGIN;
    }

    public function unprefixedTableName() {

        $basename = $this->sqlBaseTableName();

        $pluginname = $this->isUniqueInBlog()
                    ? 'digimember'
                    : $this->api->pluginName();

        $tablename = $pluginname . '_' . $basename;

        return $tablename;

    }

    //
    // protected
    //

    protected function sqlTableName()
    {
        $prefix = $this->in_network_mode
                ? $this->db()->networkTableNamePrefix()
                : $this->db()->tableNamePrefix();

        $tablename = $prefix . $this->unprefixedTableName();

        return $tablename;
    }


    protected function db()
    {
        global $ncore_db;
        if (!isset($ncore_db))
        {
            /** @var ncore_DbLib $ncore_db */
            $ncore_db = $this->api->load->library( 'db' );
        }

        return $ncore_db;
    }

    protected function networkModeForced()
    {
        return false;
    }

    protected function isUniqueInBlog() {

        return false;
    }

    protected function sqlExtraColumns()
    {
        return array();
    }

    protected function defaultOrder()
    {
        return 'id ASC';
    }

    protected function defaultValues()
    {
        $values['created'] = ncore_dbDate();

        if ($this->hasModified())
        {
            $values['modified'] = $values['created'];
        }

        return $values;
    }

    protected function buildObject( $object )
    {
        $object->table = $this->sqlBaseTableName();

        $object->status = $this->status( $object );

        $this->unpackSeriazedData( $object );
    }

    protected function serializedDataMeta() {
        return array(
            // 'data_serialized' => 'prefix_',
        );
    }

    protected function serializedColumns() {
        return array(
            // column_name without _serialized suffix
        );
    }

    protected function nameColumn()
    {
        return 'name';
    }

    protected function copyNameTemplate()
    {
        return _ncore( 'Copy of [NAME]' );
    }

    protected function notCopiedColumns()
    {
        return array( 'created', 'published', 'deleted', 'modified' );
    }


    protected function subModelsToCopy()
    {
        $models = array();

        return $models;
    }


    protected function hasTrash()
    {
        return false;
    }

    protected function hasModified()
    {
        return false;
    }

    protected function onWhere( &$where, $compound )
    {
        if ($this->hasTrash())
        {
            $this->maybeAddDeletedColToWhere( $where, $compound );
        }
    }

    abstract protected function sqlBaseTableName();
    abstract protected function sqlTableMeta();

    protected function subTableMetas()
    {
        return array(

        );
    }

    protected function protectedColumns( /** @noinspection PhpUnusedParameterInspection */ $method )
    {
        $columns = array( 'id', 'created', 'modified' );
//        switch ($method)
//        {
//            case 'create':
//                break;
//            case 'update':
//                break;
//        }
        return $columns;
    }

    protected function existingColumns()
    {
        $system_columns = array( 'id', 'created', 'modified', 'deleted'  );

        $meta = $this->sqlTableMeta();

        $columns = array_keys( $meta['columns'] );

        $columns = array_merge( $system_columns, $columns);

        foreach ($this->serializedColumns() as $col)
        {
            $columns[] = $col . '_serialized';
        }

        foreach ($this->serializedDataMeta() as $col => $prefix) {
            $columns[] = $col;
        }

        return $columns;
    }

    protected function onBeforeSave( &$data )
    {
    }

    protected function onUpdate( $id, $data )
    {
    }

    protected function renderWhere( $where, $compound='AND' )
    {
        $this->onWhere( $where, $compound );

        $sql_where = '';

        foreach ($where as $key_operator => $value)
        {
            if ($sql_where)
            {
                $sql_where .= " $compound ";
            }

            $is_or_condition = strtolower($key_operator) == 'or';
            if ($is_or_condition)
            {
                $sql_where .= '(' . $this->renderWhere( $value, 'OR' ) . ')';
                continue;
            }

            list( $key, $operator ) = $this->explodeKeyAndOperator( $key_operator );

            $is_sql = $operator === 'sql';
            if ($is_sql)
            {
                $sql_where .= "($value)";
                continue;
            }


            $is_null = !isset( $value ) || $value === 'NULL';

            $key = $this->colEscape( $key );
            $value = $this->valEscape( $value, $operator );

            if ($is_null)
            {
                $negate = $operator == '!=';
                $sql_where .= $negate
                            ? "($key IS NOT NULL)"
                            : "($key IS NULL)";
            }
            else
            {
                $sql_where .= "$key $operator $value";
            }
        }

        return $sql_where;
    }

    protected function getBySql( $sql )
    {
        $list = $this->db()->query( $sql  );

        foreach ($list as $one)
        {
            $this->buildObject( $one );
        }

        return $list;
    }

    //
    // private
    //
    private $cache = array();
    private $in_network_mode = false;
    private $network_modes = array();

    private function _sqlTableName( $basename )
    {
        $prefix = $this->db()->tableNamePrefix();

        $pluginname = $this->api->pluginName();

        $tablename = $prefix . $pluginname . '_' . $basename;

        return $tablename;
    }

    private function colEscape( $column )
    {
        return '`' . $this->db()->escape( $column ) . '`';
    }

    private function valEscape( $value, $operator = '=' )
    {
        if (!isset($value) || $value === 'NULL')
        {
            return 'NULL';
        }

        $is_in = $operator == 'in';
        if ($is_in)
        {
            if (!is_array($value))
            {
                $value = explode( ',', $value );
            }
            $sql = '';
            foreach ($value as $one)
            {
                if ($sql)
                {
                    $sql .= ',';
                }

                $sql .= "'" . $this->db()->escape( $one ) . "'";
            }

            if (!$sql)
            {
                return '(NULL)';
            }

            return "($sql)";
        }


        $is_now_date = strlen($value) >= 5
                    && is_string($value)
                    && $value[0] == 'N'
                    && substr( $value, 0, 5 ) == 'NOW()';

        if ($is_now_date)
        {
            $now = ncore_dbDate();
            $rest = substr( $value, 5 );
            if (!$rest)
            {
                $rest='';
            }
            return "'$now'$rest";
        }

        $is_like = $operator == 'like';
        if ($is_like)
        {
            $has_wildcard = strpos( $value, '%' ) !== false;
            if (!$has_wildcard)
            {
                $value = '%'.$value.'%';
            }
        }

        return "'" . $this->db()->escape( $value ) . "'";
    }

    private function explodeKeyAndOperator( $key_operator )
    {
        $tokens = explode( ' ', $key_operator );
        $key = ncore_retrieve( $tokens, 0 );
        $operator = ncore_retrieve( $tokens, 1, '=' );

        $key = trim( $key );
        $operator = trim( strtolower($operator) );

        return array( $key, $operator );
    }

    private function sqlColumns()
    {
        $sql_columns = '*';

        $columns = $this->sqlExtraColumns();

        foreach ($columns as $name => $sql )
        {
            $sql_columns .= ", ($sql) AS `$name`";
        }

        return $sql_columns;
    }

    private function _getSqlTableMeta()
    {
        $meta = $this->sqlTableMeta();

        if ($this->hasTrash())
        {
            $meta['columns']['deleted'] = 'lock_date';
        }
        if ($this->hasModified())
        {
            $meta['columns']['modified'] = 'datetime';
        }

        foreach ($this->serializedColumns() as $col)
        {
            $meta['columns'][ $col.'_serialized' ] = 'text';
        }

        foreach ($this->serializedDataMeta() as $col => $prefix) {
            $meta['columns'][ $col ] = 'text';
        }

        return $meta;
    }

    private function maybeAddDeletedColToWhere( &$where, $compound )
    {
        $is_and = strtoupper( trim($compound) ) == 'AND';

        if (!$is_and)
        {
            return;
        }

        foreach ($where as $key => $value)
        {
            $have_deleted = $key == 'deleted'
                                 || ncore_stringStartsWith( $key, 'deleted ' );
            if ($have_deleted)
            {
                return;
            }
        }

        $where[ 'deleted'] = null;
    }


    private function sanitizeData( $method, $data )
    {
        $existing_columns = $this->existingColumns();

        $protected_columns = $this->protectedColumns( $method );

        foreach ($data as $column => $value)
        {
            $is_protected = in_array( $column, $protected_columns );

            $is_existing = in_array( $column, $existing_columns );

            $is_valid = $is_existing && !$is_protected;

            if (!$is_valid)
            {
                unset( $data[ $column ]);
            }
        }

        $this->onBeforeSave( $data );

        return $data;
    }

    private function deleteVoidSubTableEntries()
    {
        $metas = $this->subTableMetas();
        if (!$metas)
        {
            return;
        }

        /** @var ncore_SqlInstallerLib $installer */
        $installer = $this->api->load->library( 'sql_installer' );

        $db = $this->db();

        $metas = $this->subTableMetas();

        $main_table = $this->sqlTableName();

        foreach ($metas as $table => $id_column)
        {
            $sub_table = $this->_sqlTableName( $table );
            $installer->deleteSubTableEntry( $db, $main_table, $sub_table, $id_column );

            /** @var ncore_BaseData $model */
            $model = $this->api->load->model( "data/$table" );
            $model->deleteVoidSubTableEntries();
        }
    }

    private function copyName( $name )
    {
        $template = $this->copyNameTemplate();

        $pattern = str_replace( '[NAME]', '(.*?)', $template );

        $is_copied = preg_match( "|^$pattern\$|", $name, $matches );

        if ($is_copied)
        {
            $name = $matches[1];

            $has_number = preg_match( '|^(.*) \(([0-9]*)\)$|', $name, $matches );
            if ($has_number)
            {
                $name   = $matches[1];
                $number = $matches[2];
                $number++;
            }
            else
            {
                $number = 2;
            }
        }
        else
        {
            $number = 1;
        }

        $name_column = $this->nameColumn();

        $new_name = '';
        $tries = 100;
        while ($tries--)
        {
            $new_base_name = $number >= 2
                           ? "$name ($number)"
                           : $name;

            $new_name = str_replace( '[NAME]', $new_base_name, $template );

            $where = array( $name_column => $new_name );

            $is_used = (bool) $this->getWhere( $where );
            if (!$is_used)
            {
                break;
            }

            $number++;
        }

        return $new_name;

    }

    protected function sanitizeSerializedData( /** @noinspection PhpUnusedParameterInspection */ $column, $array )
    {
        return $array;
    }

    private function packSerializedData( &$data, $id = false ) {

        foreach ($this->serializedColumns() as $column)
        {
            $have_data = isset( $data[$column] );
            if (!$have_data) {
                continue;
            }

            $array = $data[$column]
                   ? $data[$column]
                   : array();

            $array = $this->sanitizeSerializedData( $column, $array );

            $value_serialized = empty($array)
                              ? ''
                              : serialize( $array );
            unset($data[$column]);
            $data[ $column . '_serialized' ] = $value_serialized;
        }

        if (!$this->serializedDataMeta()) {
            return;
        }

        $must_load_seralized_data = (bool) $id;

        $obj = $must_load_seralized_data
              ? $this->get( $id )
              : false;

        foreach ($this->serializedDataMeta() as $column => $prefix)
        {
            $have_data = isset( $data[$column] );
            if (!$have_data) {
                continue;
            }

            $data_serialized = ncore_retrieve( $data, $column );

            if (!$data_serialized && $obj) {
                $data_serialized = ncore_retrieve( $obj, $column );
            }

            $array = $data_serialized
                    ? unserialize( $data_serialized )
                    : array();


            foreach ($data as $key => $value) {
                $is_seralized = ncore_stringStartsWith( $key, $prefix );
                if ($is_seralized) {
                    $array[ $key ] = $value;
                    unset( $data[ $key ] );
                }
            }

            $array = $this->sanitizeSerializedData( $column, $array );

            $data[ $column ] = serialize( $array );
        }
    }

    private function unpackSeriazedData( $object ) {


        foreach ($this->serializedColumns() as $col)
        {
            $value_serialized = ncore_retrieveAndUnset( $object, $col . '_serialized' );

            $value = $value_serialized
                   ? @unserialize( $value_serialized )
                   : null;

            $object->$col = $value;
        }

        foreach ($this->serializedDataMeta() as $column => $prefix)
        {
            $data_serialized = ncore_retrieve( $object, $column );
            if (!$data_serialized) {
                continue;
            }

            $array = unserialize( $data_serialized );
            foreach ($array as $key => $value) {

                if (!isset($object->$key)) {
                    $object->$key = $value;
                }

            }
        }
    }

    public function updateTableIfNeeded() {
        global $wpdb;
        $table_schema = $wpdb->dbname;
        $table_name = $this->sqlTableName();
        $tableData = $this->sqlTableMeta();
        foreach ($tableData['columns'] as $columnName => $columnData) {
            $row = false;
            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '".$table_schema."' AND table_name = '".$table_name."' AND column_name = '".$columnName."'"  );
            if ( is_array($row) && count($row) < 1 ) {
                $initCore = $this->api->init();
                $initCore->forceUpgrade();
                return true;
            }
        }
        return false;
    }
}
