<?php

// seperate multiple method calls by line breaks or semicolons
$config[ 'event_subscriber' ][ 'dm_ds24_product_ids_changed' ]    = 'logic/digistore_connector/updateIpnConnection/0';
$config[ 'event_subscriber' ][ 'dm_ds24_connection_established' ] = 'logic/blog_config/setDigistoreAffiliate/1';


$config[ 'event_subscriber' ][ 'dm_page_product_changed' ]        = 'data/has_preview_cache/invalidate/1';
