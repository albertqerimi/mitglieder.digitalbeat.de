<?php

class ncore_EventSubscriberLogic extends ncore_BaseLogic
{
    public function call( $event )
    {
        $subscribers = $this->getSubscribers( $event );

        $given_args = func_get_args();
        array_shift($given_args);


        foreach ($subscribers as $info)
        {
            list(  $model, $method, $no_of_accepted_args ) = $info;

            $callable = array( $model, $method );

            $args = array_slice( $given_args, 0, $no_of_accepted_args);

            call_user_func_array( $callable, $args  );
        }
    }



    private $config = false;

    private function getSubscribers( $event )
    {
        if ($this->config === false)
        {
            $model = $this->api->load->config('event_subscriber');
            $this->config = $model->get('event_subscriber');
        }

        $subscribers =& $this->config[ $event ];

        if (empty($subscribers)) {
            return array();
        }

        if (is_string($subscribers)) {

            $lines = explode( "\n", str_replace( array( ',', ';' ), "\n", $subscribers ) );
            $subscribers = array();
            foreach ($lines as $line)
            {
                $line = trim($line);
                if (!$line) {
                    continue;
                }

                list( $path, $model_name, $method, $no_of_accepted_args ) = ncore_retrieveList( '/', $line );

                switch ($path)
                {
                    case 'library':
                        $model = $this->api->load->library( $model_name );
                    break;
                    default:
                        $model = $this->api->load->model( "$path/$model_name" );
                }

                $subscribers[] = array( $model, $method, $no_of_accepted_args );
            }
        }

        return $subscribers;
    }

}
