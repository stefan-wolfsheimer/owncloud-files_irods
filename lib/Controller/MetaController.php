<?php
/**
 * MetaController
 * Controller class for editing iRODS meta data.
 * @todo implement
 *
 * Author: Stefan Wolfsheimer stefan.wolfsheimer@surfsara.nl
 * License: Apache License 2.0
 *
 */
namespace OCA\files_irods\Controller;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCA\files_irods\iRodsApi\iRodsSession;

function compare_mount_points($a, $b)
{
    return -strcmp($a['name'], $b['name']);
}

class MetaController extends Controller
{
    public function __construct($AppName, $request)
    {
        parent::__construct($AppName, $request);
    }

    /**
     * @NoAdminRequired
     */
    public function getMountPoints()
    {
        $storages = \OC::$server->query('UserStoragesService');
        $mp = [];
        foreach($storages->getStorages() as $m)
        {
            if($m->getBackend() instanceof \OCA\files_irods\Backend\iRods)
            {
                $mp[] = $m->getMountPoint();
            }
        }
        $storages = \OC::$server->query('UserGlobalStoragesService');
        foreach($storages->getStorages() as $m)
        {
            if($m->getBackend() instanceof \OCA\files_irods\Backend\iRods)
            {
                $mp[] = $m->getMountPoint();
            }
        }
        $config = \OC::$server->getConfig();
        $mount_point_json = $config->getAppValue("files_irods", "irods_mount_points");
        if(!$mount_point_json)
        {
            $mount_points = array();
        }
        else
        {
            $mount_points = json_decode($mount_point_json, true);
        }
        $ret = [];
        foreach($mp as $rootdir)
        {
            $copy = $mount_points;
            foreach($copy as &$m)
            {
                if($m['name'])
                {
                    $m['name'] = $rootdir."/".$m['name']."/";
                }
                else
                {
                    $m['name'] = $rootdir."/";
                }
                $ret[] = $m;
            }
        }
        usort($ret,
              'OCA\\files_irods\\Controller\\compare_mount_points');
        foreach($ret as &$mp)
        {
            $session = iRodsSession::createFromPath($mp['name']);
            $roles = $session->getRoles();
            $mp['groups'] = array_keys($session->getRoles());
        }
        return array("mount_points"=>$ret);
    }
};
