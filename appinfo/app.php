<?php
/**
 * Application entry point
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\AppInfo;

$app = new Application();

\OC::$server->getNavigationManager()->add(function ()
{
    $urlGenerator = \OC::$server->getURLGenerator();
    return
        [
            // The string under which your app will be referenced in owncloud
            'id' => 'files_irods',

            // The sorting weight for the navigation.
            // The higher the number, the higher will it be listed in the navigation
            'order' => 10,
        ];
});
