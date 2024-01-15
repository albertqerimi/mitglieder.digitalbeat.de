<?php

class digimember_AutoresponderHandler_PluginFlatratenewsletter extends digimember_AutoresponderHandler_PluginBase
{
    
    public function unsubscribe( $email )
    {
    }    
    
    public function getPersonalData( $email )
    {
        return array();
    }    
    
    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        $this->parseSignupFormHtml( $action, $p, $list_ids );

        $params = array();

        $params['email'] = $email;
        $params['name'] = "$first_name $last_name";
        $params['p']    = $p;
        $params['funcml'] = 'add'; // or 'unsub2'

        $i=1;
        foreach ($list_ids as $list_id)
        {
            $params[ "nlbox[$i]" ]=$list_id;
            $i++;
        }

        $handler = $this->api->load->library( 'http_request' );
        $response = $handler->getRequest( $action, $params );


        $is_success = !$response->isError();
        if (!$is_success)
        {
            throw new Exception( _ncore( 'Error adding %s to list %s. HTTP result code: %s', $email, $list_id, $response->httpCode() ) );
        }
    }


    public function formMetas()
    {
        $metas = array();

        $metas[] = array(
                'name' => 'form_html',
                'type' => 'textarea',
                'label' => _digi3('Signup Form Html' ),
                'rules' => 'defaults|required',
                'hint'  => _digi3('E.g. ' ) . '&ltform id="subscribeform" ...',
                'size'  => 32,
            );

        return $metas ;

    }


    public function instructions()
    {
        return array(
            _digi3('<strong>In the FlateRateNewsletter Controll Center</strong> select <em>Integration - Signup Forms</em>.'),
            _digi3('Create a new simple signup form with the default settings - for one list only and <strong>without</strong> CAPTCHA.'),
            _digi3('Copy the html code to the clipboard.'),
            _digi3('<strong>Here in DigiMember</strong> paste the html code into the <em>Signup Form Html</em> field.' ),
            _digi3('Save your changes.' ),
            _digi3('Click on the test settings button to validate your settings.' ),
            );
    }

    public function isActive()
    {
        return true;
    }

    private function parseSignupFormHtml( &$action, &$p, &$list_ids )
    {
        // see http://www.flatrate-newsletter.de/help/index.php?action=kb&article=54

        $html = $this->data( 'form_html' );

        $find = array( "\r", "\\" );
        $repl = array( "\n", "" );

        $html = str_replace( $find, $repl, $html );

        $action = '';
        $p = '';
        $list_ids=array();

        // <form id="subscribeform" method="post" action="http://005.frnl.de/box.php">
        preg_match( '/action="(.*)"/', $html, $matches );
        $action = ncore_retrieve( $matches, 1 );
        if (!$action)
        {
            throw new Exception( 'Invalid signup form html code: Action missing.' );
        }

        // <input name="p" type="hidden" id="p" value="832" />
        preg_match( '/<input.*?name="p".*?value="([0-9]*)"/', $html, $matches );
        $p = ncore_retrieve( $matches, 1 );
        if (!$p)
        {
            throw new Exception( 'Invalid signup form html code: Parameter p missing.' );
        }

        // <input type="hidden" name="nlbox[1]" value="3520" />
        preg_match_all( '/<input.*?name="nlbox\[.*?\]".*?value="([0-9]*)"/', $html, $matches );
        $list_ids = ncore_retrieve( $matches, 1 );
        if (!$list_ids)
        {
            throw new Exception( 'Invalid signup form html code: Parameter nlbox (the list ids) missing.' );
        }
    }

}

