<?php

class ncore_HttpRequest_HandlerFsockopen extends ncore_HttpRequest_MethodBase
{
    public function isAvailable()
    {
        return function_exists( 'fsockopen' );
    }

    public function getRequest( $url, $params, $settings = array() )
     {
        $this->api->load->helper( 'url' );

        $url = ncore_addArgs( $url, $params, '&');

        $parts = parse_url( $url );

        $scheme  = ncore_retrieve( $parts, 'scheme' );
        $host  = ncore_retrieve( $parts, 'host' );
        $path = ncore_retrieve( $parts, 'path' );
        $query = ncore_retrieve( $parts, 'query' );

        $request = '';

        if ($query)
        {
            $path .= '?' . $query;
        }

        $is_ssl = $scheme === 'https';

        $error_no = '';
        $error_msg = '';

         $headers = array();
        $contents='';

        try
        {

            $fp = $is_ssl
                ? fsockopen('ssl://' . $host, 443)
                : fsockopen( $host, 80);

            if (!$fp)
            {
                throw new Exception( "Host not responding: $host" );
            }

            fputs($fp, "GET $path HTTP/1.1\r\n");
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $request);

            $in_header=true;

            while(!feof($fp)) {

                $line = fgets($fp, 10240);

                if ($line===false) {
                    /*empty*/
                } elseif ($in_header)
                {
                    if ($line=="\r\n"  || $line=='' || $line=="\r" || $line=="\n") {
                        $in_header=false;
                    } else {
                        $headers[]=$line;
                    }
                }
                else {
                    $contents.= trim($line, "\r\n") . "\n";
                }
            }
            fclose($fp);

            if (!$contents)
            {
                throw new Exception( "Got empty response" );
            }
        }
        catch (Exception $e)
        {
            $error_no = 1;
            $error_msg = $e->getMessage();
        }

        $http_code = $this->retrieveHttpCode( $headers );


        $response = new ncore_HttpResponse( $contents, $http_code );

        if ($error_msg)
        {
            $response->setError( $error_no, $error_msg );
        }

        return $response;
    }

    /**
     * @param       $url
     * @param       $params
     * @param array $settings
     * @return ncore_HttpResponse
     * @throws Exception
     */
    public function postRequest($url, $params, $settings = array() )
    {
        $parts = parse_url( $url );

        $scheme  = ncore_retrieve( $parts, 'scheme' );
        $host  = ncore_retrieve( $parts, 'host' );
        $path = ncore_retrieve( $parts, 'path' );
        $query = ncore_retrieve( $parts, 'query' );

        $is_ssl = $scheme === 'https';

        $error_msg = '';
        $error_no = '';
        $headers = array();
        $contents='';

        try
        {

            $fp = $is_ssl
                ? fsockopen('ssl://' . $host, 443)
                : fsockopen( $host, 80);

            if (!$fp)
            {
                throw new Exception( "Host not responding: $host" );
            }

            if ($query)
            {
                parse_str( $query, $query_args );
                $params = array_merge( $query_args, $params );
            }

            $request = http_build_query( $params, '', '&' );

            fputs($fp, "POST $path HTTP/1.0\r\n");
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: ". strlen($request) ."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $request);

            $in_header=true;

            while(!feof($fp)) {

                $line = fgets($fp, 10240);

                if ($line===false) {
                    /*empty*/
                } elseif ($in_header)
                {
                    if ($line=="\r\n"  || $line=='' || $line=="\r" || $line=="\n") {
                        $in_header=false;
                    } else {
                        $headers[]=$line;
                    }
                }
                else {
                    $contents.= trim($line, "\r\n") . "\n";
                }
            }
            fclose($fp);

            if (!$contents)
            {
                throw new Exception( "Got empty response" );
            }
        }
        catch (Exception $e)
        {
            $error_no = 1;
            $error_msg = $e->getMessage();
        }

        $http_code = $this->retrieveHttpCode( $headers );


        $response = new ncore_HttpResponse( $contents, $http_code );

        if ($error_msg)
        {
            $response->setError( $error_no, $error_msg );
        }

        return $response;
    }

    private function retrieveHttpCode( $headers )
    {
        if (!$headers)
        {
            return -1;
        }

        $first_line = strtolower( $headers[0] );

        $is_http = substr( $first_line, 0,4 ) == 'http';
        if (!$is_http)
        {
            return -2;
        }

        $tokens = explode( ' ', $first_line );
        if (count($tokens)<=1)
        {
            return -3;
        }

        $http_code = $tokens[1];
        if (!is_numeric( $http_code ))
        {
            return -4;
        }

        return (int) $http_code;
    }

}
