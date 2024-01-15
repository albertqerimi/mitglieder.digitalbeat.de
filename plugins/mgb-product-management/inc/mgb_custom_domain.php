<?php
defined('ABSPATH') or die();

if (!class_exists(MgbCustomDomain::class)) {

    class MgbCustomDomain {

        private $tld;
        private $domain;
        
        private $whois_serverlist = array(
        
            "at" => "whois.nic.at", 
            "biz" => "whois.biz",
            "com" => "whois.verisign-grs.com",
            "de" => "whois.denic.de", // no puny-code, expects utf-8
            "eu" => "whois.eu",
            "info" => "whois.afilias.net",
            "mobi" => "whois.dotmobiregistry.net",	// no IDN domains 
            "name" => "whois.nic.name",
            "net" => "whois.verisign-grs.net",
            "org" => "whois.pir.org"
        );

        private $whois_responselist = array(
        
            "at" => "nothing found", 
            "biz" => "No Data Found",
            "com" => "No match for",
            "de" => "free", 
            "eu" => "Status: AVAILABLE",
            "info" => "NOT FOUND",
            "mobi" => "NOT FOUND",	
            "name" => "No match for",
            "net" => "No match for",
            "org" => "NOT FOUND"
        );

        function __construct($domain, $tld){

            // init shortcodes and JS and REST
            // clean user input and define domain and tld 
            $domain = idn_to_ascii(urldecode($domain));
            $domain = str_replace(" ","-",$domain);
            $domain = trim($domain);
            $domain = strtolower($domain);
            //if (substr($domain, 0, 7) == "http://") $domain = substr($domain, 7);
            //if (substr($domain, 0, 4) == "www.") $domain = substr($domain, 4);
            $this->domain = $domain;

            //$domain_parts = explode(".", $domain);
            //$this->tld = array_pop($domain_parts);
            $this->tld = $tld;

        }

        public function is_free(){

            if ($this->tld == "de" ){ // no puny-code, expects utf-8
                
                $this->domain = idn_to_utf8($this->domain);
        
            }

            if ($this->tld == "mobi" ){ // no IDN domains 
                if (idn_to_utf8($this->domain) != $this->domain) {
                               
                    return 0; 
                
                }
            }

            $whois_result = $this->query_whois_server($this->whois_serverlist[$this->tld], $this->domain . "." . $this->tld);

            if (stripos($whois_result, $this->whois_responselist[$this->tld])!==FALSE){
            
               return 1;
            
            } else {
                
               return 0;
                
            }     

        }

        private function query_whois_server($whoisserver, $domain) {
            $port = 43;
            $timeout = 10;
            $fp = @fsockopen($whoisserver, $port, $errno, $errstr, $timeout) or die("Socket Error " . $errno . " - " . $errstr);
            fputs($fp, $domain . "\r\n");
            $out = "";
            while(!feof($fp)){
                $out .= fgets($fp);
            }
            fclose($fp);
            $res = "";
            if((strpos(strtolower($out), "error") === FALSE) && (strpos(strtolower($out), "not allocated") === FALSE)) {
                $rows = explode("\n", $out);
                foreach($rows as $row) {
                    $row = trim($row);
                    if(($row != '') && ($row[0] != '#') && ($row[0] != '%')) {
                        $res .= $row."\n";
                    }
                }
            }
            
            return $out;

        }

    }

}