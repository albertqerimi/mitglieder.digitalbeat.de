<?php

class digimember_AutoresponderHandler_PluginGeneric extends digimember_AutoresponderHandler_PluginBase
{
    const max_param_count = 25;
    const min_param_count = 1; // must be >= 1 because 0 assigns the default value)
    const default_param_count = 3;

    public function unsubscribe( $email )
    {
    }    
    
    public function getPersonalData( $email )
    {
        return array();
    }    
    
    public function subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true, $custom_fields=array() )
    {
        $notify_url = $this->data( 'notify_url' );
        $method = strtoupper($this->data( 'method' ));
        $param_email = $this->data( 'param_email' );
        $param_first_name = $this->data( 'param_first_name' );
        $param_last_name = $this->data( 'param_last_name' );

        $have_full_name = $param_last_name && !$param_first_name;
        if ($have_full_name)
        {
            $last_name = "$first_name $last_name";
            $first_name = "";
        }

        $lib = $this->api->load->library( 'http_request' );

        $params = array(
            $param_email      => $email,
            $param_first_name => $first_name,
            $param_last_name  => $last_name,
        );

        $param_count = $this->paramCount();

        for ($i=1; $i<=$param_count; $i++)
        {
            $key = trim( $this->data( "param_extra_$i" ) );
            $val = trim( $this->data( "value_extra_$i" ) );

            if ($key)
            {
                $params[$key] = $val;
            }
        }

        if ($method == 'POST')
        {
            $reponse = $lib->postRequest( $notify_url, $params );
        }
        else
        {
            $reponse = $lib->getRequest( $notify_url, $params );
        }

        $have_error = $reponse->isError();

        if ($have_error)
        {
            throw new Exception( $reponse->errorMsg() );
        }
    }

    public function formMetas()
    {
        $min = self::min_param_count;
        $max = self::max_param_count;

        $param_count_options = array();
        for ($i=$min; $i<=$max; $i++)
        {
            $param_count_options[ $i ] = $i;
        }

        $methods = array(
            'get' => 'GET',
            'post' => 'POST',
        );

        $metas = array(
             array(
                'name' => 'notify_url',
                'type' => 'url',
                'label' => _ncore('Notification URL' ),
            ),
             array(
                'name' => 'method',
                'type' => 'select',
                'options' => $methods,
                'label' => _ncore('Method' ),
                'default' => 'post',
            ),

             array(
                'name' => 'param_email',
                'type' => 'text',
                'label' => _ncore('Parameter name for email' ),
                'hint'  => _digi3( 'E.g.: %s', 'email' ),
            ),
             array(
                'name' => 'param_first_name',
                'type' => 'text',
                'label' => _ncore('Parameter name for first name' ),
                'hint'  => _digi3( 'E.g.: %s', 'firstname' ),
            ),
             array(
                'name' => 'param_last_name',
                'type' => 'text',
                'label' => _ncore('Parameter name for last name' ),
                'hint'  => _digi3( 'E.g.: %s', 'lastname' ),
            ),
             array(
                'name' => 'param_count',
                'type' => 'select',
                'options' => $param_count_options,
                'label' => _ncore('Number of extra parameters' ),
                'default' => self::default_param_count,
                // 'rules' => "greater_equal[$min]|lower_equal[$max]",
            ),
        );

        $param_count = $this->paramCount();
        for ($i=1; $i<=$param_count; $i++)
        {
            $metas[] = array(
                    'name' => "param_extra_$i",
                    'type' => 'text',
                    'label' => _ncore('Extra parameter %s name', $i ),
                );

            $metas[] = array(
                'name' => "value_extra_$i",
                'type' => 'text',
                'label' => _ncore('Extra parameter %s value', $i ),
            );
        }

        return $metas;
    }

    public function haveInstructionNumbers()
    {
        return false;
    }

    public function instructions()
    {
        $model = $this->api->load->model( 'logic/link' );
        $download_url = $model->downloadExample( 'digimember_autoresponder' );

        $msg_templ = _digi3('<strong>Either</strong> you <a>download</a> the example script and adjust it to your needs.');

        $find = "<a>";
        $repl = "<a href='$download_url'>";

        $download = str_replace( $find, $repl, $msg_templ );

        $find = array();
        $repl = array();

        $find[] = '[HTML]';
        $repl[] = "<tt>".htmlentities('<input type="hidden" name="list_id" value="123" />')."</tt>";

        $find[] = "[PARAMNAME]";
        $repl[] = '<em>'._ncore('Extra parameter %s name', 1 ).'</em>';

        $find[] = "[PARAMVAL]";
        $repl[] = '<em>'._ncore('Extra parameter %s value', 1 ).'</em>';

        $find[] = "[INPUTNAME]";
        $repl[] = "<tt>list_id</tt>";


        $find[] = "[INPUTVAL]";
        $repl[] = "<tt>123</tt>";


        $msg_templ_1 = _digi3( 'In your signup form you may find a hidden inputs like [HTML].');

        $msg_templ_2 = _digi3( 'If so, enter for [PARAMNAME] below [INPUTNAME] and as [PARAMVAL] [INPUTVAL].');


        $notify_url_name = '<em>' . _ncore('Notification URL') . '</em>';
        $param_first_name = '<em>'._ncore('Parameter name for first name' ).'</em>';
        $param_last_name = '<em>'._ncore('Parameter name for last name' ).'</em>';

        $example_input_1 = str_replace( $find, $repl, $msg_templ_1 );
        $example_input_2 = str_replace( $find, $repl, $msg_templ_2 );

        return array(
            _digi3('<strong>Important:</strong> This is a flexible, but adavanced integration method. Warning: Programming skills are required.'),
            _digi3('You have two options:'),
            $download,
            _digi3( 'Then upload it to you server, rename it to a non guessable name and enter the URL to the script as %s below.', $notify_url_name),
            _digi3( '<strong>Or</strong> you analyze the signup form of you autoresponder.' ),
            _digi3( 'Enter the action URL as %s below.', $notify_url_name ),
            _digi3( 'Find out, what input names are used for the email and name input. Enter the input names below.'),
            _digi3( 'If you only have <strong>a single input field for the fullname</strong> (and not two input fields - one for first and one for last name), enter the name of the input field as %s. Clear the value for %s.', $param_last_name, $param_first_name ),
            $example_input_1,
            $example_input_2,
        );

    }

    private function paramCount()
    {
        $min = self::min_param_count;
        $max = self::max_param_count;
        $def = self::default_param_count;

        $param_count = max( $min, min( $max, $this->data( 'param_count', $def ) ) );

        return $param_count;
    }
}