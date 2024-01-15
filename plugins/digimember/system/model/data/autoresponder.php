<?php

class ncore_AutoresponderData extends ncore_BaseData
{
    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'autoresponder';
    }

    protected function defaultOrder()
    {
        return 'id ASC';
    }

    protected function hasTrash()
    {
        return true;
    }

    protected function hasModified()
    {
        return true;
    }


    public function status( $row )
    {
        $status = parent::status( $row );

        $is_deleted = $status == 'deleted';

        $is_active = $row->is_active == 'Y';

        if ($is_deleted)
        {
            return 'deleted';
        }

        if ($is_active)
        {
            return 'active';
        }

        return $status;
    }

    public function getOptions( $null_entry_label=false, $where=array() )
    {
        $this->api->load->helper( 'array' );

        $lib = $this->api->load->library( 'autoresponder_handler' );

        if (!isset($where['is_active']))
        {
            $where['is_active'] = 'Y';
        }

        $all = $this->getAll( $where );

        foreach ($all as $one)
        {
            $one->label = $lib->label( $one );
        }

        $options = ncore_listToArraySorted( $all, 'id', 'label' );
        if ($null_entry_label)
        {
            $this->api->load->helper( 'html_input' );

            if (!is_string($null_entry_label))
            {
                $null_entry_label = _ncore('Select autoresponder' );
            }
            $null_entry = array( 0 => ncore_htmlSelectNullEntryLabel( $null_entry_label ) );

            $options = $null_entry + $options;
        }

        return $options;
    }

    public function statusLabels()
    {
        $labels = parent::statusLabels();

        $labels['active'] = _ncore( 'Active' );
        $labels['created'] = _ncore( 'Not active' );

        return $labels;
    }

    public function getForProduct( $product_id )
    {
        $where = array( 'is_active' => 'Y' );

        $all = $this->getAll( $where );

        $result = array();

        foreach ($all as $one)
        {
            $product_ids = $one->product_ids;
            $match = in_array( 'all', $product_ids ) || in_array( $product_id, $product_ids );
            if ($match)
            {
                $result[] = $one;
            }
        }

        return $result;
    }


    protected function sqlTableMeta()
    {
       $columns = array(
        'name'                         => 'string[47]',
        'engine'                       => 'string[31]',
        'is_active'                    => 'yes_no_bit',
        'product_ids_comma_seperated'  => 'text',
        'data_serialized'              => 'text',
        'is_user_opted_out_if_deleted' => array( 'type' => 'yes_no_bit', 'default' => 'N' ),
        'is_personal_ar_data_exported' => array( 'type' => 'yes_no_bit', 'default' => 'N' ),
       );

       $indexes = array();
       // $indexes = array( 'code' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function isUniqueInBlog()
    {
        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values['is_active']                     = 'Y';
        $values['product_ids_comma_seperated']   = 'all';
        $values['data_serialized']               = '';
        $values['is_user_opted_out_if_deleted' ] = 'N';
        $values['is_personal_ar_data_exported' ] = 'N';

        return $values;
    }

    protected function buildObject( $object )
    {
        parent::buildObject( $object );

//        $must_fix = $object
//                 && $object->engine == 'leadmotor'
//                 && $object->id;
//        if ($must_fix) {
//            $data = array( 'engine' => 'cleverreach' );
//            $this->update( $object->id, $data );
//            $object->id->engine = 'cleverreach';
//        }

        $object->product_ids = $object->product_ids_comma_seperated
                             ? explode( ',', $object->product_ids_comma_seperated )
                             : array();

        if ($object->data_serialized)
        {
            $data = unserialize( $object->data_serialized );
            $object->data = $data;
        }
        else
        {
            $object->data = array();
        }
    }


}