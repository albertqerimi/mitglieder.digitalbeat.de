<?php

abstract class digimember_AutoresponderHandler_PluginWithTags extends digimember_AutoresponderHandler_PluginBase
{
    abstract public function getTagOptions();

    abstract public function setTags( $email, $add_tag_ids_comma_seperated, $remove_tag_ids_comma_seperated );

    abstract public function createTag( $new_tag_name );

    protected function hasActionSupport() {
        return true;
    }

}