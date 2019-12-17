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
        return array("mount_points"=>$mp);
    }

    /**
     * removes first part of the path
     * example: $path = /iRODS/path/to/my/file.txt
     *          return path/to/my/file.txt
     *
     * @param string $path 
     * @return string
     */
    public function stripMountPoint($path)
    {
        $tmp = explode('/', ltrim($path, '/'), 2);
        if(count($tmp) > 1)
        {
            return $tmp[1];
        }
        else
        {
            return "";
        }
    }

    /**
     * @NoAdminRequired
     */
    public function get($path)
    {
        $session = iRodsSession::createFromPath($path);
        $ipath =  $session->resolve($this->stripMountPoint($path));
        $meta = [];
        foreach($ipath->getMeta() as $m)
        {
            $meta[] = array("name"=>$m->name,
                            "value"=>$m->value,
                            "units"=>$m->units);
        }
        return array("path"=>$path,
                     "ipath"=>$ipath->getPath(),
                     "op"=>"GET",
                     "meta"=>$meta,
                     "params"=> $session->params);
    }


    /**
     * @NoAdminRequired
     *
     * @param string $path
     * @param string $entries
     */
    public function put($path, $entries)
    {
        $session = iRodsSession::createFromPath($path);
        $ipath =  $session->resolve($this->stripMountPoint($path));
        $meta = [];
        foreach($ipath->getMeta() as $m)
        {
            $meta[] = array("name"=>$m->name,
                            "value"=>$m->value,
                            "units"=>$m->units);
        }
        return array("path"=>$path,
                     "ipath"=>$ipath->getPath(),
                     "op"=>"PUT",
                     "meta"=>$meta,
                     "params"=> $session->params);
    }

    /**
     * @NoAdminRequired
     *
     * @param string $path
     * @param array $entries
     */
    public function patch($path, $entries)
    {
        $session = iRodsSession::createFromPath($path);
        $ipath =  $session->resolve($this->stripMountPoint($path));
        $meta = [];
        foreach($ipath->getMeta() as $m)
        {
            $meta[] = array("name"=>$m->name,
                            "value"=>$m->value,
                            "units"=>$m->units);
        }
        return array("path"=>$path,
                     "ipath"=>$ipath->getPath(),
                     "op"=>"PATCH",
                     "meta"=>$meta,
                     "params"=> $session->params);
    }

    /**
     * @NoAdminRequired
     *
     * @param string $path
     * @param array $entries
     */
    public function delete($path, $entries)
    {
        $session = iRodsSession::createFromPath($path);
        $ipath =  $session->resolve($this->stripMountPoint($path));
        $meta = [];
        foreach($ipath->getMeta() as $m)
        {
            $meta[] = array("name"=>$m->name,
                            "value"=>$m->value,
                            "units"=>$m->units);
        }
        return array("path"=>$path,
                     "ipath"=>$ipath->getPath(),
                     "op"=>"DELETE",
                     "meta"=>$meta,
                     "params"=> $session->params);
    }
};
