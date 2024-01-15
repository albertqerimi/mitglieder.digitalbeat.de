<?php
/**
 *  Object handles REST-APIs of our Product Servers 
 */

defined('ABSPATH') or die();

if (!class_exists(MgbKomplettProdukt::class)) {
class MgbKomplettProdukt {

    private $type;
    private $route;
    
    function __construct($type){

        $this->type = $type;
        $this->route = KP_ROUTES[$type].'/'.KP_AUTH;

    }
    
    private function rest_connect($method, $args){

        $payload = json_encode($args);
        
        $full_route = $this->route."/".$method;
        //error_log('ARGS: '.print_r($args). "FR: ". $full_route );
        $ch = curl_init($full_route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($payload))
        );
        
        $result = curl_exec($ch);
                
        curl_close($ch);
        
        return json_decode($result);

    }

    public function ListWebsites($userid){
        
        $data = array(
            'userid' => $userid,
        );
        //error_log(" userid: " . $userid );
        $result = $this->rest_connect(__FUNCTION__, $data);
       
        return $result;
    }

    public function OrderDomain($userid, $domain, $protoid, $dmv){
        
        $data = array(
            'userid' => $userid,
            'domain' => $domain,
            'protoid' => $protoid,
            'dmv'   => $dmv
        );     

        $result = $this->rest_connect(__FUNCTION__, $data);
        return $result;
    }

    public function MigrateDomain($userid, $domain, $protoid){
        
        $data = array(
            'userid' => $userid,
            'domain' => $domain,
            'protoid' => $protoid
        );     

        $result = $this->rest_connect(__FUNCTION__, $data);
        return $result;
    }

    public function UpdateUserData($user){
                
        $data = array(
            'ID' => $user->ID,
            'user_login' => $user->user_login,
            'user_pass' => $user->user_pass,
            'user_nicename' => $user->user_nicename,
            'user_email' => $user->user_email,
            'user_url' => $user->user_url,
            'display_name' => $user->display_name
        );
        
        $result = $this->rest_connect(__FUNCTION__, $data);

        return "RESPONSE: ".$result;
    }
     
}
}