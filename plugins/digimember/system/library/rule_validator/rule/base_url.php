<?php

abstract class ncore_RuleValidator_RuleBaseUrl extends ncore_RuleValidator_RuleBase
{
    protected function validateAbsoluteUrl( $url )
    {
        $url = trim( $url );

        $tokens = explode( '://', $url );

        $have_protocol = count( $tokens ) >= 2;

        if ( $have_protocol )
        {
            $protocol = $tokens[ 0 ];
            $url      = $tokens[ 1 ];
        }
        else
        {
            $protocol = 'http';
            $url      = $tokens[ 0 ];
        }

        $url = $protocol . '://' . $url;

        $result = parse_url( $url );

        $scheme = strtolower( ncore_retrieve( $result, 'scheme' ) );
        $host   = strtolower( ncore_retrieve( $result, 'host' ) );

        $domain_valid = $this->validateDomain( $host );
        $scheme_valid = in_array( $scheme, array(
             'http',
             'https'
        ) );

        $url_valid = $domain_valid && $scheme_valid;

        return $url_valid ? $url : false;
    }

    protected function validateRelativeUrl( $url )
    {
        $url = trim( $url );

        $is_valid = $url[ 0 ] == '/';

        return $is_valid;
    }

    protected function validateDomain( $domain )
    {
        $domain = trim( strtolower( $domain ), ' /' );

        $domain = str_replace( array(
             'http://',
             'https://'
        ), '', $domain );

        if ( !$domain )
        {
            return $domain;
        }

        if (NCORE_DEBUG && $domain == 'localhost')
        {
            return 'localhost';
        }

        $tokens = explode( ".", $domain );

        if ( count( $tokens ) <= 1 )
        {
            return false;
        }

        foreach ( $tokens as $token )
        {
            $token_invalid = $token == '' || !$this->validateDomainPart( $token );

            if ( $token_invalid )
            {
                return false;
            }
        }
        return $domain;
    }

    protected function validateDomainPart( $part )
    {
        return preg_match( '/^[a-z\d][a-z\d-]{0,62}$/i', $part ) && !preg_match( '/-$/', $part );
    }
}
