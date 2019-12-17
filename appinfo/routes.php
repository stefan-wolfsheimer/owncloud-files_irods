<?php
/**
 * Routes for irods owncloud plugin
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\AppInfo;
$application = new Application();

$application->registerRoutes($this, [
    'routes' => [
        [
            'name' => 'Meta#getmountpoints',
            'url' => '/api/mountpoints',
            'verb' => 'GET'
        ],
        [
            'name' => 'Meta#get',
            'url' => '/api/meta/{path}',
            'verb' => 'GET',
            'requirements' => ['path' => '.*']
        ],
        [
            'name' => 'Meta#put',
            'url' => '/api/meta/{path}',
            'verb' => 'PUT',
            'requirements' => ['path' => '.*']
        ],
        [
            'name' => 'Meta#patch',
            'url' => '/api/meta/{path}',
            'verb' => 'PATCH',
            'requirements' => ['path' => '.*']
        ],
        [
            'name' => 'Meta#delete',
            'url' => '/api/meta/{path}',
            'verb' => 'DELETE',
            'requirements' => ['path' => '.*']
        ]
    ]
]);
