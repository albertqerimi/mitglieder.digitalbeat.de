<?php

class ncore_SupportLogic extends ncore_BaseLogic
{
    function supportInfoKey()
    {
        $config = $this->api->load->model( 'logic/blog_config' );

        $key = $config->get( "support_info_key" );

        if (!$key)
        {
            $this->api->load->helper( 'string' );
            $key = ncore_randomString( 'alnum', 32 );

            $config->set( 'support_info_key', $key );
            $config->set( 'support_info_created', time() );
        }

        return $key;
    }

    function supportInfoUrl()
    {
        $key = $this->supportInfoKey();

        $base_url = $this->api->pluginUrl( 'ipn.php' );

        $args = array( 'dm_support_key' => $key );

        $url = ncore_addArgs( $base_url, $args );

        return $url;
    }

    function handleSupportInfoRequest()
    {
        $request_key = ncore_retrieve( $_GET, 'dm_support_key', false );
        if (!$request_key)
        {
            return;
        }

        $config = $this->api->load->model( 'logic/blog_config' );

        $is_enabled = $config->get( "support_info_enabled", false );
        if (!$is_enabled)
        {
            die( 'Support support url not enabled' );
        }

        $stored_key = $config->get( "support_info_key" );
        $key_valid = $stored_key && $request_key == $stored_key;
        if (!$key_valid)
        {
            die( 'Invalid support key' );
        }

        $this->dumpSupportInfo();

        exit(0);
    }

    public function getSupportInfo() {
        $output = '<textarea style="position: absolute; left:-9999px;" id="support_info" readonly>';
        $output .= $this->dumpSupportInfo();
        $output .= '</textarea>';
        echo $output;
    }

    private function dumpSupportInfo()
    {
        $supportData = array();
        $supportData['digimember']['version'] = $this->api->pluginVersion();
        $supportData['wordpress']['version'] = get_bloginfo('version');
        $supportData['system']['php_version'] = phpversion();
        $supportData['digimember']['edition'] = $this->api->edition();
        list($lang,$country) = explode('_',get_locale());
        $supportData['wordpress']['language'] = $lang;
        $currentTheme = wp_get_theme();
        $supportData['wordpress']['theme'] = $currentTheme->get( 'Name' );
        $installedPlugins = get_plugins();
        $activePlugins = get_option('active_plugins');
        $pluginList = array();
        foreach ($installedPlugins as $installedPluginKey => $installedPluginData) {
            if (in_array($installedPluginKey,$activePlugins)) {
                $pluginList[] = $installedPluginData['Name'];
            }
        }
        $supportData['wordpress']['plugins'] = $pluginList;
        $productModel = $this->api->load->model('data/product');
        $products = $productModel->getAll();
        $supportData['digimember']['productCount'] = count($products);
        $arModel = $this->api->load->model( 'data/autoresponder' );
        $activeAr = $arModel->getAll( array(
            'is_active' => 'Y'
        ));
        foreach ($activeAr as $ar) {
            $supportData['digimember']['autoresponder'][] = $ar->engine;
        }
        $paymentModel = $this->api->load->model('data/payment');
        $activePh = $paymentModel->getAll( array(
            'is_active' => 'Y'
        ));
        foreach ($activePh as $ph) {
            $supportData['digimember']['paymentprovider'][] = $ph->engine;
        }
        $supportData['system']['domain'] = $_SERVER['HTTP_HOST'];
        $hosterName = 'unbekannt';
        $dnsEntries = dns_get_record($_SERVER['SERVER_NAME']);
        if (is_array($dnsEntries)) {
            foreach ($dnsEntries as $entry) {
                if (array_key_exists('rname',$entry)) {
                    $hosterName = $entry['rname'];
                }
            }
        }
        $supportData['system']['hoster_information'] = $hosterName;
        $output = "Support info DigiMember\n";
        $output .= "--------------------\n";
        foreach ($supportData as $supportDataKey => $supportDataBlock) {
            $output .= $supportDataKey."\n";
            foreach ($supportDataBlock as $blockKey => $blockData) {
                if (is_array($blockData)) {
                    $output .= " - ".$blockKey."\n";
                    foreach ($blockData as $blockDataValue) {
                        $output .= "  -> ".$blockDataValue."\n";
                    }
                }
                else {
                    $output .= " - ".$blockKey.": ".$blockData."\n";
                }

            }
        }
        $output .= "--------------------\n";
        return $output;
    }

    /**
     * @param $text
     *
     * @return string
     */
    private function obfuscateEmail($text)
    {
        $pattern = "/[^@\s]*@[^@\s]*\.[^@\s]*/m";
        return preg_replace_callback($pattern, function($email) {
            foreach ($email as $key=>$value) {
                $em   = explode("@", $value);
                $name = implode(array_slice($em, 0, count($em) - 1), '@');
                $len  = floor(strlen($name) / 3);
                $email[$key] = substr($name, 0, $len) . str_repeat('*', $len * 2) . "@" . end($em);
            }
            return $email[0];
        }, $text);
    }

    private function dumpData( $model_name, $where = array(), $limit='0,1000', $order_by='id' )
    {
        $model = $this->api->load->model( "data/$model_name" );

        $all = $model->getAll( $where, $limit, $order_by );

        echo "<h2>Model: $model_name</h2><pre>";
        echo $this->obfuscateEmail(print_r($all, true));
        echo "</pre><p><hr /></p>";
    }

    private function ds24Test()
    {
        $model = $this->api->load->model( 'logic/digistore_connector' );
        $success = $model->testDs24ServerConnection();

        echo "<h1>Digistore24 api connection to remote Digistore24 server: ", ($success? 'ok' : 'ERROR' ), "</h1>";
    }

    private function postTest()
    {
        $action = 'connection_test';
        $args = array();

        $rpc = $this->api->load->library( 'rpc_api' );

        try
        {
            $result = $rpc->pluginApi( $action, $args );

            $success = ncore_retrieve( $result, 'status' ) == 'OK';

            $error_msg = '';
        }

        catch ( Exception $e )
        {
            $success = false;
            $error_msg = $e->getMessage();
        }

        echo "<h1>Post test to remote server: ", ($success? 'ok' : 'ERROR' );
        if ($error_msg)
        {
            echo " ($error_msg)";
        }
        echo "</h1>";
    }

}