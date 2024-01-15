<?php

$load->loadPluginClass( 'base_url' );

class ncore_RuleValidator_RulePluginList extends ncore_RuleValidator_RuleBase
{
    public function validate( $plugin_list_comma_seperated, $arg1='', $arg2='', $arg3='' )
    {
        $this->api->load->helper( 'array' );
        $plugin_names = ncore_explodeAndPurgeArray( array( ';', ',', "\r", "\n" ), $plugin_list_comma_seperated );

        if (!function_exists('get_plugins')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $present_plugins = get_plugins();

        $invalid_plugins = array();

        foreach ($plugin_names as $plugin_name) {

            $plugin_name_lower = strtolower( $plugin_name);

            foreach ($present_plugins as $plugin_file => $plugin_meta) {

                $one_plugin_name_lower = strtolower( ncore_retrieve( $plugin_meta, 'Name' ) );

                $one_plugin_file_lower = str_replace( '.php', '', strtolower( basename( $plugin_file  )) );

                $matches = $plugin_name_lower == $one_plugin_name_lower
                        || $plugin_name_lower == $one_plugin_file_lower;

                if ($matches) {
                    continue 2;
                }
            }

            $invalid_plugins[] = $plugin_name;
        }

        $this->invalid_plugins = $invalid_plugins;

        return count($invalid_plugins) == 0;

    }


    public function errorMessageTemplate()
    {

        return _ncore( 'For [NAME], these plugin name are not valid:' . implode( ', ', $this->invalid_plugins ) );
    }


    private $invalid_plugins = array();

}