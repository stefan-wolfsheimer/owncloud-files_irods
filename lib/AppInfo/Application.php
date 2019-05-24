<?php

namespace OCA\files_irods\AppInfo;

use \OCP\AppFramework\App;

use OCP\Files\External\Config\IBackendProvider;
//use OCA\files_irods\Controller\IrodsmetaController;


class Application extends App implements IBackendProvider {
    public function __construct(array $urlParams=array()){
        parent::__construct('files_irods', $urlParams);
        /*
        $container = $this->getContainer();
        $backendService = $container->getServer()->getStoragesBackendService();
		$backendService->registerBackendProvider($this);

        $container->registerService('IrodsmetaController', function($c) {
            // register the controller in the container
            return new IrodsmetaController(
                $c->query('AppName'),
                $c->query('Request'));
        });
        $this->irods_roots = [
            "LandingZone"=> "/%s/home/%s",
            "Archive"=> "/%s/home/public"
            ]; */
    }

    public function getBackends() {
        $container = $this->getContainer();
        //$irods_backend = $container->query('OCA\files_irodsmeta\Backend\iRods');
        $backends = [
            //$irods_backend
		];
        return $backends;
    }
};

