<?php

namespace OCA\files_irods\Backend;

use \OCP\Files\External\Backend\Backend;
use \OCA\files_irods\Storage;
use \OCP\Files\External\Auth\AuthMechanism;
use \OCP\Files\External\DefinitionParameter;

class iRods extends Backend {

    public function __construct() {
		$this
			->setIdentifier('files_irods')
			->setStorageClass('\OCA\files_irods\Storage\iRods')
			->setText('iRODS')
			->addParameters([
				(new DefinitionParameter('hostname',
                                         'Hostname')),
				(new DefinitionParameter('port',
                                         'Port'))])
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD);
	}
};
