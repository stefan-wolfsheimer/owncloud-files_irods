<?php
/**
 * Implementation of OwnCloud StorageAdapter
 * see \OCP\Files\Storage
 * see https://github.com/owncloud/core/blob/v10.0.10/lib/public/Files/Storage/StorageAdapter.php
 * see https://doc.owncloud.org/server/10.2/developer_manual/app/advanced/custom-storage-backend.html
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\AppInfo;

use \OCP\AppFramework\App;
use OCP\Files\External\Config\IBackendProvider;
use OCA\files_irods\Controller\MetaController;


class Application extends App implements IBackendProvider {
    public function __construct(array $urlParams=array()){
        parent::__construct('files_irods', $urlParams);
        $container = $this->getContainer();
        $backendService = $container->getServer()->getStoragesBackendService();
		$backendService->registerBackendProvider($this);

        $container->registerService('MetaController', function($c) {
            // register the controller in the container
            return new MetaController(
                $c->query('AppName'),
                $c->query('Request'));
        });
        $container->registerService('Config', function($c) {
            return $c->query("ServerContainer")->getConfig();
        });
    }

    public function getBackends() {
        return
            [
                $this->getContainer()->query('OCA\files_irods\Backend\iRods')
            ];
    }
};

