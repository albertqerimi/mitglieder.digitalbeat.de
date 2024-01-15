<?php

// seperate multiple method calls by line breaks or semicolon, e.g.:
// $config[ 'event_subscriber' ][ 'dm_on_login' ]     = 'logic/action/onLogin/3; data/user/onLogin/3';

$config[ 'event_subscriber' ][ 'dm_on_login' ]     = 'logic/action/onLogin/3';
$config[ 'event_subscriber' ][ 'dm_on_access' ]     = 'logic/log_member_access/logMemberAccess/1';
$config[ 'event_subscriber' ][ 'dm_on_page_view' ] = 'logic/action/onPageView/2';

