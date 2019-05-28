<?php

namespace OCA\files_irods\AppInfo;

use \OCP\AppFramework\App;

use OCP\Files\External\Config\IBackendProvider;
use OCA\files_irods\Controller\VirtualCollectionsController;
use OCA\files_irods\Controller\MetaController;


class Application extends App implements IBackendProvider {
    public function __construct(array $urlParams=array()){
        parent::__construct('files_irods', $urlParams);
        $container = $this->getContainer();
        $backendService = $container->getServer()->getStoragesBackendService();
		$backendService->registerBackendProvider($this);

        $container->registerService('VirtualCollectionsController', function($c) {
            // register the controller in the container
            return new VirtualCollectionsController(
                $c->query('AppName'),
                $c->query('Request'));
        });
        $container->registerService('MetaController', function($c) {
            // register the controller in the container
            return new MetaController(
                $c->query('AppName'),
                $c->query('Request'));
        });
    }

    public function getBackends() {
        return
            [
                $this->getContainer()->query('OCA\files_irods\Backend\iRods')
            ];
    }
};

