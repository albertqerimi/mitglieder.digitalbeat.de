<?php

function ncore_validateLicensekeyChecksum( $license_key )
{
    if (strlen($license_key) <= 4)
    {
        return false;
    }

    $checksum = substr( $license_key, -2 );
    $key_without_sum = substr( $license_key, 0, -2 );

    $expected_checksum = ncore_computeLicensekeyChecksum( $key_without_sum );

    return $expected_checksum == $checksum;
}

function ncore_computeLicensekeyChecksum( $key_without_sum )
{
    $sum = 0;
    $len = strlen( $key_without_sum );

    for ($i=0; $i<$len; $i++)
    {
        $sum += ord( $key_without_sum[$i] );
    }

    $sum = $sum % 255;

    $checksum = dechex ( $sum );
    if (strlen($checksum) < 2)
    {
        $checksum = "0$checksum";
    }

    return substr( $checksum, -2 ) ;
}