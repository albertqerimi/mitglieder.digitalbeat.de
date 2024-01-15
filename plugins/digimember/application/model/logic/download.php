<?php

class digimember_DownloadLogic extends ncore_BlogConfigLogic
{
    public function protectedUrl( $url )
    {
        static $cache;

        $masked_url =& $cache[ $url ];

        if (isset($masked_url)) {
            return $masked_url;
        }

        $page_id = (int) get_the_ID();

        $url_md5 = md5( $page_id.'/'.ncore_userId().'/'.$url );

        $session = $this->api->load->model( 'logic/session' );
        $data = $session->get( 'pro_'.$url_md5 );

        $access_key = ncore_retrieve( $data, 'key' );

        $key_product_id = $this->_parseAccessKey( $access_key ) ;

        /** @var digimember_DownloadData $model */
        $model = $this->api->load->model( 'data/download' );

        $downloads = $model->getAuthorizedDownloads();
        $product_id = $downloads
                    ? $downloads[0]->product_id
                    : 0;

        $must_generate_key = $key_product_id === false
                          || $key_product_id != $product_id;

        if ($must_generate_key)
        {
            $this->api->load->helper( 'string' );
            $password = ncore_randomString( 'alnum', 20 );

            $access_key = 'P'.$product_id.'x'.$password;
        }


        $this->api->load->helper( 'array' );
        $download_ids = ncore_retrieveValues( $downloads, 'id' );

        $data = array();
        $data[ 'ids' ]  = $download_ids;
        $data[ 'page' ] = $page_id;
        $data[ 'key' ]  = $access_key;
        $data[ 'url' ]  = $url;

        $session_data[ 'pro_'.$url_md5 ]    = $data;
        $session_data[ 'key_'.$access_key ] = $url_md5;

        $session->set( $session_data );


        $masked_url = ncore_addArgs( ncore_siteUrl(), array( DIGIMEMBER_DOWNLOADKEY_GET_PARAM => $access_key ) );

        return $masked_url;
    }

    public function exec( $access_key )
    {
        $product_id = $this->_parseAccessKey( $access_key ) ;

        if ($product_id===false)
        {
            return;
        }

        $session = $this->api->load->model( 'logic/session' );
        $url_md5 = $session->get( 'key_'.$access_key );
        if (!$url_md5) {
            $this->_accessDenied( $product_id );
            return;
        }

        $download = $this->api->load->model( 'data/download' );
        $data = $session->get( 'pro_'.$url_md5 );
        $download->setAuthorizedDownloads( $data['ids'] );

        $page_id = $data['page'];
        $url     = $data['url'];

        $downloads_left = $download->downloadsLeft( $url );

        $can_download = $downloads_left > 0 || $downloads_left===false || ncore_canAdmin();
        if (!$can_download)
        {
            $this->api->load->model( 'data/page_product' );
            $page = $page_id ? get_post( $page_id ) : false;
            $is_protected = $page
                            ? (bool) $this->api->page_product_data->getForPage( $page->post_type, $page->ID, $active_only=true )
                            : false;

            if ($is_protected)
            {
                $access = $this->api->load->model( 'logic/access' );
                list( $access_type, ) = $access->accessType( $page->post_type, $page->ID );

                $access_granted = $access_type != DIGI_ACCESS_NONE;
            }
            else
            {
                $access_granted = false;
            }

            if (!$access_granted) {
                $this->_accessDenied( $product_id, $page_id );
                return;
            }
        }


        $download->countDownload( $url );

        $this->_proxyUrl( $url );
    }

    private function _proxyUrl( $url, $redirect_count=0 )
    {
//        $allow_url_fopen = ini_get( 'allow_url_fopen' );

//        if ($allow_url_fopen)
//        {
//            $this->_proxyUrlByUrlFOpen( $url );
//        }
//        else
//        {
            $this->_proxyUrlByFSockOpen( $url );
//        }

    }

//    private function _proxyUrlByUrlFOpen( $url )
//    {
//       $opts = array();
//
//       $context = stream_context_create($opts);
//
//       $fp = fopen( $url, 'r', false, $context);
//       fpassthru($fp);
//       fclose($fp);
//    }

    private function _proxyUrlByFSockOpen( $url, $redirect_count=0 )
    {
        $find = array( "<br />\n", '<br />', "\n" );
        $repl = ' ';

        $url = str_replace( $find, $repl, $url );

        $parts = parse_url( $url );

        $scheme = ncore_retrieve( $parts, 'scheme' );

        $is_ssl  = $scheme == 'https';
        $is_http = $scheme == 'http';

        $host  = ncore_retrieve( $parts, 'host' );
        $path  = ncore_retrieve( $parts, 'path', '/' );
        $query = ncore_retrieve( $parts, 'query', '' );
        $port  = ncore_retrieve( $parts, 'port', 0 );

        $user = ncore_retrieve( $parts, 'user', false );
        $pass = ncore_retrieve( $parts, 'pass', '' );

        $is_valid = $host && ($is_ssl || $is_http );
        if (!$is_valid) {
            $msg = _digi( 'The protected URL is not valid. The URL must start with http://or https://.' );
            die( $msg );
        }

        $this->disableCaching();

        @ignore_user_abort(true);
        @set_time_limit(0);

        if ($is_ssl) {
            $ssl='ssl://';
            if (!$port ) $port=443;
        } else {
            $ssl='';
            if (!$port ) $port=80;
        }

        $io = fsockopen( $ssl . $host, $port, $errno, $errstr, 5 );
        if ($io === false)
        {
            die( "ERROR: $errno, $errstr" );
        }

        $path  = str_replace( ' ', '%20', $path );
        $query = urlencode( $query );


        $language = ncore_retrieve( $_SERVER, 'HTTP_ACCEPT_LANGUAGE', 'de, en-us, en;q=0.50' );

        $headers  = "GET $path" . ($query?'?' . $query : '') . " HTTP/1.1\r\n";
        $headers .= "Host: $host\r\n";
        $headers .= "User-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1) Gecko/20021204\r\n";
        $headers .= "Referer: http://$host/\r\n";
        $headers .= "Accept: */*\r\n";
        $headers .= "Accept-Language: $language\r\n";
        // $headers .= "Accept-Encoding: gzip, deflate, compress;q=0.9\r\n";

        if ($user) {
            $headers .= "Authorization: Basic " . base64_encode( "$user:$pass" ) . "\r\n";
        }

        $headers .= "Connection: Close\r\n\r\n";

        fputs ( $io, $headers );
        unset($headers);

        $header = '';

        do
        {
            $line = fgets ( $io, 4096 );

            if ($line === false) {

                $url_label = nl2br(htmlentities2($url));

                $url = "<a href=\"$url\">$url_label</a>";

                $msg = ncore_canAdmin()
                     ? sprintf(_digi( 'Wordpress could not download the URL %s. Please validate, that the URL in the shortcode is correct. Server response: %s'), $url, $header )
                     : _digi( 'Wordpress could not download the protected content from the given URL.' );
                die( $msg );
            }

            $header .= $line;

            $pos = strpos ( $header, "\r\n\r\n" );

        } while ( $pos === false );

        $have_content_in_header = strlen($header) > $pos +4;
        $content = $have_content_in_header
                 ? substr( $header, $pos + 4 )
                 : '';

        $header = $this->_decodeHeader ( $header );

        $status       = ncore_retrieve( $header, 'status', 0 );

        $location =  ncore_retrieve( $header, 'location' );
        $is_redirect = ($status == 301 || $status == 302) && $location;
        if ($location) {
            if ($redirect_count>=10) {
                die( "Aborted after $redirect_count redirects" );
            }
            $method = __FUNCTION__;
            $this->$method( $location, $redirect_count+1 );
            return;
        }

        $headers_to_forward = array(
            'content-type'        => 'application/binary',
            'content-length'      => false,
            'content-encoding'    => false,
            'accept-ranges'       => false,
            'date'                => false,
            'age'                 => false,
            'expires'             => false,
        );

        $filename = $this->_retrieveFilenameFromHeaders( $header );
        if (!$filename)
        {
            $filename = str_replace( '%20', ' ', basename( $path ) );
        }

        header( "Content-Disposition: attachment; filename=\"$filename\"" );

        foreach ($headers_to_forward as $key => $default)
        {
            $value = ncore_retrieve( $header, $key, $default );
            if ($value) {
                header( "$key: $value" );
            }
        }

        $eight_mb = 8 * 1024 * 1024;

        if ($content) {
            echo $content;
        }

        while ( ! feof ( $io ) )
        {
            $part = fread ( $io, $eight_mb );
            echo $part;
        }

        fclose ( $io );

        exit;
    }


    private function _accessDenied( $product_obj_or_id, $page_id=false )
    {
        $model = $this->api->load->model( 'data/product' );

        $product = $this->api->product_data->resolveToObj( $product_obj_or_id );

        if (!$product && $page_id)
        {
            $where = array( 'type' => 'download', 'login_url' => $page_id );
            $product = $this->api->product_data->getWhere( $where );
        }

        if (!$product) {
            return;
        }

        switch ($product->access_denied_type)
        {
            case DIGIMEMBER_AD_URL:
                $url = $product->access_denied_url;
                break;

            case DIGIMEMBER_AD_PAGE:
                $url = $product->access_denied_page;
                break;

            default:
                $url = '';
        }

        $this->api->load->helper( 'url' );
        $url = ncore_resolveUrl( $url );

        if (!$url) {
            $url = ncore_siteUrl();
        }

        ncore_redirect( $url );
    }

    private function _decodeHeader( $str )
    {
        $part = preg_split ( "/\r?\n/", $str, -1, PREG_SPLIT_NO_EMPTY );

        $out = array ();

        for ( $h = 0; $h < sizeof ( $part ); $h++ )
        {
            if ( $h != 0 )
            {
                $pos = strpos ( $part[$h], ':' );

                $k = strtolower ( str_replace ( ' ', '', substr ( $part[$h], 0, $pos ) ) );

                $v = trim ( substr ( $part[$h], ( $pos + 1 ) ) );
            }
            else
            {
                $k = 'status';

                $v = explode ( ' ', $part[$h] );

                $v = $v[1];
            }

            if ( $k == 'set-cookie' )
            {
                    $out['cookies'][] = $v;
            }
            else if ( $k == 'content-type' )
            {
                if ( ( $cs = strpos ( $v, ';' ) ) !== false )
                {
                    $out[$k] = substr ( $v, 0, $cs );
                }
                else
                {
                    $out[$k] = $v;
                }
            }
            else
            {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    private function _parseAccessKey( $access_key )
    {
        if (!$access_key || $access_key[0] != 'P') {
            return false;
        }

        $pos = strpos( $access_key, 'x' );
        $product_id = (int) substr( $access_key, 1, $pos-1 );

        return $product_id;
    }

    private function disableCaching()
    {
        ncore_disableCaching();

        $level =  ob_get_level();
        for ($i=0; $i<$level; $i++)
        {
            ob_end_clean();
        }
    }

    private function _retrieveFilenameFromHeaders( $header )
    {
        $line = ncore_retrieve( $header, 'content-disposition' );

        if (!$line) {
            return false;
        }

        $tokens = explode( ';', $line );
        foreach ($tokens as $one)
        {
            list( $k, $v ) = ncore_retrieveList( '=', $one, 2, true );

            $k = strtolower(trim($k));
            if ($k === 'filename')
            {
                $filename = trim( $v, '"\'\\ ' );
                return $filename;
            }
        }

        return false;
    }

}