<?php

class ncore_ArcfLinksData extends ncore_BaseData
{

    public function set(  $fieldData )
    {
        $where = array(
            'autoresponder'    => $fieldData['autoresponder'],
            'customfield'    => $fieldData['customfield'],
        );

        $row = $this->getWhere( $where );
        if ($row)
        {
            if ($fieldData && is_array($fieldData))
            {
                $data = array();
                foreach ($fieldData as $fieldKey => $fieldValue) {
                    $data[$fieldKey] = $fieldValue;
                }
                $this->update( $row->id, $data );
            }
            else
            {
                $this->delete( $row->id );
            }
        }
        elseif ($fieldData && is_array($fieldData))
        {
            $data = array();
            foreach ($fieldData as $fieldKey => $fieldValue) {
                $data[$fieldKey] = $fieldValue;
            }
            return $this->create( $data );
        }
    }

    public function getAllForAr($id) {
        return $this->getAll(array(
            'autoresponder' => $id
        ));
    }

    public function getAllForCf($id) {
        return $this->getAll(array(
            'customfield' => $id
        ));
    }

    public function getArListByCf($id) {
        $providerList = array();
        $arLibrary = $this->api->load->library( 'autoresponder_handler' );
        $allProviders = $arLibrary->getProviders();
        $arObjects = $this->getAllForCf($id);
        foreach ($arObjects as $arObject) {
            if ($arId = ncore_retrieve($arObject,'autoresponder', false)) {
                $autoresponderPlugin = $arLibrary->plugin($arId);
                $engineName = $autoresponderPlugin->getEngine();
                if ($providerName = ncore_retrieve($allProviders, $engineName, false)) {
                    $providerList[] = $providerName;
                }
            }
        }
        if (count($providerList) > 0) {
            $output = '<p>';
            $output .= implode('</p><p>',$providerList);
            $output .= '</p>';
            return $output;
        }
        return '<p>'._ncore('none').'</p>';
    }

    public function deleteByArAndCf($ar_id, $cf_id) {
        $link = $this->getWhere(array(
            'autoresponder' => $ar_id,
            'customfield' => $cf_id
        ));
        if ($link) {
            $this->delete($link->id);
        }
    }

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_ARCFLINK;
    }

    protected function sqlBaseTableName()
    {
        return 'arcf_links';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'autoresponder' => 'int',
            'customfield' => 'int',
            'mapping'    => 'string[63]',
       );

       $indexes = array();

       $meta = array(
           'columns' => $columns,
           'indexes' => $indexes,
       );

       return $meta;
    }

    /**
     * createTableIfNeeded
     * creates table of the model when called and the table doesnt exist.
     * @return bool
     */
    public function createTableIfNeeded() {
        global $wpdb;
        $table_name = $this->sqlTableName();
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
        if ( ! $wpdb->get_var( $query ) == $table_name ) {
            $initCore = $this->api->init();
            $initCore->forceUpgrade();
            return true;
        }
        return false;
    }

}