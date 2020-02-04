<?php
/**
 * Storage backend
 *
 * Implementation of a OwnCloud Storage Backend
 * see \OCP\Files\External\Backend\Backend
 * https://github.com/owncloud/core/blob/v10.0.10/lib/public/Files/External/Backend/Backend.php
 *
 * Configuration is written to OwnCloud database:
 * select * from oc_external_config;
 * +-----------+----------+-----------------+--------+
 * | config_id | mount_id | key             | value  |
 * +-----------+----------+-----------------+--------+
 * |         1 |        1 | hostname        | irods  |
 * |         2 |        1 | port            | 1247   |
 * |         3 |        1 | common_password | mypass |
 * |         4 |        1 | using_pam       | 1      |
 * +-----------+----------+-----------------+--------+
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\Backend;

use \OCP\Files\External\Backend\Backend;
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
                                         'Port')),
                (new DefinitionParameter('zone',
                                         'Zone')),
                (new DefinitionParameter('common_password',
                                         'Common Password'))
                ->setType(DefinitionParameter::VALUE_PASSWORD)
                ->setFlag(DefinitionParameter::FLAG_OPTIONAL),
                (new DefinitionParameter('using_pam',
                                         'PAM Auth'))
                ->setType(DefinitionParameter::VALUE_BOOLEAN)])
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD)
            ->addAuthScheme(AuthMechanism::SCHEME_BUILTIN);
	}
};
