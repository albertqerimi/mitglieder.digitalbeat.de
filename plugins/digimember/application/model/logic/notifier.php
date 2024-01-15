<?php

class digimember_NotifierLogic extends ncore_NotifierLogic
{
    public function purgeCache( $msg_key, $context )
    {
        $cachekey = "notify_${msg_key}_$context";

        ncore_cacheStore( $cachekey, $templates=false, $lifetime=0 );
    }

    protected function mailTemplates( $msg_key, $context )
    {
        $cachekey = "notify_${msg_key}_$context";

        try
        {
            $force_reload = !empty($_GET['reload']);

            if (!$force_reload)
            {
                $templates = ncore_cacheRetrieve( $cachekey );

                if ($templates) {
                    return $templates;
                }
            }

            $rpc = $this->api->load->library( 'rpc_api' );

            $args[ 'language' ]           = get_bloginfo('language');
            $args[ 'locale' ]             = get_locale();

            $args[ 'msg_key' ]            = $msg_key;
            $args[ 'context']             = $context;


            $data = $rpc->mailtemplateApi( 'fetch', $args );

            $templates = ncore_retrieve( $data, 'mail_templates', array() );
            $lifetime  = ncore_retrieve( $data, 'lifetime', 86400 );

            ncore_cacheStore( $cachekey, $templates, $lifetime );
        }
        catch (Exception $e) {

            $this->api->logError('api', _ncore( 'Error contacting the mailtemplate server:' ) . ' ' . $e->getMessage() );
        }

        return $templates;
    }
}

