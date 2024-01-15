<?php

class ncore_ActionData extends ncore_BaseData
{

//    public function getConditionsCompatibleWithFacebook()
//    {
//        $all = array_keys( $this->conditionOptions() );
//
//        return array_values( array_diff( $all, $this->getConditionsNotCompatibleWithFacebook() ) );
//    }

//    public function getConditionsNotCompatibleWithFacebook()
//    {
//        return array( 'paused', 'never_online' );
//    }

//    public function isCompatibleWithFacebook( $action_obj_or_id )
//    {
//        if (!ncore_hasFacebookApp())
//        {
//            return false;
//        }
//
//        $action = $this->resolveToObj( $action_obj_or_id );
//
//        $is_compatible = in_array( $action->condition_type, $this->getConditionsCompatibleWithFacebook() );
//
//        return $is_compatible;
//    }


    public function sqlTableName()
    {
        return parent::sqlTableName();
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'action';
    }

    protected function defaultOrder()
    {
        return 'name ASC, id ASC';
    }

    protected function isUniqueInBlog()
    {
        return true;
    }

    protected function hasTrash()
    {
        return true;
    }

    protected function hasModified()
    {
        return true;
    }

    protected function sqlTableMeta()
    {
       $columns = array(

        'name' => 'string[63]',

        'is_active' => 'yes_no_bit',

        'condition_type'                => 'string[15]',

        'condition_page'                => 'int',
        'condition_page_view_time'      => 'int',

        'condition_login_count'         => 'int',
        'condition_login_after_days'    => 'int',

        'condition_paused_days'        => 'int',
        'condition_never_online_days'  => 'int',

        'condition_prd_expired_days'   => 'int',
        'condition_prd_expired_before' => 'yes_no_bit',
        'condition_prd_expired_last_queued_at' => 'lock_date',

        'condition_product_ids_comma_seperated' => 'text',

        'condition_webinar_ids_comma_seperated' => 'text',
        'condition_webinar_watch_for_seconds' => 'int',
        'condition_webinar_before_minutes' => 'int',

//        'fb_is_active'        => 'yes_no_bit',
//        'fb_wall_message'     => 'text',
//        'fb_wall_link'        => 'string[255]',
//        'fb_wall_picture'     => 'string[255]',
//        'fb_wall_name'        => 'string[63]',
//        'fb_wall_caption'     => 'string[127]',
//        'fb_wall_description' => 'text',
//        'fb_wall_source'      => 'string[255]',

        'klicktipp_is_active'   => 'yes_no_bit',
        'klicktipp_ar_id'       => 'id',
        'klicktipp_tags_add'    => 'text',
        'klicktipp_tags_remove' => 'text',

        'email_is_active' => 'yes_no_bit',
        'email_subject'   => 'string[255]',
        'email_body'      => 'text',
        'email_is_sent_if_push_is_sent' => 'yes_no_bit',

        'webpush_is_active' => 'yes_no_bit',
        'webpush_message_id' => 'id',

       );

       $indexes = array();

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }


    protected function buildObject( $object )
    {
        parent::buildObject( $object );
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values['is_active']           = 'Y';
        //  $values['fb_is_active']      = 'N';
        $values['klicktipp_is_active']  = 'N';
        $values['email_is_active']      = 'N';
        $values['webpush_is_active']    = 'N';
        $values['email_is_sent_if_push_is_sent'] = 'N';

        $values['condition_type']              = 'page_view';
        $values['condition_login_count']       = 1;
        $values['condition_login_after_days']  = 0;
        $values['condition_paused_days']       = 7;
        $values['condition_never_online_days'] = 7;

        $values['condition_webinar_watch_for_seconds'] = 30;
        $values['condition_webinar_before_minutes'] = 60;

        $site_url = home_url();

        $site_url_without_http = trim( str_replace( array( 'http://', 'https://' ), '', $site_url ), '/' );

        // $values['fb_wall_link']    = $site_url;
        // $values['fb_wall_caption'] = $site_url_without_http;
        // $values['fb_wall_source']  = $site_url;
        // $values['fb_wall_name']    = get_bloginfo( 'name' );

        return $values;
    }

}

