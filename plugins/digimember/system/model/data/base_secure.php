<?php

abstract class ncore_BaseSecureData extends ncore_BaseData
{

    public function accessCheck( $enable )
    {
        $previous = $this->access_check_enabled;
        $this->access_check_enabled = (bool) $enable;
        return $previous;
    }

    public function readAccessGranted( $object )
    {
        if ($this->accessCheckDisabled())
        {
            return true;
        }

        return $this->mayRead( $object );
    }

    public function writeAccessGranted( $object )
    {
        if ($this->accessCheckDisabled())
        {
            return true;
        }

        return $this->mayUpdate( $object );
    }

    public function createAccessGranted()
    {
        if ($this->accessCheckDisabled())
        {
            return true;
        }

        return $this->mayCreate();
    }

    public function deleteAccessGranted( $object )
    {
        if ($this->accessCheckDisabled())
        {
            return true;
        }

        return $this->mayDelete( $object );
    }


    public function create( $data )
    {
        if (!$this->createAccessGranted())
        {
            $this->onCreateAccessDenied();
            return false;
        }

        return parent::create( $data );
    }

    public function update( $obj_or_id, $data, $where = array() )
    {
        if (!$this->writeAccessGranted( $obj_or_id))
        {
            $obj = $this->resolveToObj( $obj_or_id );
            $this->onWriteAccessDenied( $obj );
            return false;
        }

        return parent::update( $obj_or_id, $data, $where );
    }

    public function delete( $id )
    {
        if (!$this->deleteAccessGranted( $id))
        {
            $obj = $this->resolveToObj( $id );
            $this->onDeleteAccessDenied( $obj );
            return false;
        }

        return parent::delete( $id );
    }

    public function getAll( $where=array(), $limit=false, $order_by='' )
    {
        $must_update_indeces = false;

        $all = parent::getAll( $where, $limit, $order_by );

        foreach ($all as $index => $one)
        {
            if (!$this->readAccessGranted( $one ))
            {
                $this->onReadAccessDenied( $one );
                unset( $all[ $index ] );
                $must_update_indeces = true;
            }
        }

        if ($must_update_indeces)
        {
            $all = array_values( $all );
        }

        return $all;
    }

    protected function ownerKey()
    {
        return 'user_id';
    }

    protected function mayRead( $object_or_id )
    {
        if ($this->currrentUserIsAdmin())
        {
            return true;
        }

        if ($this->currentUserIsOwner( $object_or_id ))
        {
            return true;
        }

        return false;
    }

    protected function mayUpdate( $object_or_id )
    {
        if ($this->currrentUserIsAdmin())
        {
            return true;
        }

        if ($this->currentUserIsOwner( $object_or_id ))
        {
            return true;
        }

        return false;
    }

    protected function mayDelete( $object_or_id )
    {
        if ($this->currrentUserIsAdmin())
        {
            return true;
        }

        if ($this->currentUserIsOwner( $object_or_id ))
        {
            return true;
        }

        return false;
    }

    protected function mayCreate()
    {
        return true;
    }

    protected function onCreateAccessDenied()
    {
        $class = get_class( $this );
        throw new Exception( "Create access denied for class $class" );
    }

    protected function onDeleteAccessDenied( $obj )
    {
        $class = get_class( $this );

        $label = is_object( $obj )
               ? '#' . ncore_retrieve( $obj, 'id', 0 )
               : $obj;

        throw new Exception( "Delete access denied for class $class on object $label" );
    }

    protected function onWriteAccessDenied( $obj )
    {
        $class = get_class( $this );

        $label = is_object( $obj )
               ? '#' . ncore_retrieve( $obj, 'id', 0 )
               : $obj;

        throw new Exception( "Write access denied for class $class on object $label" );
    }

    protected function onReadAccessDenied( $obj )
    {
        $class = get_class( $this );

        $label = is_object( $obj )
               ? '#' . ncore_retrieve( $obj, 'id', 0 )
               : $obj;

        throw new Exception( "Read access denied for class $class on object $label" );
    }

    protected function adminCapability()
    {
        return array( 'manage_options' );
    }

    protected function currentUserIsOwner( $object_or_id )
    {
        $object = $this->resolveToObj( $object_or_id );

        $key = $this->ownerKey();
        $owner_id = ncore_retrieve( $object, $key );

        $current_user_id = ncore_userId();

        $is_owner = $current_user_id > 0
                  && $owner_id == $current_user_id;

        return $is_owner;
    }

    private $is_admin = null;
    private $access_check_enabled = true;

    protected function currrentUserIsAdmin()
    {
        if (!isset( $this->is_admin ))
        {
            $this->is_admin = false;
            foreach ($this->adminCapability() as $capabality){
                if (current_user_can( $capabality))
                {
                    $this->is_admin = true;
                    break;
                }
            }
        }

        return $this->is_admin;
    }


    private function accessCheckDisabled()
    {
        return !$this->access_check_enabled;
    }


}