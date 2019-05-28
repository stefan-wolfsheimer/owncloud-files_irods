<?php

namespace OCA\files_irods\AppInfo;
use OCP\Util;

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

\OC::$server->getEventDispatcher()->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() {
		Util::addScript('files_irods', 'irods_popup');
	}
);

