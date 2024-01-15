<?php

class digimember_FacebookAccount extends digimember_FacebookBaseModule
{
    public function __construct( $api, $parent, $app_id, $app_secret ) // , $use_extended_permissions=false )
    {
        parent::__construct( $api, $parent, $app_id, $app_secret ); // , $use_extended_permissions );
    }

    public function setAccessToken( $access_token )
    {
        $this->access_token = $access_token;
    }

    public function userData()
    {
        try
        {
            $params = array();
            $params[ 'access_token' ] = $this->access_token;
            $params['fields']         = 'id,email,first_name,last_name'; // ,gender,link,timezone,name';

            $response = digimember_facebook_get( '/me', $params );

/*
Array
(
    [id] => 734348629948086
    [email] => facebook@neise-games.de
    [first_name] => Christian
    [last_name] => Neise

    [gender] => male
    [link] => https://www.facebook.com/app_scoped_user_id/734348629948086/
    [locale] => de_DE
    [name] => Christian Neise
    [timezone] => 2
    [updated_time] => 2014-02-18T22:38:59+0000
    [verified] => 1
)
*/


        }
        catch (Exception $e)
        {
            $response = array();
        }

        return $response;
    }


    function postFeed( $data, $key_prefix='' ) {

        $valid_keys = array(
            'message',
            'link',
            'picture',
            'name',
            'caption',
            'description',
            'source'
        );

        $defaults = array(
        );

        $params = array();
        foreach ($valid_keys as $key)
        {
            $params[$key] = ncore_retrieve( $data, $key_prefix.$key, false );
            if (!$params[$key]) {
                $params[$key] = @$defaults[ $key ];
            }
        }

        $params[ 'access_token' ] = $this->access_token;

        $fb_id = 'me'; // ncore_washText( ncore_retrieve( $data, array( 'fb_id', 'fb_user_id' ), 'me' ) );

        $url = "/$fb_id/feed";

        $response = digimember_facebook_post($url, $params );

        $post_id = ncore_retrieve( $response, 'id' );

        return $post_id;
    }

    private $access_token = '';
}