<?php
namespace OCA\files_irods\AppInfo;
$application = new Application();

$application->registerRoutes($this, [
    'routes' => [
        [
            'name' => 'VirtualCollections#get',
            'url' => '/api/virtual',
            'verb' => 'GET'
        ],
        [
            'name' => 'Meta#get',
            'url' => '/api/meta/{path}',
            'verb' => 'GET',
            'requirements' => ['path' => '.*']
        ],
        // [
        //     'name' => 'irodsmeta#update',
        //     'url' => '/api/meta/{path}',
        //     'verb' => 'PUT',
        //     'requirements' => ['path' => '.*']
        // ]
    ]
]);
