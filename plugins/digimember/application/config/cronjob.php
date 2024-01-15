<?php

$config[NCORE_CRON_MINUTELY] = '
    logic/action
    queue/ipn_out
    queue/action_out
    queue/notifier
    queue/webpush
';

$config[NCORE_CRON_HOURLY] = '
    queue/ipn_out
    queue/action_out
    queue/notifier
';

$config[NCORE_CRON_DAILY] = '
    queue/ipn_out
    queue/action_out
    queue/notifier
    data/timer
    data/user_product
    data/apilog
    data/log
    data/config_store
    data/session_store
    data/one_time_login
    data/ip_log
    data/ip_lock
    data/ip_counter
    logic/cronjob
    data/has_preview_cache
    data/lock
    logic/digistore_connector
    logic/action
    logic/infobox
';

$config[NCORE_CRON_WEEKLY] = '
    data/server_cookie
';

