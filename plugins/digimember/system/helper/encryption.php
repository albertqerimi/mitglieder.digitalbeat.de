<?php

function ncore_hash( $text )
{
    if (!function_exists('hash_algos'))
    {
        return array( 'md5', md5($text) );
    }

    $algos_to_try = array( 'sha512', 'sha384', 'sha256', 'md5' );

    $algos_present = hash_algos();

    foreach ($algos_to_try as $algo)
    {
        $have_algo = in_array( $algo, $algos_to_try );
        if ($have_algo)
        {
            $hash = hash( $algo, $text );

            return array( $algo, $hash );
        }
    }

    return array( 'md5', md5($text) );
}

function ncore_hashCompare($a, $b)
{
    if (!is_string($a) || !is_string($b)) {
        return false;
    }

    $len = strlen($a);
    if ($len !== strlen($b)) {
        return false;
    }

    $is_equal = true;
    for ($i=0; $i<$len; $i++)
    {
        if ($a[$i]!=$b[$i])
        {
            $is_equal = false;
        }
    }

    return $is_equal;
}

