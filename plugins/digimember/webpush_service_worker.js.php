<?php

define( 'NCORE_IS_AJAX',  false );
define( 'NCORE_IS_IPN',   false );
define( 'WP_USE_THEMES',  false );
define( 'WP_ADMIN',       true  ); // prevent 404 not found errors and redirects
$_SERVER['PHP_SELF'] = '/wp-admin/digimember/webpush_service_worker.js.php'; // prevent php notice message (Undefined offset) in vars.php
define( 'DONOTCACHEPAGE', 1 );


require_once dirname(__FILE__).'/digimember.php';

header( 'content-type: text/javascript; charset=utf-8' );

$api = dm_api();

$get_message_url = $api->link_logic->ajaxUrl( 'ajax/webpush', 'get_message', array( 'key' => '__KEY__', 'token' => '__TOKEN__', 'endpoint' => '__ENDPOINT__' ) );

?>

var target_url = '/';
var service_worker_version = "<?=$api->pluginVersion()?>";

if (typeof digimember_isVersionHigher == 'undefined')
{
    function digimember_isVersionHigher(a, b) {

        if (a === b) {
           return 0;
        }

        var a_components = a.split(".");
        var b_components = b.split(".");

        var len = Math.min(a_components.length, b_components.length);

        for (var i = 0; i < len; i++) {

            if (parseInt(a_components[i]) > parseInt(b_components[i])) {
                return 1;
            }

            if (parseInt(a_components[i]) < parseInt(b_components[i])) {
                return 0;
            }
        }

        if (a_components.length > b_components.length) {
            return 1;
        }

        return 0;
    }
}

self.addEventListener('push', function(event) {
    console.group('digimember sw push');
    console.info('digimember sw push: Received a push message', event);

    if (registration.pushManager)
    {
        registration.pushManager.getSubscription().then( function(subscription)
        {
            console.info('digimember sw push: For subscription', subscription);

            var ajax_url = "<?=$get_message_url?>";

            var key      = subscription.getKey('p256dh');
            var token    = subscription.getKey('auth');
            var endpoint = subscription.endpoint;

            key   = (key   ? btoa(String.fromCharCode.apply(null, new Uint8Array(key)))   : null);
            token = (token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null);

            ajax_url = ajax_url.replace( /__KEY__/, key ).replace( /__TOKEN__/, token ).replace( /__ENDPOINT__/, endpoint );

            console.info( 'digimember sw push: Fetching url: ', ajax_url );

            fetch( ajax_url )
              .then(
                function(response) {
                  if (response.status !== 200) {
                    console.log('digimember sw push: Looks like there was a problem. Status Code: ' +
                      response.status);
                    return;
                  }

                    response.json().then(function(data) {

                        var is_success  = data.status == 'SUCCESS';
                        var new_version = data.service_worker_version;
                        var must_update = digimember_isVersionHigher( new_version, service_worker_version );

                        if (is_success) {
                            target_url = data.url;

                            var title = data.title;

                            delete data.title;
                            delete data.status;
                            delete data.url;
                            delete data.service_worker_version;

                            self.registration.showNotification( title, data ).then(function () {
                                console.info('digimember sw push ok: ',  data);
                            }).catch(function (ex) {
                                console.error('digimember sw push error:', ex);
                            });
                        }
                        else {
                            console.error('digimember sw push: ',  data);
                        }

                        if (must_update)
                        {
                            console.info( 'digimember sw push: Updating service worker from version ' + service_worker_version + ' to version ' + new_version );
                            self.registration.update();
                        }
                  });
                }
              )
              .catch(function(err) {
                console.error('digimember sw push: Fetch Error :-S', err);
              });
         });
     }
    console.groupEnd();
});






self.addEventListener('notificationclick', function(event) {

  console.log('digimember sw click: On notification click: ', event.notification.tag);
  // Android doesn’t close the notification when you click on it
  // See: http://crbug.com/463146
  event.notification.close();

  // This looks to see if the current is already open and
  // focuses if it is
  event.waitUntil(clients.matchAll({
    type: 'window'
  }).then(function(clientList) {
    for (var i = 0; i < clientList.length; i++) {
      var client = clientList[i];
      if (client.url === '/' && 'focus' in client) {
        return client.focus();
      }
    }
    if (clients.openWindow) {
      return clients.openWindow( target_url );
    }
  }));
});

