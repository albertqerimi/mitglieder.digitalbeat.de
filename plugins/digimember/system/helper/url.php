<?php

function ncore_2ndLvlDomain()
{
	$url = ncore_currentUrl();
	$host = parse_url( $url, PHP_URL_HOST );

	$tokens = explode( '.', $host );

	$count = count($tokens);

	$domain = $count >= 2
			? $tokens[$count-2] . '.' . $tokens[$count-1]
			: $host;

	return $domain;
}

function ncore_currentUrl()
{
    static $current_url;
    if (isset($current_url))
    {
        return $current_url;
    }

    $is_ssl = !empty( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off';

	$http = $is_ssl ? 'https' : 'http';

    $current_url = ncore_retrieve( $_REQUEST, 'current_url' );
	$server      = ncore_retrieve( $_SERVER,  'HTTP_HOST' );
	$url         = ncore_retrieve( $_SERVER,  'REQUEST_URI' );
    $query       = ncore_retrieve( $_SERVER,  'QUERY_STRING' );

    $is_url = ncore_stringStartsWith( $url, 'https://' )
           || ncore_stringStartsWith( $url, 'http://' );
    if ($is_url)
    {
        $url = parse_url( $url, PHP_URL_PATH );

        if (!$query) {
            $url = parse_url( $url, PHP_URL_QUERY );
        }
    }

    $have_query_in_url = strpos( $url, '?' ) !== false;
    if (!$have_query_in_url && $query)
    {
        $url .= '?' . $query;
    }


    $is_ajax_request = defined( 'NCORE_IS_AJAX' ) && NCORE_IS_AJAX;

    if ($current_url)
    {
        // empty
    }
    elseif ($is_ajax_request)
    {
        $current_url = ncore_siteUrl();
    }
	elseif ($server)
	{
		$current_url = "$http://$server$url";
	}
	else
	{
		$current_url = '';
	}

    $current_url = str_replace( '&amp;', '&', $current_url );

    return ncore_toAbsoluteUrl( $current_url );
}

function ncore_removeArgs( $url, $arg_names, $arg_sep='&amp;', $url_encode=true )
{
	$parts = parse_url( $url );

	$scheme = ncore_retrieve($parts,'scheme');
	$host = ncore_retrieve($parts,'host');
    $port     = ncore_retrieve($parts,'port');
	$path = ncore_retrieve($parts,'path');
	$query = ncore_retrieve($parts,'query');
	$fragment = ncore_retrieve($parts,'fragment');

	if (!is_array($arg_names))
	{
		$arg_names = array( $arg_names );
	}

	parse_str( $query, $params );
	if ($params)
	{
		foreach ($params as $key => $value)
		{
			$is_removed = in_array( $key, $arg_names );
			if ($is_removed)
			{
				unset( $params[ $key ] );
			}
		}
	}
	if (!$params)
	{
		$params = array();
	}

	$query = ncore_queryString( $params, $arg_sep, $url_encode );

	if (!$scheme)
	{
		$is_ssl = isset( $_SERVER['HTTPS'] );
		$scheme = $is_ssl ? 'https': 'http';
	}

    $port = $port ? ':'.$port : '';
    $url = $host
        ? "$scheme://$host$port"
        : '';

	$url .= "$path";

	if ($query)
	{
		$url .= "?$query";
	}

	if ($fragment)
	{
		$url .= "#$fragment";
	}

	return $url;

}

function ncore_addArgs( $url, $args, $arg_sep='&amp;', $url_encode=true )
{
    if ($url==='current') $url = ncore_currentUrl();

	$parts = parse_url( $url );

	$scheme   = ncore_retrieve($parts,'scheme');
	$host     = ncore_retrieve($parts,'host');
	$port     = ncore_retrieve($parts,'port');
	$path     = ncore_retrieve($parts,'path');
	$query    = ncore_retrieve($parts,'query');
	$fragment = ncore_retrieve($parts,'fragment');

	parse_str( $query, $params );
	if (!$params)
	{
		$params = array();
	}

	$params = array_merge( $params, $args );

	$query = ncore_queryString( $params, $arg_sep, $url_encode );

	if (!$scheme)
	{
		$is_ssl = isset( $_SERVER['HTTPS'] );
		$scheme = $is_ssl ? 'https': 'http';
	}

	$port = $port ? ':'.$port : '';
	$url = $host
		 ? "$scheme://$host$port"
		 : '';

	$url .= "$path";

	if ($query)
	{
		$url .= "?$query";
	}

	if ($fragment)
	{
		$url .= "#$fragment";
	}

	return $url;
}

function ncore_queryString( $params, $arg_sep='&amp;', $url_encode=true )
{
	$query = '';

	foreach ($params as $key => $value)
	{
		if (is_array($value))
		{
			foreach ($value as $one)
			{
				if ($query)
				{
					$query .= $arg_sep;
				}

				if ($url_encode)
				{
					$query .= urlencode( $key ) . '[]=' . urlencode( $one );
				}
				else
				{
					$query .= $key . '[]=' . $one;
				}
			}
							  }
		else
		{
			if ($query)
			{
				$query .= $arg_sep;
			}

			if ($url_encode)
			{
				$query .= urlencode( $key ) . '=' . urlencode( $value );
			}
			else
			{
				$query .= $key . '=' . $value;
			}
		}

	}

	return $query;
}


function ncore_redirect( $url )
{
    if (defined('NCORE_IS_AJAX') && NCORE_IS_AJAX)
    {
        /** @var ncore_NetIDNA2Lib $lib */
        $lib = ncore_api()->load->library( 'Net_IDNA2' );
        $url = $lib->encode( $url );

        $response = new ncore_AjaxResponse( ncore_api() );
        $response->redirect( $url );
        $response->output();
        exit;
    }


	$headers_sent = headers_sent();

	if ($headers_sent)
	{
	    /** @var ncore_NetIDNA2Lib $lib */
        $lib = ncore_api()->load->library( 'Net_IDNA2' );
        $url = $lib->encode( $url );

		echo "
<script type='text/javascript'>
location.href=\"$url\";
</script>
";

        $label = _ncore( 'Click here' );
        die("<a href=\"$url\">$label</a>");
	}
	else
	{
		wp_redirect( $url );
		exit;
	}

}

function ncore_toAbsoluteUrl( $url )
{
    $RELATIVE_URL_START_CHARS = array( '/', '.', '?', '#' );

	$is_relative_url = !$url || in_array( $url[0], $RELATIVE_URL_START_CHARS );

	if ($is_relative_url)
	{
		$url = ncore_siteUrl( $url );
	}

	return $url;
}

function ncore_isAbsoluteUrl( $url ) {

    if (!$url) return false;

    $first_char = $url[0];

    $is_not_absolute = $first_char != 'h' && $first_char != '/';
    if ($is_not_absolute) {
        return false;
    }

    $second_char = $url[1];

    $is_absolute = $first_char == '/' && $second_char == '/';
    if ($is_absolute) {
        return true;
    }

    $head = substr( $url, 0, 10 );
    $pos = strpos( $head, '://' );
    $have_protocol = $pos !== false;
    if (!$have_protocol) {
        return false;
    }

    return true;
}

function ncore_resolveUrl( $url_or_page_id )
{
	if (!$url_or_page_id)
	{
		return '';
	}

	$is_url = !is_numeric( $url_or_page_id );

	if ($is_url)
	{
		$url = $url_or_page_id;

        $is_path = $url == '' || $url[0] === '/';
        if ($is_path)
        {
            $url = ncore_siteUrl( $url );
        }

		return $url;
	}

	$page_id = $url_or_page_id;

	/** @var digimember_LinkLogic $model */
	$model = ncore_api()->load->model( 'logic/link' );
	return $model->readPost( 'page', $page_id );
}

function ncore_linkReplace( $msg_template, $url, $asPopup = true )
{
	$args = func_get_args();

	$last_arg = end( $args );
	$defaultAsPopup = $last_arg === true;

	$urls = array();
	$as_popups = array();
	$attributes = array();

	$current_url = false;

	foreach ( $args as $index => $one )
	{
		$is_first = $index == 0;
		if ($is_first)
		{
			continue;
		}

		$is_url = is_string( $one );
		if ($is_url)
		{
			$current_url = $one;
			$urls[] = $current_url;
			$as_popups[] = $defaultAsPopup;
            $attributes[] = [];
		}
		elseif (is_bool( $one ))
		{
			$asPopup=$one;
			$urls[] = $current_url;
			$as_popups[] = $asPopup;
            $attributes[] = [];
		}
		elseif (is_array($one)) {
            $urls[] = ncore_retrieveAndUnset($one, 'url', $current_url);
            $as_popups[] = $defaultAsPopup;
            $attributes[] = $one;
        }
		else
		{
			trigger_error( 'Invalid argument' );
			continue;
		}
	}

    $have_link = false;
	$msg       = $msg_template;
	foreach ($urls as $index => $url)
	{
		$asPopup = $as_popups[ $index ];
		$attributes[$index]['href'] = $url;

		if ($asPopup)
		{
            $attributes[$index]['target'] = '_blank';
		}
		$attr = ncore_renderAttributes($attributes[$index]);

		$pos = strpos( $msg, '<a>' );
		if ($pos === false)
		{
			return $have_link || !$url
                   ? $msg
                   : "<a $attr>$msg</a>";
		}

        $have_link = true;

		$first_part = substr( $msg, 0, $pos+2 );
		$second_part = substr( $msg, $pos+2 );

		$msg = $first_part.$attr.$second_part;
	}

	if (NCORE_DEBUG && strpos( $msg, '<a>' ) !== false)
	{
		trigger_error( 'Too many <a> (or to few url in argument list) for msg: ' . $msg_template );
	}

	return $msg;
}

function ncore_logoutUrl( $redirect_url = false )
{
    if (!$redirect_url) {
        $redirect_url = site_url();
    }
    
    return str_replace( '&amp;','&', wp_logout_url( $redirect_url ) );
}

function ncore_DigistoreSignupUrl() {

    $url = '';

    if (function_exists( 'dbizz_networkSetting' )) {
        $url = dbizz_networkSetting( 'digistore_signup_url' );
    }

    if (!$url) {
        $url = 'https://www.digistore24.com/signup/';
    }

    return $url;
}


function ncore_licenseUrl( $home_url='current' )
{
    if ($home_url === 'current')
    {
        $home_url = ncore_siteUrl();
    }

    $is_http = strtolower( substr( $home_url, 0, 7) ) == 'http://';
    $is_https = strtolower( substr( $home_url, 0, 8) ) == 'https://';

    if ($is_http)
    {
        $home_url = substr( $home_url, 7 );
    }

    if ($is_https)
    {
        $home_url = substr( $home_url, 8 );
    }

    return $home_url;
}

function ncore_sanitizeAbsoluteUrl( $url )
{
    $must_check_proto = $url[0] === 'h';
    if ($must_check_proto)
    {
        list( $proto ) = explode( '://', $url );

        $have_proto = $proto === 'http' || $proto === 'https';
    }
    else
    {
        $have_proto = false;
    }

    $have_path = $url[0] === '/';
    if ($have_path)
    {
        return ncore_siteUrl( $url );
    }

    if (!$have_proto)
    {
        $url = "http://$url";
    }

    return $url;
}

function ncore_domainInList( $url_or_domain, $domain_list, $seperator="\n" )
{
    $host = parse_url( $url_or_domain, PHP_URL_HOST );

    $domain = $host
            ? $host
            : trim($url_or_domain);

    if (!$domain) {
        return false;
    }

    $domain      = strtolower( $domain );
    $domain_list = strtolower( $domain_list );

    $search_parts = explode( '.', $domain);
    $search_count = count( $search_parts );

    $lines = explode( $seperator, $domain_list );
    foreach ($lines as $one) {
        $one = trim($one);
        if (!$one) {
            continue;
        }

        $parts = explode( '.', $one );
        $count = count($parts);

        if ($count > $search_count) {
            continue;
        }

        for ($i=1; $i<=$count; $i++) {
            $index = $count-$i;
            $search_index = $search_count-$i;

            $is_match = $search_parts[ $search_index ] == $parts[ $index ];
            if (!$is_match) {
                continue 2;
            }
        }

        return true;
    }


    return false;
}

//function ncore_urlSamePage( $url1, $url2, $may_resolve_page_url=true )
//{
//    if ($url1==$url2) {
//        return true;
//    }
//
//    $resolved_url1 = ncore_resolveUrl( $url1 );
//    $resolved_url2 = ncore_resolveUrl( $url2 );
//
//    $tokens1 = parse_url( $resolved_url1 );
//    $tokens2 = parse_url( $resolved_url2 );
//
//    $host1 = strtolower( ncore_retrieve( $tokens1, 'host' ) );
//    $host2 = strtolower( ncore_retrieve( $tokens2, 'host' ) );
//
//    if ($host1 != $host2) {
//        return false;
//    }
//
//    $path1 = strtolower( ncore_retrieve( $tokens1, 'path' ) );
//    $path2 = strtolower( ncore_retrieve( $tokens2, 'path' ) );
//
//    $path1 = trim( $path1, '/' );
//    $path2 = trim( $path2, '/' );
//
//    $query1 = strtolower( ncore_retrieve( $tokens1, 'query' ) );
//    $query2 = strtolower( ncore_retrieve( $tokens2, 'query' ) );
//
//    if ($path1 == $path2 && $query1 == $query2) {
//        return true;
//    }
//
//    parse_str( $query1, $args1 );
//    parse_str( $query2, $args2 );
//
//    $page1 = is_numeric( $url1 )
//           ? $url1
//           : ncore_retrieve( $args1, 'page' );
//    $page2 = is_numeric( $url2 )
//           ? $url2
//           : ncore_retrieve( $args2, 'page' );
//
//    if ($page1 && $page2 && $page1==$page2) {
//        return true;
//    }
//
//    if (!$page1 && !$page2) {
//        return $path1 == $path2;
//    }
//
//    if (!$may_resolve_page_url) {
//        return false;
//    }
//
//
//    $next_try_url_1 = $page1
//                    ? ncore_siteUrl( get_page_uri( $page1 ))
//                    : $url1;
//
//    $next_try_url_2 = $page2
//                    ? ncore_siteUrl( get_page_uri( $page2 ))
//                    : $url2;
//
//    return ncore_urlSamePage( $next_try_url_1, $next_try_url_2, $may_resolve_page_url=false );
//}